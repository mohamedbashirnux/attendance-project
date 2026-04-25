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

    $subject_id = trim($_POST['id'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');

    // Validate input
    if (empty($subject_id) || empty($subject_name)) {
        throw new Exception("Subject ID and name are required");
    }

    // Check if subject exists and belongs to this faculty
    $check_sql = "SELECT department_id FROM subjects WHERE id = ? AND faculty_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$subject_id, $faculty_id]);
    $subject_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subject_data) {
        throw new Exception("Subject not found or access denied");
    }

    $department_id = $subject_data['department_id'];

    // Check if subject name already exists for another subject in the same department
    $check_name_sql = "SELECT id FROM subjects WHERE subject_name = ? AND department_id = ? AND faculty_id = ? AND id != ?";
    $check_name_stmt = $conn->prepare($check_name_sql);
    $check_name_stmt->execute([$subject_name, $department_id, $faculty_id, $subject_id]);

    if ($check_name_stmt->rowCount() > 0) {
        echo json_encode(["status" => "exists", "message" => "Subject name already exists in this department"]);
        exit();
    }

    // Update subject
    $sql = "UPDATE subjects SET subject_name = ? WHERE id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$subject_name, $subject_id, $faculty_id])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode(["status" => "success", "message" => "Subject updated successfully"]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>