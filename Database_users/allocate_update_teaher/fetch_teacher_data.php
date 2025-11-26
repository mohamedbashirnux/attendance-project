<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['tid']) || !isset($_POST['subject']) || !isset($_POST['className']) || !isset($_POST['studyMode'])) {
        echo json_encode(["error" => "Missing required parameters"]);
        exit();
    }

    $tid = htmlspecialchars($_POST['tid']);
    $subject = htmlspecialchars($_POST['subject']);
    $className = htmlspecialchars($_POST['className']);
    $studyMode = htmlspecialchars($_POST['studyMode']);

    include "../../connection/connect.php";

    try {
        $sql = "SELECT tid, teacher_name, department_name, class_name, study_mode, subject_name, faculty_name 
                FROM allocate_teacher_subject 
                WHERE tid = :tid AND subject_name = :subject_name AND class_name = :class_name AND study_mode = :study_mode";
        $stmt = $conn->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':tid', $tid, PDO::PARAM_STR);
        $stmt->bindParam(':subject_name', $subject, PDO::PARAM_STR);
        $stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
        $stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode($row);
        } else {
            echo json_encode(["error" => "No data found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error: " . htmlspecialchars($e->getMessage())]);
    }

    $conn = null; // Close the connection
}
?>
