<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Suppress PHP warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Include the faculty session management
include "../../Account_users/session_faculty.php";

// Include database connection
include "../../connection/connect.php";

// Clear any unexpected output from includes
ob_clean();

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    $class_id = trim($_POST['class_id'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $status = trim($_POST['status'] ?? 'approved');

    // Validate required fields with specific error messages
    $missing_fields = [];
    if (empty($class_id)) $missing_fields[] = 'Class ID';
    if (empty($student_id)) $missing_fields[] = 'Student ID';
    if (empty($full_name)) $missing_fields[] = 'Student Name';
    if (empty($phone)) $missing_fields[] = 'Student Number';
    if (empty($password)) $missing_fields[] = 'Password';
    
    if (!empty($missing_fields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
    }

    // Verify that the class belongs to this faculty
    $verify_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$class_id, $faculty_id]);
    
    if (!$verify_stmt->fetch()) {
        throw new Exception("Access denied - class not found or doesn't belong to your faculty");
    }

    // Check if student_id already exists
    $check_student_id_sql = "SELECT id, full_name FROM students WHERE student_id = ?";
    $check_student_id_stmt = $conn->prepare($check_student_id_sql);
    $check_student_id_stmt->execute([$student_id]);
    $existing_student = $check_student_id_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_student) {
        throw new Exception("Student ID '{$student_id}' already exists(may be its not your faculty) (used by: {$existing_student['full_name']})");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new student
    $insert_sql = "INSERT INTO students (student_id, class_id, full_name, phone, password, status) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);

    if (!$insert_stmt->execute([$student_id, $class_id, $full_name, $phone, $hashed_password, $status])) {
        $errorInfo = $insert_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Student added successfully"
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>