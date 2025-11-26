<?php
// Include the database connection
include "../../connection/connect.php";

// Get POST data
$student_id = $_POST['student_id'];
$subject_name = $_POST['subject_name'];
$class_name = $_POST['class_name'];
$absent_date = $_POST['absent_date'];
$excuses = $_POST['excuses'];

try {
    // Prepare the update statement
    $stmt = $conn->prepare("
        UPDATE absents 
        SET excuses = :excuses 
        WHERE student_id = :student_id 
        AND subject_name = :subject_name 
        AND class_name = :class_name 
        AND absent_date = :absent_date
    ");

    // Bind parameters
    $stmt->bindParam(':excuses', $excuses, PDO::PARAM_STR);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
    $stmt->bindParam(':subject_name', $subject_name, PDO::PARAM_STR);
    $stmt->bindParam(':class_name', $class_name, PDO::PARAM_STR);
    $stmt->bindParam(':absent_date', $absent_date, PDO::PARAM_STR);

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Excuses updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update excuses']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>