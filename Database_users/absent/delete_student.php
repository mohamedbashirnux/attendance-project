<?php
header('Content-Type: application/json');
include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $absence_id = $_POST['id']; // Using the absence record ID for precise deletion

    try {
        // Delete from absences table using the ID
        $stmt = $conn->prepare("DELETE FROM absences WHERE id = :id");
        $stmt->bindParam(':id', $absence_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Absence record deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No record found to delete']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}