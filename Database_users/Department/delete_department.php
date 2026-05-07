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

// Get faculty information from session
$sessionInfo = getSessionInfo();
if (!$sessionInfo) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Session error - please login again']);
    ob_end_flush();
    exit();
}

$faculty_id = $sessionInfo['faculty_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    ob_end_flush();
    exit();
}

$departmentName = trim($_POST['department_name'] ?? '');

if (empty($departmentName)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Department name is required']);
    ob_end_flush();
    exit();
}

// Start transaction
$conn->beginTransaction();

try {
    // First verify the department belongs to this faculty
    $verifySql = "SELECT id FROM departments WHERE department_name = ? AND faculty_id = ?";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([$departmentName, $faculty_id]);
    
    if ($verifyStmt->rowCount() === 0) {
        throw new Exception("Department not found or access denied");
    }

    // Delete from related tables first (only if they exist)
    // Check which tables exist before trying to delete from them
    $tables_to_check = [
        'absents',
        'allocate_teacher_subject', 
        'subject_class',
        'subjects',
        'students'
    ];
    
    foreach ($tables_to_check as $table) {
        try {
            // Check if table exists
            $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
            if ($checkTable->rowCount() > 0) {
                // Table exists, safe to delete
                $sql = "DELETE FROM $table WHERE department_name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$departmentName]);
            }
        } catch (PDOException $e) {
            // Table doesn't exist or other error, continue with next table
            continue;
        }
    }

    // Finally delete from departments table
    $sql = "DELETE FROM departments WHERE department_name = ? AND faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$departmentName, $faculty_id]);

    // Commit transaction
    $conn->commit();
    
    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Department deleted successfully']);
} catch (Exception $e) {
    $conn->rollBack();
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

ob_end_flush();
?>