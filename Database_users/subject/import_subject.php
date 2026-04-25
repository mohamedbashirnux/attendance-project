<?php
// CSV ONLY IMPORT - Simple subject names import
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

include "../../Account_users/session_faculty.php";
include "../../connection/connect.php";

ob_clean();
header('Content-Type: application/json');

try {
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];
    $department_id = trim($_POST['department_id'] ?? '');

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    if (empty($department_id)) {
        throw new Exception("Department ID is required");
    }

    // Verify department belongs to this faculty
    $verify_sql = "SELECT id FROM departments WHERE id = ? AND faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$department_id, $faculty_id]);
    
    if ($verify_stmt->rowCount() === 0) {
        throw new Exception("Invalid department selected");
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Please select a valid file");
    }

    $uploadedFile = $_FILES['file'];
    $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

    // ONLY ACCEPT CSV
    if ($fileExtension !== 'csv') {
        throw new Exception("Only CSV files are allowed! Please save your Excel as CSV first (File → Save As → CSV UTF-8)");
    }

    // Read CSV file
    $handle = fopen($uploadedFile['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception("Could not open CSV file");
    }

    $successCount = 0;
    $errorCount = 0;
    $duplicates = [];
    $errors = [];
    $rowNumber = 0;

    while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $rowNumber++;
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        // Get subject name from first column and remove BOM
        $subject_name = trim(preg_replace('/^\x{FEFF}/u', '', $row[0] ?? ''));

        // Validate subject name
        if (empty($subject_name)) {
            $errors[] = "Row $rowNumber: Subject name is empty";
            $errorCount++;
            continue;
        }

        try {
            // Check if subject already exists in this department
            $check_sql = "SELECT id FROM subjects WHERE subject_name = ? AND department_id = ? AND faculty_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$subject_name, $department_id, $faculty_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $duplicates[] = $subject_name;
                $errorCount++;
                continue;
            }

            // Insert subject
            $insert_sql = "INSERT INTO subjects (faculty_id, department_id, subject_name) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            if ($insert_stmt->execute([$faculty_id, $department_id, $subject_name])) {
                $successCount++;
            } else {
                $errors[] = "Row $rowNumber: Failed to insert subject '$subject_name'";
                $errorCount++;
            }

        } catch (PDOException $e) {
            $errors[] = "Row $rowNumber: Database error - " . $e->getMessage();
            $errorCount++;
        }
    }
    fclose($handle);

    // Prepare response message
    $message = "";
    if ($successCount > 0) {
        $message = "$successCount subject" . ($successCount > 1 ? "s" : "") . " imported successfully";
    }
    if ($errorCount > 0) {
        if ($successCount > 0) {
            $message .= ". ";
        }
        $message .= "$errorCount skipped";
        if (count($duplicates) > 0) {
            $message .= " (" . count($duplicates) . " duplicate" . (count($duplicates) > 1 ? "s" : "") . ")";
        }
    }
    if (empty($message)) {
        $message = "No subjects imported";
    }

    ob_clean();
    echo json_encode([
        "status" => $successCount > 0 ? "success" : "error",
        "message" => $message,
        "success_count" => $successCount,
        "error_count" => $errorCount,
        "duplicates" => $duplicates,
        "errors" => $errors
    ]);
    exit();

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit();
} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit();
} catch (Error $e) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "System error: " . $e->getMessage()
    ]);
    exit();
}
?>