<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../../interval/Auth_user.php");
    exit();
}

include "../../connection/connect.php";

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get the student ID and new status
        $studentId = filter_var($_POST['student_id'], FILTER_SANITIZE_STRING);
        $newStatus = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
        
        // Validate the status value
        if (!in_array($newStatus, ['pending', 'approved'])) {
            echo json_encode(["success" => false, "message" => "Invalid status value."]);
            exit();
        }
        
        // Update the student status
        $updateSql = "UPDATE students SET status = :status WHERE student_id = :student_id";
        $stmt = $conn->prepare($updateSql);
        $stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    "success" => true, 
                    "message" => "Student status updated successfully.",
                    "new_status" => $newStatus
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Student not found."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update student status."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request method."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
