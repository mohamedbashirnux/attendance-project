<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $originalUsername = $_POST['originalUsername'];
    $newUsername = $_POST['username'];
    $newPassword = $_POST['password'];

    if (empty($newUsername) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit();
    }

    try {
        // Check if new username already exists (excluding current user)
        $checkQuery = "SELECT * FROM super_admin WHERE username = :newUsername AND username != :originalUsername";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':newUsername', $newUsername);
        $stmt->bindParam(':originalUsername', $originalUsername);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists!']);
            exit();
        }

        // Update admin
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE super_admin SET username = :newUsername, password = :password WHERE username = :originalUsername";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':newUsername', $newUsername);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':originalUsername', $originalUsername);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Admin updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating admin']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn = null;
?>
