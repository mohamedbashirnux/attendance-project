<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";


// Collect session info
$subject_name     = filterRequest('subject_name');
$class_name       = filterRequest('class_name');
$department_name  = filterRequest('department_name');
$study_mode       = filterRequest('study_mode');
$faculty_name     = filterRequest('faculty_name');
$semester         = filterRequest('semester');
$academic         = filterRequest('academic');
$session_date     = filterRequest('session_date'); // 👈 Teacher chooses the real class date

// Prepare the SQL statement
$stmt = $conn->prepare("INSERT INTO `submit_session`
    (`subject_name`, `class_name`, `department_name`, `study_mode`, `faculty_name`, `semester`, `academic`, `session_date`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

// Execute the statement
$stmt->execute([
    $subject_name,
    $class_name,
    $department_name,
    $study_mode,
    $faculty_name,
    $semester,
    $academic,
    $session_date
]);

// Check if inserted
if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "success", "message" => "Session recorded successfully"]);
} else {
    echo json_encode(["status" => "fail", "message" => "Failed to record session"]);
}
?>
