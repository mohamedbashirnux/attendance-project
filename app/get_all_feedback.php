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

$user_id = filterRequest('user_id');
$user_type = filterRequest('user_type'); // 'student' or 'teacher'
$filter = filterRequest('filter'); // 'all', 'students', 'teachers'

// Backward compatibility
if (empty($user_id) && !empty($_POST['student_id'])) {
    $user_id = filterRequest('student_id');
    $user_type = 'student';
}

if (empty($user_id)) {
    echo json_encode([
        "status" => "fail",
        "message" => "User ID is required"
    ]);
    exit;
}

// Default to student if not specified
if (empty($user_type)) {
    $user_type = 'student';
}

try {
    // Get user's own feedback
    $myFeedbackStmt = $conn->prepare("
        SELECT 
            f.id,
            f.user_id,
            f.user_type,
            CASE 
                WHEN f.user_type = 'student' THEN s.full_name
                WHEN f.user_type = 'teacher' THEN t.full_name
            END as user_name,
            f.rating,
            f.feedback_type,
            f.feedback,
            f.status,
            f.admin_notes,
            f.submitted_at
        FROM app_feedback f
        LEFT JOIN students s ON f.user_id = s.student_id AND f.user_type = 'student'
        LEFT JOIN teachers t ON f.user_id = t.teacher_id AND f.user_type = 'teacher'
        WHERE f.user_id = ? AND f.user_type = ?
        ORDER BY f.submitted_at DESC
    ");
    $myFeedbackStmt->execute([$user_id, $user_type]);
    $myFeedback = $myFeedbackStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all feedback based on filter
    $allFeedbackQuery = "
        SELECT 
            f.id,
            f.user_id,
            f.user_type,
            CASE 
                WHEN f.user_type = 'student' THEN s.full_name
                WHEN f.user_type = 'teacher' THEN t.full_name
            END as user_name,
            f.rating,
            f.feedback_type,
            f.feedback,
            f.status,
            f.admin_notes,
            f.submitted_at
        FROM app_feedback f
        LEFT JOIN students s ON f.user_id = s.student_id AND f.user_type = 'student'
        LEFT JOIN teachers t ON f.user_id = t.teacher_id AND f.user_type = 'teacher'
    ";
    
    // Apply filter
    if ($filter === 'students') {
        $allFeedbackQuery .= " WHERE f.user_type = 'student'";
    } elseif ($filter === 'teachers') {
        $allFeedbackQuery .= " WHERE f.user_type = 'teacher'";
    }
    
    $allFeedbackQuery .= " ORDER BY f.submitted_at DESC LIMIT 100";
    
    $allFeedbackStmt = $conn->prepare($allFeedbackQuery);
    $allFeedbackStmt->execute();
    $allFeedback = $allFeedbackStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "my_feedback" => $myFeedback,
        "all_feedback" => $allFeedback,
        "my_count" => count($myFeedback),
        "all_count" => count($allFeedback)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
