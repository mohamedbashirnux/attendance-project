<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

try {
    $faculty_id = $_GET['faculty_id'] ?? '';
    $department_id = $_GET['department_id'] ?? '';
    
    if (empty($faculty_id) || empty($department_id)) {
        echo json_encode(['success' => false, 'message' => 'Faculty ID and Department ID required']);
        exit();
    }
    
    // Fetch classes for the selected faculty and department
    $sql = "SELECT id, class_name, study_mode, semester, academic_year 
            FROM classes 
            WHERE faculty_id = ? AND department_id = ? 
            ORDER BY class_name ASC, study_mode ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$faculty_id, $department_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
