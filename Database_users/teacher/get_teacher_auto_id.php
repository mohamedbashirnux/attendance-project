<?php
// Helper function to get teacher's auto-increment ID by teacher_id
// This is used when creating allocations

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

    if (!isset($_GET['teacher_id'])) {
        throw new Exception("Teacher ID is required");
    }

    $teacher_id = trim($_GET['teacher_id']);

    // Get teacher's auto-increment ID and basic info
    $sql = "SELECT id, teacher_id, full_name FROM teachers WHERE teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($teacher) {
        echo json_encode([
            'success' => true,
            'auto_id' => $teacher['id'],
            'teacher_id' => $teacher['teacher_id'],
            'full_name' => $teacher['full_name']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher not found'
        ]);
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush();
?>