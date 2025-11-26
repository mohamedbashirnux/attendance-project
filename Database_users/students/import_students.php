<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

include "../../connection/connect.php";
require('../../library/php-excel-reader/excel_reader2.php');
require('../../library/SpreadsheetReader.php');

$response = ['status' => 'error', 'message' => ''];

try {
    // Check if the required fields are set, including file upload and form fields
    if (isset($_FILES['file']) && isset($_POST['departmentName']) && isset($_POST['className']) && isset($_POST['studyMode']) && isset($_POST['class_id']) && isset($_POST['faculty']) && isset($_POST['password'])) {
        // Sanitize user input
        $department_name = filter_var($_POST['departmentName'], FILTER_SANITIZE_STRING);
        $className = filter_var($_POST['className'], FILTER_SANITIZE_STRING);
        $studyMode = filter_var($_POST['studyMode'], FILTER_SANITIZE_STRING);
        $class_id = filter_var($_POST['class_id'], FILTER_SANITIZE_STRING);
        $faculty = filter_var($_POST['faculty'], FILTER_SANITIZE_STRING);
        $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

        // Allowed MIME types for file uploads
        $allowedMimes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/xls',
            'text/xlsx',
            'application/vnd.oasis.opendocument.spreadsheet'
        ];

        // Check if the uploaded file is an Excel file
        $fileType = $_FILES["file"]["type"];
        if (in_array($fileType, $allowedMimes)) {
            $uploadFilePath = '../../uploads/' . basename($_FILES['file']['name']);
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFilePath)) {
                // Read the uploaded Excel file
                $Reader = new SpreadsheetReader($uploadFilePath);
                $Reader->ChangeSheet(0); // Process the first sheet

                $count = 0;
                $errors = [];
                $insertSuccess = true;

                // Prepare SQL statements for checking duplicates and inserting data
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_id = :student_id AND department_name = :department_name");
                $insertStmt = $conn->prepare("INSERT INTO students (student_id, student_name, tell, department_name, class_name, c_id, study_mode, faculty_name, password) VALUES (:student_id, :student_name, :tell, :department_name, :class_name, :c_id, :study_mode, :faculty_name, :password)");

                // Loop through each row in the spreadsheet
                foreach ($Reader as $Row) {
                    $count++;

                    // Check if row data is incomplete (we expect at least 3 columns)
                    if (count($Row) < 3) {
                        $errors[] = "Incomplete data at row $count";
                        continue;
                    }

                    // Sanitize and prepare the data for each student
                    $student_id = isset($Row[0]) ? filter_var($Row[0], FILTER_SANITIZE_STRING) : '';
                    $student_name = isset($Row[1]) ? filter_var($Row[1], FILTER_SANITIZE_STRING) : '';
                    $tell = isset($Row[2]) ? filter_var($Row[2], FILTER_SANITIZE_STRING) : '';

                    // Skip rows that contain empty cells (student_id, student_name, tell, or password)
                    if (empty($student_id) || empty($student_name) || empty($tell) || empty($password)) {
                        continue;
                    }

                    // Check if the student ID already exists in the database
                    $checkStmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
                    $checkStmt->bindParam(':department_name', $department_name, PDO::PARAM_STR);
                    $checkStmt->execute();
                    $countExists = $checkStmt->fetchColumn();

                    // Skip duplicate entries
                    if ($countExists > 0) {
                        $errors[] = "This ID '$student_id' already exists.";
                        $insertSuccess = false;
                        continue;
                    }

                    // Insert the student data, including the plain-text password
                    $insertStmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
                    $insertStmt->bindParam(':student_name', $student_name, PDO::PARAM_STR);
                    $insertStmt->bindParam(':tell', $tell, PDO::PARAM_STR);
                    $insertStmt->bindParam(':department_name', $department_name, PDO::PARAM_STR);
                    $insertStmt->bindParam(':class_name', $className, PDO::PARAM_STR);
                    $insertStmt->bindParam(':c_id', $class_id, PDO::PARAM_STR);
                    $insertStmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
                    $insertStmt->bindParam(':faculty_name', $faculty, PDO::PARAM_STR);
                    $insertStmt->bindParam(':password', $password, PDO::PARAM_STR); // No hashing here

                    if (!$insertStmt->execute()) {
                        $errors[] = "Failed to insert row $count: " . $insertStmt->errorInfo()[2];
                    }
                }

                // Provide feedback based on success or errors
                if ($insertSuccess && empty($errors)) {
                    $response['status'] = 'success';
                    $response['message'] = 'Students imported successfully';
                } else {
                    $response['message'] = 'Students imported successfully, but some duplicates were ignored. ' . implode(', ', $errors);
                }
            } else {
                $response['message'] = 'Failed to move uploaded file.';
            }
        } else {
            $response['message'] = 'Only Excel files are allowed!';
        }
    } else {
        $response['message'] = 'No file uploaded or missing department/class information.';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
