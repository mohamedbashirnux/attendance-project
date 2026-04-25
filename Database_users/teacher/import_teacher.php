<?php
// Suppress PHP warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Include the faculty session management
include "../../Account_users/session_faculty.php";

// Include database connection
include "../../connection/connect.php";

// Include the SpreadsheetReader library
require_once '../../library/SpreadsheetReader.php';

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

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Please select a valid Excel file");
    }

    $uploadedFile = $_FILES['file'];
    $fileName = $uploadedFile['name'];
    $fileTmpName = $uploadedFile['tmp_name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate file extension
    if (!in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
        throw new Exception("Invalid file format. Please upload Excel (.xlsx, .xls) or CSV file");
    }

    // Read the spreadsheet
    $reader = new SpreadsheetReader($fileTmpName);
    $sheets = $reader->Sheets();
    
    if (empty($sheets)) {
        throw new Exception("No sheets found in the uploaded file");
    }

    // Use the first sheet
    $reader->ChangeSheet(0);
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $rowNumber = 0;

    foreach ($reader as $row) {
        $rowNumber++;
        
        // Skip header row
        if ($rowNumber === 1) {
            continue;
        }

        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        // Validate row has required columns (now only 4: ID, Name, Username, Password)
        if (count($row) < 4) {
            $errors[] = "Row $rowNumber: Missing required columns";
            $errorCount++;
            continue;
        }

        $teacher_id = trim($row[0] ?? '');
        $full_name = trim($row[1] ?? '');
        $username = trim($row[2] ?? '');
        $password = trim($row[3] ?? '');

        // Validate required fields
        if (empty($teacher_id) || empty($full_name) || empty($username) || empty($password)) {
            $errors[] = "Row $rowNumber: Missing required data";
            $errorCount++;
            continue;
        }

        try {
            // Check if teacher already exists
            $check_sql = "SELECT id FROM teachers WHERE id = ? OR username = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$teacher_id, $username]);
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = "Row $rowNumber: Teacher ID '$teacher_id' or username '$username' already exists";
                $errorCount++;
                continue;
            }

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert teacher (removed department_id)
            $insert_sql = "INSERT INTO teachers (id, faculty_id, full_name, username, password) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            if ($insert_stmt->execute([$teacher_id, $faculty_id, $full_name, $username, $hashed_password])) {
                $successCount++;
            } else {
                $errors[] = "Row $rowNumber: Failed to insert teacher";
                $errorCount++;
            }

        } catch (PDOException $e) {
            $errors[] = "Row $rowNumber: Database error - " . $e->getMessage();
            $errorCount++;
        }
    }

    // Prepare response message
    $message = "Import completed: $successCount teachers added successfully";
    if ($errorCount > 0) {
        $message .= ", $errorCount errors occurred";
        if (count($errors) <= 5) {
            $message .= ": " . implode("; ", $errors);
        } else {
            $message .= ". First 5 errors: " . implode("; ", array_slice($errors, 0, 5));
        }
    }

    ob_clean();
    echo json_encode([
        "status" => $successCount > 0 ? "success" : "error",
        "message" => $message,
        "success_count" => $successCount,
        "error_count" => $errorCount
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}

ob_end_flush();
?>