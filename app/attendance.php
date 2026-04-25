<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "conn.php";

// Get input - only for ABSENT students
$student_id = filterRequest('student_id');
$class_id = filterRequest('class_id');
$subject_class_id = filterRequest('subject_class_id');
$teacher_id = filterRequest('teacher_id');
$absence_date = filterRequest('absence_date');
$excuse = filterRequest('excuse');

// Validate required fields
if(empty($student_id) || empty($class_id) || empty($subject_class_id) || empty($teacher_id) || empty($absence_date)) {
    echo json_encode([
        "status" => "fail", 
        "message" => "Required fields missing: student_id, class_id, subject_class_id, teacher_id, absence_date"
    ]);
    exit();
}

// Handle excuse
if(empty($excuse)) {
    $excuse = 'No Excuse';
}

// Validate excuse enum
$valid_excuses = ['Family Emergency', 'Medical Appointment', 'Personal Reason', 'Official Duty', 'Other', 'No Excuse'];
if(!in_array($excuse, $valid_excuses)) {
    echo json_encode([
        "status" => "fail", 
        "message" => "Invalid excuse. Must be one of: " . implode(', ', $valid_excuses)
    ]);
    exit();
}

try {
    // Get the internal student ID (auto-increment)
    $student_check = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR id = ?");
    $student_check->execute([$student_id, $student_id]);
    $student_record = $student_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_record) {
        echo json_encode([
            "status" => "fail", 
            "message" => "Student not found with ID: " . $student_id
        ]);
        exit();
    }
    
    $internal_student_id = $student_record['id'];
    
    // Get the internal teacher ID (auto-increment)
    $teacher_check = $conn->prepare("SELECT id FROM Teachers WHERE teacher_id = ? OR id = ?");
    $teacher_check->execute([$teacher_id, $teacher_id]);
    $teacher_record = $teacher_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher_record) {
        echo json_encode([
            "status" => "fail", 
            "message" => "Teacher not found with ID: " . $teacher_id
        ]);
        exit();
    }
    
    $internal_teacher_id = $teacher_record['id'];
    
    // Insert absence record (only for absent students)
    $stmt = $conn->prepare("INSERT INTO absences (student_id, class_id, subject_class_id, teacher_id, absence_date, excuse) VALUES (?, ?, ?, ?, ?, ?)");
    
    if($stmt->execute([$internal_student_id, $class_id, $subject_class_id, $internal_teacher_id, $absence_date, $excuse])) {
        echo json_encode([
            "status" => "success", 
            "message" => "Absence recorded successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "fail", 
            "message" => "Failed to record absence"
        ]);
    }
    
} catch (PDOException $e) {
    // Handle duplicate entry (student already marked absent for this date)
    if($e->getCode() == 23000) {
        echo json_encode([
            "status" => "fail", 
            "message" => "This student is already marked absent for this date and subject."
        ]);
    } else {
        echo json_encode([
            "status" => "fail", 
            "message" => "Database error: " . $e->getMessage()
        ]);
    }
}
?>