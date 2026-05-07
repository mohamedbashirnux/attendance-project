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

// Set content type to JSON
header('Content-Type: application/json');

// Get faculty information from session
$sessionInfo = getSessionInfo();
if (!$sessionInfo) {
    echo json_encode([
        'error' => 'Session error',
        'departments' => [],
        'total_pages' => 0,
        'current_page' => 1
    ]);
    exit();
}

$faculty_id = $sessionInfo['faculty_id'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$offset = ($page - 1) * $per_page;

try {
    // Check if this is a dropdown request
    if (isset($_GET['dropdown']) && $_GET['dropdown'] === 'true') {
        // Return departments for dropdown
        $sql = "SELECT id, department_name FROM departments WHERE faculty_id = ? ORDER BY department_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$faculty_id]);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'departments' => $departments
        ]);
        exit();
    }

    // Count total records for this faculty
    $sql = "SELECT COUNT(*) as total FROM departments WHERE faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$faculty_id]);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Fetch departments for the current page
    $sql = "SELECT d.id, d.department_name, f.faculty_name 
            FROM departments d 
            JOIN faculty f ON d.faculty_id = f.id 
            WHERE d.faculty_id = ? 
            ORDER BY d.department_name 
            LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $faculty_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->bindParam(3, $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clear any remaining output buffer content
    ob_clean();
    
    echo json_encode([
        'departments' => $departments,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
} catch (PDOException $e) {
    // Clear any remaining output buffer content
    ob_clean();
    
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'departments' => [],
        'total_pages' => 0,
        'current_page' => 1
    ]);
}

// End output buffering
ob_end_flush();
?>
