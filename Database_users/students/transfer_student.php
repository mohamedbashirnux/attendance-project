<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

try {
    // Get POST data
    $student_id = $_POST['student_id'] ?? '';
    $new_class_id = $_POST['new_class_id'] ?? '';
    
    // Validate inputs
    if (empty($student_id) || empty($new_class_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Get current student info
    $check_sql = "SELECT s.id, s.student_id, s.full_name, s.class_id as old_class_id, 
                         c.class_name as old_class_name
                  FROM students s
                  JOIN classes c ON s.class_id = c.id
                  WHERE s.id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$student_id]);
    $student = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }
    
    // Check if new class exists
    $class_check_sql = "SELECT class_name, study_mode FROM classes WHERE id = ?";
    $class_check_stmt = $conn->prepare($class_check_sql);
    $class_check_stmt->execute([$new_class_id]);
    $new_class = $class_check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$new_class) {
        echo json_encode(['success' => false, 'message' => 'New class not found']);
        exit();
    }
    
    // Check if student is already in this class
    if ($student['old_class_id'] == $new_class_id) {
        echo json_encode(['success' => false, 'message' => 'Student is already in this class']);
        exit();
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Update student's class_id
    $update_sql = "UPDATE students SET class_id = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$new_class_id, $student_id]);
    
    // Update all absences records to new class_id
    $update_absences_sql = "UPDATE absences SET class_id = ? WHERE student_id = ?";
    $update_absences_stmt = $conn->prepare($update_absences_sql);
    $update_absences_stmt->execute([$new_class_id, $student['id']]);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Student transferred successfully from ' . $student['old_class_name'] . ' to ' . $new_class['class_name']
    ]);
    
} catch (PDOException $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
