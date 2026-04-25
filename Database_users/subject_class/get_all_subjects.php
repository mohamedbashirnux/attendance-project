<?php
// Suppress PHP warnings to ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Set content type to JSON first
header('Content-Type: application/json; charset=utf-8');

// Include the faculty session management
include "../../Account_users/session_faculty.php";

// Include database connection
include "../../connection/connect.php";

// Clear any unexpected output from includes
ob_clean();

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

    // Get ALL subjects from the department with assignment status
    $subjects_sql = "SELECT s.id, s.subject_name,
                           CASE WHEN sc.id IS NOT NULL THEN 1 ELSE 0 END as is_assigned
                    FROM subjects s 
                    LEFT JOIN subject_class sc ON s.id = sc.subject_id AND sc.class_id = ? AND sc.faculty_id = ?
                    WHERE s.department_id = ? AND s.faculty_id = ? 
                    ORDER BY s.subject_name";
    $subjects_stmt = $conn->prepare($subjects_sql);
    $subjects_stmt->execute([$class_id, $faculty_id, $department_id, $faculty_id]);
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert is_assigned to boolean for clarity
    foreach ($subjects as &$subject) {
        $subject['is_assigned'] = (bool)$subject['is_assigned'];
    }

    ob_clean();
    echo json_encode(['success' => true, 'subjects' => $subjects]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'debug' => [
        'file' => __FILE__,
        'line' => __LINE__
    ]]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage(), 'debug' => [
        'file' => __FILE__,
        'line' => __LINE__
    ]]);
}

ob_end_flush();
?>