<?php
// Suppress PHP warnings to ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

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

    // Check if this is a request for assignment count
    if (isset($_GET['action']) && $_GET['action'] === 'count') {
        header('Content-Type: application/json');
        $class_id = $_GET['class_id'] ?? '';
        $faculty_id = $_GET['faculty_id'] ?? '';
        
        if ($class_id && $faculty_id) {
            $count_sql = "SELECT COUNT(*) as total FROM subject_class WHERE class_id = ? AND faculty_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->execute([$class_id, $faculty_id]);
        } else {
            $count_sql = "SELECT COUNT(*) as total FROM subject_class WHERE faculty_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->execute([$faculty_id]);
        }
        
        $total = $count_stmt->fetchColumn();
        echo json_encode(['total' => $total]);
        exit();
    }

    // Default behavior: show assignments for this faculty and class
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $class_id = $_GET['class_id'] ?? '';
    $faculty_id_param = $_GET['faculty_id'] ?? '';
    
    $sql = "SELECT sc.id, s.subject_name, d.department_name, sc.created_at
            FROM subject_class sc 
            JOIN subjects s ON sc.subject_id = s.id 
            JOIN departments d ON s.department_id = d.id 
            WHERE sc.faculty_id = ?";
    
    $params = [$faculty_id];
    
    if ($class_id) {
        $sql .= " AND sc.class_id = ?";
        $params[] = $class_id;
    }
    
    if (!empty($search)) {
        $sql .= " AND s.subject_name LIKE ?";
        $searchParam = "%$search%";
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY s.subject_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML table rows
    if (count($assignments) > 0) {
        foreach ($assignments as $assignment) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($assignment['subject_name']) . "</td>";
            echo "<td>" . htmlspecialchars($assignment['department_name']) . "</td>";
            echo "<td>" . date('Y-m-d', strtotime($assignment['created_at'])) . "</td>";
            echo "<td class='text-end'>";
            echo "<button class='btn btn-sm btn-danger remove-btn' data-id='" . $assignment['id'] . "' data-subject-name='" . htmlspecialchars($assignment['subject_name']) . "'>Remove</button>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4' class='text-center'>No assignments found.</td></tr>";
    }

} catch (Exception $e) {
    echo "<tr><td colspan='4'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

ob_end_flush();
?>