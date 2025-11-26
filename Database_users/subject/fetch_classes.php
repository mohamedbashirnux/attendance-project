<?php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];
$department_name = isset($_GET['department_name']) ? $_GET['department_name'] : '';
if (!$department_name) {
    exit("No department name provided.");
}

include "../../connection/connect.php";

$sql = "SELECT id, class_name, study_mode, department_name, semester, academic FROM classes WHERE department_name = :department_name AND faculty_name = :faculty_name";
$stmt = $conn->prepare($sql);
$stmt->execute([':department_name' => $department_name, ':faculty_name' => $faculty]);

$options = "";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $classWithStudyMode = htmlspecialchars($row['class_name']) . " (" . htmlspecialchars($row['study_mode']) . ")";
    $options .= "<option value=\"" . htmlspecialchars($row['id']) . "\" data-class-name=\"" . htmlspecialchars($row['class_name']) . "\" data-department-name=\"" . htmlspecialchars($row['department_name']) . "\" data-academic=\"" . htmlspecialchars($row['academic']) . "\" data-semester=\"" . htmlspecialchars($row['semester']) . "\">" . $classWithStudyMode . "</option>";
}

echo $options;
?>
