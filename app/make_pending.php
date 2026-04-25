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

    // Update the status from 'approved' to 'pending'
    $updateSql = "UPDATE teacher_subject_allocation tsa
    INNER JOIN classes c ON tsa.class_id = c.id
    INNER JOIN departments d ON c.department_id = d.id
    INNER JOIN subjects s ON tsa.subject_id = s.id
    SET tsa.status = 'pending'
    WHERE TRIM(LOWER(c.class_name)) = TRIM(LOWER(?)) 
    AND TRIM(LOWER(d.department_name)) = TRIM(LOWER(?)) 
    AND TRIM(LOWER(c.study_mode)) = TRIM(LOWER(?)) 
    AND TRIM(LOWER(s.subject_name)) = TRIM(LOWER(?))
    AND tsa.teacher_id = ?
    AND tsa.status = 'approved'";

    $updateStmt = $conn->prepare($updateSql);
    $result = $updateStmt->execute([$class_name, $department_name, $study_mode, $subject_name, $teacherAutoId]);

    if ($result && $updateStmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Class status changed from approved to pending successfully"]);
    } else {
        echo json_encode(["status" => "fail", "message" => "No approved class found to update or already pending"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "fail", "message" => "Database error: " . $e->getMessage()]);
}
?>