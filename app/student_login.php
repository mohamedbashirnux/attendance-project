<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

$student_id = filterRequest('student_id');
$password = filterRequest('password');

if (empty($student_id) || empty($password)) {
    echo json_encode([
        "status" => "fail", 
        "message" => "Student ID and password are required"
    ]);
    exit;
}

try {
    // Get student with class, department, and faculty info
    $stmt = $conn->prepare("
        SELECT s.*, c.class_name, c.study_mode, c.semester, c.academic_year,
               d.department_name, f.faculty_name
        FROM students s
        JOIN classes c ON s.class_id = c.id
        JOIN departments d ON c.department_id = d.id
        JOIN faculty f ON c.faculty_id = f.id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode([
            "status" => "fail", 
            "message" => "Invalid Student ID or Password"
        ]);
        exit;
    }

    // Verify password
    $passwordValid = false;
    if (password_verify($password, $student['password'])) {
        $passwordValid = true;
    } elseif ($password === $student['password']) {
        $passwordValid = true;
    }

    if (!$passwordValid) {
        echo json_encode([
            "status" => "fail", 
            "message" => "Invalid Student ID or Password"
        ]);
        exit;
    }

    // Check if approved
    if ($student['status'] !== 'approved') {
        echo json_encode([
            "status" => "fail", 
            "message" => "Your account is not approved yet. Please contact administration."
        ]);
        exit;
    }

    // Return student data
    echo json_encode([
        "status" => "success",
        "users" => [[
            "student_id" => $student['student_id'],
            "student_name" => $student['full_name'],
            "class_name" => $student['class_name'],
            "department_name" => $student['department_name'],
            "faculty_name" => $student['faculty_name'],
            "study_mode" => $student['study_mode'],
            "semester" => $student['semester'],
            "academic_year" => $student['academic_year'],
            "student_status" => $student['status']
        ]]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error", 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
