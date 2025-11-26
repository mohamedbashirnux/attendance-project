<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../../interval/Auth_user.php");
    exit();
}

include "../../connection/connect.php";

try {
    $departmentName = $_GET['department_name'] ?? '';
    $className = $_GET['class_name'] ?? '';
    $studyMode = $_GET['study_mode'] ?? '';
    $facultyName = $_GET['faculty_name'] ?? '';

    if (empty($departmentName) || empty($className) || empty($studyMode) || empty($facultyName)) {
        echo '<option value="">Invalid parameters</option>';
        exit();
    }

    // Fetch teachers for the specific class from allocate_teacher_subject table
    $sql = "SELECT DISTINCT teacher_name 
            FROM allocate_teacher_subject 
            WHERE department_name = :department_name 
              AND class_name = :class_name 
              AND study_mode = :study_mode 
              AND faculty_name = :faculty_name
              AND status = 'approved'
            ORDER BY teacher_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
    $stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
    $stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
    $stmt->bindParam(':faculty_name', $facultyName, PDO::PARAM_STR);
    $stmt->execute();

    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($teachers)) {
        foreach ($teachers as $teacher) {
            echo '<option value="' . htmlspecialchars($teacher['teacher_name']) . '">' . htmlspecialchars($teacher['teacher_name']) . '</option>';
        }
    } else {
        echo '<option value="">No teachers found for this class</option>';
    }

} catch (PDOException $e) {
    echo '<option value="">Error loading teachers</option>';
} catch (Exception $e) {
    echo '<option value="">Error loading teachers</option>';
}

$conn = null;
?>
