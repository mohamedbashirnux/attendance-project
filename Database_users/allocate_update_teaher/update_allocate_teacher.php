<?php
session_start();

// Database connection details
include "../../connection/connect.php";

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

// Get POST data
$tid = $_POST['tid'] ?? '';
$teacherName = $_POST['teacherName'] ?? '';
$departmentName = $_POST['departmentName'] ?? '';
$className = $_POST['className'] ?? '';
$studyMode = $_POST['studyMode'] ?? '';
$subjectName = $_POST['subjectName'] ?? '';
$facultyName = $_POST['facultyName'] ?? '';

// Validate POST data (example validation)
if (empty($tid) || empty($teacherName) || empty($departmentName) || empty($className) || empty($studyMode) || empty($subjectName) || empty($facultyName)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

try {
    // Prepare and bind SQL statement
    $sql = "UPDATE allocate_teacher_subject 
            SET department_name = :departmentName, 
                class_name = :className, 
                study_mode = :studyMode, 
                subject_name = :subjectName, 
                faculty_name = :facultyName 
            WHERE tid = :tid AND teacher_name = :teacherName";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':departmentName', $departmentName, PDO::PARAM_STR);
    $stmt->bindParam(':className', $className, PDO::PARAM_STR);
    $stmt->bindParam(':studyMode', $studyMode, PDO::PARAM_STR);
    $stmt->bindParam(':subjectName', $subjectName, PDO::PARAM_STR);
    $stmt->bindParam(':facultyName', $facultyName, PDO::PARAM_STR);
    $stmt->bindParam(':tid', $tid, PDO::PARAM_STR);
    $stmt->bindParam(':teacherName', $teacherName, PDO::PARAM_STR);

    // Execute SQL statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating record.']);
    }

    // Close connection
    $conn = null;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
