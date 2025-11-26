<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

$student_id = filterRequest('student_id');
$password = filterRequest('password');
$device_token = filterRequest('device_token'); // New parameter for device token

// Check if student_id and password are provided
if (empty($student_id) || empty($password)) {
    echo json_encode(array("status" => "fail", "message" => "Student ID and password are required"));
    exit;
}

// Prepare and execute the SQL statement to find the student
try {
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ? AND password = ?");
    $stmt->execute(array($student_id, $password));

    // Fetch results as associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if any results were found
    if ($results) {
        $student = $results[0]; // Get the first (and should be only) student
        
        // NEW LOGIC: Always allow login and update device token
        // This means notifications will go to the newest device
        if (!empty($device_token)) {
            $updateStmt = $conn->prepare("UPDATE students SET device_token = ?, last_token_update = NOW() WHERE student_id = ?");
            $updateStmt->execute(array($device_token, $student_id));
            
            // Log the device token update
            error_log("Device token updated for student $student_id to: " . substr($device_token, 0, 20) . "...");
        }

        echo json_encode(array("status" => "success", "users" => $results));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Invalid Student ID or Password"));
    }
} catch (PDOException $e) {
    echo json_encode(array("status" => "error", "message" => "Database error: " . $e->getMessage()));
}
?>
