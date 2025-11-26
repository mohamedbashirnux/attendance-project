<?php
header('Content-Type: application/json');

$departmentName = $_POST['editDepartmentName'];
$facultyName = $_POST['editFacultyName'];
$originalDepartmentName = $_POST['originalDepartmentName'];

// Database connection

include "../../connection/connect.php";

// Begin transaction
$conn->beginTransaction();

try {
    // Update department in departments table
    $sql = "UPDATE departments SET department_name = ?, faculty_name = ? WHERE department_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$departmentName, $facultyName, $originalDepartmentName]);

    // Update department in students table
    $sqlUpdateStudents = "UPDATE students SET department_name = ? WHERE department_name = ?";
    $stmtUpdateStudents = $conn->prepare($sqlUpdateStudents);
    $stmtUpdateStudents->execute([$departmentName, $originalDepartmentName]);

    // Update department in allocate_teacher_subject table
    $sqlUpdateAllocate = "UPDATE allocate_teacher_subject SET department_name = ? WHERE department_name = ?";
    $stmtUpdateAllocate = $conn->prepare($sqlUpdateAllocate);
    $stmtUpdateAllocate->execute([$departmentName, $originalDepartmentName]);

    // Update department in subjects table
    $sqlUpdateSubjects = "UPDATE subjects SET department_name = ? WHERE department_name = ?";
    $stmtUpdateSubjects = $conn->prepare($sqlUpdateSubjects);
    $stmtUpdateSubjects->execute([$departmentName, $originalDepartmentName]);

    // Update department in subject_class table
    $sqlUpdateSubjectClass = "UPDATE subject_class SET department_name = ? WHERE department_name = ?";
    $stmtUpdateSubjectClass = $conn->prepare($sqlUpdateSubjectClass);
    $stmtUpdateSubjectClass->execute([$departmentName, $originalDepartmentName]);

    // Update department in absents table
    $sqlUpdateAbsents = "UPDATE absents SET department_name = ? WHERE department_name = ?";
    $stmtUpdateAbsents = $conn->prepare($sqlUpdateAbsents);
    $stmtUpdateAbsents->execute([$departmentName, $originalDepartmentName]);

    // Commit transaction
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Department updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// $conn->close();
?>
