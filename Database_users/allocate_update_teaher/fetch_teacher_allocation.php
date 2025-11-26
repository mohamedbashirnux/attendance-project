<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../../interval/Auth_user.php");
    exit();
}

include "../../connection/connect.php";

try {
    $teacherName = $_GET['teacher_name'] ?? '';
    $subjectName = $_GET['subject_name'] ?? '';
    $departmentName = $_GET['department_name'] ?? '';
    $className = $_GET['class_name'] ?? '';
    $studyMode = $_GET['study_mode'] ?? '';
    $facultyName = $_GET['faculty_name'] ?? '';

    if (empty($teacherName) || empty($subjectName) || empty($departmentName) || empty($className) || empty($studyMode) || empty($facultyName)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    // Fetch teacher allocation data from allocate_teacher_subject table
    $sql = "SELECT start_time, end_time 
            FROM allocate_teacher_subject 
            WHERE teacher_name = :teacher_name 
              AND subject_name = :subject_name
              AND department_name = :department_name 
              AND class_name = :class_name 
              AND study_mode = :study_mode 
              AND faculty_name = :faculty_name
              AND status = 'approved'
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':teacher_name', $teacherName, PDO::PARAM_STR);
    $stmt->bindParam(':subject_name', $subjectName, PDO::PARAM_STR);
    $stmt->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
    $stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
    $stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
    $stmt->bindParam(':faculty_name', $facultyName, PDO::PARAM_STR);
    $stmt->execute();

    $allocation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($allocation) {
        echo json_encode([
            'success' => true, 
            'data' => [
                'start_time' => $allocation['start_time'],
                'end_time' => $allocation['end_time']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No allocation found for this teacher and subject']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn = null;
?>
