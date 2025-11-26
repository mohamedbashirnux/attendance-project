<?php
session_start();

include "../../connection/connect.php";

if (!isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];
$department_name = isset($_GET['department_name']) ? $_GET['department_name'] : '';
$class_name = isset($_GET['class_name']) ? $_GET['class_name'] : '';
$study_mode = isset($_GET['study_mode']) ? $_GET['study_mode'] : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$start = ($page > 1) ? ($page - 1) * $perPage : 0;

$query = "SELECT DISTINCT subjects.subject_name, classes.semester
          FROM classes
          INNER JOIN subjects ON classes.department_name = subjects.department_name
          AND classes.semester = subjects.semester
          WHERE classes.class_name = :class_name AND classes.department_name = :department_name AND classes.study_mode = :study_mode
          ORDER BY classes.semester
          LIMIT :start, :perPage";

$stmt = $conn->prepare($query);
$stmt->bindParam(':class_name', $class_name);
$stmt->bindParam(':department_name', $department_name);
$stmt->bindParam(':study_mode', $study_mode);
$stmt->bindParam(':start', $start, PDO::PARAM_INT);
$stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();

$classes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $classes[] = $row;
}

$queryTotal = "SELECT COUNT(DISTINCT subjects.subject_name) as total
               FROM classes
               INNER JOIN subjects ON classes.department_name = subjects.department_name
               AND classes.semester = subjects.semester
               WHERE classes.class_name = :class_name AND classes.department_name = :department_name AND classes.study_mode = :study_mode";

$stmtTotal = $conn->prepare($queryTotal);
$stmtTotal->bindParam(':class_name', $class_name);
$stmtTotal->bindParam(':department_name', $department_name);
$stmtTotal->bindParam(':study_mode', $study_mode);
$stmtTotal->execute();
$total = $stmtTotal->fetchColumn();

$totalPages = ceil($total / $perPage);

$response = [
    'classes' => $classes,
    'total_pages' => $totalPages,
    'current_page' => $page,
];

echo json_encode($response);
?>
