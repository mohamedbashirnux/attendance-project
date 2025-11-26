<?php
session_start();

include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id']) && !empty($_POST['class_name']) && !empty($_POST['department_name']) && !empty($_POST['study_mode'])) {
        $id = $_POST['id'];
        $className = $_POST['class_name'];
        $departmentName = $_POST['department_name'];
        $studyMode = $_POST['study_mode'];

        // Start transaction
        $conn->beginTransaction();

        try {
            // Prepare and execute delete from classes
            $sql = "DELETE FROM classes WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Prepare and execute delete from allocate_teacher_subject
            $sql = "DELETE FROM allocate_teacher_subject WHERE c_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Prepare and execute delete from students
            $sql = "DELETE FROM students WHERE c_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Prepare and execute delete from absents
            $sql = "DELETE FROM absents WHERE class_name = :className AND department_name = :departmentName AND study_mode = :studyMode";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':className', $className, PDO::PARAM_STR);
            $stmt->bindParam(':departmentName', $departmentName, PDO::PARAM_STR);
            $stmt->bindParam(':studyMode', $studyMode, PDO::PARAM_STR);
            $stmt->execute();

            // Prepare and execute delete from subject_class
            $sql = "DELETE FROM subject_class WHERE class_name = :className AND department_name = :departmentName AND study_mode = :studyMode";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':className', $className, PDO::PARAM_STR);
            $stmt->bindParam(':departmentName', $departmentName, PDO::PARAM_STR);
            $stmt->bindParam(':studyMode', $studyMode, PDO::PARAM_STR);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }

        // Close connection
        $conn = null;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
