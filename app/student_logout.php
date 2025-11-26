<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

$student_id = $_POST['student_id'] ?? '';

try {
    if (empty($student_id)) {
        echo json_encode([
            'status' => 'fail',
            'message' => 'Student ID is required'
        ]);
        exit;
    }

    // DON'T clear device token - keep it for notifications
    // Just update the last_token_update to show logout time
    $stmt = $conn->prepare("UPDATE students SET last_token_update = NOW() WHERE student_id = ?");
    $result = $stmt->execute([$student_id]);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'fail',
            'message' => 'Failed to logout'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>