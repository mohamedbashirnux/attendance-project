<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

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
        // Check if the new faculty name already exists
        $checkQuery = "SELECT * FROM facultytable WHERE faculty_name = :facultyName";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':facultyName', $editFacultyName);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'error' => 'Faculty exists']);
            exit();
        }

        // Update faculty name
        $updateQuery = "UPDATE facultytable SET faculty_name = :newName WHERE faculty_name = :oldName";
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
