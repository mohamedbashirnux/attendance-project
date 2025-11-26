<?php
session_start();
include "../../connection/connect.php";

// Check session for faculty
if (!isset($_SESSION['faculty'])) {
    exit('Faculty not set in session.');
}

$faculty = $_SESSION['faculty'];

// Check for either department_id or department_name
if (isset($_GET['department_id'])) {
    $departmentIdentifier = $_GET['department_id'];
    $paramName = 'department_id';
} elseif (isset($_GET['department_name'])) {
    $departmentIdentifier = $_GET['department_name'];
    $paramName = 'department_name';
} else {
    exit('Neither Department ID nor Department Name provided.');
}

// Prepare SQL query to fetch classes by department and faculty
$sql = "SELECT class_name FROM classes WHERE $paramName = :department_identifier AND faculty_name = :faculty_name";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':department_identifier', $departmentIdentifier, PDO::PARAM_STR);
$stmt->bindParam(':faculty_name', $faculty, PDO::PARAM_STR);
$stmt->execute();

// Fetch classes and output as options for select dropdown
$options = '';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options .= "<option value='{$row['class_name']}'>{$row['class_name']}</option>";
}

echo $options;

$stmt->closeCursor();
$conn = null;
?>
