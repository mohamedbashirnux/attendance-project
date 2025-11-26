<?php
session_start();

// Database connection details
include "../../connection/connect.php";

// Check if department_name and class_name are provided via GET
if (isset($_GET['department_name']) && isset($_GET['class_name'])) {
    $departmentName = $_GET['department_name'];
    $className = $_GET['class_name'];
    
    // Fetch subjects based on department_name and class_name
    try {
        $sql = "SELECT subject_name FROM subjects WHERE department_name = :department_name AND class_name = :class_name";
        $stmt = $conn->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
        $stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
        
        $stmt->execute();
        $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Output subjects as options for select dropdown
        foreach ($subjects as $subject) {
            echo "<option value='" . htmlspecialchars($subject) . "'>" . htmlspecialchars($subject) . "</option>";
        }
        
    } catch (PDOException $e) {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "<option value=''>No subjects found</option>";
}

// Close the connection
$conn = null;
?>
