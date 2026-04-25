<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

// Get the parameters (can be GET or POST)
$class_name = trim(filterRequest('class_name'));
$department_name = trim(filterRequest('department_name'));
$study_mode = trim(filterRequest('study_mode'));
$subject_name = trim(filterRequest('subject_name'));

try {
    // Validate required parameters
    if (empty($class_name) || empty($department_name) || empty($study_mode) || empty($subject_name)) {
        throw new Exception('Missing required parameters: class_name, department_name, study_mode, subject_name');
    }

    // Get class information using the provided parameters
    $classQuery = "SELECT c.id as class_id, c.class_name, c.study_mode, c.semester, c.academic_year, 
                          d.department_name, f.faculty_name
                   FROM classes c 
                   JOIN departments d ON c.department_id = d.id 
                   JOIN faculties f ON c.faculty_id = f.id
                   WHERE TRIM(LOWER(c.class_name)) = TRIM(LOWER(?)) 
                   AND TRIM(LOWER(d.department_name)) = TRIM(LOWER(?)) 
                   AND TRIM(LOWER(c.study_mode)) = TRIM(LOWER(?))";
    
    $stmt = $conn->prepare($classQuery);
    $stmt->execute([$class_name, $department_name, $study_mode]);
    $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$classInfo) {
        throw new Exception('Class not found with the provided criteria');
    }

    $class_id = $classInfo['class_id'];

    // Get subject_class_id for this subject and class
    $subjectClassQuery = "SELECT s.id as subject_id, s.subject_name
                         FROM subjects s
                         WHERE TRIM(LOWER(s.subject_name)) = TRIM(LOWER(?))";
    
    $stmt = $conn->prepare($subjectClassQuery);
    $stmt->execute([$subject_name]);
    $subjectInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subjectInfo) {
        throw new Exception('Subject not found: ' . $subject_name);
    }

    $subject_id = $subjectInfo['subject_id'];

    // Get actual total sessions from attendance_sessions table
    $sessionsQuery = "SELECT COUNT(*) as total_sessions
                     FROM attendance_sessions 
                     WHERE class_id = ? AND subject_class_id = ?";
    
    $stmt = $conn->prepare($sessionsQuery);
    $stmt->execute([$class_id, $subject_id]);
    $sessionsResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSessions = (int)$sessionsResult['total_sessions'];

    // If no sessions found, set to 0 (no attendance taken yet)
    if ($totalSessions === 0) {
        // Get student count for empty report
        $studentCountQuery = "SELECT COUNT(*) as student_count FROM students WHERE class_id = ? AND status = 'active'";
        $stmt = $conn->prepare($studentCountQuery);
        $stmt->execute([$class_id]);
        $studentCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['student_count'];

        // Return empty report with message
        echo json_encode([
            'status' => 'success',
            'subject_info' => [
                'subject_name' => $subject_name,
                'class_name' => $classInfo['class_name'],
                'department_name' => $classInfo['department_name'],
                'study_mode' => $classInfo['study_mode'],
                'semester' => $classInfo['semester'],
                'academic_year' => $classInfo['academic_year'],
                'faculty_name' => $classInfo['faculty_name'],
                'total_sessions' => 0,
                'class_attendance_rate' => 100.0,
                'total_students' => $studentCount,
                'total_absences' => 0
            ],
            'students' => [],
            'message' => 'No attendance sessions found for this subject yet.'
        ]);
        exit();
    }

    // Get all students in the class
    $studentsQuery = "SELECT id, student_id, full_name as student_name 
                     FROM students 
                     WHERE class_id = ? AND status = 'active'
                     ORDER BY student_id";
    
    $stmt = $conn->prepare($studentsQuery);
    $stmt->execute([$class_id]);
    $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reportData = [];
    $totalAbsences = 0;
    $totalPossibleAttendances = 0;

    foreach ($allStudents as $student) {
        $internal_student_id = $student['id'];
        $student_id = $student['student_id'];
        $studentName = $student['student_name'];

        // Count absences for this student in this subject
        $absenceQuery = "SELECT COUNT(*) as absence_count 
                        FROM absences 
                        WHERE student_id = ? AND class_id = ? AND subject_class_id = ?";
        
        $stmt = $conn->prepare($absenceQuery);
        $stmt->execute([$internal_student_id, $class_id, $subject_id]);
        $absenceCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['absence_count'];

        // Get absence dates with excuses for this student in this subject
        $datesQuery = "SELECT absence_date as absent_date, excuse as excuses
                      FROM absences 
                      WHERE student_id = ? AND class_id = ? AND subject_class_id = ?
                      ORDER BY absence_date DESC";
        
        $stmt = $conn->prepare($datesQuery);
        $stmt->execute([$internal_student_id, $class_id, $subject_id]);
        $absenceDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate attendance rate
        $attendanceRate = $totalSessions > 0 ? (($totalSessions - $absenceCount) / $totalSessions) * 100 : 100;

        // Determine status
        $status = 'Good';
        $statusColor = 'green';
        if ($attendanceRate < 60) {
            $status = 'Critical';
            $statusColor = 'red';
        } elseif ($attendanceRate < 80) {
            $status = 'Warning';
            $statusColor = 'orange';
        }

        $studentData = [
            'student_id' => $student_id,
            'student_name' => $studentName,
            'absences' => $absenceCount,
            'total_sessions' => $totalSessions,
            'attendance_rate' => round($attendanceRate, 1),
            'status' => $status,
            'status_color' => $statusColor,
            'absence_dates' => $absenceDates
        ];

        $reportData[] = $studentData;
        $totalAbsences += $absenceCount;
        $totalPossibleAttendances += $totalSessions;
    }

    // Calculate class attendance rate
    $classAttendanceRate = $totalPossibleAttendances > 0 ? 
        (($totalPossibleAttendances - $totalAbsences) / $totalPossibleAttendances) * 100 : 100;

    // Sort by attendance rate (worst first)
    usort($reportData, function($a, $b) {
        return $a['attendance_rate'] <=> $b['attendance_rate'];
    });

    // Return comprehensive report
    echo json_encode([
        'status' => 'success',
        'subject_info' => [
            'subject_name' => $subject_name,
            'class_name' => $classInfo['class_name'],
            'department_name' => $classInfo['department_name'],
            'study_mode' => $classInfo['study_mode'],
            'semester' => $classInfo['semester'],
            'academic_year' => $classInfo['academic_year'],
            'faculty_name' => $classInfo['faculty_name'],
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
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>