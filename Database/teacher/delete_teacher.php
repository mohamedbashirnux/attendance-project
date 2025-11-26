<?php
include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

// Check if teacherID parameter exists in POST request
if (isset($_POST['teacherID'])) {
    $teacherID = $_POST['teacherID'];

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Prepare and execute SQL statement to delete from allocate_teacher_subject
        $deleteAllocationSql = "DELETE FROM allocate_teacher_subject WHERE tid = :tid";
        $stmt = $conn->prepare($deleteAllocationSql);
        $stmt->bindParam(':tid', $teacherID, PDO::PARAM_INT);
        $stmt->execute();

        // Prepare and execute SQL statement to delete teacher
        $deleteTeacherSql = "DELETE FROM teachertable WHERE tid = :tid";
        $stmt = $conn->prepare($deleteTeacherSql);
        $stmt->bindParam(':tid', $teacherID, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Commit transaction if both deletes are successful
            $conn->commit();
            echo "Teacher and their allocations deleted successfully";
        } else {
            throw new Exception("Error deleting teacher");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request";
}

// Close connection
$conn = null; // Use null to close the PDO connection
?>
