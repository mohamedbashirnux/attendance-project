<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: auth_admin.php");
    exit();
}

include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $facultyName = $_POST['facultyName'];

    if (empty($facultyName)) {
        echo json_encode(['success' => false, 'error' => 'Faculty name is required']);
        exit();
    }

    $sql = "DELETE FROM facultytable WHERE faculty_name = :facultyName";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':facultyName', $facultyName);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error deleting faculty']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error deleting faculty: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

// Close connection
$conn = null;
?>
