<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

include "../../connection/connect.php";

try {
   

    $faculty = $_SESSION['faculty'];

    $sql = "SELECT student_id, student_name, department_name, class_name, study_mode, faculty_name FROM students WHERE faculty_name = :faculty";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':faculty', $faculty, PDO::PARAM_STR);
    $stmt->execute();

    $students = [];
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $students[] = [
                'student_id' => $row['student_id'],
                'student_name' => $row['student_name'],
                'department_name' => $row['department_name'],
                'class_name' => $row['class_name'],
                'study_mode' => $row['study_mode'],
                'faculty_name' => $row['faculty_name']
            ];
        }
    } else {
        $students = null;
    }

    echo json_encode(['students' => $students, 'message' => ($students ? '' : 'No students found.')]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
