<?php
// Database connection
include "../../connection/connect.php"; // Assuming this includes the PDO connection setup

header('Content-Type: application/json');  // Set the content type to JSON

// Check if faculty_name parameter is provided
if (!isset($_GET['faculty_name']) || empty($_GET['faculty_name'])) {
    echo json_encode(['error' => 'Faculty name is required']);
    exit;
}

// Get the faculty name from the request
$faculty_name = $_GET['faculty_name'];

try {
    // Prepare SQL statement to fetch user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE faculty_name = :faculty_name");
    $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user data was found
    if ($user) {
        // Return user data as JSON
        echo json_encode($user);
    } else {
        // No user found
        echo json_encode(['error' => 'User not found']);
    }
} catch (PDOException $e) {
    // Handle any errors
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

// Close the connection
$conn = null;
?>
