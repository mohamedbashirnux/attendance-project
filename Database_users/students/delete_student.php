<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    // Redirect to the login page or appropriate access denied page
    header("Location: ../interval/Auth_user.php");
    exit();
}
// Include database connection details
include "../../connection/connect.php";

// Check if student_id is provided
if (isset($_POST['student_id'])) {
    // Sanitize the student_id to prevent SQL injection
    $student_id = filter_var($_POST['student_id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        // Begin a transaction
        $conn->beginTransaction();

        // Check if student exists
        $checkStudent = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
        $checkStudent->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $checkStudent->execute();

        if ($checkStudent->rowCount() > 0) {
            // Student exists, proceed with deletion
            
            // Step 1: Delete the student's absents
            $deleteAbsents = $conn->prepare("DELETE FROM absents WHERE student_id = :student_id");
            $deleteAbsents->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $deleteAbsents->execute();

            // Step 2: Delete the student from students table
            $deleteStudent = $conn->prepare("DELETE FROM students WHERE student_id = :student_id");
            $deleteStudent->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            
            // Execute the deletion and check
            if ($deleteStudent->execute()) {
                // Commit the transaction if both deletions succeed
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Student and related absents deleted successfully.']);
            } else {
                throw new Exception('Failed to delete student.');
            }
        } else {
            // No student found with the given student_id
            echo json_encode(['success' => false, 'message' => 'No student found with the provided ID.']);
        }

    } catch (PDOException $e) {
        // Roll back the transaction in case of a database error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        // Roll back the transaction in case of a general error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    // student_id not provided
    echo json_encode(['success' => false, 'message' => 'Student ID not provided.']);
}

// Close connection
$conn = null;
?>
