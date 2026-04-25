<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "conn.php";

// Get the class, department, study mode, subject name, and teacher_id from the request
$class_name = trim(html_entity_decode(filterRequest('class_name'), ENT_QUOTES, 'UTF-8'));
$department_name = trim(html_entity_decode(filterRequest('department_name'), ENT_QUOTES, 'UTF-8'));
$study_mode = trim(html_entity_decode(filterRequest('study_mode'), ENT_QUOTES, 'UTF-8'));
$subject_name = trim(html_entity_decode(filterRequest('subject_name'), ENT_QUOTES, 'UTF-8'));
$teacher_id = trim(filterRequest('teacher_id'));

// Validate required fields
if (empty($class_name) || empty($department_name) || empty($study_mode) || empty($subject_name) || empty($teacher_id)) {
    echo json_encode(["status" => "fail", "message" => "All fields including teacher_id are required"]);
    exit;
}

try {
    // First, get the teacher's auto-increment ID from the teacher_id (varchar)
    $teacherAutoIdSql = "SELECT id FROM Teachers WHERE teacher_id = ?";
    $teacherStmt = $conn->prepare($teacherAutoIdSql);
    $teacherStmt->execute([$teacher_id]);
    $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        echo json_encode(["status" => "fail", "message" => "Teacher not found with teacher_id: " . $teacher_id]);
        exit;
    }
    
    $teacherAutoId = $teacher['id'];
    
    // Query to get the status
    $sql = "SELECT tsa.status 
            FROM teacher_subject_allocation tsa
            INNER JOIN classes c ON tsa.class_id = c.id
            INNER JOIN departments d ON c.department_id = d.id
            INNER JOIN subjects s ON tsa.subject_id = s.id
            WHERE TRIM(LOWER(c.class_name)) = TRIM(LOWER(?)) 
            AND TRIM(LOWER(d.department_name)) = TRIM(LOWER(?)) 
            AND TRIM(LOWER(c.study_mode)) = TRIM(LOWER(?)) 
            AND TRIM(LOWER(s.subject_name)) = TRIM(LOWER(?))
            AND tsa.teacher_id = ?
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$class_name, $department_name, $study_mode, $subject_name, $teacherAutoId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $current_status = $result['status'];
        
        // Return normal status without cleanup
        echo json_encode([
            "status" => "success", 
            "class_status" => $current_status,
            "message" => "Status retrieved successfully"
        ]);
    } else {
        // Debug query to see what's available
        $debugSql = "SELECT c.class_name, d.department_name, c.study_mode, s.subject_name, 
                            tsa.status, t.teacher_id as teacher_varchar_id
                     FROM teacher_subject_allocation tsa
                     INNER JOIN classes c ON tsa.class_id = c.id
                     INNER JOIN departments d ON c.department_id = d.id
                     INNER JOIN subjects s ON tsa.subject_id = s.id
                     INNER JOIN Teachers t ON tsa.teacher_id = t.id
                     WHERE tsa.teacher_id = ?";
        
        $debugStmt = $conn->prepare($debugSql);
        $debugStmt->execute([$teacherAutoId]);
        $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "fail", 
            "message" => "No allocation found for the specified criteria and teacher",
            "debug_info" => [
                "teacher_auto_id" => $teacherAutoId,
                "searching_for" => [
                    "class_name" => $class_name,
                    "department_name" => $department_name,
                    "study_mode" => $study_mode,
                    "subject_name" => $subject_name
                ],
                "available_allocations" => $debugResults
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(["status" => "fail", "message" => "Database error: " . $e->getMessage()]);
}
?>
