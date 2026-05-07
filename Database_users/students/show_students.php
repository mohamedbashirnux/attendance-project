<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Suppress PHP warnings to ensure clean output
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
    $class_id = $_GET['class_id'] ?? '';
    $search = trim($_GET['search'] ?? '');
    
    if (empty($class_id)) {
        throw new Exception("Class ID is required");
    }

    // Check if this is a request for student count only
    if (isset($_GET['action']) && $_GET['action'] === 'count') {
        header('Content-Type: application/json');
        
        // Verify that the class belongs to this faculty
        $verify_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->execute([$class_id, $faculty_id]);
        
        if (!$verify_stmt->fetch()) {
            throw new Exception("Access denied - class not found or doesn't belong to your faculty");
        }

        // Get total count of students in this class
        $count_sql = "SELECT COUNT(*) as total FROM students WHERE class_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->execute([$class_id]);
        $total = $count_stmt->fetchColumn();
        
        echo json_encode(['total' => $total]);
        exit();
    }

    // Verify that the class belongs to this faculty
    $verify_sql = "SELECT id FROM classes WHERE id = ? AND faculty_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->execute([$class_id, $faculty_id]);
    
    if (!$verify_stmt->fetch()) {
        throw new Exception("Access denied - class not found or doesn't belong to your faculty");
    }

    // Get students for this class
    $sql = "SELECT s.id, s.student_id, s.full_name, s.phone, s.status, s.created_at
            FROM students s 
            WHERE s.class_id = ?";
    
    $params = [$class_id];
    
    // Add search functionality
    if (!empty($search)) {
        $sql .= " AND (s.student_id LIKE ? OR s.full_name LIKE ? OR s.phone LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY s.full_name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML table rows
    if (count($students) > 0) {
        $row_number = 1;
        foreach ($students as $student) {
            // Determine status badge
            $statusBadge = $student['status'] === 'approved' ? 'bg-label-success' : 'bg-label-warning';
            
            echo "<tr>";
            echo "<td>" . $row_number . "</td>";
            echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['phone']) . "</td>";
            echo "<td>••••••••</td>"; // Show masked password
            echo "<td>";
            echo "<span class='badge $statusBadge status-btn' style='cursor: pointer;' data-id='" . $student['id'] . "' data-status='" . $student['status'] . "' data-student-name='" . htmlspecialchars($student['full_name']) . "'>" . ucfirst($student['status']) . "</span>";
            echo "</td>";
            echo "<td class='text-end'>";
            
            // Direct action buttons
            echo "<button class='btn btn-sm btn-info me-1 transfer-btn' data-id='" . $student['id'] . "' data-student-id='" . htmlspecialchars($student['student_id']) . "' data-full-name='" . htmlspecialchars($student['full_name']) . "' title='Transfer Student'><i class='bx bx-transfer'></i></button>";
            echo "<button class='btn btn-sm btn-warning me-1 edit-btn' data-id='" . $student['id'] . "' data-student-id='" . htmlspecialchars($student['student_id']) . "' data-full-name='" . htmlspecialchars($student['full_name']) . "' data-phone='" . htmlspecialchars($student['phone'] ?? '') . "' data-status='" . $student['status'] . "'><i class='bx bx-edit'></i></button>";
            echo "<button class='btn btn-sm btn-danger delete-btn' data-id='" . $student['id'] . "' data-full-name='" . htmlspecialchars($student['full_name']) . "' data-student-id='" . htmlspecialchars($student['student_id']) . "'><i class='bx bx-trash'></i></button>";
            
            echo "</td>";
            echo "</tr>";
            
            $row_number++;
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>No students found in this class.</td></tr>";
    }

} catch (Exception $e) {
    echo "<tr><td colspan='7'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

ob_end_flush();
?>