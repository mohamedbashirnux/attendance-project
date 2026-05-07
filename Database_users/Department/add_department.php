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

    $departmentName = trim($_POST['departmentName'] ?? '');

    // Validate input
    if (empty($departmentName)) {
        throw new Exception("Department name is required");
    }

    // Check if department already exists for this faculty
    $check_sql = "SELECT * FROM departments WHERE department_name = ? AND faculty_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$departmentName, $faculty_id]);

    if ($check_stmt->rowCount() > 0) {
        throw new Exception("Department already exists");
    }

    // Insert new department
    $sql = "INSERT INTO departments (department_name, faculty_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$departmentName, $faculty_id])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    // Clear any unexpected output
    ob_clean();
    
    // Return success response
    echo json_encode(["status" => "success", "message" => "Department added successfully"]);

} catch (Exception $e) {
    // Clear any unexpected output
    ob_clean();
    
    // Return error response
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (PDOException $e) {
    // Clear any unexpected output
    ob_clean();
    
    // Return database error response
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

// End output buffering and flush
ob_end_flush();
