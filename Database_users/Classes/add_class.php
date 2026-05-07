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

    $department_id = trim($_POST['departmentName'] ?? '');
    $class_name = trim($_POST['className'] ?? '');
    $study_mode = trim($_POST['studyMode'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $academic_year = trim($_POST['academicYear'] ?? '');

    // Validate input
    if (empty($department_id) || empty($class_name) || empty($study_mode) || empty($semester) || empty($academic_year)) {
        throw new Exception("All fields are required");
    }

    // Get valid ENUM values from database
    $study_mode_query = "SHOW COLUMNS FROM classes LIKE 'study_mode'";
    $study_mode_result = $conn->query($study_mode_query);
    $study_mode_row = $study_mode_result->fetch(PDO::FETCH_ASSOC);
    preg_match_all("/'([^']+)'/", $study_mode_row['Type'], $study_mode_matches);
    $valid_study_modes = $study_mode_matches[1];

    $semester_query = "SHOW COLUMNS FROM classes LIKE 'semester'";
    $semester_result = $conn->query($semester_query);
    $semester_row = $semester_result->fetch(PDO::FETCH_ASSOC);
    preg_match_all("/'([^']+)'/", $semester_row['Type'], $semester_matches);
    $valid_semesters = $semester_matches[1];
    
    // Validate ENUM values
    if (!in_array($study_mode, $valid_study_modes)) {
        throw new Exception("Invalid study mode selected");
    }
    
    if (!in_array($semester, $valid_semesters)) {
        throw new Exception("Invalid semester selected");
    }
    
    // Validate academic year format (YYYY/YYYY)
    if (!preg_match('/^\d{4}\/\d{4}$/', $academic_year)) {
        throw new Exception("Invalid academic year format");
    }

    // Verify department belongs to this faculty
    $verify_sql = "SELECT id FROM departments WHERE id = ? AND faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$department_id, $faculty_id]);
    
    if ($verify_stmt->rowCount() === 0) {
        throw new Exception("Invalid department selected");
    }

    // Check if class already exists
    $check_sql = "SELECT id FROM classes WHERE faculty_id = ? AND department_id = ? AND class_name = ? AND study_mode = ? AND semester = ? AND academic_year = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$faculty_id, $department_id, $class_name, $study_mode, $semester, $academic_year]);

    if ($check_stmt->rowCount() > 0) {
        throw new Exception("Class already exists");
    }

    // Insert new class
    $sql = "INSERT INTO classes (faculty_id, department_id, class_name, study_mode, semester, academic_year) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute([$faculty_id, $department_id, $class_name, $study_mode, $semester, $academic_year])) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode(["status" => "success", "message" => "Class added successfully"]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>