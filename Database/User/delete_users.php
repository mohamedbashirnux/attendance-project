<?php
// Database connection
include "../../connection/connect.php"; // Assuming this includes the PDO connection setup

// Check if POST request with faculty_name
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['faculty_name'])) {
    $facultyName = $_POST['faculty_name'];

    try {
        // Prepare and execute delete query
        $query = "DELETE FROM users WHERE faculty_name = :faculty_name";
        $stmt = $conn->prepare($query);

        // Bind the faculty name parameter
        $stmt->bindParam(':faculty_name', $facultyName, PDO::PARAM_STR);

        // Execute the statement
        if ($stmt->execute()) {
            echo "User deleted successfully";
        } else {
            echo "Error deleting user.";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    // Close the connection
    $conn = null;
}
?>
