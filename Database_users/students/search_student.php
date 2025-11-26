<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

include "../../connection/connect.php";

try {
   

    if (isset($_GET['student_id'])) {
        $student_id = filter_var($_GET['student_id'], FILTER_SANITIZE_STRING);

        $query = "
            SELECT s.student_id, s.student_name, d.department_name, c.class_name, c.study_mode 
            FROM students s
            JOIN departments d ON s.department_id = d.department_id
            JOIN classes c ON s.class_id = c.class_id
            WHERE s.student_id = :student_id
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'student' => $student]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No student found with the given ID.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Student ID not provided.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
