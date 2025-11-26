<?php
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Database connection details
include "../../connection/connect.php";

// Check if POST data is received
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $newSubject = isset($_POST['new_subject']) ? trim($_POST['new_subject']) : '';
    $departmentName = isset($_POST['department_name']) ? trim($_POST['department_name']) : '';
    $facultyName = isset($_POST['faculty_name']) ? trim($_POST['faculty_name']) : '';

    // Validate input data
    if (empty($newSubject) || empty($departmentName) || empty($facultyName)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }

    try {
        $conn->beginTransaction();

        // Check if the subject already exists for the specified class and department
        $checkSql = "SELECT * FROM subjects WHERE subject_name = :subject_name AND department_name = :department_name";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([':subject_name' => $newSubject, ':department_name' => $departmentName]);

        if ($checkStmt->rowCount() > 0) {
            // Subject already exists for this class and department
            echo json_encode(['success' => false, 'error' => 'Subject already exists for this class and department']);
            $conn->rollBack();
            exit();
        }

        // Insert the new subject
        $insertSql = "INSERT INTO subjects (subject_name, department_name, faculty_name) VALUES (:subject_name, :department_name, :faculty_name)";
        $insertStmt = $conn->prepare($insertSql);

        if ($insertStmt->execute([
            ':subject_name' => $newSubject,
            ':department_name' => $departmentName,
            ':faculty_name' => $facultyName
        ])) {
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to insert subject.");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
