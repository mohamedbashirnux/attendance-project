<?php
include "../../connection/connect.php";

require('../../library/php-excel-reader/excel_reader2.php');
require('../../library/SpreadsheetReader.php');

$response = ['status' => 'error', 'message' => '', 'duplicates' => []];

try {
    // Check if the required form data and file are present
    if (isset($_FILES['file']) && isset($_POST['department_name']) && isset($_POST['faculty'])) {
        $department_name = trim($_POST['department_name']);
        $faculty = trim($_POST['faculty']);

        // Allowed MIME types for Excel files
        $allowedMimes = [
            'application/vnd.ms-excel', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/xls', 
            'text/xlsx', 
            'application/vnd.oasis.opendocument.spreadsheet'
        ];

        $fileType = $_FILES["file"]["type"];

        // Ensure the uploaded file is an Excel file
        if (in_array($fileType, $allowedMimes)) {
            $uploadFilePath = '../../uploads/' . basename($_FILES['file']['name']);
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFilePath)) {
                $Reader = new SpreadsheetReader($uploadFilePath);
                $Reader->ChangeSheet(0); // Use the first sheet

                $count = 0;
                $duplicates = [];
                $insertSuccess = true;

                // Prepare statements for duplicate check and insertion
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM subjects WHERE LOWER(TRIM(subject_name)) = LOWER(TRIM(:subject_name)) AND department_name = :department_name");
                $insertStmt = $conn->prepare("INSERT INTO subjects (subject_name, department_name, faculty_name) VALUES (:subject_name, :department_name, :faculty_name)");

                $insertedSubjects = [];  // To track already inserted subjects from the file

                foreach ($Reader as $Row) {
                    $count++;

                    // Get the first column (subject name) and ignore other columns if any
                    $subjectName = isset($Row[0]) ? trim($Row[0]) : '';

                    // Skip if the subject name is empty or contains only spaces
                    if (empty($subjectName)) {
                        continue;
                    }

                    // Check if subject already exists in the database (case-insensitive)
                    $checkStmt->execute([':subject_name' => $subjectName, ':department_name' => $department_name]);
                    $countExists = $checkStmt->fetchColumn();

                    if ($countExists > 0) {
                        $duplicates[] = $subjectName; // Add to duplicate list
                        continue; // Skip to the next row if duplicate is found
                    }

                    // Check if the subject was already inserted in this file (case-insensitive)
                    if (in_array(strtolower($subjectName), $insertedSubjects)) {
                        $duplicates[] = $subjectName; // Add to duplicate list
                        continue;
                    }

                    // Add the subject to the inserted tracker
                    $insertedSubjects[] = strtolower($subjectName);

                    // Insert new subject into the database
                    $insertStmt->execute([
                        ':subject_name' => $subjectName, 
                        ':department_name' => $department_name, 
                        ':faculty_name' => $faculty
                    ]);
                }

                // Check if the import was successful
                if ($insertSuccess) {
                    $response['status'] = 'success';
                    $response['message'] = 'Subjects imported successfully. Duplicates have been removed.';
                    $response['duplicates'] = $duplicates;
                } else {
                    $response['message'] = 'An error occurred while importing subjects.';
                }

            } else {
                $response['message'] = 'Failed to move uploaded file.';
            }
        } else {
            $response['message'] = 'Only Excel files are allowed.';
        }
    } else {
        $response['message'] = 'No file uploaded or missing department/faculty information.';
    }
} catch (Exception $e) {
    // Log any exceptions that occur
    $response['message'] = $e->getMessage();
}

$conn = null;

// Send the response as JSON
echo json_encode($response);
?>
