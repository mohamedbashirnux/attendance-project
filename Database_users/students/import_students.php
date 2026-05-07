<?php
// CSV ONLY IMPORT - NO EXCEL LIBRARY, NO HTML GARBAGE
date_default_timezone_set('Africa/Mogadishu');
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
    $class_id = trim($_POST['class_id'] ?? '');

    if (empty($class_id)) {
        throw new Exception("Class ID is required");
    }

    // Verify class
    $verify_stmt = $conn->prepare("SELECT id, class_name FROM classes WHERE id = ? AND faculty_id = ?");
    $verify_stmt->execute([$class_id, $faculty_id]);
    $class_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_data) {
        throw new Exception("Access denied - class not found");
    }
    
    $class_name = $class_data['class_name'];

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Please select a valid file");
    }

    $file = $_FILES['excel_file'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // ONLY ACCEPT CSV
    if ($file_extension !== 'csv') {
        throw new Exception("Only CSV files are allowed! Please save your Excel as CSV first (File → Save As → CSV UTF-8)");
    }

    $imported_count = 0;
    $skipped_count = 0;
    $duplicate_ids = [];
    $processed_ids = [];

    // Read CSV file with UTF-8 BOM handling
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception("Could not open CSV file");
    }

    $row_number = 0;
    while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $row_number++;
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        try {
            // Expected: Column 0 = Student ID, Column 1 = Name, Column 2 = Phone
            // Remove BOM and trim whitespace
            $student_id = trim(preg_replace('/^\x{FEFF}/u', '', $row[0] ?? ''));
            $full_name = trim(preg_replace('/^\x{FEFF}/u', '', $row[1] ?? ''));
            $phone = trim($row[2] ?? '');
            $password = $class_name;

            // Validate required fields
            if (empty($student_id) || empty($full_name) || empty($phone)) {
                $skipped_count++;
                $duplicate_ids[] = "Row {$row_number}: Missing required fields";
                continue;
            }

            // Check for duplicates in file
            if (in_array($student_id, $processed_ids)) {
                $skipped_count++;
                $duplicate_ids[] = "Student ID {$student_id} (duplicate in file)";
                continue;
            }

            // Check if exists in database
            $check_stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
            $check_stmt->execute([$student_id]);
            
            if ($check_stmt->fetch()) {
                $skipped_count++;
                $duplicate_ids[] = "Student ID {$student_id} (already exists)";
                continue;
            }

            // Insert student
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO students (student_id, class_id, full_name, phone, password, status) VALUES (?, ?, ?, ?, ?, 'approved')");
            
            if ($insert_stmt->execute([$student_id, $class_id, $full_name, $phone, $hashed_password])) {
                $imported_count++;
                $processed_ids[] = $student_id;
            } else {
                $skipped_count++;
                $duplicate_ids[] = "Row {$row_number}: Database error";
            }

        } catch (Exception $e) {
            $skipped_count++;
            $duplicate_ids[] = "Row {$row_number}: " . $e->getMessage();
        }
    }
    fclose($handle);

    $message = "Import completed! ";
    if ($imported_count > 0) {
        $message .= "{$imported_count} students imported successfully. ";
    }
    if ($skipped_count > 0) {
        $message .= "{$skipped_count} students skipped.";
    }

    ob_clean();
    echo json_encode([
        "success" => true,
        "message" => $message,
        "imported_count" => $imported_count,
        "skipped_count" => $skipped_count,
        "duplicate_ids" => $duplicate_ids
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

ob_end_flush();
?>
