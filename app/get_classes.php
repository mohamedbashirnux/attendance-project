<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

$teacherId = filterRequest('teacher_id');

if (empty($teacherId)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Teacher ID is required"
    ]);
    exit;
}

// First, get the teacher's auto-increment ID from the teacher_id (varchar)
$teacherAutoIdSql = "SELECT id FROM Teachers WHERE teacher_id = ?";

try {
    $teacherStmt = $conn->prepare($teacherAutoIdSql);
    $teacherStmt->execute([$teacherId]);
    $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        echo json_encode([
            "status" => "fail",
            "message" => "Teacher not found"
        ]);
        exit;
    }

    $teacherAutoId = $teacher['id'];
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
}

// Get all classes assigned to this teacher through teacher_subject_allocation (both approved and pending)
$sql = "SELECT 
    c.id AS class_id,
    sc.id AS subject_class_id,
    tsa.id AS teacher_subject_allocation_id,
    f.faculty_name,
    c.class_name,
    c.study_mode,
    c.semester,
    c.academic_year,
    d.department_name,
    s.subject_name,
    tsa.start_time,
    tsa.end_time,
    tsa.status,
    t.teacher_id,
    t.full_name AS teacher_name
FROM teacher_subject_allocation tsa
INNER JOIN classes c ON tsa.class_id = c.id
INNER JOIN departments d ON c.department_id = d.id
INNER JOIN faculty f ON c.faculty_id = f.id
INNER JOIN subjects s ON tsa.subject_id = s.id
INNER JOIN Teachers t ON tsa.teacher_id = t.id
INNER JOIN subject_class sc ON sc.class_id = c.id AND sc.subject_id = s.id
WHERE tsa.teacher_id = ?
ORDER BY c.class_name ASC, s.subject_name ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$teacherAutoId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            "status" => "success",
            "details" => $data
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "No classes found for this teacher"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>