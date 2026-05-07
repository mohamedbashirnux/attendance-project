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

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
if (!$department_id) { echo json_encode(['classes' => []]); exit(); }

try {
    $stmt = $conn->prepare("SELECT id, class_name, study_mode, semester, academic_year FROM classes WHERE department_id = ? ORDER BY class_name");
    $stmt->execute([$department_id]);
    echo json_encode(['classes' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage(), 'classes' => []]);
}
ob_end_flush();
?>
