<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($user_id) || empty($username)) {
        echo json_encode(['success' => false, 'error' => 'User ID and username are required']);
        exit();
    }

    try {
        // Check if username already exists for another user
        $checkQuery = "SELECT * FROM faculty_users WHERE username = :username AND id != :user_id";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
            exit();
        }

        // Update user
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE faculty_users SET username = :username, password = :password WHERE id = :user_id";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':user_id', $user_id);
        } else {
            $updateQuery = "UPDATE faculty_users SET username = :username WHERE id = :user_id";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':user_id', $user_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error updating user']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn = null;
?>
