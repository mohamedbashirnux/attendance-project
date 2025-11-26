<?php
$teacherID = $_POST['id'];
$fullName = $_POST['fullname'];
$username = $_POST['username'];
$password = $_POST['password']; // Be cautious with handling passwords; consider hashing them before storing

// Database connection
include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

try {
    // Begin transaction
    $conn->beginTransaction();

    // Update teacher details in the teachertable
    $sql = "UPDATE teachertable SET teacher_name = :fullname, username = :username, password = :password WHERE tid = :tid";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':fullname', $fullName);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':tid', $teacherID);

    if (!$stmt->execute()) {
        throw new Exception('Error updating teacher');
    }

    // Update teacher name in the allocate_teacher_subject table
    $sqlAllocate = "UPDATE allocate_teacher_subject SET teacher_name = :fullname WHERE tid = :tid";
    $stmtAllocate = $conn->prepare($sqlAllocate);
    
    // Bind parameters
    $stmtAllocate->bindParam(':fullname', $fullName);
    $stmtAllocate->bindParam(':tid', $teacherID);

    if (!$stmtAllocate->execute()) {
        throw new Exception('Error updating allocate_teacher_subject');
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Teacher and allocation updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Close connection
$conn = null; // Use null to close the PDO connection
?>
