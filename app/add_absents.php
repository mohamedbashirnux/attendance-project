<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

$student_id = filterRequest('student_id');
$student_name = filterRequest('student_name');
$class_name = filterRequest('class_name');
$department_name = filterRequest('department_name');
$study_mode = filterRequest('study_mode');
$subject_name = filterRequest('subject_name');
$absent_date = filterRequest('absent_date');
$statuses = filterRequest('statuses');
$excuses = filterRequest('excuses');
$faculty_name = filterRequest('faculty_name');
$semester = filterRequest('semester');
$academic = filterRequest('academic');

// Preparing the SQL statement for inserting data with semester and academic
$stmt = $conn->prepare("INSERT INTO `absents`(`student_id`, `student_name`, `class_name`, `department_name`, `study_mode`, `subject_name`, `absent_date`, `statuses`, `excuses`, `faculty_name`, `semester`, `academic`) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");

// Executing the prepared statement with the provided values including semester and academic
$stmt->execute(array($student_id, $student_name, $class_name, $department_name, $study_mode, $subject_name, $absent_date, $statuses, $excuses, $faculty_name, $semester, $academic));

// Checking the number of affected rows
$count = $stmt->rowCount();

// Returning the status as JSON response
if ($count > 0) {
    echo json_encode(array("status" => "success"));
} else {
    echo json_encode(array("status" => "fail")); 
}
?>
