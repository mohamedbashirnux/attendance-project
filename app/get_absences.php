<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Example PHP code for fetching absences
include "conn.php"; // Your database connection

// Get the student_id from the request
$student_id = $_REQUEST['student_id'] ?? null; // Get student ID

// Check if student_id is provided
if (empty($student_id)) {
    echo json_encode(array("status" => "fail", "message" => "Student ID is required"));
    exit;
}

// Prepare and execute the SQL statement to find absences
$stmt = $conn->prepare("SELECT * FROM absents WHERE student_id = ?");
$stmt->execute(array($student_id));

// Fetch results as associative array
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if any results were found and respond accordingly
if ($results) {
    echo json_encode(array("status" => "success", "absences" => $results));
} else {
    echo json_encode(array("status" => "fail", "message" => "No absence records found for this student"));
}

?>
