<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Suppress PHP warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Include the faculty session management
include "../../Account_users/session_faculty.php";

// Include database connection
include "../../connection/connect.php";

// Clear any unexpected output from includes
ob_clean();

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }
        // Log received POST data for debugging
        error_log("POST data: " . print_r($_POST, true));
        
        // Check if timetable table has the new structure
        $check_columns_sql = "SHOW COLUMNS FROM timetable LIKE 'class_id'";
        $check_stmt = $conn->query($check_columns_sql);
        $has_new_structure = ($check_stmt->rowCount() > 0);

        if ($has_new_structure) {
            // NEW STRUCTURE: Use foreign keys
            $class_id = filter_var($_POST['class_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
            $allocation_id = filter_var($_POST['allocation_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
            $dayOfWeek = filter_var($_POST['dayOfWeek'] ?? '', FILTER_SANITIZE_STRING);
            $locationHall = filter_var($_POST['locationHall'] ?? '', FILTER_SANITIZE_STRING);

            // Validate required fields
            if (empty($class_id)) {
                throw new Exception("Class ID is required.");
            }
            if (empty($allocation_id)) {
                throw new Exception("Please select a subject.");
            }
            if (empty($dayOfWeek)) {
                throw new Exception("Please select a day of week.");
            }
            if (empty($locationHall)) {
                throw new Exception("Location/Hall is required.");
            }

            // Verify that the allocation exists and get its time information
            $verify_sql = "SELECT tsa.start_time, tsa.end_time 
                          FROM teacher_subject_allocation tsa
                          WHERE tsa.id = ? AND tsa.class_id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->execute([$allocation_id, $class_id]);
            $allocation = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$allocation) {
                throw new Exception("Invalid allocation or class. Please refresh and try again.");
            }

            // Check if this allocation already has a timetable entry for this day
            $check_sql = "SELECT id FROM timetable 
                         WHERE allocation_id = ? AND day_of_week = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$allocation_id, $dayOfWeek]);
            
            if ($check_stmt->fetch()) {
                throw new Exception("This subject already has a timetable entry for " . $dayOfWeek . ".");
            }

            // Insert into timetable table using foreign keys
            $insertSql = "INSERT INTO timetable (
                class_id, allocation_id, day_of_week, time_start, time_end, location_hall
            ) VALUES (
                :class_id, :allocation_id, :dayOfWeek, :timeStart, :timeEnd, :locationHall
            )";

            $stmt = $conn->prepare($insertSql);
            $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt->bindParam(':allocation_id', $allocation_id, PDO::PARAM_INT);
            $stmt->bindParam(':dayOfWeek', $dayOfWeek, PDO::PARAM_STR);
            $stmt->bindParam(':timeStart', $allocation['start_time'], PDO::PARAM_STR);
            $stmt->bindParam(':timeEnd', $allocation['end_time'], PDO::PARAM_STR);
            $stmt->bindParam(':locationHall', $locationHall, PDO::PARAM_STR);

        } else {
            // OLD STRUCTURE: Use text fields (backward compatibility)
            $departmentName = filter_var($_POST['departmentName'], FILTER_SANITIZE_STRING);
            $className = filter_var($_POST['className'], FILTER_SANITIZE_STRING);
            $studyMode = filter_var($_POST['studyMode'], FILTER_SANITIZE_STRING);
            $semester = filter_var($_POST['semester'], FILTER_SANITIZE_STRING);
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
                throw new Exception("All fields are required.");
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
            $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
            $stmt->bindParam(':faculty', $faculty, PDO::PARAM_STR);
            $stmt->bindParam(':academic', $academic, PDO::PARAM_STR);
            $stmt->bindParam(':teacherName', $teacherName, PDO::PARAM_STR);
            $stmt->bindParam(':subjectName', $subjectName, PDO::PARAM_STR);
            $stmt->bindParam(':dayOfWeek', $dayOfWeek, PDO::PARAM_STR);
            $stmt->bindParam(':timeStart', $timeStart, PDO::PARAM_STR);
            $stmt->bindParam(':timeEnd', $timeEnd, PDO::PARAM_STR);
            $stmt->bindParam(':locationHall', $locationHall, PDO::PARAM_STR);
        }

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(["success" => true, "message" => "Timetable entry added successfully."]);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Database error: " . print_r($errorInfo, true));
            throw new Exception("Error inserting timetable entry: " . $errorInfo[2]);
        }
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>
