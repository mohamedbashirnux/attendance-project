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
    $student_id = trim($_POST['student_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $status = trim($_POST['status'] ?? 'approved');

    // Validate required fields
    if (empty($id) || empty($student_id) || empty($full_name) || empty($phone)) {
        throw new Exception("All required fields must be filled");
    }

    // Verify that the student exists and belongs to a class in this faculty
    $verify_sql = "SELECT s.id, s.class_id FROM students s 
                   JOIN classes c ON s.class_id = c.id 
                   WHERE s.id = ? AND c.faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$id, $faculty_id]);
    $student = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("Access denied - student not found or doesn't belong to your faculty");
    }

    // Check if student_id already exists (excluding current student)
    $check_student_id_sql = "SELECT id FROM students WHERE student_id = ? AND id != ?";
    $check_student_id_stmt = $conn->prepare($check_student_id_sql);
    $check_student_id_stmt->execute([$student_id, $id]);
    
    if ($check_student_id_stmt->fetch()) {
        throw new Exception("Student ID already exists");
    }

    // Prepare update query
    if (!empty($password)) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE students SET student_id = ?, full_name = ?, phone = ?, password = ?, status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $params = [$student_id, $full_name, $phone, $hashed_password, $status, $id];
    } else {
        // Update without changing password
        $update_sql = "UPDATE students SET student_id = ?, full_name = ?, phone = ?, status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $params = [$student_id, $full_name, $phone, $status, $id];
    }

    if (!$update_stmt->execute($params)) {
        $errorInfo = $update_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Student updated successfully"
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