<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "conn.php";

$user_id = filterRequest('user_id'); // Can be student_id or teacher_id
$user_type = filterRequest('user_type'); // 'student' or 'teacher'
$rating = filterRequest('rating');
$feedback_type = filterRequest('feedback_type');
$feedback = filterRequest('feedback');

// Backward compatibility: if student_id is sent, use it
if (empty($user_id) && !empty($_POST['student_id'])) {
    $user_id = filterRequest('student_id');
    $user_type = 'student';
}

if (empty($user_id) || empty($rating) || empty($feedback)) {
    echo json_encode([
        "status" => "fail",
        "message" => "User ID, rating, and feedback are required"
    ]);
    exit;
}

// Default to student if not specified
if (empty($user_type)) {
    $user_type = 'student';
}

// Validate user_type
if (!in_array($user_type, ['student', 'teacher'])) {
    echo json_encode([
        "status" => "fail",
        "message" => "Invalid user type. Must be 'student' or 'teacher'"
    ]);
    exit;
}

try {
    // Check if user exists based on type
    if ($user_type === 'student') {
        $checkStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $checkStmt->execute([$user_id]);
        
        if (!$checkStmt->fetch()) {
            echo json_encode([
                "status" => "fail",
                "message" => "Student not found. Please check your student ID."
            ]);
            exit;
        }
    } else if ($user_type === 'teacher') {
        $checkStmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE teacher_id = ?");
        $checkStmt->execute([$user_id]);
        
        if (!$checkStmt->fetch()) {
            echo json_encode([
                "status" => "fail",
                "message" => "Teacher not found. Please check your teacher ID."
            ]);
            exit;
        }
    }
    
    $stmt = $conn->prepare("
        INSERT INTO app_feedback 
        (user_id, user_type, rating, feedback_type, feedback, submitted_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $user_type,
        $rating,
        $feedback_type,
        $feedback
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Feedback submitted successfully"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
