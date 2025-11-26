<?php
session_start();

if (!isset($_SESSION['faculty']) || !isset($_GET['class_name'])) {
    exit('Missing required parameters');
}

$faculty = $_SESSION['faculty'];
$className = $_GET['class_name'];

include "../../connection/connect.php";

try {
    // Prepare the SQL statement using PDO
    $sql = "SELECT DISTINCT study_mode FROM classes WHERE class_name = ? AND faculty_name = ?";
    $stmt = $conn->prepare($sql);
    
    // Execute the query with the parameters
    $stmt->execute([$className, $faculty]);

    $options = '';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options .= "<option value='" . htmlspecialchars($row['study_mode']) . "'>" . htmlspecialchars($row['study_mode']) . "</option>";
    }

    echo $options;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the connection
$conn = null;
?>
