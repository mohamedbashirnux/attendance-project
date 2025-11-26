<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../../interval/Auth_user.php");
    exit();
}

include "../../connection/connect.php";

try {
    $subjectName = $_GET['subject_name'] ?? '';
    $departmentName = $_GET['department_name'] ?? '';
    $className = $_GET['class_name'] ?? '';
    $studyMode = $_GET['study_mode'] ?? '';
    $facultyName = $_GET['faculty_name'] ?? '';

    if (empty($subjectName) || empty($departmentName) || empty($className) || empty($studyMode) || empty($facultyName)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    // Fetch teacher allocation data from allocate_teacher_subject table
    $sql = "SELECT teacher_name, start_time, end_time 
            FROM allocate_teacher_subject 
            WHERE subject_name = :subject_name
              AND department_name = :department_name 
              AND class_name = :class_name 
              AND study_mode = :study_mode 
              AND faculty_name = :faculty_name
              AND status = 'approved'
            LIMIT 1";

    $stmt = $conn->prepare($sql);
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
                'teacher_name' => $allocation['teacher_name'],
                'start_time' => $allocation['start_time'],
                'end_time' => $allocation['end_time']
            ]
        ]);
    } else {
        // Try without status filter to see if that's the issue
        $sql2 = "SELECT teacher_name, start_time, end_time 
                FROM allocate_teacher_subject 
                WHERE subject_name = :subject_name
                  AND department_name = :department_name 
                  AND class_name = :class_name 
                  AND study_mode = :study_mode 
                  AND faculty_name = :faculty_name
                LIMIT 1";
        
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bindParam(':subject_name', $subjectName, PDO::PARAM_STR);
        $stmt2->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
        $stmt2->bindParam(':class_name', $className, PDO::PARAM_STR);
        $stmt2->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
        $stmt2->bindParam(':faculty_name', $facultyName, PDO::PARAM_STR);
        $stmt2->execute();
        
        $allocation2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($allocation2) {
            echo json_encode([
                'success' => true, 
                'data' => [
                    'teacher_name' => $allocation2['teacher_name'],
                    'start_time' => $allocation2['start_time'],
                    'end_time' => $allocation2['end_time']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No allocation found for this subject']);
        }
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn = null;
?>
