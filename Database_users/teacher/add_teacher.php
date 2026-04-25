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

    $teacher_id = trim($_POST['id'] ?? '');
    $full_name = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if (empty($teacher_id) || empty($full_name) || empty($username) || empty($password)) {
        throw new Exception("All fields are required");
    }

    // Check if teacher ID already exists
    $check_id_sql = "SELECT id FROM teachers WHERE teacher_id = ?";
    $check_id_stmt = $conn->prepare($check_id_sql);
    $check_id_stmt->execute([$teacher_id]);

    // Check if username already exists
    $check_username_sql = "SELECT id FROM teachers WHERE username = ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    $check_username_stmt->execute([$username]);

    $id_exists = $check_id_stmt->rowCount() > 0;
    $username_exists = $check_username_stmt->rowCount() > 0;

    if ($id_exists && $username_exists) {
        echo json_encode(['success' => false, 'error' => 'both_exists']);
        exit();
    } elseif ($id_exists) {
        echo json_encode(['success' => false, 'error' => 'id_exists']);
        exit();
    } elseif ($username_exists) {
        echo json_encode(['success' => false, 'error' => 'username_exists']);
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new teacher
    $sql = "INSERT INTO teachers (teacher_id, faculty_id, full_name, username, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$teacher_id, $faculty_id, $full_name, $username, $hashed_password])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode(["success" => true, "message" => "Teacher added successfully"]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>