<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

$debug = [];

try {
    $class_id = filterRequest('class_id');
    $subject_class_id = filterRequest('subject_class_id');

    if (!empty($class_id) && !empty($subject_class_id)) {
        $classQuery = "SELECT c.id as class_id, c.class_name, c.study_mode, c.semester, c.academic_year, d.department_name
                       FROM classes c JOIN departments d ON c.department_id = d.id WHERE c.id = ?";
        $stmt = $conn->prepare($classQuery);
        $stmt->execute([$class_id]);
        $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$classInfo) {
            throw new Exception('Class not found with ID: ' . $class_id);
        }

        $subjectQuery = "SELECT sc.id as subject_class_id, s.subject_name, s.id as subject_id
                         FROM subject_class sc JOIN subjects s ON sc.subject_id = s.id WHERE sc.id = ?";
        $stmt = $conn->prepare($subjectQuery);
        $stmt->execute([$subject_class_id]);
        $subjectInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subjectInfo) {
            throw new Exception('Subject not found with subject_class_id: ' . $subject_class_id);
        }

        $subject_name = $subjectInfo['subject_name'];
    } else {
        $class_name = trim(filterRequest('class_name'));
        $department_name = trim(filterRequest('department_name'));
        $study_mode = trim(filterRequest('study_mode'));
        $subject_name = trim(filterRequest('subject_name'));

        if (empty($class_name) || empty($department_name) || empty($study_mode) || empty($subject_name)) {
            throw new Exception('Missing required parameters');
        }

        $classQuery = "SELECT c.id as class_id, c.class_name, c.study_mode, c.semester, c.academic_year, d.department_name
                       FROM classes c JOIN departments d ON c.department_id = d.id 
                       WHERE TRIM(LOWER(c.class_name)) = TRIM(LOWER(?)) 
                       AND TRIM(LOWER(d.department_name)) = TRIM(LOWER(?)) 
                       AND TRIM(LOWER(c.study_mode)) = TRIM(LOWER(?))";
        
        $stmt = $conn->prepare($classQuery);
        $stmt->execute([$class_name, $department_name, $study_mode]);
        $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$classInfo) {
            throw new Exception('Class not found');
        }

        $class_id = $classInfo['class_id'];

        $subjectClassQuery = "SELECT sc.id as subject_class_id, s.subject_name
                              FROM subject_class sc JOIN subjects s ON sc.subject_id = s.id
                              WHERE sc.class_id = ? AND TRIM(LOWER(s.subject_name)) = TRIM(LOWER(?))";
        
        $stmt = $conn->prepare($subjectClassQuery);
        $stmt->execute([$class_id, $subject_name]);
        $subjectInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subjectInfo) {
            throw new Exception('Subject not found for this class');
        }

        $subject_class_id = $subjectInfo['subject_class_id'];
    }

    $debug['class_id'] = $class_id;
    $debug['subject_class_id'] = $subject_class_id;

    // Get total sessions
    $sessionsQuery = "SELECT COUNT(*) as total_sessions FROM attendance_sessions 
                      WHERE class_id = ? AND subject_class_id = ?";
    $stmt = $conn->prepare($sessionsQuery);
    $stmt->execute([$class_id, $subject_class_id]);
    $totalSessions = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_sessions'];
    $debug['total_sessions'] = $totalSessions;

    // Get all students - REMOVE status filter
    $studentsQuery = "SELECT id, student_id, full_name as student_name, status 
                      FROM students WHERE class_id = ? ORDER BY student_id";
    $stmt = $conn->prepare($studentsQuery);
    $stmt->execute([$class_id]);
    $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug['students_found'] = count($allStudents);
    $debug['sample_students'] = array_slice($allStudents, 0, 2);

    $reportData = [];
    $totalAbsences = 0;
    $totalPossibleAttendances = 0;

    foreach ($allStudents as $student) {
        $internal_student_id = $student['id'];
        $student_id = $student['student_id'];
        $studentName = $student['student_name'];

        $absenceQuery = "SELECT COUNT(*) as absence_count FROM absences 
                         WHERE student_id = ? AND class_id = ? AND subject_class_id = ?";
        $stmt = $conn->prepare($absenceQuery);
        $stmt->execute([$internal_student_id, $class_id, $subject_class_id]);
        $absenceCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['absence_count'];

        $datesQuery = "SELECT absence_date as absent_date, excuse as excuses
                       FROM absences WHERE student_id = ? AND class_id = ? AND subject_class_id = ?
                       ORDER BY absence_date DESC";
        $stmt = $conn->prepare($datesQuery);
        $stmt->execute([$internal_student_id, $class_id, $subject_class_id]);
        $absenceDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attendanceRate = $totalSessions > 0 ? (($totalSessions - $absenceCount) / $totalSessions) * 100 : 100;

        $status = 'Good';
        $statusColor = 'green';
        if ($attendanceRate < 60) {
            $status = 'Critical';
            $statusColor = 'red';
        } elseif ($attendanceRate < 80) {
            $status = 'Warning';
            $statusColor = 'orange';
        }

        $reportData[] = [
            'student_id' => $student_id,
            'student_name' => $studentName,
            'absences' => $absenceCount,
            'total_sessions' => $totalSessions,
            'attendance_rate' => round($attendanceRate, 1),
            'status' => $status,
            'status_color' => $statusColor,
            'absence_dates' => $absenceDates
        ];

        $totalAbsences += $absenceCount;
        $totalPossibleAttendances += $totalSessions;
    }

    $classAttendanceRate = $totalPossibleAttendances > 0 
        ? (($totalPossibleAttendances - $totalAbsences) / $totalPossibleAttendances) * 100 
        : 100;

    usort($reportData, function($a, $b) {
        return $a['attendance_rate'] <=> $b['attendance_rate'];
    });

    echo json_encode([
        'status' => 'success',
        'debug' => $debug,
        'subject_info' => [
            'subject_name' => $subject_name,
            'class_name' => $classInfo['class_name'],
            'department_name' => $classInfo['department_name'],
            'study_mode' => $classInfo['study_mode'],
            'semester' => $classInfo['semester'],
            'academic_year' => $classInfo['academic_year'],
            'total_sessions' => $totalSessions,
            'class_attendance_rate' => round($classAttendanceRate, 1),
            'total_students' => count($allStudents),
            'total_absences' => $totalAbsences
        ],
        'students' => $reportData
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => $debug
    ]);
}
?>
