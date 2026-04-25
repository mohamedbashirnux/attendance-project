<?php
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
    $day_of_week = trim($_POST['day_of_week'] ?? '');

    // Validate input
    if (empty($teacher_id) || empty($class_id) || empty($subject_id) || empty($start_time) || empty($end_time)) {
        throw new Exception("All fields are required");
    }

    // Get teacher's auto ID from teacher_id
    $teacher_sql = "SELECT id, full_name FROM teachers WHERE teacher_id = ? AND faculty_id = ? AND status = 'active'";
    $teacher_stmt = $conn->prepare($teacher_sql);
    $teacher_stmt->execute([$teacher_id, $faculty_id]);
    $teacher_data = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher_data) {
        throw new Exception("Teacher not found or inactive");
    }

    $teacher_auto_id = $teacher_data['id'];

    // Validate class belongs to faculty
    $class_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->execute([$class_id, $faculty_id]);
    if ($class_stmt->rowCount() == 0) {
        throw new Exception("Class not found or doesn't belong to your faculty");
    }

    // Validate subject exists
    $subject_sql = "SELECT id FROM subjects WHERE id = ?";
    $subject_stmt = $conn->prepare($subject_sql);
    $subject_stmt->execute([$subject_id]);
    if ($subject_stmt->rowCount() == 0) {
        throw new Exception("Subject not found");
    }

    // Validate time format
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) || 
        !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
        throw new Exception("Invalid time format. Use HH:MM format");
    }

    // Check if end time is after start time
    if (strtotime($end_time) <= strtotime($start_time)) {
        throw new Exception("End time must be after start time");
    }

    // Check for conflicts (same teacher, same time, same day)
    $conflict_sql = "SELECT id FROM teacher_subject_allocation 
                    WHERE teacher_auto_id = ? 
                    AND (day_of_week = ? OR day_of_week IS NULL OR ? IS NULL)
                    AND status != 'rejected'
                    AND (
                        (start_time <= ? AND end_time > ?) OR
                        (start_time < ? AND end_time >= ?) OR
                        (start_time >= ? AND end_time <= ?)
                    )";
    
    $conflict_stmt = $conn->prepare($conflict_sql);
    $conflict_stmt->execute([
        $teacher_auto_id, $day_of_week, $day_of_week,
        $start_time, $start_time,
        $end_time, $end_time,
        $start_time, $end_time
    ]);

    if ($conflict_stmt->rowCount() > 0) {
        throw new Exception("Time conflict: Teacher already has an allocation during this time period");
    }

    // Check for duplicate allocation
    $duplicate_sql = "SELECT id FROM teacher_subject_allocation 
                     WHERE teacher_auto_id = ? AND class_id = ? AND subject_id = ? 
                     AND start_time = ? AND end_time = ? AND status != 'rejected'";
    $duplicate_stmt = $conn->prepare($duplicate_sql);
    $duplicate_stmt->execute([$teacher_auto_id, $class_id, $subject_id, $start_time, $end_time]);

    if ($duplicate_stmt->rowCount() > 0) {
        throw new Exception("This allocation already exists");
    }

    // Insert new allocation with improved structure
    $sql = "INSERT INTO teacher_subject_allocation 
            (teacher_auto_id, teacher_id, class_id, subject_id, start_time, end_time, day_of_week, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$teacher_auto_id, $teacher_id, $class_id, $subject_id, $start_time, $end_time, $day_of_week])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    // Get the auto-generated ID
    $allocation_id = $conn->lastInsertId();

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Teacher allocation added successfully",
        "allocation_id" => $allocation_id,
        "teacher_name" => $teacher_data['full_name']
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