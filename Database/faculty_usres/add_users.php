<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

try {
    $faculty_id = $_POST['faculty'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username already exists
    $checkSql = "SELECT * FROM faculty_users WHERE username = :username";
    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'Username already exists']);
        exit();
    }

    // Insert new user
    $insertSql = "INSERT INTO faculty_users (faculty_id, username, password) VALUES (:faculty_id, :username, :password)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bindParam(':faculty_id', $faculty_id);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error adding user']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
?>
