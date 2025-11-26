<?php
header('Content-Type: application/json');

session_start();
include "../../connection/connect.php";

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    echo json_encode(['study_mode' => '', 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['class_name']) || !isset($_GET['id']) || !isset($_GET['department_name'])) {
    echo json_encode(['study_mode' => '', 'message' => 'Invalid parameters']);
    exit();
}

$class_name = $_GET['class_name'];
$id = $_GET['id'];
$department_name = $_GET['department_name'];

$sql = "SELECT study_mode FROM classes WHERE class_name = :class_name AND faculty_name = :faculty_name AND id = :id AND department_name = :department_name";
$stmt = $conn->prepare($sql);
$stmt->execute([':class_name' => $class_name, ':faculty_name' => $_SESSION['faculty'], ':id' => $id, ':department_name' => $department_name]);

$study_mode = '';
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $study_mode = htmlspecialchars($row['study_mode']);
}

echo json_encode(['study_mode' => $study_mode]);
?>
