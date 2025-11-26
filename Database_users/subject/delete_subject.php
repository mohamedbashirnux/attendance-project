<?php
session_start();

include "../../connection/connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id']) && !empty($_POST['subject_name']) && !empty($_POST['department_name'])) {
        $id = $_POST['id'];
        $subject_name = $_POST['subject_name'];
        $department_name = $_POST['department_name'];

        try {
            $conn->beginTransaction();

            // Prepare and execute deletion from `subjects`
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = :id");
            if (!$stmt->execute([':id' => $id])) {
                throw new Exception('Failed to delete from subjects');
            }

            // Prepare and execute deletion from `allocate_teacher_subject`
            $stmt1 = $conn->prepare("DELETE FROM allocate_teacher_subject WHERE subject_name = :subject_name AND department_name = :department_name");
            if (!$stmt1->execute([':subject_name' => $subject_name, ':department_name' => $department_name])) {
                throw new Exception('Failed to delete from allocate_teacher_subject');
            }

            // Prepare and execute deletion from `subject_class`
            $stmt2 = $conn->prepare("DELETE FROM subject_class WHERE subject_name = :subject_name AND department_name = :department_name");
            if (!$stmt2->execute([':subject_name' => $subject_name, ':department_name' => $department_name])) {
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
