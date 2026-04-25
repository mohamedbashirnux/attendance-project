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

    $id = trim($_POST['id'] ?? '');
    $status = trim($_POST['status'] ?? '');

    // Validate required fields
    if (empty($id) || empty($status)) {
        throw new Exception("Student ID and status are required");
    }

    // Validate status value
    if (!in_array($status, ['pending', 'approved'])) {
        throw new Exception("Invalid status value");
    }

    // Verify that the student exists and belongs to a class in this faculty
    $verify_sql = "SELECT s.id FROM students s 
                   JOIN classes c ON s.class_id = c.id 
                   WHERE s.id = ? AND c.faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$id, $faculty_id]);
    
    if (!$verify_stmt->fetch()) {
        throw new Exception("Access denied - student not found or doesn't belong to your faculty");
    }

    // Update student status
    $update_sql = "UPDATE students SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);

    if (!$update_stmt->execute([$status, $id])) {
        $errorInfo = $update_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Status updated successfully"
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