<?php
session_start();
header('Content-Type: application/json'); // Ensure the correct content type is set

include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = $_POST['tid'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $departmentName = $_POST['department_name'] ?? '';
    $className = $_POST['class_name'] ?? '';
    $studyMode = $_POST['study_mode'] ?? '';
    $subjectName = $_POST['subject_name'] ?? '';

    // Validate POST data (basic validation)
    if (empty($tid) || empty($startTime) || empty($endTime) || empty($departmentName) || empty($className) || empty($studyMode) || empty($subjectName)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit();
    }

    try {
        // Prepare and bind SQL statement
        $sql = "UPDATE allocate_teacher_subject 
                SET start_time = :startTime, end_time = :endTime 
                WHERE department_name = :departmentName 
                AND class_name = :className 
                AND study_mode = :studyMode 
                AND subject_name = :subjectName 
                AND tid = :tid";
                
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':startTime', $startTime, PDO::PARAM_STR);
        $stmt->bindParam(':endTime', $endTime, PDO::PARAM_STR);
        $stmt->bindParam(':departmentName', $departmentName, PDO::PARAM_STR);
        $stmt->bindParam(':className', $className, PDO::PARAM_STR);
        $stmt->bindParam(':studyMode', $studyMode, PDO::PARAM_STR);
        $stmt->bindParam(':subjectName', $subjectName, PDO::PARAM_STR);
        $stmt->bindParam(':tid', $tid, PDO::PARAM_STR);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No rows affected or data is the same.']);
        }

        $stmt->closeCursor(); // Close the cursor to free up database resources
        $conn = null; // Close the connection
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
