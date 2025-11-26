<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

// Include database connection details
include "../../connection/connect.php";

try {
    // Begin a transaction
    $conn->beginTransaction();

    // Step 1: Delete all students' absents
    $deleteAbsents = $conn->prepare("DELETE FROM absents");
    $deleteAbsents->execute();

    // Step 2: Delete all students from students table
    $deleteStudents = $conn->prepare("DELETE FROM students");
    $deleteStudents->execute();

    // Commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'All students and related records deleted successfully.']);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
$conn = null;
?>
