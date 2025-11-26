<?php
// Establish database connection
include "../../connection/connect.php";

session_start();
$faculty_name = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';

if (!$faculty_name) {
    echo json_encode(['status' => 'error', 'message' => 'Faculty not logged in']);
    exit;
}

try {
    // Query to get departments based on faculty name
    $sql = "SELECT department_name FROM departments WHERE faculty_name = :faculty_name";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':faculty_name', $faculty_name, PDO::PARAM_STR);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($departments)) {
        echo json_encode(['status' => 'success', 'data' => $departments]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No departments found for this faculty']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn = null;
?>
