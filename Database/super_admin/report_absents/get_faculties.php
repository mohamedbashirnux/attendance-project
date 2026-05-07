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

try {
    $stmt = $conn->query("SELECT id, faculty_name FROM faculty ORDER BY faculty_name");
    echo json_encode(['faculties' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage(), 'faculties' => []]);
}
ob_end_flush();
?>
