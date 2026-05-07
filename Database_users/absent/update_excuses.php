<?php
// Include the database connection
include "../../connection/connect.php";

// Get POST data
$absence_id = $_POST['absence_id']; // Using the absence record ID for precise update
$excuses = $_POST['excuses'];

try {
    // Prepare the update statement using the absence ID
    $stmt = $conn->prepare("
        UPDATE absences 
        SET excuse = :excuses, updated_at = CURRENT_TIMESTAMP
        WHERE id = :absence_id
    ");

    // Bind parameters
    $stmt->bindParam(':excuses', $excuses, PDO::PARAM_STR);
    $stmt->bindParam(':absence_id', $absence_id, PDO::PARAM_INT);

    // Execute the statement
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Excuse updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No record found to update']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update excuse']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>