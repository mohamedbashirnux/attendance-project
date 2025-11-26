<?php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];

if (isset($_GET['class_id'])) {
    $class_id = $_GET['class_id'];
} else {
    exit("No class ID provided.");
}

include "../../connection/connect.php";

try {
    // Prepare the SQL statement using PDO
    $sql = "SELECT study_mode FROM classes WHERE id = :class_id AND faculty_name = :faculty_name";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters and execute the query
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->bindParam(':faculty_name', $faculty, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the result
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo htmlspecialchars($row['study_mode']);
    } else {
        echo "No study mode found for the selected class.";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the connection
$conn = null;
?>
