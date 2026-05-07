<?php
// Suppress PHP warnings
error_reporting(0);
ini_set('display_errors', 0);

// Set timezone
date_default_timezone_set('Africa/Mogadishu');

// Set JSON header
header('Content-Type: application/json');

// Include session and database
include "../../Account_users/session_faculty.php";
include "../../connection/connect.php";

try {
    // Session is already checked by session_faculty.php
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit();
    }

    $faculty_id = $sessionInfo['faculty_id'];
    
    // Get parameters
    $subject_name = $_GET['subject_name'] ?? '';
    $class_id = $_GET['class_id'] ?? '';
    
    if (empty($subject_name) || empty($class_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit();
    }
    
    // Get teacher and time information for this subject in this class
    $sql = "SELECT t.full_name as teacher_name, tsa.start_time, tsa.end_time
            FROM teacher_subject_allocation tsa
            JOIN subjects s ON tsa.subject_id = s.id
            JOIN teachers t ON tsa.teacher_id = t.id
            JOIN classes c ON tsa.class_id = c.id
            WHERE s.subject_name = ? AND tsa.class_id = ? AND c.faculty_id = ?
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$subject_name, $class_id, $faculty_id]);
    $allocation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($allocation) {
        echo json_encode([
            'success' => true,
            'data' => [
                'teacher_name' => $allocation['teacher_name'],
                'start_time' => $allocation['start_time'],
                'end_time' => $allocation['end_time']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Allocation not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
