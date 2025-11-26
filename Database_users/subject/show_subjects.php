<?php
header('Content-Type: application/json');
include "../../connection/connect.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    echo json_encode(['subjects' => [], 'message' => 'No subjects found for this class']);
    exit();
}

// Check if department_name and faculty_name are provided
if (isset($_GET['department_name']) && isset($_GET['faculty_name'])) {
    $department_name = $_GET['department_name'];
    $faculty_name = $_GET['faculty_name'];
} else {
    echo json_encode(['subjects' => [], 'message' => 'Invalid parameters']);
    exit();
}

// Fetch all subjects without pagination
$sql = "SELECT * FROM subjects WHERE department_name = :department_name AND faculty_name = :faculty_name";
$stmt = $conn->prepare($sql);
$stmt->execute([':department_name' => $department_name, ':faculty_name' => $faculty_name]);

$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the data as JSON
echo json_encode([
    'subjects' => $subjects,
    'message' => empty($subjects) ? 'No subjects found for this class' : ''
]);

// Close the statement and connection
$stmt->closeCursor();
$conn = null;
?>
