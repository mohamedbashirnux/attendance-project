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

// Clear any unexpected output from includes
ob_clean();

header('Content-Type: application/json');

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $class_id = trim($_POST['id'] ?? '');

    if (empty($class_id)) {
        throw new Exception("Class ID is required");
    }

    // Start transaction
    $conn->beginTransaction();

    // First verify the class belongs to this faculty
    $verify_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$class_id, $faculty_id]);
    
    if ($verify_stmt->rowCount() === 0) {
        throw new Exception("Class not found or access denied");
    }

    // Delete from related tables first (only if they exist)
    $tables_to_check = [
        'absents',
        'allocate_teacher_subject',
        'subject_class',
        'students'
    ];
    
    foreach ($tables_to_check as $table) {
        try {
            // Check if table exists
            $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
            if ($checkTable->rowCount() > 0) {
                // Check if table has class_id column
                $checkColumn = $conn->query("SHOW COLUMNS FROM $table LIKE 'class_id'");
                if ($checkColumn->rowCount() > 0) {
                    // Table exists and has class_id column, safe to delete
                    $sql = "DELETE FROM $table WHERE class_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$class_id]);
                }
            }
        } catch (PDOException $e) {
            // Table doesn't exist or other error, continue with next table
            continue;
        }
    }

    // Finally delete from classes table
    $sql = "DELETE FROM classes WHERE id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$class_id, $faculty_id]);

    // Commit transaction
    $conn->commit();
    
    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Class deleted successfully']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

ob_end_flush();
?>