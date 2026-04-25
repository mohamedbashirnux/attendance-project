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
    // Get student info
    $studentQuery = "
        SELECT s.id as internal_id, s.student_id, s.full_name, s.class_id,
               c.class_name, d.department_name, c.study_mode
        FROM students s
        JOIN classes c ON s.class_id = c.id
        JOIN departments d ON c.department_id = d.id
        WHERE s.student_id = ? AND s.status = 'approved'
    ";
    
    $stmt = $conn->prepare($studentQuery);
    $stmt->execute([$student_id]);
    $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentInfo) {
        echo json_encode([
            "status" => "fail", 
            "message" => "Student not found or not approved"
        ]);
        exit;
    }

    $internal_student_id = $studentInfo['internal_id'];
    $class_id = $studentInfo['class_id'];

    // Get all absences for this student
    $absencesQuery = "
        SELECT 
            a.id,
            a.absence_date,
            a.excuse,
            a.created_at,
            s.subject_name,
            t.full_name as teacher_name
        FROM absences a
        JOIN subject_class sc ON a.subject_class_id = sc.id
        JOIN subjects s ON sc.subject_id = s.id
        LEFT JOIN teachers t ON a.teacher_id = t.id
        WHERE a.student_id = ? AND a.class_id = ?
        ORDER BY a.absence_date DESC, a.created_at DESC
    ";
    
    $stmt = $conn->prepare($absencesQuery);
    $stmt->execute([$internal_student_id, $class_id]);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total sessions per subject for this class
    $sessionsQuery = "
        SELECT 
            sc.id as subject_class_id,
            s.subject_name,
            COUNT(ats.id) as total_sessions
        FROM subject_class sc
        JOIN subjects s ON sc.subject_id = s.id
        LEFT JOIN attendance_sessions ats ON ats.subject_class_id = sc.id AND ats.class_id = ?
        WHERE sc.class_id = ?
        GROUP BY sc.id, s.subject_name
    ";
    
    $stmt = $conn->prepare($sessionsQuery);
    $stmt->execute([$class_id, $class_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a map of subject sessions
    $sessionMap = [];
    foreach ($sessions as $session) {
        $sessionMap[$session['subject_name']] = (int)$session['total_sessions'];
    }

    // Group absences by subject
    $absencesBySubject = [];
    foreach ($absences as $absence) {
        $subject = $absence['subject_name'];
        
        if (!isset($absencesBySubject[$subject])) {
            $totalSessions = $sessionMap[$subject] ?? 0;
            $absencesBySubject[$subject] = [
                'subject_name' => $subject,
                'total_sessions' => $totalSessions,
                'absence_count' => 0,
                'attendance_rate' => 0,
                'records' => []
            ];
        }
        
        $absencesBySubject[$subject]['absence_count']++;
        $absencesBySubject[$subject]['records'][] = [
            'id' => $absence['id'],
            'absence_date' => $absence['absence_date'],
            'excuse' => $absence['excuse'],
            'teacher_name' => $absence['teacher_name'],
            'created_at' => $absence['created_at']
        ];
    }

    // Calculate attendance rates
    foreach ($absencesBySubject as $subject => &$data) {
        if ($data['total_sessions'] > 0) {
            $attended = $data['total_sessions'] - $data['absence_count'];
            $data['attendance_rate'] = round(($attended / $data['total_sessions']) * 100, 1);
        } else {
            $data['attendance_rate'] = 100;
        }
    }

    // Convert to indexed array
    $groupedAbsences = array_values($absencesBySubject);

    // Calculate overall stats
    $totalSessions = array_sum($sessionMap);
    $totalAbsences = count($absences);
    $overallRate = $totalSessions > 0 ? 
        round((($totalSessions - $totalAbsences) / $totalSessions) * 100, 1) : 100;

    echo json_encode([
        "status" => "success",
        "student_info" => [
            "student_id" => $studentInfo['student_id'],
            "full_name" => $studentInfo['full_name'],
            "class_name" => $studentInfo['class_name'],
            "department_name" => $studentInfo['department_name'],
            "study_mode" => $studentInfo['study_mode']
        ],
        "overall_stats" => [
            "total_sessions" => $totalSessions,
            "total_absences" => $totalAbsences,
            "attendance_rate" => $overallRate
        ],
        "absences_by_subject" => $groupedAbsences,
        "recent_absences" => array_slice($absences, 0, 10)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error", 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
