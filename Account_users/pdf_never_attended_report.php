<?php
require('../fpdf184/fpdf.php');
include "../connection/connect.php";
include "session_faculty.php";

try {
    // Get session info for faculty information
    $sessionInfo = getSessionInfo();
    $faculty = $sessionInfo['faculty_name'];
    $faculty_id = $sessionInfo['faculty_id'];

    // Fetch query parameters
    $class_id = $_GET['class_id'] ?? '';
    $department_id = $_GET['department_id'] ?? '';
    $faculty_id_param = $_GET['faculty_id'] ?? $faculty_id;
    $subject_name = $_GET['subject_name'] ?? '';

    // Validate required parameters
    if (empty($class_id) || empty($subject_name)) {
        die("Error: Missing required parameters");
    }

    // Get class information
    $class_sql = "SELECT c.class_name, c.study_mode, c.semester, c.academic_year, d.department_name 
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.id 
                  WHERE c.id = ? AND c.faculty_id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->execute([$class_id, $faculty_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        die("Error: Class not found");
    }

    // Get subject_class_id for this subject and class
    $subjectClassQuery = "SELECT sc.id as subject_class_id
                          FROM subject_class sc
                          JOIN subjects s ON sc.subject_id = s.id
                          WHERE sc.class_id = ? AND s.subject_name = ?";
    $stmt = $conn->prepare($subjectClassQuery);
    $stmt->execute([$class_id, $subject_name]);
    $subjectClassInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subjectClassInfo) {
        die("Error: Subject not found for this class");
    }
    
    $subject_class_id = $subjectClassInfo['subject_class_id'];

    // Get total sessions for this subject
    $total_sessions_sql = "SELECT COUNT(*) as total_sessions 
                          FROM attendance_sessions 
                          WHERE class_id = ? AND subject_class_id = ?";
    $total_stmt = $conn->prepare($total_sessions_sql);
    $total_stmt->execute([$class_id, $subject_class_id]);
    $total_sessions_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_sessions = $total_sessions_result['total_sessions'];

    // Build the SQL query to find students who never attended
    $sql = "
    SELECT 
        s.student_id,
        s.full_name as student_name,
        subj.subject_name,
        COUNT(a.id) AS absence_count,
        ? as total_sessions
    FROM
        absences a
    INNER JOIN students s ON a.student_id = s.id
    INNER JOIN subject_class sc ON a.subject_class_id = sc.id
    INNER JOIN subjects subj ON sc.subject_id = subj.id
    WHERE 
        a.class_id = ?
        AND a.subject_class_id = ?
    GROUP BY 
        s.student_id, s.full_name, subj.subject_name
    HAVING 
        COUNT(a.id) = ? AND COUNT(a.id) > 0
    ORDER BY 
        s.full_name ASC
    ";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Execute with parameters
    $stmt->execute([$total_sessions, $class_id, $subject_class_id, $total_sessions]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total students
    $total_students = count($results);

    // Create PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add header to PDF
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Image('capital.png', $pdf->GetX(), $pdf->GetY(), 30);
    $pdf->Cell(0, 10, 'Absent Report', 0, 1, 'C');

    $pdf->SetTextColor(255, 0, 0); 
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, 'NEVER ATTENDED REPORT', 0, 1, 'C');
    $pdf->SetTextColor(0); 
    
    $pdf->Ln(20);

    // Add information
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(35, 6, 'Faculty:', 0, 0);
    $pdf->Cell(60, 6, $faculty, 0, 1);
    $pdf->Cell(35, 6, 'Department:', 0, 0);
    $pdf->Cell(60, 6, $class_info['department_name'], 0, 1);
    $pdf->Cell(35, 6, 'Class:', 0, 0);
    $pdf->Cell(60, 6, $class_info['class_name'] . ' (' . $class_info['study_mode'] . ')', 0, 1);
    $pdf->Cell(35, 6, 'Semester:', 0, 0);
    $pdf->Cell(60, 6, $class_info['semester'], 0, 1);
    $pdf->Cell(35, 6, 'Academic Year:', 0, 0);
    $pdf->Cell(60, 6, $class_info['academic_year'], 0, 1);
    $pdf->Cell(35, 6, 'Subject Name:', 0, 0);
    $pdf->Cell(60, 6, $subject_name, 0, 1);
    $pdf->Cell(35, 6, 'Total Sessions:', 0, 0);
    $pdf->Cell(60, 6, $total_sessions, 0, 1);
    $pdf->Cell(35, 6, 'Never Attended Count:', 0, 0);
    $pdf->Cell(60, 6, $total_students, 0, 1);

    $pdf->Ln(5);
    
    // Add warning note BEFORE the table
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(0, 6, 'WARNING: These students have NEVER attended this subject!', 0, 1, 'C');
    $pdf->SetTextColor(0);

    $pdf->Ln(5);

    // Add table headers with red background for critical alert
    $pdf->SetFillColor(255, 100, 100); // Red color for critical
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(10, 6, 'No.', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Student ID', 1, 0, 'C', true);
    $pdf->Cell(70, 6, 'Student Name', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Total Sessions', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Absences', 1, 0, 'C', true);
    $pdf->Cell(35, 6, 'Status', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 7);

    // Add table content
    if ($total_sessions == 0) {
        // No sessions taken yet - show info message
        $pdf->SetFillColor(255, 255, 200); // Light yellow background
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(200, 100, 0); // Orange text
        $pdf->Cell(190, 10, 'NO SESSIONS TAKEN YET', 1, 1, 'C', true);
        $pdf->SetTextColor(0); // Reset to black
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 6, 'No attendance sessions have been recorded for this subject yet.', 0, 1, 'C');
    } elseif (!empty($results)) {
        $row_number = 1;
        foreach ($results as $student) {
            $absence_count = (int)$student['absence_count'];
            $total_sessions_count = (int)$student['total_sessions'];

            // Red background for all rows (critical)
            $pdf->SetFillColor(255, 220, 220);

            // Print row
            $pdf->Cell(10, 6, $row_number, 1, 0, 'C', true);
            $pdf->Cell(25, 6, $student['student_id'], 1, 0, '', true);
            $pdf->Cell(70, 6, $student['student_name'], 1, 0, '', true);
            $pdf->Cell(25, 6, $total_sessions_count, 1, 0, 'C', true);
            $pdf->Cell(25, 6, $absence_count, 1, 0, 'C', true);
            $pdf->Cell(35, 6, 'NEVER ATTENDED', 1, 1, 'C', true);
            
            $row_number++;
        }
    } else {
        // No students found - show good news message
        $pdf->SetFillColor(200, 255, 200); // Light green background
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 150, 0); // Green text
        $pdf->Cell(190, 10, 'EXCELLENT NEWS! No students have missed all sessions.', 1, 1, 'C', true);
        $pdf->SetTextColor(0); // Reset to black
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 6, 'All students have attended at least one session for this subject.', 0, 1, 'C');
    }

    $pdf->Ln(10);
    
    // Add signature section
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, 'Authorized Signature:', 0, 1, 'L');
    $pdf->Ln(15);
    $pdf->Cell(50, 10, '____________________', 0, 1, 'L');
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(50, 10, 'Head of Department', 0, 1, 'L');
    
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d'), 0, 0, 'R');

    // Output PDF for download
    $subject_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', $subject_name);
    $pdf->Output('D', 'Never_Attended_' . $subject_name_sanitized . '_report.pdf'); 

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
