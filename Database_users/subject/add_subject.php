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

    $subject_name = trim($_POST['subject_name'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');

    // Validate input
    if (empty($subject_name) || empty($department_id)) {
        throw new Exception("Subject name and department are required");
    }

    // Verify department belongs to this faculty
    $verify_sql = "SELECT id FROM departments WHERE id = ? AND faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$department_id, $faculty_id]);
    
    if ($verify_stmt->rowCount() === 0) {
        throw new Exception("Invalid department selected");
    }

    // Check if subject already exists in this department
    $check_sql = "SELECT id FROM subjects WHERE subject_name = ? AND department_id = ? AND faculty_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$subject_name, $department_id, $faculty_id]);

    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Subject already exists in this department']);
        exit();
    }

    // Insert new subject
    $sql = "INSERT INTO subjects (faculty_id, department_id, subject_name) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$faculty_id, $department_id, $subject_name])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode(["success" => true, "message" => "Subject added successfully"]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>