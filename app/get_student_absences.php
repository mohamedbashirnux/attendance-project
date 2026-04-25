<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

// Get student_id from request
$student_id = filterRequest('student_id');

try {
    // Validate required parameter
    if (empty($student_id)) {
        throw new Exception('Missing required parameter: student_id');
    }

    // Get student information
    $studentQuery = "SELECT s.id as internal_id, s.student_id, s.full_name, s.class_id, 
                            c.class_name, d.department_name, c.study_mode, c.semester, c.academic_year
                     FROM students s
                     JOIN classes c ON s.class_id = c.id
                     JOIN departments d ON c.department_id = d.id
                     WHERE s.student_id = ? AND s.status = 'approved'";
    
    $stmt = $conn->prepare($studentQuery);
    $stmt->execute([$student_id]);
    $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentInfo) {
        throw new Exception('Student not found or not approved');
    }

    $internal_student_id = $studentInfo['internal_id'];
    $class_id = $studentInfo['class_id'];

    // Get all absences for this student from absences table
    $absencesQuery = "SELECT a.id, a.absence_date, a.excuse, a.created_at,
                             sub.subject_name,
                             t.full_name as teacher_name
                      FROM absences a
                      LEFT JOIN subjects sub ON a.subject_class_id = sub.id
                      LEFT JOIN teachers t ON a.teacher_id = t.id
                      WHERE a.student_id = ? AND a.class_id = ?
                      ORDER BY a.absence_date DESC";
    
    $stmt = $conn->prepare($absencesQuery);
    $stmt->execute([$internal_student_id, $class_id]);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total absence count
    $totalAbsences = count($absences);

    // Group absences by subject
    $absencesBySubject = [];
    foreach ($absences as $absence) {
        $subject = $absence['subject_name'] ?? 'Unknown Subject';
        if (!isset($absencesBySubject[$subject])) {
            $absencesBySubject[$subject] = [
                'subject_name' => $subject,
                'absence_count' => 0,
                'absences' => []
            ];
        }
        $absencesBySubject[$subject]['absence_count']++;
        $absencesBySubject[$subject]['absences'][] = [
            'date' => $absence['absence_date'],
            'excuse' => $absence['excuse'],
            'teacher' => $absence['teacher_name'],
            'recorded_at' => $absence['created_at']
        ];
    }

    // Convert to indexed array
    $subjectSummary = array_values($absencesBySubject);

    // Return comprehensive report
    echo json_encode([
        'status' => 'success',
        'student_info' => [
            'student_id' => $studentInfo['student_id'],
            'full_name' => $studentInfo['full_name'],
            'class_name' => $studentInfo['class_name'],
            'department_name' => $studentInfo['department_name'],
            'study_mode' => $studentInfo['study_mode'],
            'semester' => $studentInfo['semester'],
            'academic_year' => $studentInfo['academic_year']
        ],
        'total_absences' => $totalAbsences,
        'absences_by_subject' => $subjectSummary,
        'all_absences' => $absences
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
