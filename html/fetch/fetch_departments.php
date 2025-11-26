<?php
// Remove session dependencies for super admin access
include "../../connection/connect.php";

$faculty_name = isset($_GET['faculty_name']) ? $_GET['faculty_name'] : '';

if (!$faculty_name) {
    echo '<option value="" disabled selected>Please select a faculty first</option>';
    exit();
}

$sql = "SELECT DISTINCT department_name FROM departments WHERE faculty_name = :faculty_name";
$stmt = $conn->prepare($sql);
$stmt->execute([':faculty_name' => $faculty_name]);

$options = "";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options .= "<option value=\"" . htmlspecialchars($row['department_name']) . "\">" . htmlspecialchars($row['department_name']) . "</option>";
}

echo $options;
?>
