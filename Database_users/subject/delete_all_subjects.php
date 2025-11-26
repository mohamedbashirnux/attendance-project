<?php
session_start();

include "../../connection/connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = isset($_POST['class_name']) ? $_POST['class_name'] : '';
    $departmentName = isset($_POST['department_name']) ? $_POST['department_name'] : '';
    $studyMode = isset($_POST['study_mode']) ? $_POST['study_mode'] : '';
    $facultyName = isset($_POST['faculty_name']) ? $_POST['faculty_name'] : '';

    if (!empty($className) && !empty($departmentName) && !empty($studyMode) && !empty($facultyName)) {
        try {
            $conn->beginTransaction();

            // SQL query to delete all subjects matching the criteria from `subject_class`
            $sqlSubjectClass = "DELETE FROM subject_class WHERE class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode AND faculty_name = :faculty_name";
            $stmtSubjectClass = $conn->prepare($sqlSubjectClass);
            if (!$stmtSubjectClass->execute([
                ':class_name' => $className,
                ':department_name' => $departmentName,
                ':study_mode' => $studyMode,
                ':faculty_name' => $facultyName
            ])) {
                throw new Exception('Failed to delete from subject_class');
            }

            // SQL query to delete matching records from `allocate_teacher_subject`
            $sqlAllocateTeacher = "DELETE FROM allocate_teacher_subject WHERE class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode AND faculty_name = :faculty_name";
            $stmtAllocateTeacher = $conn->prepare($sqlAllocateTeacher);
            if (!$stmtAllocateTeacher->execute([
                ':class_name' => $className,
                ':department_name' => $departmentName,
                ':study_mode' => $studyMode,
                ':faculty_name' => $facultyName
            ])) {
                throw new Exception('Failed to delete from allocate_teacher_subject');
            }

            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
