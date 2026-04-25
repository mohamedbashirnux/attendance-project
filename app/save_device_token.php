<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

$student_id = filterRequest('student_id');
$device_token = filterRequest('device_token');

if (empty($student_id) || empty($device_token)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Student ID and device token are required"
    ]);
    exit;
}

try {
    // Check if token already exists for this student
    $stmt = $conn->prepare("SELECT id FROM device_tokens WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing token
        $stmt = $conn->prepare("UPDATE device_tokens SET device_token = ?, updated_at = NOW() WHERE student_id = ?");
        $stmt->execute([$device_token, $student_id]);
    } else {
        // Insert new token
        $stmt = $conn->prepare("INSERT INTO device_tokens (student_id, device_token, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$student_id, $device_token]);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Device token saved successfully"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
