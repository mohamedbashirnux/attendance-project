<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

// Handle AJAX request to update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        if (!isset($_POST['tid']) || !isset($_POST['status']) || !isset($_POST['subject']) || !isset($_POST['class_name']) || !isset($_POST['study_mode']) || !isset($_POST['current_time'])) {
            header("Content-Type: application/json");
            echo json_encode(["error" => "Missing required parameters"]);
            exit();
        }

        $tid = htmlspecialchars($_POST['tid']);
        $status = htmlspecialchars($_POST['status']);
        $subject = htmlspecialchars($_POST['subject']);
        $className = htmlspecialchars($_POST['class_name']);
        $studyMode = htmlspecialchars($_POST['study_mode']);
        $currentTime = htmlspecialchars($_POST['current_time']);

        include "../../connection/connect.php";

        try {
            // Fetch the start and end time for the class
            $sql = "SELECT start_time, end_time FROM allocate_teacher_subject WHERE tid = :tid AND subject_name = :subject AND class_name = :class_name AND study_mode = :study_mode";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':tid', $tid, PDO::PARAM_INT);
            $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
            $stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $startTime = $row['start_time'];
                $endTime = $row['end_time'];

                // Determine the new status based on time
                if ($status === 'waiting') {
                    if ($currentTime >= $startTime && $currentTime <= $endTime) {
                        $status = 'approved'; // Automatically approve if within time range
                    } else {
                        $status = 'waiting'; // Otherwise, keep as waiting
                    }
                } else {
                    // Revert to 'pending' if the status was not 'waiting'
                    $status = 'pending';
                }

                $sql = "UPDATE allocate_teacher_subject SET status = :status WHERE tid = :tid AND subject_name = :subject AND class_name = :class_name AND study_mode = :study_mode";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':tid', $tid, PDO::PARAM_INT);
                $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
                $stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
                $stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    header("Content-Type: application/json");
                    echo json_encode(["status" => $status]);
                } else {
                    header("Content-Type: application/json");
                    echo json_encode(["error" => "Failed to update status"]);
                }
            } else {
                header("Content-Type: application/json");
                echo json_encode(["error" => "Class data not found"]);
            }
        } catch (PDOException $e) {
            header("Content-Type: application/json");
            echo json_encode(["error" => "Error: " . htmlspecialchars($e->getMessage())]);
        }

        $conn = null; // Close the connection
        exit();
    }
}
?>
