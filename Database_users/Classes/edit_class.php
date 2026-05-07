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

header('Content-Type: application/json');

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $class_id = trim($_POST['classId'] ?? '');
    $department_id = trim($_POST['departmentName'] ?? '');
    $class_name = trim($_POST['className'] ?? '');
    $study_mode = trim($_POST['studyMode'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $academic_year = trim($_POST['academicYear'] ?? '');

    // Validate input
    if (empty($class_id) || empty($department_id) || empty($class_name) || empty($study_mode) || empty($semester) || empty($academic_year)) {
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

    // Begin transaction
    $conn->beginTransaction();

    // Verify the class belongs to this faculty
    $verify_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$class_id, $faculty_id]);
    
    if ($verify_stmt->rowCount() === 0) {
        throw new Exception("Class not found or access denied");
    }

    // Verify department belongs to this faculty
    $dept_verify_sql = "SELECT id FROM departments WHERE id = ? AND faculty_id = ?";
    $dept_verify_stmt = $conn->prepare($dept_verify_sql);
    $dept_verify_stmt->execute([$department_id, $faculty_id]);
    
    if ($dept_verify_stmt->rowCount() === 0) {
        throw new Exception("Invalid department selected");
    }

    // Check if updated class already exists (excluding current class)
    $check_sql = "SELECT id FROM classes WHERE faculty_id = ? AND department_id = ? AND class_name = ? AND study_mode = ? AND semester = ? AND academic_year = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$faculty_id, $department_id, $class_name, $study_mode, $semester, $academic_year, $class_id]);
    
    if ($check_stmt->rowCount() > 0) {
        throw new Exception("Class already exists");
    }

    // Update class
    $sql = "UPDATE classes SET department_id = ?, class_name = ?, study_mode = ?, semester = ?, academic_year = ? WHERE id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$department_id, $class_name, $study_mode, $semester, $academic_year, $class_id, $faculty_id]);

    // Commit transaction
    $conn->commit();
    
    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Class updated successfully']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

ob_end_flush();
?>