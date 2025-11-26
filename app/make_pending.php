<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

// Retrieve and filter the input parameters
$class_name = filterRequest('class_name');
$department_name = filterRequest('department_name');
$study_mode = filterRequest('study_mode');
$subject_name = filterRequest('subject_name');
// $teacher_name = filterRequest('teacher_name');

// Preparing the SQL statement for updating data
$stmt = $conn->prepare("UPDATE `allocate_teacher_subject` 
                        SET `status` = 'pending' 
                        WHERE `department_name` = ? 
                        AND `class_name` = ? 
                        AND `study_mode` = ? 
                        AND `subject_name` = ?");

// Executing the prepared statement with the provided values
$stmt->execute(array( $department_name, $class_name, $study_mode, $subject_name));

// Checking the number of affected rows
$count = $stmt->rowCount();

// Returning the status as a JSON response
if ($count > 0) {
    echo json_encode(array("status" => "success"));
} else {
    echo json_encode(array("status" => "fail")); 
}
?>
