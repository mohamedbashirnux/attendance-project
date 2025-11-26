<?php
session_start();

include "../../connection/connect.php";
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentName = $_POST['department_name'] ?? '';

    if (empty($departmentName)) {
        echo json_encode(['status' => 'error', 'message' => 'Department name is required']);
        exit();
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        $sql = "DELETE FROM departments WHERE department_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$departmentName]);

        $sql = "DELETE FROM allocate_teacher_subject WHERE department_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$departmentName]);

        $sql = "DELETE FROM students WHERE department_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$departmentName]);

        $sql = "DELETE FROM subjects WHERE department_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$departmentName]);

        $sql = "DELETE FROM subject_class WHERE department_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$departmentName]);

        $sql = "DELETE FROM absents WHERE department_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$departmentName]);

        // Commit transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Department and related records deleted successfully']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
