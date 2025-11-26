<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

// Get the POST parameters
$class_name = $_POST['class_name'] ?? '';
$department_name = $_POST['department_name'] ?? '';
$study_mode = $_POST['study_mode'] ?? '';
$subject_name = $_POST['subject_name'] ?? '';

try {
    // First, get total sessions for this subject
    $sessionQuery = "SELECT COUNT(*) as total_sessions 
                     FROM submit_session 
                     WHERE class_name = ? AND department_name = ? AND study_mode = ? AND subject_name = ?";
    $stmt = $conn->prepare($sessionQuery);
    $stmt->execute([$class_name, $department_name, $study_mode, $subject_name]);
    $totalSessions = $stmt->fetch(PDO::FETCH_ASSOC)['total_sessions'];

    // Get all students in the class
    $studentsQuery = "SELECT student_id, student_name 
                      FROM students 
                      WHERE class_name = ? AND department_name = ? AND study_mode = ?";
    $stmt = $conn->prepare($studentsQuery);
    $stmt->execute([$class_name, $department_name, $study_mode]);
    $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reportData = [];
    $totalAbsences = 0;
    $totalPossibleAttendances = 0;

    foreach ($allStudents as $student) {
        $studentId = $student['student_id'];
        $studentName = $student['student_name'];

        // Count absences for this student in this subject
        $absenceQuery = "SELECT COUNT(*) as absence_count 
                         FROM absents 
                         WHERE student_id = ? AND class_name = ? AND department_name = ? AND study_mode = ? AND subject_name = ?";
        $stmt = $conn->prepare($absenceQuery);
        $stmt->execute([$studentId, $class_name, $department_name, $study_mode, $subject_name]);
        $absenceCount = $stmt->fetch(PDO::FETCH_ASSOC)['absence_count'];

        // Get absence dates for this student in this subject
        $datesQuery = "SELECT absent_date, statuses, excuses 
                       FROM absents 
                       WHERE student_id = ? AND class_name = ? AND department_name = ? AND study_mode = ? AND subject_name = ?
                       ORDER BY absent_date DESC";
        $stmt = $conn->prepare($datesQuery);
        $stmt->execute([$studentId, $class_name, $department_name, $study_mode, $subject_name]);
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
            'student_id' => $studentId,
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
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'subject_info' => [
            'subject_name' => $subject_name,
            'class_name' => $class_name,
            'department_name' => $department_name,
            'study_mode' => $study_mode,
            'total_sessions' => $totalSessions,
            'class_attendance_rate' => round($classAttendanceRate, 1),
            'total_students' => count($allStudents),
            'total_absences' => $totalAbsences
        ],
        'students' => $reportData
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'fail',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
