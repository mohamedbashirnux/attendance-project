<?php
// Establish database connection
include "../../connection/connect.php";

// Retrieve POST data
$departmentName = $_POST['departmentName'] ?? '';
$className = $_POST['className'] ?? '';
$studyMode = $_POST['studyMode'] ?? '';
$facultyName = $_POST['facultyName'] ?? ''; // Capture facultyName from POST data
$semester = $_POST['semester'] ?? ''; // Capture semester from POST data
$academicYear = $_POST['academicYear'] ?? ''; // Capture academicYear from POST data

try {
    // Check if the class already exists based on departmentName, className, and studyMode (without checking semester)
    $sql = "SELECT * FROM classes WHERE department_name = :departmentName AND class_name = :className AND study_mode = :studyMode";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':departmentName', $departmentName, PDO::PARAM_STR);
    $stmt->bindParam(':className', $className, PDO::PARAM_STR);
    $stmt->bindParam(':studyMode', $studyMode, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Class already exists
        echo json_encode(['status' => 'warning', 'message' => 'Class already exists']);
        exit;
    }

    // Insert new class into the database (including semester and academic)
    $sql = "INSERT INTO classes (department_name, class_name, study_mode, faculty_name, semester, academic) 
            VALUES (:departmentName, :className, :studyMode, :facultyName, :semester, :academicYear)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':departmentName', $departmentName, PDO::PARAM_STR);
    $stmt->bindParam(':className', $className, PDO::PARAM_STR);
    $stmt->bindParam(':studyMode', $studyMode, PDO::PARAM_STR);
    $stmt->bindParam(':facultyName', $facultyName, PDO::PARAM_STR);
    $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
    $stmt->bindParam(':academicYear', $academicYear, PDO::PARAM_STR); // Bind academicYear as 'academic'

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add class']);
    }

    // Close the cursor and the connection
    $stmt->closeCursor();
    $conn = null;
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
