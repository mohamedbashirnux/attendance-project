<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    include "../../connection/connect.php";

    // Retrieve form data
    $departmentName = isset($_POST['departmentName']) ? $_POST['departmentName'] : '';
    $className = isset($_POST['className']) ? $_POST['className'] : '';
    $studyMode = isset($_POST['studyMode']) ? $_POST['studyMode'] : '';
    $faculty = isset($_POST['faculty']) ? $_POST['faculty'] : '';
    $selectedSubjects = isset($_POST['selectedSubjects']) ? $_POST['selectedSubjects'] : '';

    if (empty($departmentName) || empty($className) || empty($studyMode) || empty($faculty) || empty($selectedSubjects)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit();
    }

    // Convert selected subjects to an array
    $subjectsArray = explode(', ', $selectedSubjects);

    try {
        $conn->beginTransaction();

        // Prepare SQL statements
        $checkStmt = $conn->prepare("SELECT * FROM subject_class WHERE subject_name = :subject_name AND class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode AND faculty_name = :faculty_name");
        $insertStmt = $conn->prepare("INSERT INTO subject_class (subject_name, class_name, department_name, study_mode, faculty_name) VALUES (:subject_name, :class_name, :department_name, :study_mode, :faculty_name)");

        foreach ($subjectsArray as $subject) {
            // Check if the subject already exists
            $checkStmt->execute([
                ':subject_name' => $subject,
                ':class_name' => $className,
                ':department_name' => $departmentName,
                ':study_mode' => $studyMode,
                ':faculty_name' => $faculty
            ]);

            if ($checkStmt->rowCount() > 0) {
                // If the subject already exists, return an error message
                echo json_encode(['status' => 'error', 'message' => 'Subject "' . $subject . '" already exists for the specified class and department.']);
                $conn->rollBack();
                exit();
            } else {
                // Insert the subject if it does not exist
                if (!$insertStmt->execute([
                    ':subject_name' => $subject,
                    ':class_name' => $className,
                    ':department_name' => $departmentName,
                    ':study_mode' => $studyMode,
                    ':faculty_name' => $faculty
                ])) {
                    echo json_encode(['status' => 'error', 'message' => 'Error inserting subject: ' . implode(', ', $insertStmt->errorInfo())]);
                    $conn->rollBack();
                    exit();
                }
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Subjects inserted successfully.']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
