<?php
// Establish database connection
include "../../connection/connect.php";

// Retrieve GET parameter
$department = $_GET['department'] ?? '';

try {
    // Fetch classes for the selected department
    $sql = "SELECT department_name, class_name, study_mode, semester, academic FROM classes WHERE department_name = :department";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':department', $department, PDO::PARAM_STR);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $classes]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
