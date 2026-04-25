<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "fail",
        "message" => "Only POST method is allowed"
    ]);
    exit;
}

$teacher_id = filterRequest('teacher_id');
$lesson_id = filterRequest('lesson_id');

if (empty($teacher_id) || empty($lesson_id)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Teacher ID and Lesson ID are required"
    ]);
    exit;
}

try {
    // Get teacher auto-increment ID
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
    
    // Verify that this lesson belongs to this teacher
    $verifySql = "SELECT file_path FROM lesson_materials 
                  WHERE id = ? AND teacher_id = ?";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([$lesson_id, $teacher_auto_id]);
    $lesson = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lesson) {
        echo json_encode([
            "status" => "fail",
            "message" => "Lesson not found or you don't have permission to delete it"
        ]);
        exit;
    }
    
    // Delete file from server
    $file_path = "../" . $lesson['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete record from database
    $deleteSql = "DELETE FROM lesson_materials WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->execute([$lesson_id]);
    
    echo json_encode([
        "status" => "success",
        "message" => "Lesson deleted successfully"
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
