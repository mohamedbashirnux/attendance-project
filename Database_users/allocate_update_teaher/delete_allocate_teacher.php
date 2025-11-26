<?php
session_start();
include "../connection/connect.php";

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['tid']) || !isset($_POST['subject']) || !isset($_POST['class_name']) || !isset($_POST['study_mode'])) {
        header("Content-Type: application/json");
        echo json_encode(["error" => "Missing required parameters"]);
        exit();
    }

    $tid = htmlspecialchars($_POST['tid']);
    $subject = htmlspecialchars($_POST['subject']);
    $className = htmlspecialchars($_POST['class_name']);
    $studyMode = htmlspecialchars($_POST['study_mode']);



    try {
        $sql = "DELETE FROM allocate_teacher_subject WHERE tid = ? AND subject_name = ? AND class_name = ? AND study_mode = ?";
        $stmt = $conn->prepare($sql);
        
        // Execute with parameters in array
        $stmt->execute([$tid, $subject, $className, $studyMode]);

        header("Content-Type: application/json");
        echo json_encode(["success" => true]);

    } catch (PDOException $e) {
        header("Content-Type: application/json");
        echo json_encode(["error" => "Error deleting record: " . $e->getMessage()]);
    }

    // Close the connection
    $conn = null;
}
?>
