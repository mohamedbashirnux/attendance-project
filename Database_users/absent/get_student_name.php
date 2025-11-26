<?php
include "../../connection/connect.php";

$student_id = $_GET['student_id'];
$class_name = $_GET['class_name'];
$study_mode = $_GET['study_mode'];
$department_name = $_GET['department_name'];
$faculty = $_GET['faculty'];

try {
    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT student_name FROM students WHERE student_id = ? AND class_name = ? AND study_mode = ? AND department_name = ? AND faculty_name = ?");
    
    // Execute the statement with bound parameters
    $stmt->execute([$student_id, $class_name, $study_mode, $department_name, $faculty]);

    // Fetch the student name
    $student_name = $stmt->fetchColumn();

    // Check if student exists
    if ($student_name) {
        echo json_encode(['success' => true, 'student_name' => $student_name]);
    } else {
        echo json_encode(['success' => false]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Close the connection
$conn = null;
?>
