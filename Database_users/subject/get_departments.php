<?php
// Get departments for the logged-in faculty
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

    // Fetch departments for this faculty
    $dept_sql = "SELECT id, department_name FROM departments WHERE faculty_id = ? ORDER BY department_name";
    $dept_stmt = $conn->prepare($dept_sql);
    $dept_stmt->execute([$faculty_id]);
    $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'departments' => $departments
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();
?>