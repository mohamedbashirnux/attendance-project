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

    $allocation_id = trim($_POST['id'] ?? '');

    // Validate input
    if (empty($allocation_id)) {
        throw new Exception("Allocation ID is required");
    }

    // Check if allocation exists and belongs to this faculty
    $check_sql = "SELECT tsa.id FROM teacher_subject_allocation tsa 
                  JOIN classes c ON tsa.class_id = c.id 
                  WHERE tsa.id = ? AND c.faculty_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$allocation_id, $faculty_id]);
    
    if ($check_stmt->rowCount() === 0) {
        throw new Exception("Allocation not found or access denied");
    }

    // Delete allocation
    $delete_sql = "DELETE FROM teacher_subject_allocation WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);

    if (!$delete_stmt->execute([$allocation_id])) {
        $errorInfo = $delete_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Allocation deleted successfully"
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