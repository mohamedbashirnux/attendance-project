<?php
// Database connection
include "../../connection/connect.php"; // Assuming this includes the PDO connection setup

try {
    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $faculty_name = $_POST['faculty'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Check if the faculty_name is valid
        $query = "SELECT COUNT(*) FROM facultytable WHERE faculty_name = :faculty_name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn(); // Fetches the first column (COUNT result)

        if ($count == 0) {
            die("Error: Invalid faculty name.");
        }

        // Check if a user with the same faculty already exists
        $query = "SELECT COUNT(*) FROM users WHERE faculty_name = :faculty_name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            die("Error: Faculty already has an account.");
        }

        // Insert user into database
        $query = "INSERT INTO users (faculty_name, username, password) VALUES (:faculty_name, :username, :password)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR); // Insert plain text password (consider hashing in production)

        if ($stmt->execute()) {
            echo "User added successfully";
        } else {
            echo "Error adding user: " . $stmt->errorInfo()[2]; // PDO error handling
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
// Close the connection
$conn = null;
?>
