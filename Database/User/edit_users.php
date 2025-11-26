<?php
// Database connection
include "../../connection/connect.php"; // Assuming this includes the PDO connection setup

// Get form data
$faculty_name = isset($_POST['faculty_name']) ? $_POST['faculty_name'] : '';
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate form data
if (empty($faculty_name) || empty($username) || empty($password)) {
    echo 'All fields are required';
    exit;
}

try {
    // Update user details
    $query = "UPDATE users SET username = :username, password = :password WHERE faculty_name = :faculty_name";
    $stmt = $conn->prepare($query);
    
    // Bind the parameters
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);  // Ensure passwords are securely hashed in real applications
    $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
    
    // Execute the query
    $stmt->execute();
    
    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        echo 'User updated successfully';
    } else {
        echo 'No changes made or user not found';
    }

} catch (PDOException $e) {
    echo 'Error updating user: ' . $e->getMessage();
}

// Close the connection
$conn = null;
?>

