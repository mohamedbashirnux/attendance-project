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
    $class_id = $_GET['class_id'] ?? '';

    if (empty($class_id)) {
        throw new Exception("Class ID is required");
    }

    // Get ALL subjects assigned to this class from subject_class table
    // This will show all subjects regardless of whether attendance has been taken
    $subjects_sql = "SELECT DISTINCT s.id, s.subject_name 
                   FROM subjects s 
                   JOIN subject_class sc ON s.id = sc.subject_id
                   WHERE sc.class_id = ?
                   ORDER BY s.subject_name";
    $subjects_stmt = $conn->prepare($subjects_sql);
    $subjects_stmt->execute([$class_id]);
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        'success' => true, 
        'subjects' => $subjects,
        'debug' => [
            'class_id' => $class_id,
            'faculty_id' => $faculty_id,
            'count' => count($subjects)
        ]
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>