<?php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];
include "../../connection/connect.php";

$sql = "SELECT DISTINCT department_name FROM departments WHERE faculty_name = :faculty_name";
$stmt = $conn->prepare($sql);
$stmt->execute([':faculty_name' => $faculty]);

$options = "";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options .= "<option value=\"" . htmlspecialchars($row['department_name']) . "\">" . htmlspecialchars($row['department_name']) . "</option>";
}

echo $options;
?>
