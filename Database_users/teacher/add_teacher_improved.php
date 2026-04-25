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

    $teacher_id = trim($_POST['teacher_id'] ?? '');
    $full_name = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validate input
    if (empty($teacher_id) || empty($full_name) || empty($username) || empty($password)) {
        throw new Exception("Teacher ID, Full Name, Username, and Password are required");
    }

    // Validate teacher_id format (you can customize this)
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $teacher_id)) {
        throw new Exception("Teacher ID can only contain letters, numbers, hyphens, and underscores");
    }

    // Validate username format
    if (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
        throw new Exception("Username can only contain letters, numbers, and underscores");
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
        echo json_encode(['success' => false, 'error' => 'both_exists', 'message' => 'Both Teacher ID and Username already exist']);
        exit();
    } elseif ($id_exists) {
        echo json_encode(['success' => false, 'error' => 'id_exists', 'message' => 'Teacher ID already exists']);
        exit();
    } elseif ($username_exists) {
        echo json_encode(['success' => false, 'error' => 'username_exists', 'message' => 'Username already exists']);
        exit();
    }

    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new teacher with improved structure
    $sql = "INSERT INTO teachers (teacher_id, faculty_id, full_name, username, password, email, phone, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$teacher_id, $faculty_id, $full_name, $username, $hashed_password, $email, $phone])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    // Get the auto-generated ID
    $auto_id = $conn->lastInsertId();

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Teacher added successfully",
        "teacher_auto_id" => $auto_id,
        "teacher_id" => $teacher_id
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