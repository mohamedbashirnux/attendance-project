<?php
// Remove session dependencies for super admin access
include "../../connection/connect.php";

// Get the required parameters from the URL
$class_name = $_GET['class_name'] ?? '';
$department_name = $_GET['department_name'] ?? '';
$study_mode = $_GET['study_mode'] ?? '';
$faculty_name = $_GET['faculty_name'] ?? '';

// Validate required parameters
if (empty($class_name) || empty($department_name) || empty($study_mode) || empty($faculty_name)) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    // Prepare the SQL statement to get subjects based on class, department, study_mode, and faculty
    $stmt = $conn->prepare("
        SELECT DISTINCT subject_class.subject_name
        FROM classes
        INNER JOIN subject_class 
        ON classes.department_name = subject_class.department_name
        AND classes.study_mode = subject_class.study_mode 
        AND classes.class_name = subject_class.class_name 
        WHERE classes.class_name = ? 
        AND classes.department_name = ? 
        AND classes.study_mode = ? 
        AND classes.faculty_name = ?
    ");
    
    // Execute the statement with bound parameters
    $stmt->execute([$class_name, $department_name, $study_mode, $faculty_name]);

    // Fetch all results
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output the subjects as JSON
    echo json_encode(['subjects' => $subjects]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

// Close the connection
$conn = null;
?>
