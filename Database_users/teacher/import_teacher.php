<?php
include "../../connection/connect.php";

require('../../library/php-excel-reader/excel_reader2.php');
require('../../library/SpreadsheetReader.php');

$response = ['status' => 'error', 'message' => ''];

try {
    if (isset($_FILES['file'])) {
        $allowedMimes = [
            'application/vnd.ms-excel', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/xls', 
            'text/xlsx', 
            'application/vnd.oasis.opendocument.spreadsheet'
        ];

        $fileType = $_FILES["file"]["type"];

        if (in_array($fileType, $allowedMimes)) {
            $uploadFilePath = '../../uploads/' . basename($_FILES['file']['name']);
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFilePath)) {
                $Reader = new SpreadsheetReader($uploadFilePath);

                $Reader->ChangeSheet(0);
                $count = 0;
                $errors = [];
                $insertSuccess = true;

                // Prepare statements for duplicate check and insertion
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM teachertable WHERE tid = :tid");
                $insertStmt = $conn->prepare("INSERT INTO teachertable (tid, teacher_name, username, password) VALUES (:tid, :teacher_name, :username, :password)");

                foreach ($Reader as $Row) {
                    $count++;
                    // skips titles from excel file while inserting
                    if (count($Row) < 4) {
                        $errors[] = "Incomplete data at row $count";
                        continue; // Skip if row data is incomplete
                    }

                    $tid = isset($Row[0]) ? $Row[0] : '';
                    $teacherName = isset($Row[1]) ? $Row[1] : '';
                    $username = isset($Row[2]) ? $Row[2] : '';
                    $password = isset($Row[3]) ? $Row[3] : '';

                    // Check if teacher already exists
                    $checkStmt->bindParam(':tid', $tid, PDO::PARAM_STR);
                    $checkStmt->execute();
                    $countExists = $checkStmt->fetchColumn();

                    if ($countExists > 0) {
                        $errors[] = "Teacher with ID '$tid' already exists.";
                        $insertSuccess = false;
                        continue; // Skip to next row
                    }

                    // Insert new teacher
                    $insertStmt->bindParam(':tid', $tid, PDO::PARAM_STR);
                    $insertStmt->bindParam(':teacher_name', $teacherName, PDO::PARAM_STR);
                    $insertStmt->bindParam(':username', $username, PDO::PARAM_STR);
                    $insertStmt->bindParam(':password', $password, PDO::PARAM_STR);

                    if (!$insertStmt->execute()) {
                        $errors[] = "Failed to insert row $count: " . $insertStmt->errorInfo()[2];
                    }
                }

                if ($insertSuccess && empty($errors)) {
                    $response['status'] = 'success';
                    $response['message'] = 'Teachers imported successfully';
                } else {
                    $response['message'] = implode(', ', $errors);
                }

            } else {
                $response['message'] = 'Failed to move uploaded file.';
            }
        } else {
            $response['message'] = 'Only Excel files are allowed!';
        }
    } else {
        $response['message'] = 'No file uploaded.';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn = null;

echo json_encode($response);
?>
