<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

// Get parameters
$student_id = filterRequest('student_id');
$teacher_id = filterRequest('teacher_id');
$class_id = filterRequest('class_id');
$subject_id = filterRequest('subject_id');

try {
    // Determine if request is from student or teacher
    if (!empty($student_id)) {
        // Student request - get their class_id first
        $studentSql = "SELECT class_id FROM students WHERE student_id = ?";
        $studentStmt = $conn->prepare($studentSql);
        $studentStmt->execute([$student_id]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            echo json_encode([
                "status" => "fail",
                "message" => "Student not found"
            ]);
            exit;
        }
        
        $class_id = $student['class_id'];
    } elseif (!empty($teacher_id)) {
        // Teacher request - verify teacher exists
        $teacherSql = "SELECT id FROM teachers WHERE teacher_id = ?";
        $teacherStmt = $conn->prepare($teacherSql);
        $teacherStmt->execute([$teacher_id]);
        $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$teacher) {
            echo json_encode([
                "status" => "fail",
                "message" => "Teacher not found"
            ]);
            exit;
        }
        
        $teacher_auto_id = $teacher['id'];
    }
    
    // Build query based on filters
    $sql = "SELECT lm.id AS lesson_id,
                   lm.title,
                   lm.description,
                   lm.file_path,
                   lm.file_name,
                   lm.file_size,
                   lm.upload_date,
                   lm.status,
                   t.teacher_id,
                   t.full_name AS teacher_name,
                   c.class_name,
                   c.study_mode,
                   c.semester,
                   s.subject_name
            FROM lesson_materials lm
            JOIN teachers t ON lm.teacher_id = t.id
            JOIN classes c ON lm.class_id = c.id
            JOIN subjects s ON lm.subject_id = s.id
            WHERE lm.status = 'active'";
    
    $params = [];
    
    // Add filters
    if (!empty($class_id)) {
        $sql .= " AND lm.class_id = ?";
        $params[] = $class_id;
    }
    
    if (!empty($subject_id)) {
        $sql .= " AND lm.subject_id = ?";
        $params[] = $subject_id;
    }
    
    if (!empty($teacher_id) && isset($teacher_auto_id)) {
        $sql .= " AND lm.teacher_id = ?";
        $params[] = $teacher_auto_id;
    }
    
    $sql .= " ORDER BY lm.upload_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($lessons && count($lessons) > 0) {
        // Format file sizes to human readable
        foreach ($lessons as &$lesson) {
            $lesson['file_size_formatted'] = formatFileSize($lesson['file_size']);
            $lesson['upload_date_formatted'] = date('M d, Y h:i A', strtotime($lesson['upload_date']));
        }
        
        echo json_encode([
            "status" => "success",
            "count" => count($lessons),
            "lessons" => $lessons
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "count" => 0,
            "lessons" => [],
            "message" => "No lessons found"
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage()
    ]);
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
