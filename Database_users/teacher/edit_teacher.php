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

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    $teacher_id = trim($_POST['id'] ?? '');
    $full_name = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if (empty($teacher_id) || empty($full_name) || empty($username)) {
        throw new Exception("ID, Full Name, and Username are required");
    }

    // Check if teacher exists
    $check_sql = "SELECT id FROM teachers WHERE teacher_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$teacher_id]);
    
    if ($check_stmt->rowCount() === 0) {
        throw new Exception("Teacher not found");
    }

    // Check if username already exists for another teacher
    $check_username_sql = "SELECT id FROM teachers WHERE username = ? AND teacher_id != ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    $check_username_stmt->execute([$username, $teacher_id]);

    if ($check_username_stmt->rowCount() > 0) {
        throw new Exception("Username already exists for another teacher");
    }

    // Prepare update query
    if (!empty($password)) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE teachers SET full_name = ?, username = ?, password = ? WHERE teacher_id = ?";
        $params = [$full_name, $username, $hashed_password, $teacher_id];
    } else {
        // Update without changing password
        $sql = "UPDATE teachers SET full_name = ?, username = ? WHERE teacher_id = ?";
        $params = [$full_name, $username, $teacher_id];
    }

    $stmt = $conn->prepare($sql);

    if (!$stmt->execute($params)) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode(["status" => "success", "message" => "Teacher updated successfully"]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>