<?php
error_reporting(0); ini_set('display_errors', 0);
ob_start();
session_start();
include "../../../connection/connect.php";
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['super_admin_logged_in']) || $_SESSION['super_admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']); exit();
}

$faculty_id = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;
if (!$faculty_id) { echo json_encode(['departments' => []]); exit(); }

try {
    $stmt = $conn->prepare("SELECT id, department_name FROM departments WHERE faculty_id = ? ORDER BY department_name");
    $stmt->execute([$faculty_id]);
    echo json_encode(['departments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage(), 'departments' => []]);
}
ob_end_flush();
?>
