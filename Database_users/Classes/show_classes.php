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

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    // Check if this is a dropdown request
    if (isset($_GET['dropdown']) && $_GET['dropdown'] === 'true') {
        $department_id = $_GET['department_id'] ?? '';
        
        if (empty($department_id)) {
            echo json_encode(['classes' => []]);
            exit();
        }
        
        // Return classes for dropdown
        $sql = "SELECT c.id, c.class_name, c.study_mode, c.semester, c.academic_year 
                FROM classes c 
                JOIN departments d ON c.department_id = d.id 
                WHERE c.department_id = ? AND d.faculty_id = ? 
                ORDER BY c.class_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$department_id, $faculty_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'classes' => $classes
        ]);
        exit();
    }

    // Check if this is a request for form data (departments + ENUM values)
    if (isset($_GET['action']) && $_GET['action'] === 'form_data') {
        // Fetch departments for this faculty
        $dept_sql = "SELECT id, department_name FROM departments WHERE faculty_id = ? ORDER BY department_name";
        $dept_stmt = $conn->prepare($dept_sql);
        $dept_stmt->execute([$faculty_id]);
        $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get ENUM values for study_mode
        $study_mode_query = "SHOW COLUMNS FROM classes LIKE 'study_mode'";
        $study_mode_result = $conn->query($study_mode_query);
        $study_mode_row = $study_mode_result->fetch(PDO::FETCH_ASSOC);
        preg_match_all("/'([^']+)'/", $study_mode_row['Type'], $study_mode_matches);
        $study_modes = $study_mode_matches[1];

        // Get ENUM values for semester
        $semester_query = "SHOW COLUMNS FROM classes LIKE 'semester'";
        $semester_result = $conn->query($semester_query);
        $semester_row = $semester_result->fetch(PDO::FETCH_ASSOC);
        preg_match_all("/'([^']+)'/", $semester_row['Type'], $semester_matches);
        $semesters = $semester_matches[1];

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'departments' => $departments,
            'study_modes' => $study_modes,
            'semesters' => $semesters
        ]);
        ob_end_flush();
        exit();
    }

    // Check if this is a request for department counts
    if (isset($_GET['action']) && $_GET['action'] === 'department_counts') {
        $count_sql = "SELECT department_id, COUNT(*) as count 
                      FROM classes 
                      WHERE faculty_id = ? 
                      GROUP BY department_id";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->execute([$faculty_id]);
        $counts_result = $count_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($counts_result as $row) {
            $counts[$row['department_id']] = (int)$row['count'];
        }
        
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'counts' => $counts
        ]);
        ob_end_flush();
        exit();
    }

    // Default behavior: fetch classes with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $offset = ($page - 1) * $per_page;

    // Check if filtering by department
    $department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;

    // Build the WHERE clause based on filters
    $where_conditions = ["c.faculty_id = ?"];
    $params = [$faculty_id];

    if ($department_id) {
        $where_conditions[] = "c.department_id = ?";
        $params[] = $department_id;
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Count total records for this faculty (with optional department filter)
    $count_sql = "SELECT COUNT(*) as total FROM classes c 
                  JOIN departments d ON c.department_id = d.id 
                  WHERE " . $where_clause;
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Fetch classes for the current page (with optional department filter)
    $sql = "SELECT c.id, c.class_name, c.study_mode, c.semester, c.academic_year, d.department_name 
            FROM classes c 
            JOIN departments d ON c.department_id = d.id 
            WHERE " . $where_clause . " 
            ORDER BY d.department_name, c.class_name 
            LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    
    // Bind all parameters
    $param_index = 1;
    foreach ($params as $param) {
        $stmt->bindValue($param_index++, $param, PDO::PARAM_INT);
    }
    $stmt->bindParam($param_index++, $offset, PDO::PARAM_INT);
    $stmt->bindParam($param_index, $per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log the query and results (remove this in production)
    error_log("Faculty ID: " . $faculty_id);
    error_log("Classes found: " . count($classes));

    // Format the response to match the expected structure
    $formatted_classes = [];
    foreach ($classes as $class) {
        $formatted_classes[] = [
            'id' => $class['id'],
            'department_name' => $class['department_name'],
            'class_name' => $class['class_name'],
            'study_mode' => $class['study_mode'],
            'semester' => $class['semester'],
            'academic' => $class['academic_year']
        ];
    }

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'classes' => $formatted_classes,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_records' => $total_records
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'classes' => [],
        'total_pages' => 0,
        'current_page' => 1,
        'total_records' => 0
    ]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error: ' . $e->getMessage(),
        'classes' => [],
        'total_pages' => 0,
        'current_page' => 1,
        'total_records' => 0
    ]);
}

ob_end_flush();
?>