<?php
error_reporting(0); ini_set('display_errors', 0);
ob_start();
session_start();
include "../../../connection/connect.php";
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['super_admin_logged_in']) || $_SESSION['super_admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
}

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']); exit();
}

try {
    $stmt = $conn->prepare("
        SELECT DISTINCT s.id, s.subject_name
        FROM subjects s
        JOIN subject_class sc ON s.id = sc.subject_id
        WHERE sc.class_id = ?
        ORDER BY s.subject_name
    ");
    $stmt->execute([$class_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'count' => count($subjects)
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
ob_end_flush();
?>
