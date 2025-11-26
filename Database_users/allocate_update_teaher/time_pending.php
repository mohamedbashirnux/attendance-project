<?php
include "../../connection/connect.php";
date_default_timezone_set('Africa/Mogadishu');

$currentTime = date('H:i');

try {
    // Update statuses to 'approved' if within the time range
    $sql = "UPDATE allocate_teacher_subject
            SET status = 'approved'
            WHERE status = 'waiting' AND start_time <= :currentTime AND end_time >= :currentTime";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':currentTime', $currentTime, PDO::PARAM_STR);
    $stmt->execute();

    // Revert statuses to 'pending' if the end time has passed
    $sql = "UPDATE allocate_teacher_subject
            SET status = 'pending'
            WHERE status = 'approved' AND end_time < :currentTime";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':currentTime', $currentTime, PDO::PARAM_STR);
    $stmt->execute();

    // Revert statuses to 'pending' if they are 'approved' but the current time has not yet reached the start time
    $sql = "UPDATE allocate_teacher_subject
            SET status = 'pending'
            WHERE status = 'approved' AND start_time > :currentTime";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':currentTime', $currentTime, PDO::PARAM_STR);
    $stmt->execute();

    $stmt->closeCursor(); // Close the cursor
    $conn = null; // Close the PDO connection

    // Optionally, you can return a response
    header("Content-Type: application/json");
    echo json_encode(["status" => "Updated successfully"]);
} catch (PDOException $e) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "Error: " . htmlspecialchars($e->getMessage())]);
}
?>
