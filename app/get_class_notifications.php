<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "conn.php";

$student_id = filterRequest('student_id');

if (empty($student_id)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Student ID is required"
    ]);
    exit;
}

try {
    // Get student's class_id
    $studentStmt = $conn->prepare("SELECT class_id FROM students WHERE student_id = ?");
    $studentStmt->execute([$student_id]);
    $studentData = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentData) {
        echo json_encode([
            "status" => "fail",
            "message" => "Student not found"
        ]);
        exit;
    }

    $class_id = $studentData['class_id'];

    // Get all notifications for this class (last 30 days)
    $stmt = $conn->prepare("
        SELECT 
            id,
            title,
            message,
            sent_count,
            sent_at
        FROM notification_logs
        WHERE class_id = ?
        AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY sent_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$class_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "notifications" => $notifications,
        "count" => count($notifications)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
