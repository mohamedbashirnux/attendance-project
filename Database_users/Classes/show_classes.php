<?php
session_start();

// Check if faculty is logged in
if (!isset($_SESSION['faculty'])) {
    exit("Faculty not logged in.");
}

$facultyName = $_SESSION['faculty'];

// Establish database connection
include "../../connection/connect.php";

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$offset = ($page - 1) * $per_page;

try {
    // Count total records
    $sql = "SELECT COUNT(*) as total FROM classes WHERE faculty_name = :faculty_name";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':faculty_name', $facultyName, PDO::PARAM_STR);
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Fetch classes for the current page
    $sql = "SELECT * FROM classes WHERE faculty_name = :faculty_name LIMIT :offset, :per_page";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':faculty_name', $facultyName, PDO::PARAM_STR);
    // Bind offset and per_page as integers
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'classes' => $rows,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn = null;
?>
