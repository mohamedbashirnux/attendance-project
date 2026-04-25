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

    // Check if this is a request for a specific teacher (for editing)
    if (isset($_GET['id'])) {
        header('Content-Type: application/json');
        $teacher_id = $_GET['id'];
        
        $sql = "SELECT t.id, t.teacher_id, t.full_name, t.username, t.password, f.faculty_name 
                FROM teachers t 
                JOIN faculty f ON t.faculty_id = f.id 
                WHERE t.teacher_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            // Format response to match expected structure
            $response = [
                'auto_id' => $teacher['id'],  // Auto-increment ID for allocations
                'tid' => $teacher['teacher_id'],  // Teacher ID for display
                'teacher_name' => $teacher['full_name'],
                'username' => $teacher['username'],
                'password' => $teacher['password'], // Note: In production, don't return passwords
                'faculty_name' => $teacher['faculty_name']
            ];
            echo json_encode($response);
        } else {
            echo json_encode(null);
        }
        exit();
    }

    // Default behavior: show all teachers (HTML table rows)
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $sql = "SELECT t.id, t.teacher_id, t.full_name, t.username, t.password, f.faculty_name 
            FROM teachers t 
            JOIN faculty f ON t.faculty_id = f.id";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " WHERE t.teacher_id LIKE ? OR t.full_name LIKE ? OR t.username LIKE ? OR f.faculty_name LIKE ?";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    $sql .= " ORDER BY t.teacher_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML table rows
    if (count($teachers) > 0) {
        foreach ($teachers as $teacher) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($teacher['teacher_id']) . "</td>";
            echo "<td>" . htmlspecialchars($teacher['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($teacher['username']) . "</td>";
            echo "<td>••••••••</td>"; // Hide password for security
            echo "<td>";
            echo "<button class='btn btn-sm btn-warning me-1' onclick='openEditModal(\"" . htmlspecialchars($teacher['teacher_id']) . "\")'>Edit</button>";
            echo "<button class='btn btn-sm btn-danger' onclick='deleteTeacher(\"" . htmlspecialchars($teacher['teacher_id']) . "\")'>Delete</button>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No teachers found.</td></tr>";
    }

} catch (Exception $e) {
    echo "<tr><td colspan='5'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

ob_end_flush();
?>