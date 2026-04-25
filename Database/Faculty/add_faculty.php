<?php
header('Content-Type: application/json');

// Database connection using PDO
include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

try {
    // Get POST data
    $facultyName = $_POST['facultyName'];

    // Check if faculty already exists
    $checkSql = "SELECT * FROM faculty WHERE faculty_name = :facultyName";
    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(':facultyName', $facultyName);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo json_encode(['success' => false, 'error' => 'Faculty exists']);
        exit();
    }

    // Insert new faculty
    $insertSql = "INSERT INTO faculty (faculty_name) VALUES (:facultyName)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bindParam(':facultyName', $facultyName);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error adding faculty']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

// Close connection
$conn = null;
?>
