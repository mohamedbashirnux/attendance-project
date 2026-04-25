<?php
header('Content-Type: application/json');

// Database connection using PDO
include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $originalFacultyName = $_POST['originalFacultyName'];
    $editFacultyName = $_POST['editFacultyName'];

    if (empty($editFacultyName)) {
        echo json_encode(['success' => false, 'error' => 'Faculty name is required']);
        exit();
    }

    try {
        // Check if the new faculty name already exists (excluding current record)
        $checkQuery = "SELECT * FROM faculty WHERE faculty_name = :facultyName AND faculty_name != :oldName";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':facultyName', $editFacultyName);
        $stmt->bindParam(':oldName', $originalFacultyName);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'error' => 'Faculty exists']);
            exit();
        }

        // Update faculty name
        $updateQuery = "UPDATE faculty SET faculty_name = :newName WHERE faculty_name = :oldName";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':newName', $editFacultyName);
        $stmt->bindParam(':oldName', $originalFacultyName);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error updating faculty']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

// Close connection
$conn = null;
?>
