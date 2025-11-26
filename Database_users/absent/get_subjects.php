<?php
include "../../connection/connect.php";

$class_name = $_GET['class_name'];
$department_name = $_GET['department_name'];
$study_mode = $_GET['study_mode'];

try {
    // Prepare the SQL statement
    $stmt = $conn->prepare("
        SELECT DISTINCT subject_class.subject_name
        FROM classes
        INNER JOIN subject_class 
        ON classes.department_name = subject_class.department_name
        AND classes.study_mode = subject_class.study_mode 
        AND classes.class_name = subject_class.class_name 
        WHERE classes.class_name = ? AND classes.department_name = ? AND classes.study_mode = ?
    ");
    
    // Execute the statement with bound parameters
    $stmt->execute([$class_name, $department_name, $study_mode]);

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
