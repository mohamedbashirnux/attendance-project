<?php
// Suppress PHP warnings to ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Set content type to JSON first
header('Content-Type: application/json; charset=utf-8');

// Include the faculty session management
include "../../Account_users/session_faculty.php";

// Include database connection
include "../../connection/connect.php";

// Clear any unexpected output from includes
ob_clean();

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

    $subject_ids = $_POST['subject_ids'] ?? [];
    $class_id = trim($_POST['class_id'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');

    // Validate input
    if (empty($subject_ids) || empty($class_id) || empty($department_id)) {
        throw new Exception("Subjects, class, and department are required");
    }

    if (!is_array($subject_ids)) {
        throw new Exception("Invalid subject selection");
    }

    // Verify class belongs to this faculty
    $verify_class_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $verify_class_stmt = $conn->prepare($verify_class_sql);
    $verify_class_stmt->execute([$class_id, $faculty_id]);
    
    if ($verify_class_stmt->rowCount() === 0) {
        throw new Exception("Invalid class selected");
    }

    $assigned_count = 0;
    $already_assigned_count = 0;
    $errors = [];

    foreach ($subject_ids as $subject_id) {
        try {
            // Verify subject belongs to this faculty and department
            $verify_subject_sql = "SELECT id FROM subjects WHERE id = ? AND faculty_id = ? AND department_id = ?";
            $verify_subject_stmt = $conn->prepare($verify_subject_sql);
            $verify_subject_stmt->execute([$subject_id, $faculty_id, $department_id]);
            
            if ($verify_subject_stmt->rowCount() === 0) {
                $errors[] = "Invalid subject ID: $subject_id";
                continue;
            }

            // Check if assignment already exists
            $check_sql = "SELECT id FROM subject_class WHERE subject_id = ? AND class_id = ? AND faculty_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$subject_id, $class_id, $faculty_id]);

            if ($check_stmt->rowCount() > 0) {
                $already_assigned_count++;
                continue;
            }

            // Insert new assignment
            $insert_sql = "INSERT INTO subject_class (faculty_id, subject_id, class_id) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);

            if ($insert_stmt->execute([$faculty_id, $subject_id, $class_id])) {
                $assigned_count++;
            } else {
                $errorInfo = $insert_stmt->errorInfo();
                $errors[] = "Failed to assign subject ID $subject_id: " . $errorInfo[2];
            }

        } catch (PDOException $e) {
            $errors[] = "Database error for subject ID $subject_id: " . $e->getMessage();
        }
    }

    // Prepare response
    if ($assigned_count > 0 || $already_assigned_count > 0) {
        $message = "";
        if ($assigned_count > 0) {
            $message .= "$assigned_count subject(s) assigned successfully";
        }
        if ($already_assigned_count > 0) {
            if ($assigned_count > 0) $message .= ". ";
            $message .= "$already_assigned_count subject(s) were already assigned";
        }
        
        ob_clean();
        echo json_encode([
            "success" => true, 
            "message" => $message,
            "assigned_count" => $assigned_count,
            "already_assigned_count" => $already_assigned_count,
            "errors" => $errors
        ]);
    } else {
        ob_clean();
        echo json_encode([
            "success" => false, 
            "message" => "No subjects were assigned. " . implode(", ", $errors)
        ]);
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