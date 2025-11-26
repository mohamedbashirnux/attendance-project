<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

// Function to filter and sanitize user input

// Get the teacher ID from the request
$teacherId = filterRequest('teacher_id');

// Prepare and execute the SQL statement
$stmt = $conn->prepare("SELECT class_name, status, study_mode, department_name, subject_name FROM `allocate_teacher_subject` WHERE `tid` = ?");
$stmt->execute(array($teacherId));

// Fetch results as associative array
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if any results were found and respond accordingly
if ($results) {
    echo json_encode(array("status" => "success", "details" => $results));
} else {
    echo json_encode(array("status" => "fail", "message" => "No details found for the specified teacher ID"));
}

?>
