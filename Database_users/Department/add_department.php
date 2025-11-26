<?php
session_start();

include "../../connection/connect.php";

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Unauthorized access");
}

$faculty = $_SESSION['faculty'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $departmentName = $_POST['departmentName'];

    // Validate input (You can add more validation if needed)

    // Check if department already exists
    $check_sql = "SELECT * FROM departments WHERE department_name = ? AND faculty_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$departmentName, $faculty]);

    if ($check_stmt->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Department already exists"]);
    } else {
        $sql = "INSERT INTO departments (department_name, faculty_name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$departmentName, $faculty])) {
            echo json_encode(["status" => "success", "message" => "Department added successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error adding department"]);
        }
    }
}
?>
