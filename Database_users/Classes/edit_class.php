<?php
session_start(); // Ensure session is started to use session variables

include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $originalDepartmentName = $_POST['originalDepartmentName'];
    $originalClassName = $_POST['originalClassName'];
    $originalStudyMode = $_POST['originalStudyMode'];

    $departmentName = $_POST['departmentName'];
    $className = $_POST['className'];
    $studyMode = $_POST['studyMode'];
    $semester = $_POST['semester'];
    $academic = $_POST['academicYear'];

    // Check if any required field is empty
    if (empty($originalDepartmentName) || empty($originalClassName) || empty($originalStudyMode) || 
        empty($departmentName) || empty($className) || empty($studyMode) || empty($semester) || empty($academic)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
        exit;
    }

    try {
        // Check if the edited class already exists
        $stmtCheckExistence = $conn->prepare("SELECT * FROM classes WHERE department_name = :departmentName AND class_name = :className AND study_mode = :studyMode");
        $stmtCheckExistence->bindParam(':departmentName', $departmentName);
        $stmtCheckExistence->bindParam(':className', $className);
        $stmtCheckExistence->bindParam(':studyMode', $studyMode);
        $stmtCheckExistence->execute();

        if ($stmtCheckExistence->rowCount() > 0 && (
            $originalDepartmentName !== $departmentName || 
            $originalClassName !== $className || 
            $originalStudyMode !== $studyMode)) {
            echo json_encode(['status' => 'warning', 'message' => 'Class already exists']);
            exit;
        }

        $conn->beginTransaction(); // Start a transaction

        // Update class in `classes` table
        $updateClassQuery = "UPDATE classes SET department_name = :departmentName, class_name = :className, study_mode = :studyMode, semester = :semester, academic = :academic
                            WHERE department_name = :originalDepartmentName AND class_name = :originalClassName 
                            AND study_mode = :originalStudyMode";
        $stmtUpdateClass = $conn->prepare($updateClassQuery);
        $stmtUpdateClass->bindParam(':departmentName', $departmentName);
        $stmtUpdateClass->bindParam(':className', $className);
        $stmtUpdateClass->bindParam(':studyMode', $studyMode);
        $stmtUpdateClass->bindParam(':semester', $semester);
        $stmtUpdateClass->bindParam(':academic', $academic);
        $stmtUpdateClass->bindParam(':originalDepartmentName', $originalDepartmentName);
        $stmtUpdateClass->bindParam(':originalClassName', $originalClassName);
        $stmtUpdateClass->bindParam(':originalStudyMode', $originalStudyMode);

        if (!$stmtUpdateClass->execute()) {
            throw new Exception("Failed to update class: " . implode(", ", $stmtUpdateClass->errorInfo()));
        }

        // Update `students` table
        $updateStudentsQuery = "UPDATE students SET department_name = :departmentName, class_name = :className, study_mode = :studyMode, semester = :semester, academic = :academic
                                WHERE department_name = :originalDepartmentName AND class_name = :originalClassName 
                                AND study_mode = :originalStudyMode AND faculty_name = :facultyName";
        $stmtUpdateStudents = $conn->prepare($updateStudentsQuery);
        $stmtUpdateStudents->bindParam(':departmentName', $departmentName);
        $stmtUpdateStudents->bindParam(':className', $className);
        $stmtUpdateStudents->bindParam(':studyMode', $studyMode);
        $stmtUpdateStudents->bindParam(':semester', $semester);
        $stmtUpdateStudents->bindParam(':academic', $academic);
        $stmtUpdateStudents->bindParam(':originalDepartmentName', $originalDepartmentName);
        $stmtUpdateStudents->bindParam(':originalClassName', $originalClassName);
        $stmtUpdateStudents->bindParam(':originalStudyMode', $originalStudyMode);
        $stmtUpdateStudents->bindParam(':facultyName', $_SESSION['faculty']);

        if (!$stmtUpdateStudents->execute()) {
            throw new Exception("Failed to update students: " . implode(", ", $stmtUpdateStudents->errorInfo()));
        }

        // Update `allocate_teacher_subject` table
        $updateAllocateQuery = "UPDATE allocate_teacher_subject SET department_name = :departmentName, class_name = :className, study_mode = :studyMode 
                                WHERE department_name = :originalDepartmentName AND class_name = :originalClassName 
                                AND study_mode = :originalStudyMode AND faculty_name = :facultyName";
        $stmtUpdateAllocate = $conn->prepare($updateAllocateQuery);
        $stmtUpdateAllocate->bindParam(':departmentName', $departmentName);
        $stmtUpdateAllocate->bindParam(':className', $className);
        $stmtUpdateAllocate->bindParam(':studyMode', $studyMode);
        $stmtUpdateAllocate->bindParam(':originalDepartmentName', $originalDepartmentName);
        $stmtUpdateAllocate->bindParam(':originalClassName', $originalClassName);
        $stmtUpdateAllocate->bindParam(':originalStudyMode', $originalStudyMode);
        $stmtUpdateAllocate->bindParam(':facultyName', $_SESSION['faculty']);

        if (!$stmtUpdateAllocate->execute()) {
            throw new Exception("Failed to update allocate_teacher_subject: " . implode(", ", $stmtUpdateAllocate->errorInfo()));
        }
                // Update `submit_session` table
        $updateSubmitSessionQuery = "UPDATE submit_session SET department_name = :departmentName, class_name = :className, study_mode = :studyMode, semester = :semester, academic = :academic
                                     WHERE department_name = :originalDepartmentName AND class_name = :originalClassName 
                                     AND study_mode = :originalStudyMode";
        $stmtUpdateSubmitSession = $conn->prepare($updateSubmitSessionQuery);
        $stmtUpdateSubmitSession->bindParam(':departmentName', $departmentName);
        $stmtUpdateSubmitSession->bindParam(':className', $className);
        $stmtUpdateSubmitSession->bindParam(':studyMode', $studyMode);
        $stmtUpdateSubmitSession->bindParam(':semester', $semester);
        $stmtUpdateSubmitSession->bindParam(':academic', $academic);
        $stmtUpdateSubmitSession->bindParam(':originalDepartmentName', $originalDepartmentName);
        $stmtUpdateSubmitSession->bindParam(':originalClassName', $originalClassName);
        $stmtUpdateSubmitSession->bindParam(':originalStudyMode', $originalStudyMode);

        if (!$stmtUpdateSubmitSession->execute()) {
            throw new Exception("Failed to update submit_session: " . implode(", ", $stmtUpdateSubmitSession->errorInfo()));
        }

        // Update `absents` table (with same columns as students)
        $updateAbsentsQuery = "UPDATE absents SET department_name = :departmentName, class_name = :className, study_mode = :studyMode, semester = :semester, academic = :academic
                               WHERE department_name = :originalDepartmentName AND class_name = :originalClassName 
                               AND study_mode = :originalStudyMode";
        $stmtUpdateAbsents = $conn->prepare($updateAbsentsQuery);
        $stmtUpdateAbsents->bindParam(':departmentName', $departmentName);
        $stmtUpdateAbsents->bindParam(':className', $className);
        $stmtUpdateAbsents->bindParam(':studyMode', $studyMode);
        $stmtUpdateAbsents->bindParam(':semester', $semester);
        $stmtUpdateAbsents->bindParam(':academic', $academic);
        $stmtUpdateAbsents->bindParam(':originalDepartmentName', $originalDepartmentName);
        $stmtUpdateAbsents->bindParam(':originalClassName', $originalClassName);
        $stmtUpdateAbsents->bindParam(':originalStudyMode', $originalStudyMode);

        if (!$stmtUpdateAbsents->execute()) {
            throw new Exception("Failed to update absents: " . implode(", ", $stmtUpdateAbsents->errorInfo()));
        }

        // Update `subject_class` table
        $updateSubjectClassQuery = "UPDATE subject_class SET department_name = :departmentName, class_name = :className, study_mode = :studyMode 
                                    WHERE department_name = :originalDepartmentName AND class_name = :originalClassName 
                                    AND study_mode = :originalStudyMode AND faculty_name = :facultyName";
        $stmtUpdateSubjectClass = $conn->prepare($updateSubjectClassQuery);
        $stmtUpdateSubjectClass->bindParam(':departmentName', $departmentName);
        $stmtUpdateSubjectClass->bindParam(':className', $className);
        $stmtUpdateSubjectClass->bindParam(':studyMode', $studyMode);
        $stmtUpdateSubjectClass->bindParam(':originalDepartmentName', $originalDepartmentName);
        $stmtUpdateSubjectClass->bindParam(':originalClassName', $originalClassName);
        $stmtUpdateSubjectClass->bindParam(':originalStudyMode', $originalStudyMode);
        $stmtUpdateSubjectClass->bindParam(':facultyName', $_SESSION['faculty']);

        if (!$stmtUpdateSubjectClass->execute()) {
            throw new Exception("Failed to update subject_class: " . implode(", ", $stmtUpdateSubjectClass->errorInfo()));
        }

        $conn->commit(); // Commit transaction
        echo json_encode(['status' => 'success', 'message' => 'All records updated successfully']);
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback transaction if any error occurs
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $conn = null;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
