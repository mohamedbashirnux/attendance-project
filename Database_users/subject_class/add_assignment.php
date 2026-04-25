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

    $subject_id = trim($_POST['subject_id'] ?? '');
    $class_id = trim($_POST['class_id'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');

    // Validate input
    if (empty($subject_id) || empty($class_id) || empty($department_id)) {
        throw new Exception("Subject, class, and department are required");
    }

    // Verify subject belongs to this faculty and department
    $verify_subject_sql = "SELECT id FROM subjects WHERE id = ? AND faculty_id = ? AND department_id = ?";
    $verify_subject_stmt = $conn->prepare($verify_subject_sql);
    $verify_subject_stmt->execute([$subject_id, $faculty_id, $department_id]);
    
    if ($verify_subject_stmt->rowCount() === 0) {
        throw new Exception("Invalid subject selected");
    }

    // Verify class belongs to this faculty
    $verify_class_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $verify_class_stmt = $conn->prepare($verify_class_sql);
    $verify_class_stmt->execute([$class_id, $faculty_id]);
    
    if ($verify_class_stmt->rowCount() === 0) {
        throw new Exception("Invalid class selected");
    }

    // Check if assignment already exists
    $check_sql = "SELECT id FROM subject_class WHERE subject_id = ? AND class_id = ? AND faculty_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$subject_id, $class_id, $faculty_id]);

    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Subject already assigned to this class']);
        exit();
    }

    // Insert new assignment
    $sql = "INSERT INTO subject_class (faculty_id, subject_id, class_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$faculty_id, $subject_id, $class_id])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode(["success" => true, "message" => "Subject assigned to class successfully"]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>