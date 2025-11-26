<?php
session_start();

include "../../connection/connect.php";
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$offset = ($page - 1) * $per_page;

// Count total records
$sql = "SELECT COUNT(*) as total FROM departments WHERE faculty_name = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$faculty]);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Fetch departments for the current page
$sql = "SELECT faculty_name, department_name FROM departments WHERE faculty_name = ? LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bindParam(1, $faculty, PDO::PARAM_STR);
$stmt->bindParam(2, $offset, PDO::PARAM_INT);
$stmt->bindParam(3, $per_page, PDO::PARAM_INT);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'departments' => $departments,
    'total_pages' => $total_pages,
    'current_page' => $page
]);
?>
