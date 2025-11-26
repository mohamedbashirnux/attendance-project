<?php
session_start();
include "../../connection/connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['department_name']) && !empty($_POST['faculty_name'])) {
        $department_name = $_POST['department_name'];
        $faculty_name = $_POST['faculty_name'];

        try {
            $conn->beginTransaction();

            // Delete from subjects table
            $stmt = $conn->prepare("DELETE FROM subjects WHERE department_name = :department_name AND faculty_name = :faculty_name");
            if (!$stmt->execute([':department_name' => $department_name, ':faculty_name' => $faculty_name])) {
                throw new Exception('Failed to delete from subjects');
            }

            // Delete from allocate_teacher_subject table
            $stmt1 = $conn->prepare("DELETE FROM allocate_teacher_subject WHERE department_name = :department_name");
            if (!$stmt1->execute([':department_name' => $department_name])) {
                throw new Exception('Failed to delete from allocate_teacher_subject');
            }

            // Delete from subject_class table
            $stmt2 = $conn->prepare("DELETE FROM subject_class WHERE department_name = :department_name");
            if (!$stmt2->execute([':department_name' => $department_name])) {
                throw new Exception('Failed to delete from subject_class');
            }
            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>