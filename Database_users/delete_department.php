<?php
session_start();

// Redirect if session variables are not set
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendanceproject";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare SQL statement
$sql = "DELETE FROM departments WHERE faculty_name = ? AND department_name = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error);
}

// Bind parameters
$department_name = $_POST['department_name'];
$stmt->bind_param("ss", $faculty, $department_name);

// Execute statement
if ($stmt->execute()) {
    echo "Department deleted successfully.";
} else {
    echo "Error deleting department: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
