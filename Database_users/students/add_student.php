<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

// Database connection details
include "../../connection/connect.php";

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate and sanitize input data
        $studentId = filter_var($_POST['studentId'], FILTER_SANITIZE_NUMBER_INT);
        $studentName = filter_var($_POST['studentName'], FILTER_SANITIZE_STRING);
        $departmentName = filter_var($_POST['departmentName'], FILTER_SANITIZE_STRING);
        $className = filter_var($_POST['className'], FILTER_SANITIZE_STRING);
        $studyMode = filter_var($_POST['studyMode'], FILTER_SANITIZE_STRING);
        $faculty = $_POST['faculty'];
        $c_id = $_POST['class_id'];
        $studentnumber = $_POST['studentnumber'];
        $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
        $semester = filter_var($_POST['semester'], FILTER_SANITIZE_STRING);
        $academic = filter_var($_POST['academic'], FILTER_SANITIZE_STRING);

        // Check if all required fields are provided
        if (empty($studentId) || empty($studentName) || empty($departmentName) || 
            empty($className) || empty($c_id) || empty($studyMode) || 
            empty($faculty) || empty($studentnumber) || empty($password) || 
            empty($semester) || empty($academic)) {
            echo json_encode(["success" => false, "message" => "All fields are required."]);
            exit();
        }

        // Check if student ID already exists
        $checkSql = "SELECT * FROM students WHERE student_id = :studentId";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            echo json_encode(["success" => false, "message" => "Student ID already exists."]);
            exit();
        }

        // Insert new student into database
        $insertSql = "INSERT INTO students (
            student_id, student_name, department_name, class_name, 
            c_id, study_mode, faculty_name, tell, password, 
            semester, academic, status
        ) VALUES (
            :studentId, :studentName, :departmentName, :className, 
            :c_id, :studyMode, :faculty, :studentnumber, :password,
            :semester, :academic, 'pending'
        )";
        
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bindParam(':studentId', $studentId, PDO::PARAM_INT);
        $insertStmt->bindParam(':studentName', $studentName, PDO::PARAM_STR);
        $insertStmt->bindParam(':departmentName', $departmentName, PDO::PARAM_STR);
        $insertStmt->bindParam(':className', $className, PDO::PARAM_STR);
        $insertStmt->bindParam(':c_id', $c_id, PDO::PARAM_STR);
        $insertStmt->bindParam(':studyMode', $studyMode, PDO::PARAM_STR);
        $insertStmt->bindParam(':faculty', $faculty, PDO::PARAM_STR);
        $insertStmt->bindParam(':studentnumber', $studentnumber, PDO::PARAM_STR);
        $insertStmt->bindParam(':password', $password, PDO::PARAM_STR);
        $insertStmt->bindParam(':semester', $semester, PDO::PARAM_STR);
        $insertStmt->bindParam(':academic', $academic, PDO::PARAM_STR);

        if ($insertStmt->execute()) {
            echo json_encode(["success" => true, "message" => "Student added successfully."]);
        } else {
            throw new Exception("Error inserting student.");
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request method."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>