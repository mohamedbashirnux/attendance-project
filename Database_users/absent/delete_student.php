<?php
header('Content-Type: application/json');
include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $subject_name = $_POST['subject_name'];
    $class_name = $_POST['class_name'];
    $absent_date = $_POST['absent_date'];

    try {
        // Start a transaction
        $conn->beginTransaction();

        // Delete from absents table
        $stmt1 = $conn->prepare("
            DELETE FROM absents 
            WHERE student_id = :student_id 
            AND subject_name = :subject_name 
            AND class_name = :class_name 
            AND absent_date = :absent_date
        ");
        $stmt1->bindParam(':student_id', $student_id);
        $stmt1->bindParam(':subject_name', $subject_name);
        $stmt1->bindParam(':class_name', $class_name);
        $stmt1->bindParam(':absent_date', $absent_date);
        $stmt1->execute();

        // Delete from back_ups table
        $stmt2 = $conn->prepare("
            DELETE FROM back_ups 
            WHERE student_id = :student_id 
            AND subject_name = :subject_name 
            AND class_name = :class_name 
            AND absent_date = :absent_date
        ");
        $stmt2->bindParam(':student_id', $student_id);
        $stmt2->bindParam(':subject_name', $subject_name);
        $stmt2->bindParam(':class_name', $class_name);
        $stmt2->bindParam(':absent_date', $absent_date);
        $stmt2->execute();

        // Commit the transaction
        $conn->commit();

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        // Rollback the transaction if an error occurs
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}