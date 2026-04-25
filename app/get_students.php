<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

$classId = filterRequest('class_id');

if (empty($classId)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Class ID is required"
    ]);
    exit;
}

// Simple query - only student_id and full_name
$sql = "SELECT student_id, full_name as student_name FROM students WHERE class_id = ? AND status = 'approved' ORDER BY full_name ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$classId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            "status" => "success",
            "details" => $data
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "No approved students found in this class"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>