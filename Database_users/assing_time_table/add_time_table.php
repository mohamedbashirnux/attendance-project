<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

// Database connection
include "../../connection/connect.php";

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitize input
        $departmentName = filter_var($_POST['departmentName'], FILTER_SANITIZE_STRING);
        $className = filter_var($_POST['className'], FILTER_SANITIZE_STRING);
        $studyMode = filter_var($_POST['studyMode'], FILTER_SANITIZE_STRING);
        $semester = filter_var($_POST['semester'], FILTER_SANITIZE_NUMBER_INT);
        $faculty = filter_var($_POST['faculty'], FILTER_SANITIZE_STRING);
        $academic = filter_var($_POST['academic'], FILTER_SANITIZE_STRING);
        $teacherName = filter_var($_POST['teacherName'], FILTER_SANITIZE_STRING);
        $subjectName = filter_var($_POST['subjectName'], FILTER_SANITIZE_STRING);
        $dayOfWeek = filter_var($_POST['dayOfWeek'], FILTER_SANITIZE_STRING);
        $timeStart = filter_var($_POST['timeStart'], FILTER_SANITIZE_STRING);
        $timeEnd = filter_var($_POST['timeEnd'], FILTER_SANITIZE_STRING);
        $locationHall = filter_var($_POST['locationHall'], FILTER_SANITIZE_STRING);

        // Validate required fields
        if (empty($departmentName) || empty($className) || empty($studyMode) || empty($semester) ||
            empty($faculty) || empty($academic) || empty($teacherName) || empty($subjectName) ||
            empty($dayOfWeek) || empty($timeStart) || empty($timeEnd) || empty($locationHall)) {
            echo json_encode(["success" => false, "message" => "All fields are required."]);
            exit();
        }

        // Insert into timetable table
        $insertSql = "INSERT INTO timetable (
            department_name, class_name, study_mode, semester, faculty_name, academic_year,
            teacher_name, subject_name, day_of_week, time_start, time_end, location_hall
        ) VALUES (
            :departmentName, :className, :studyMode, :semester, :faculty, :academic,
            :teacherName, :subjectName, :dayOfWeek, :timeStart, :timeEnd, :locationHall
        )";

        $stmt = $conn->prepare($insertSql);
        $stmt->bindParam(':departmentName', $departmentName, PDO::PARAM_STR);
        $stmt->bindParam(':className', $className, PDO::PARAM_STR);
        $stmt->bindParam(':studyMode', $studyMode, PDO::PARAM_STR);
        $stmt->bindParam(':semester', $semester, PDO::PARAM_INT);
        $stmt->bindParam(':faculty', $faculty, PDO::PARAM_STR);
        $stmt->bindParam(':academic', $academic, PDO::PARAM_STR);
        $stmt->bindParam(':teacherName', $teacherName, PDO::PARAM_STR);
        $stmt->bindParam(':subjectName', $subjectName, PDO::PARAM_STR);
        $stmt->bindParam(':dayOfWeek', $dayOfWeek, PDO::PARAM_STR);
        $stmt->bindParam(':timeStart', $timeStart, PDO::PARAM_STR);
        $stmt->bindParam(':timeEnd', $timeEnd, PDO::PARAM_STR);
        $stmt->bindParam(':locationHall', $locationHall, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Timetable entry added successfully."]);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Database error: " . print_r($errorInfo, true));
            throw new Exception("Error inserting timetable entry: " . $errorInfo[2]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request method."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
