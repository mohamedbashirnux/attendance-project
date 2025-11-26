<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

// Function to filter and sanitize user input



// Get the class, department, study mode, and subject name from the request
$class_name = filterRequest('class_name');
$department_name = filterRequest('department_name');
$study_mode = filterRequest('study_mode');
$subject_name = filterRequest('subject_name');

// Prepare and execute the SQL statement
$stmt = $conn->prepare("SELECT status FROM allocate_teacher_subject WHERE class_name = ? AND department_name = ? AND study_mode = ? AND subject_name = ?");
$stmt->execute(array($class_name, $department_name, $study_mode, $subject_name));

// Fetch results as an associative array
$results = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if any results were found and respond accordingly
if ($results) {
    echo json_encode(array("status" => $results['status']));
} else {
    echo json_encode(array("status" => "fail", "message" => "No details found for the specified criteria"));
}

?>
