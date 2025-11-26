<?php
session_start();
include "../../connection/connect.php"; // Adjust path as needed

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentName = filter_var($_POST['department_name'] ?? '', FILTER_SANITIZE_STRING);
    $className = filter_var($_POST['class_name'] ?? '', FILTER_SANITIZE_STRING);
    $studyMode = filter_var($_POST['study_mode'] ?? '', FILTER_SANITIZE_STRING);
    $facultyName = filter_var($_POST['faculty_name'] ?? '', FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($departmentName) || empty($className) || empty($studyMode) || empty($facultyName) || empty($status)) {
        echo json_encode(["success" => false, "message" => "All class parameters and status are required."]);
        exit();
    }

    if (!in_array($status, ['pending', 'approved'])) {
        echo json_encode(["success" => false, "message" => "Invalid status value."]);
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE students SET status = :status WHERE department_name = :department_name AND class_name = :class_name AND study_mode = :study_mode AND faculty_name = :faculty_name");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
        $stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
        $stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
        $stmt->bindParam(':faculty_name', $facultyName, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "All students in the class updated to " . $status . " successfully.", "count" => $stmt->rowCount()]);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Database error in update_all_students_status.php: " . print_r($errorInfo, true));
            echo json_encode(["success" => false, "message" => "Database error: " . $errorInfo[2]]);
        }
    } catch (PDOException $e) {
        error_log("PDO Exception in update_all_students_status.php: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "PDO Exception: " . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("General Exception in update_all_students_status.php: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "General Exception: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
