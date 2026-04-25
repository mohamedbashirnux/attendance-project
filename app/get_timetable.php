<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

$student_id = filterRequest('student_id');

if (empty($student_id)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Student ID is required"
    ]);
    exit;
}

try {
    // First, get the student's class_id
    $studentSql = "SELECT s.class_id, c.class_name, c.study_mode, c.semester, c.academic_year,
                          d.department_name, f.faculty_name
                   FROM students s
                   JOIN classes c ON s.class_id = c.id
                   JOIN departments d ON c.department_id = d.id
                   JOIN faculty f ON c.faculty_id = f.id
                   WHERE s.student_id = ?";
    
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
    
    // Check if timetable table has the new structure (class_id and allocation_id columns)
    $check_columns_sql = "SHOW COLUMNS FROM timetable LIKE 'class_id'";
    $check_stmt = $conn->query($check_columns_sql);
    $has_new_structure = ($check_stmt->rowCount() > 0);
    
    if ($has_new_structure) {
        // NEW STRUCTURE: Use proper JOINs with foreign keys
        $sql = "SELECT tt.id AS timetable_id,
                       tt.day_of_week,
                       tt.time_start,
                       tt.time_end,
                       tt.location_hall,
                       s.subject_name,
                       t.full_name AS teacher_name,
                       t.teacher_id,
                       c.class_name,
                       c.study_mode,
                       c.semester,
                       c.academic_year,
                       d.department_name,
                       f.faculty_name,
                       tsa.status AS allocation_status
                FROM timetable tt
                JOIN classes c ON tt.class_id = c.id
                JOIN departments d ON c.department_id = d.id
                JOIN faculty f ON c.faculty_id = f.id
                JOIN teacher_subject_allocation tsa ON tt.allocation_id = tsa.id
                JOIN teachers t ON tsa.teacher_id = t.id
                JOIN subjects s ON tsa.subject_id = s.id
                WHERE tt.class_id = ?
                ORDER BY FIELD(tt.day_of_week, 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), 
                         tt.time_start ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$class_id]);
    } else {
        // OLD STRUCTURE: Use text-based fields (fallback for backward compatibility)
        $sql = "SELECT id AS timetable_id,
                       day_of_week,
                       time_start,
                       time_end,
                       location_hall,
                       subject_name,
                       teacher_name,
                       class_name,
                       study_mode,
                       semester,
                       academic_year,
                       department_name,
                       faculty_name
                FROM timetable
                WHERE class_name = ? 
                  AND study_mode = ? 
                  AND department_name = ?
                ORDER BY FIELD(day_of_week, 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), 
                         time_start ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$student['class_name'], $student['study_mode'], $student['department_name']]);
    }
    
    $timetable = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($timetable && count($timetable) > 0) {
        // Format the response
        $response = [
            "status" => "success",
            "class_info" => [
                "class_name" => $student['class_name'],
                "study_mode" => $student['study_mode'],
                "semester" => $student['semester'],
                "academic_year" => $student['academic_year'],
                "department_name" => $student['department_name'],
                "faculty_name" => $student['faculty_name']
            ],
            "timetable" => []
        ];
        
        // Group by day of week for better organization
        $days_order = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $grouped_by_day = [];
        
        foreach ($timetable as $entry) {
            $day = $entry['day_of_week'];
            if (!isset($grouped_by_day[$day])) {
                $grouped_by_day[$day] = [];
            }
            
            $grouped_by_day[$day][] = [
                "timetable_id" => $entry['timetable_id'],
                "subject_name" => $entry['subject_name'],
                "teacher_name" => $entry['teacher_name'],
                "teacher_id" => $entry['teacher_id'] ?? null,
                "time_start" => $entry['time_start'],
                "time_end" => $entry['time_end'],
                "location_hall" => $entry['location_hall'],
                "allocation_status" => $entry['allocation_status'] ?? null
            ];
        }
        
        // Build the final timetable array in day order
        foreach ($days_order as $day) {
            if (isset($grouped_by_day[$day])) {
                $response['timetable'][] = [
                    "day" => $day,
                    "classes" => $grouped_by_day[$day]
                ];
            }
        }
        
        echo json_encode($response);
    } else {
        echo json_encode([
            "status" => "success",
            "class_info" => [
                "class_name" => $student['class_name'],
                "study_mode" => $student['study_mode'],
                "semester" => $student['semester'],
                "academic_year" => $student['academic_year'],
                "department_name" => $student['department_name'],
                "faculty_name" => $student['faculty_name']
            ],
            "timetable" => [],
            "message" => "No timetable entries found for this class"
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
