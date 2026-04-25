<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

try {
    $faculty_id = $_GET['faculty_id'] ?? '';
    
    if (empty($faculty_id)) {
        echo json_encode(['success' => false, 'message' => 'Faculty ID required']);
        exit();
    }
    
    // Fetch departments for the selected faculty
    $sql = "SELECT id, department_name FROM departments WHERE faculty_id = ? ORDER BY department_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$faculty_id]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'departments' => $departments
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
