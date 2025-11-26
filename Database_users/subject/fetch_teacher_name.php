<?php
session_start();

// Database connection settings
include "../../connection/connect.php";

// Check if teacher_id is provided
if (!isset($_GET['teacher_id'])) {
    exit('Teacher ID not provided.');
}

$teacherId = $_GET['teacher_id'];

// Prepare SQL query to fetch teacher name by ID
$sql = "SELECT teacher_name FROM teachertable WHERE tid = :teacher_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_STR);

// Execute query
$stmt->execute();

// Fetch teacher name
$teacherName = $stmt->fetchColumn();

if ($teacherName) {
    echo trim($teacherName); // Ensure no extra spaces
} else {
    echo "Unknown teacher"; // Return a default message if teacher not found
}

// Close statement and connection
$stmt->closeCursor();
$conn = null;
?>
