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

    // Get department_id from POST data (sent from the frontend)
    $department_id = trim($_POST['department_id'] ?? '');

    // Delete subjects for this faculty and department (or all if no department specified)
    if ($department_id) {
        $sql = "DELETE FROM subjects WHERE faculty_id = ? AND department_id = ?";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([$faculty_id, $department_id]);
    } else {
        $sql = "DELETE FROM subjects WHERE faculty_id = ?";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([$faculty_id]);
    }
    
    if (!$success) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    $deletedCount = $stmt->rowCount();

    ob_clean();
    echo json_encode([
        "status" => "success", 
        "message" => "All subjects deleted successfully",
        "deleted_count" => $deletedCount
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>