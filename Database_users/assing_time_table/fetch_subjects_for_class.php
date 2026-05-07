<?php
// Suppress PHP warnings
error_reporting(0);
ini_set('display_errors', 0);

// Set timezone
date_default_timezone_set('Africa/Mogadishu');

// Include session and database
include "../../Account_users/session_faculty.php";
include "../../connection/connect.php";

try {
    // Session is already checked by session_faculty.php
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        echo '<option value="">Session expired</option>';
        exit();
    }

    $faculty_id = $sessionInfo['faculty_id'];
    
    // Get parameters
    $class_id = $_GET['class_id'] ?? '';
    
    if (empty($class_id)) {
        echo '<option value="">No class selected</option>';
        exit();
    }
    
    // Get subjects allocated to this class with teacher information
    $sql = "SELECT DISTINCT s.id, s.subject_name, t.full_name as teacher_name, 
                   tsa.start_time, tsa.end_time, tsa.id as allocation_id
            FROM teacher_subject_allocation tsa
            JOIN subjects s ON tsa.subject_id = s.id
            JOIN teachers t ON tsa.teacher_id = t.id
            JOIN classes c ON tsa.class_id = c.id
            WHERE tsa.class_id = ? AND c.faculty_id = ?
            ORDER BY s.subject_name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$class_id, $faculty_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($subjects) > 0) {
        foreach ($subjects as $subject) {
            echo '<option value="' . htmlspecialchars($subject['subject_name']) . '" 
                         data-teacher="' . htmlspecialchars($subject['teacher_name']) . '"
                         data-start-time="' . htmlspecialchars($subject['start_time']) . '"
                         data-end-time="' . htmlspecialchars($subject['end_time']) . '"
                         data-allocation-id="' . $subject['allocation_id'] . '">' 
                  . htmlspecialchars($subject['subject_name']) . '</option>';
        }
    } else {
        echo '<option value="">No subjects allocated to this class</option>';
    }
    
} catch (Exception $e) {
    echo '<option value="">Error loading subjects</option>';
}
?>
