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

    $teacher_id = trim($_POST['teacher_id'] ?? '');
    $class_id = trim($_POST['class_id'] ?? '');
    $subject_id = trim($_POST['subject_id'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    // Validate input
    if (empty($teacher_id) || empty($class_id) || empty($subject_id) || empty($start_time) || empty($end_time)) {
        throw new Exception("All fields are required");
    }

    // Validate time format and logic
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) || 
        !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
        throw new Exception("Invalid time format");
    }

    if ($start_time >= $end_time) {
        throw new Exception("End time must be after start time");
    }

    // Verify teacher exists (teachers are shared across faculties)
    $verify_teacher_sql = "SELECT id FROM teachers WHERE id = ?";
    $verify_teacher_stmt = $conn->prepare($verify_teacher_sql);
    $verify_teacher_stmt->execute([$teacher_id]);
    
    if ($verify_teacher_stmt->rowCount() === 0) {
        throw new Exception("Teacher not found");
    }

    // Verify class belongs to this faculty
    $verify_class_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $verify_class_stmt = $conn->prepare($verify_class_sql);
    $verify_class_stmt->execute([$class_id, $faculty_id]);
    
    if ($verify_class_stmt->rowCount() === 0) {
        throw new Exception("Class not found or does not belong to your faculty");
    }

    // Verify subject is assigned to this class
    $verify_subject_sql = "SELECT sc.id FROM subject_class sc 
                          JOIN subjects s ON sc.subject_id = s.id 
                          WHERE sc.subject_id = ? AND sc.class_id = ? AND sc.faculty_id = ?";
    $verify_subject_stmt = $conn->prepare($verify_subject_sql);
    $verify_subject_stmt->execute([$subject_id, $class_id, $faculty_id]);
    
    if ($verify_subject_stmt->rowCount() === 0) {
        throw new Exception("Subject is not assigned to this class");
    }

    // Check for duplicate allocation (same teacher, class, subject, start_time)
    $check_duplicate_sql = "SELECT id FROM teacher_subject_allocation 
                           WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND start_time = ?";
    $check_duplicate_stmt = $conn->prepare($check_duplicate_sql);
    $check_duplicate_stmt->execute([$teacher_id, $class_id, $subject_id, $start_time]);

    if ($check_duplicate_stmt->rowCount() > 0) {
        throw new Exception("This teacher is already allocated to this subject at the same time");
    }

    // Check for time conflicts for the same teacher
    $check_conflict_sql = "SELECT id FROM teacher_subject_allocation 
                          WHERE teacher_id = ? 
                          AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))";
    $check_conflict_stmt = $conn->prepare($check_conflict_sql);
    $check_conflict_stmt->execute([$teacher_id, $start_time, $start_time, $end_time, $end_time]);

    if ($check_conflict_stmt->rowCount() > 0) {
        throw new Exception("Teacher has a time conflict with existing allocation");
    }

    // Insert new allocation
    $insert_sql = "INSERT INTO teacher_subject_allocation (teacher_id, class_id, subject_id, start_time, end_time, status) 
                   VALUES (?, ?, ?, ?, ?, 'pending')";
    $insert_stmt = $conn->prepare($insert_sql);

    if (!$insert_stmt->execute([$teacher_id, $class_id, $subject_id, $start_time, $end_time])) {
        $errorInfo = $insert_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Teacher allocation created successfully (Status: Pending)"
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