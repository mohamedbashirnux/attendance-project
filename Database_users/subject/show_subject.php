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

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    // Check if this is a request for a specific subject (for editing)
    if (isset($_GET['id'])) {
        header('Content-Type: application/json');
        $subject_id = $_GET['id'];
        
        $sql = "SELECT s.id, s.subject_name, d.department_name, f.faculty_name 
                FROM subjects s 
                JOIN departments d ON s.department_id = d.id 
                JOIN faculty f ON s.faculty_id = f.id 
                WHERE s.id = ? AND s.faculty_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$subject_id, $faculty_id]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subject) {
            echo json_encode($subject);
        } else {
            echo json_encode(null);
        }
        exit();
    }

    // Check if this is a request for subject count
    if (isset($_GET['action']) && $_GET['action'] === 'count') {
        header('Content-Type: application/json');
        $department_id = $_GET['department_id'] ?? '';
        
        if ($department_id) {
            $count_sql = "SELECT COUNT(*) as total FROM subjects WHERE faculty_id = ? AND department_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->execute([$faculty_id, $department_id]);
        } else {
            $count_sql = "SELECT COUNT(*) as total FROM subjects WHERE faculty_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->execute([$faculty_id]);
        }
        
        $total = $count_stmt->fetchColumn();
        echo json_encode(['total' => $total]);
        exit();
    }

    // Default behavior: show subjects for this faculty
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $department_id = $_GET['department_id'] ?? '';
    
    $sql = "SELECT s.id, s.subject_name, d.department_name, f.faculty_name 
            FROM subjects s 
            JOIN departments d ON s.department_id = d.id 
            JOIN faculty f ON s.faculty_id = f.id 
            WHERE s.faculty_id = ?";
    
    $params = [$faculty_id];
    
    if ($department_id) {
        $sql .= " AND s.department_id = ?";
        $params[] = $department_id;
    }
    
    if (!empty($search)) {
        $sql .= " AND s.subject_name LIKE ?";
        $searchParam = "%$search%";
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY d.department_name, s.subject_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML table rows
    if (count($subjects) > 0) {
        foreach ($subjects as $subject) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($subject['subject_name']) . "</td>";
            echo "<td>" . htmlspecialchars($subject['department_name']) . "</td>";
            echo "<td>" . htmlspecialchars($subject['faculty_name']) . "</td>";
            echo "<td class='text-end'>";
            echo "<button class='btn btn-sm btn-warning me-1 edit-btn' data-id='" . $subject['id'] . "' data-subject-name='" . htmlspecialchars($subject['subject_name']) . "' data-department-name='" . htmlspecialchars($subject['department_name']) . "' data-faculty-name='" . htmlspecialchars($subject['faculty_name']) . "'>Edit</button>";
            echo "<button class='btn btn-sm btn-danger delete-btn' data-id='" . $subject['id'] . "' data-subject-name='" . htmlspecialchars($subject['subject_name']) . "'>Delete</button>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4' class='text-center'>No subjects found.</td></tr>";
    }

} catch (Exception $e) {
    echo "<tr><td colspan='3'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

ob_end_flush();
?>