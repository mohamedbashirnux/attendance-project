<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "conn.php";

// Function to filter and sanitize user input

// Get the class, department, and study mode from the request
$class_name = filterRequest('class_name');
$department_name = filterRequest('department_name');
$study_mode = filterRequest('study_mode');

// Prepare and execute the SQL statement with ordering by student name and including status
// Only select students where status = 'approved'
$stmt = $conn->prepare("SELECT student_id, student_name, tell, password, department_name, class_name, study_mode, semester, academic, faculty_name, status FROM `students` WHERE `class_name` = ? AND `department_name` = ? AND `study_mode` = ? AND `status` = 'approved' ORDER BY `student_name` ASC");
$stmt->execute(array($class_name, $department_name, $study_mode));

// Fetch results as an associative array
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if any results were found and respond accordingly
if ($results) {
    echo json_encode(array("status" => "success", "details" => $results));
} else {
    echo json_encode(array("status" => "fail", "message" => "No approved students found for the specified criteria"));
}

?>
