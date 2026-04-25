<?php
// Get all teachers with both auto-increment ID and teacher_id for allocation dropdowns

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

include "../../Account_users/session_faculty.php";
include "../../connection/connect.php";
ob_clean();

header('Content-Type: application/json');

try {
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    // Get all teachers for the current faculty
    $sql = "SELECT t.id as auto_id, t.teacher_id, t.full_name, f.faculty_name 
            FROM teachers t 
            JOIN faculty f ON t.faculty_id = f.id 
            WHERE t.faculty_id = ?
            ORDER BY t.teacher_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$faculty_id]);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'teachers' => $teachers
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush();
?>