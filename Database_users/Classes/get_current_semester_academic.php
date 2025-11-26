<?php
session_start(); // Start session to access session variables if needed

include "../../connection/connect.php"; // Include your database connection

// Prepare an array to hold the current semester and academic year
$response = [];

// Assuming you have a table or way to fetch current semester and academic year
try {
    // Example query to get the current semester and academic year
    // Adjust the table and column names according to your database structure
    $query = "SELECT semester, academic_year FROM current_academic WHERE id = 1"; // Modify as needed
    $stmt = $conn->prepare($query);
    $stmt->execute();

    // Fetch the result
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['semester'] = $row['semester'];
        $response['academic'] = $row['academic_year'];
        $response['status'] = 'success';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'No current academic data found';
    }
} catch (PDOException $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

// Return the response as JSON
echo json_encode($response);

// Close the database connection
$conn = null;
?>
