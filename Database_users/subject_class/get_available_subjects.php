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
    $department_id = $_GET['department_id'] ?? '';
    $class_id = $_GET['class_id'] ?? '';

    if (empty($department_id) || empty($class_id)) {
        throw new Exception("Department ID and Class ID are required");
    }

    // Get subjects from the same department that are NOT already assigned to this class
    $subjects_sql = "SELECT s.id, s.subject_name 
                   FROM subjects s 
                   WHERE s.department_id = ? AND s.faculty_id = ? 
                   AND s.id NOT IN (
                       SELECT sc.subject_id 
                       FROM subject_class sc 
                       WHERE sc.class_id = ? AND sc.faculty_id = ?
                   )
                   ORDER BY s.subject_name";
    $subjects_stmt = $conn->prepare($subjects_sql);
    $subjects_stmt->execute([$department_id, $faculty_id, $class_id, $faculty_id]);
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode(['success' => true, 'subjects' => $subjects]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>