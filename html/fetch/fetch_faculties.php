<?php
// Remove session dependencies for super admin access
include "../../connection/connect.php";

$sql = "SELECT DISTINCT faculty_name FROM facultytable ORDER BY faculty_name";
$stmt = $conn->prepare($sql);
$stmt->execute();

$options = "";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options .= "<option value=\"" . htmlspecialchars($row['faculty_name']) . "\">" . htmlspecialchars($row['faculty_name']) . "</option>";
}

echo $options;
?>
