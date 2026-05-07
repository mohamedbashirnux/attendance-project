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

$departmentName = trim($_POST['editDepartmentName'] ?? '');
$originalDepartmentName = trim($_POST['originalDepartmentName'] ?? '');

// Validate input
if (empty($departmentName) || empty($originalDepartmentName)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Department name is required']);
    ob_end_flush();
    exit();
}

// Begin transaction
$conn->beginTransaction();

try {
    // First, get the department ID
    $getDeptSql = "SELECT id FROM departments WHERE department_name = ? AND faculty_id = ?";
    $getDeptStmt = $conn->prepare($getDeptSql);
    $getDeptStmt->execute([$originalDepartmentName, $faculty_id]);
    $deptResult = $getDeptStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$deptResult) {
        throw new Exception("Department not found");
    }
    
    $department_id = $deptResult['id'];

    // Check if new department name already exists (excluding current department)
    $checkSql = "SELECT id FROM departments WHERE department_name = ? AND faculty_id = ? AND id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$departmentName, $faculty_id, $department_id]);
    
    if ($checkStmt->rowCount() > 0) {
        throw new Exception("Department name already exists");
    }

    // Update department in departments table
    $sql = "UPDATE departments SET department_name = ? WHERE id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$departmentName, $department_id, $faculty_id]);

    // Update department in related tables (only if they exist)
    $tables_to_update = [
        'students',
        'allocate_teacher_subject',
        'subjects', 
        'subject_class',
        'absents'
    ];
    
    foreach ($tables_to_update as $table) {
        try {
            // Check if table exists
            $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
            if ($checkTable->rowCount() > 0) {
                // Table exists, safe to update
                $sqlUpdate = "UPDATE $table SET department_name = ? WHERE department_name = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->execute([$departmentName, $originalDepartmentName]);
            }
        } catch (PDOException $e) {
            // Table doesn't exist or other error, continue with next table
            continue;
        }
    }

    // Commit transaction
    $conn->commit();
    
    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Department updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

ob_end_flush();
?>
