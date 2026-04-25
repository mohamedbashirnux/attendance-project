<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "conn.php";

$lesson_id = filterRequest('lesson_id');

if (empty($lesson_id)) {
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "fail",
        "message" => "Lesson ID is required"
    ]);
    exit;
}

try {
    // Get lesson information
    $sql = "SELECT lm.file_path, lm.file_name, lm.title
            FROM lesson_materials lm
            WHERE lm.id = ? AND lm.status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lesson) {
        header("Content-Type: application/json");
        echo json_encode([
            "status" => "fail",
            "message" => "Lesson not found or has been archived"
        ]);
        exit;
    }
    
    // Build full file path
    $file_path = "../" . $lesson['file_path'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        header("Content-Type: application/json");
        echo json_encode([
            "status" => "fail",
            "message" => "File not found on server"
        ]);
        exit;
    }
    
    // Set headers for file download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($lesson['file_name']) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read and output file
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
