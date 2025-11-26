<?php
session_start();
include "../../connection/connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['subject_name']) && !empty($_POST['department_name']) && !empty($_POST['id']) && !empty($_POST['faculty_name']) && !empty($_POST['original_subject_name'])) {
        $newSubjectName = $_POST['subject_name'];
        $departmentName = $_POST['department_name'];
        $id = $_POST['id'];
        $facultyName = $_POST['faculty_name'];
        $originalSubjectName = $_POST['original_subject_name'];

        try {
            $conn->beginTransaction();

            // Check if the new subject name already exists in the 'subjects' table
            $stmt_check = $conn->prepare("SELECT id FROM subjects WHERE subject_name = :subject_name AND department_name = :department_name AND faculty_name = :faculty_name AND id != :id");
            $stmt_check->execute([
                ':subject_name' => $newSubjectName,
                ':department_name' => $departmentName,
                ':faculty_name' => $facultyName,
                ':id' => $id
            ]);

            if ($stmt_check->rowCount() > 0) {
                echo json_encode(['status' => 'exists']);
                exit; // Stop further execution
            }

            // Update the 'subjects' table
            $stmt = $conn->prepare("UPDATE subjects SET subject_name = :subject_name, department_name = :department_name, faculty_name = :faculty_name WHERE id = :id");
            if ($stmt->execute([
                ':subject_name' => $newSubjectName,
                ':department_name' => $departmentName,
                ':faculty_name' => $facultyName,
                ':id' => $id
            ])) {
                // Update the 'subject_class' table
                $stmt_update_subject_class = $conn->prepare("UPDATE subject_class SET subject_name = :subject_name WHERE subject_name = :original_subject_name AND department_name = :department_name AND faculty_name = :faculty_name");
                $stmt_update_subject_class->execute([
                    ':subject_name' => $newSubjectName,
                    ':original_subject_name' => $originalSubjectName,
                    ':department_name' => $departmentName,
                    ':faculty_name' => $facultyName
                ]);

                // Update the 'allocate_teacher_subject' table
                $stmt_update_allocate_teacher = $conn->prepare("UPDATE allocate_teacher_subject SET subject_name = :subject_name WHERE subject_name = :original_subject_name AND department_name = :department_name AND faculty_name = :faculty_name");
                if ($stmt_update_allocate_teacher->execute([
                    ':subject_name' => $newSubjectName,
                    ':original_subject_name' => $originalSubjectName,
                    ':department_name' => $departmentName,
                    ':faculty_name' => $facultyName
                ])) {
                    $conn->commit();
                    echo json_encode(['status' => 'success']);
                } else {
                    // Rollback and show error if updating allocate_teacher_subject fails
                    $conn->rollBack();
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update allocate_teacher_subject.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update subject.']);
            }
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Error occurred: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
