<?php
// Updated add_allocation.php for new database structure
error_reporting(0);
ini_set('display_errors', 0);

ob_start();

include "../../Account_users/session_faculty.php";
include "../../connection/connect.php";

ob_clean();
header('Content-Type: application/json');

try {
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    $teacher_auto_id = trim($_POST['teacher_id'] ?? '');  // This is the auto-increment ID from teachers table
    $class_id = trim($_POST['class_id'] ?? '');
    $subject_id = trim($_POST['subject_id'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    // Validate input
    if (empty($teacher_auto_id) || empty($class_id) || empty($subject_id) || empty($start_time) || empty($end_time)) {
        throw new Exception("All fields are required");
    }

    // Normalize time format - add leading zeros if needed
    $start_time = date('H:i', strtotime($start_time));
    $end_time = date('H:i', strtotime($end_time));

    if (strtotime($start_time) >= strtotime($end_time)) {
        throw new Exception("End time must be after start time");
    }

    // Check if teacher exists
    $teacher_check_sql = "SELECT id, teacher_id, full_name FROM teachers WHERE id = ?";
    $teacher_check_stmt = $conn->prepare($teacher_check_sql);
    $teacher_check_stmt->execute([$teacher_auto_id]);
    $teacher = $teacher_check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        throw new Exception("Teacher not found");
    }

    // REMOVED: Time conflict check - Teachers can now teach multiple subjects/classes at same time

    // Check for duplicate allocation (same teacher, same class, same subject)
    // Only prevent exact duplicates: same teacher teaching same subject to same class
    $duplicate_check_sql = "SELECT id FROM teacher_subject_allocation 
                           WHERE teacher_id = ? AND class_id = ? AND subject_id = ?";
    $duplicate_check_stmt = $conn->prepare($duplicate_check_sql);
    $duplicate_check_stmt->execute([$teacher_auto_id, $class_id, $subject_id]);

    if ($duplicate_check_stmt->rowCount() > 0) {
        throw new Exception("This allocation already exists: same teacher, class, and subject");
    }

    // Insert new allocation
    $sql = "INSERT INTO teacher_subject_allocation 
            (teacher_id, class_id, subject_id, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$teacher_auto_id, $class_id, $subject_id, $start_time, $end_time])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Teacher allocation added successfully",
        "teacher_info" => [
            "teacher_id" => $teacher['teacher_id'],
            "teacher_name" => $teacher['full_name']
        ]
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
