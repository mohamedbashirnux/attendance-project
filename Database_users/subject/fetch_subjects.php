<?php
session_start();
include "../../connection/connect.php";

if (!isset($_SESSION['faculty'])) {
    error_log('Faculty not set in session.');
    exit('Faculty not set in session.');
}

if (!isset($_GET['department_name']) || !isset($_GET['class_name']) || !isset($_GET['study_mode']) || !isset($_GET['faculty_name'])) {
    error_log('Missing required parameters.');
    exit('Missing required parameters.');
}

$faculty = $_SESSION['faculty'];
$departmentName = $_GET['department_name'];
$className = $_GET['class_name'];
$studyMode = $_GET['study_mode'];
$facultyName = $_GET['faculty_name'];

error_log("Parameters received - Department: $departmentName, Class: $className, Study Mode: $studyMode, Faculty: $facultyName");

$sql = "SELECT DISTINCT subject_name FROM subjects WHERE department_name = :department_name AND class_name = :class_name AND study_mode = :study_mode AND faculty_name = :faculty_name";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':department_name' => $departmentName,
    ':class_name' => $className,
    ':study_mode' => $studyMode,
    ':faculty_name' => $facultyName
]);

$options = '<option value="" disabled selected>Choose subject</option>';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options .= "<option value='" . htmlspecialchars($row['subject_name']) . "'>" . htmlspecialchars($row['subject_name']) . "</option>";
}

echo $options;
?>
