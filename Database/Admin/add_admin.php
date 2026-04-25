<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

try {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username already exists
    $checkSql = "SELECT * FROM super_admin WHERE username = :username";
    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists!']);
        exit();
    }

    // Insert new admin
    $insertSql = "INSERT INTO super_admin (username, password) VALUES (:username, :password)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding admin']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
?>
