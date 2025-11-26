<?php
session_start();

include "../../connection/connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id']) && !empty($_POST['subject_name']) && !empty($_POST['department_name']) && !empty($_POST['class_name']) && !empty($_POST['study_mode'])) {
        $id = $_POST['id'];
        $subject_name = $_POST['subject_name'];
        $department_name = $_POST['department_name'];
        $class_name = $_POST['class_name'];
        $study_mode = $_POST['study_mode'];

        try {
            $conn->beginTransaction();

            // Delete from `subject_class`
            $sqlSubjectClass = "DELETE FROM subject_class WHERE id = :id AND subject_name = :subject_name AND department_name = :department_name AND class_name = :class_name AND study_mode = :study_mode";
            $stmtSubjectClass = $conn->prepare($sqlSubjectClass);
            if (!$stmtSubjectClass->execute([
                ':id' => $id,
                ':subject_name' => $subject_name,
                ':department_name' => $department_name,
                ':class_name' => $class_name,
                ':study_mode' => $study_mode
            ])) {
                throw new Exception('Failed to delete from subject_class');
            }

            // Delete from `allocate_teacher_subject`
            $sqlAllocateTeacher = "DELETE FROM allocate_teacher_subject WHERE subject_name = :subject_name AND department_name = :department_name AND class_name = :class_name AND study_mode = :study_mode";
            $stmtAllocateTeacher = $conn->prepare($sqlAllocateTeacher);
            if (!$stmtAllocateTeacher->execute([
                ':subject_name' => $subject_name,
                ':department_name' => $department_name,
                ':class_name' => $class_name,
                ':study_mode' => $study_mode
            ])) {
                throw new Exception('Failed to delete from allocate_teacher_subject');
            }

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Subject deleted successfully.']);
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
