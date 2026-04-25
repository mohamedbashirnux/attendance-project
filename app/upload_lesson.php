<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "fail",
        "message" => "Only POST method is allowed"
    ]);
    exit();
}

try {
    // Get form data
    $teacher_id = filterRequest('teacher_id');
    $class_id = filterRequest('class_id');
    $subject_class_id = filterRequest('subject_id'); // This is actually subject_class_id from Flutter
    $title = filterRequest('title');
    $description = filterRequest('description');

    // Validate required fields
    if (empty($teacher_id) || empty($class_id) || empty($subject_class_id) || empty($title)) {
        echo json_encode([
            "status" => "fail",
            "message" => "Teacher ID, Class ID, Subject ID, and Title are required"
        ]);
        exit();
    }

    // Verify teacher exists and get auto-increment ID
    $teacherSql = "SELECT id FROM teachers WHERE teacher_id = ?";
    $teacherStmt = $conn->prepare($teacherSql);
    $teacherStmt->execute([$teacher_id]);
    $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        echo json_encode([
            "status" => "fail",
            "message" => "Teacher not found"
        ]);
        exit();
    }

    $teacher_auto_id = $teacher['id'];

    // Get the actual subject_id from subject_class table
    $subjectSql = "SELECT subject_id FROM subject_class WHERE id = ?";
    $subjectStmt = $conn->prepare($subjectSql);
    $subjectStmt->execute([$subject_class_id]);
    $subjectResult = $subjectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$subjectResult) {
        echo json_encode([
            "status" => "fail",
            "message" => "Subject not found"
        ]);
        exit();
    }

    $subject_id = $subjectResult['subject_id'];

    // Verify that this teacher is assigned to this class and subject
    $verifySql = "SELECT id FROM teacher_subject_allocation 
                  WHERE teacher_id = ? AND class_id = ? AND subject_id = ?";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([$teacher_auto_id, $class_id, $subject_id]);

    if (!$verifyStmt->fetch()) {
        echo json_encode([
            "status" => "fail",
            "message" => "You are not assigned to teach this subject in this class"
        ]);
        exit();
    }

    // Check if file was uploaded
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            "status" => "fail",
            "message" => "No file uploaded or upload error occurred"
        ]);
        exit();
    }

    $file = $_FILES['pdf_file'];

    // Validate file type (only PDF)
    $allowed_types = ['application/pdf'];
    $file_type = mime_content_type($file['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        echo json_encode([
            "status" => "fail",
            "message" => "Only PDF files are allowed"
        ]);
        exit();
    }

    // Validate file size (max 10MB)
    $max_size = 10 * 1024 * 1024; // 10MB in bytes
    if ($file['size'] > $max_size) {
        echo json_encode([
            "status" => "fail",
            "message" => "File size must not exceed 10MB"
        ]);
        exit();
    }

    // Create upload directory if it doesn't exist
    $upload_dir = "../uploads/lessons/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_filename = 'lesson_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $unique_filename;

    // Move uploaded file to destination
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        echo json_encode([
            "status" => "fail",
            "message" => "Failed to save file"
        ]);
        exit();
    }

    // Save file information to database
    $insertSql = "INSERT INTO lesson_materials 
                  (teacher_id, class_id, subject_id, title, description, file_path, file_name, file_size, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->execute([
        $teacher_auto_id,
        $class_id,
        $subject_id,
        $title,
        $description,
        'uploads/lessons/' . $unique_filename,
        $file['name'],
        $file['size']
    ]);

    $lesson_id = $conn->lastInsertId();

    // ============================================
    // NEW CODE: Send notifications to students
    // ============================================
    try {
        // Get class, subject, and teacher names for notification
        $notificationQuery = "SELECT c.class_name, s.subject_name, t.full_name as teacher_name
                             FROM classes c, subjects s, teachers t
                             WHERE c.id = ? AND s.id = ? AND t.id = ?";
        $notificationStmt = $conn->prepare($notificationQuery);
        $notificationStmt->execute([$class_id, $subject_id, $teacher_auto_id]);
        $info = $notificationStmt->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            $notificationTitle = "New Lesson: " . $title;
            $notificationBody = "Uploaded by " . $info['teacher_name'] . " • " . $info['subject_name'];

            // Call send_lesson_notification.php to notify students
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/attendanceproject2/app/send_lesson_notification.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'class_id' => $class_id,
                'title' => $notificationTitle,
                'body' => $notificationBody
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $notificationResult = curl_exec($ch);
            curl_close($ch);

            // Optional: Log notification result
            error_log("Notification sent: " . $notificationResult);
        }
    } catch (Exception $e) {
        // Don't fail the upload if notification fails
        error_log("Notification error: " . $e->getMessage());
    }
    // ============================================
    // END OF NEW CODE
    // ============================================

    echo json_encode([
        "status" => "success",
        "message" => "Lesson uploaded successfully",
        "lesson_id" => $lesson_id,
        "file_name" => $unique_filename
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
