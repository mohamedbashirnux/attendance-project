<?php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];
$department_name = isset($_GET['department_name']) ? $_GET['department_name'] : '';
$class_name = isset($_GET['class_name']) ? $_GET['class_name'] : '';
$study_mode = isset($_GET['study_mode']) ? $_GET['study_mode'] : '';

// Database connection
include "../../connection/connect.php";

try {
    // Prepare and execute query for subjects
    $query = "SELECT DISTINCT subject_class.subject_name
                FROM classes
                INNER JOIN subject_class ON classes.department_name = subject_class.department_name
                AND classes.study_mode = subject_class.study_mode 
                AND classes.class_name = subject_class.class_name 
                WHERE classes.class_name = :class_name 
                AND classes.department_name = :department_name 
                AND classes.study_mode = :study_mode";
                
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':class_name', $class_name, PDO::PARAM_STR);
    $stmt->bindParam(':department_name', $department_name, PDO::PARAM_STR);
    $stmt->bindParam(':study_mode', $study_mode, PDO::PARAM_STR);
    
    // Execute the statement
    $stmt->execute();
    
    // Fetch results
    $subjects = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $subjects[] = $row['subject_name'];
    }
    
    // Output options
    foreach ($subjects as $subject) {
        echo '<option value="' . htmlspecialchars($subject) . '">' . htmlspecialchars($subject) . '</option>';
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Close the connection
$conn = null;
?>
