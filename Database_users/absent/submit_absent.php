<?php
include "../../connection/connect.php";

// Get the data from the POST request
$data = json_decode(file_get_contents('php://input'), true);

$student_id = $data['student_id'];
$student_name = $data['student_name'];
$class_name = $data['class_name'];
$department_name = $data['department_name'];
$study_mode = $data['study_mode'];
$subject_name = $data['subject'];
$absent_date = $data['absent_date'];
$statuses = $data['status'];
$excuses = $data['cudur_daar'];
$faculty = $data['faculty'];

try {
    // Prepare the SQL statement using PDO
    $stmt = $conn->prepare("
        INSERT INTO absents (student_id, student_name, class_name, department_name, study_mode, subject_name, absent_date, statuses, excuses, faculty_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Execute the statement with bound parameters
    $stmt->execute([$student_id, $student_name, $class_name, $department_name, $study_mode, $subject_name, $absent_date, $statuses, $excuses, $faculty]);

    // If execution is successful
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Return an error message if the query fails
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Close the connection
$conn = null;
?>
