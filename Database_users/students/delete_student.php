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

    // Validate required fields
    if (empty($id)) {
        throw new Exception("Student ID is required");
    }

    // Verify that the student exists and belongs to a class in this faculty
    $verify_sql = "SELECT s.id, s.student_id, s.full_name FROM students s 
                   JOIN classes c ON s.class_id = c.id 
                   WHERE s.id = ? AND c.faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$id, $faculty_id]);
    $student = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("Access denied - student not found or doesn't belong to your faculty");
    }

    // Delete the student
    $delete_sql = "DELETE FROM students WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);

    if (!$delete_stmt->execute([$id])) {
        $errorInfo = $delete_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Student deleted successfully"
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