<?php
session_start();
include "../../connection/connect.php";

if (!isset($_SESSION['faculty']) || !isset($_GET['department_name']) || !isset($_GET['class_name']) || !isset($_GET['faculty_name'])) {
    exit('Missing required parameters');
}

$faculty = $_SESSION['faculty'];
$departmentName = $_GET['department_name'];
$className = $_GET['class_name'];
$facultyName = $_GET['faculty_name'];

$sql = "SELECT DISTINCT study_mode FROM classes WHERE department_name = :department_name AND class_name = :class_name AND faculty_name = :faculty_name";
$stmt = $conn->prepare($sql);
$stmt->execute([':department_name' => $departmentName, ':class_name' => $className, ':faculty_name' => $facultyName]);

$options = '<option value="" disabled selected>Choose study mode</option>';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options .= '<option value="' . htmlspecialchars($row['study_mode']) . '">' . htmlspecialchars($row['study_mode']) . '</option>';
}

echo $options;
?>
