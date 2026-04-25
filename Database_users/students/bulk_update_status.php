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
    $status = trim($_POST['status'] ?? '');

    // Validate required fields
    if (empty($class_id) || empty($status)) {
        throw new Exception("Class ID and status are required");
    }

    // Validate status value
    if (!in_array($status, ['pending', 'approved'])) {
        throw new Exception("Invalid status value");
    }

    // Verify that the class belongs to this faculty
    $verify_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$class_id, $faculty_id]);
    
    if (!$verify_stmt->fetch()) {
        throw new Exception("Access denied - class not found or doesn't belong to your faculty");
    }

    // Update all students in this class
    $update_sql = "UPDATE students SET status = ? WHERE class_id = ?";
    $update_stmt = $conn->prepare($update_sql);

    if (!$update_stmt->execute([$status, $class_id])) {
        $errorInfo = $update_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    $affected_rows = $update_stmt->rowCount();

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Updated {$affected_rows} students to {$status} status",
        "affected_rows" => $affected_rows
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