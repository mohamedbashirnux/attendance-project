<?php
require('../fpdf184/fpdf.php');
include "../connection/connect.php";

try {
    // Include the faculty session management
    include 'session_faculty.php';

    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    $faculty = $sessionInfo['faculty_name'];
    $faculty_id = $sessionInfo['faculty_id'];

    // Fetch query parameters using new structure
    $class_id = $_GET['class_id'] ?? '';
    $department_id = $_GET['department_id'] ?? '';
    $faculty_id_param = $_GET['faculty_id'] ?? $faculty_id;

    // Validate required parameters
    if (empty($class_id)) {
        throw new Exception('Missing required parameter: class_id');
    }

    // Get class information
    $class_sql = "SELECT c.class_name, c.study_mode, c.semester, c.academic_year, d.department_name
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.id 
                  WHERE c.id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->execute([$class_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        throw new Exception('Class not found');
    }

    // Extract class information
    $class_name = $class_info['class_name'];
    $department_name = $class_info['department_name'];
    $study_mode = $class_info['study_mode'];
    $semester = $class_info['semester'];
    $academic_year = $class_info['academic_year'];
    $faculty_name = $faculty; // Use session faculty name

    // Query to fetch students who missed 3 or more times in one subject
    $stmt = $conn->prepare("
    SELECT 
        s.student_id,
        s.full_name as student_name,
        subj.subject_name,
        COUNT(a.id) AS absence_count
    FROM 
        absences a
    INNER JOIN students s ON a.student_id = s.id
    INNER JOIN subject_class sc ON a.subject_class_id = sc.id
    INNER JOIN subjects subj ON sc.subject_id = subj.id
    WHERE
        a.class_id = ?
    GROUP BY 
        s.id, s.student_id, s.full_name, subj.subject_name
    HAVING 
        absence_count >= 3
    ORDER BY 
        s.full_name ASC, subj.subject_name ASC
    ");

    // Execute query
    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total unique students and total records
    $unique_students = [];
    foreach ($results as $row) {
        $unique_students[$row['student_id']] = $row['student_name'];
    }
    $total_students = count($unique_students);
    $total_records = count($results);

    // Create PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Logo at top left
    $pdf->Image('capital.png', 10, 10, 25);
    
    // Title at top center
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetY(15);
    $pdf->Cell(0, 8, 'RE-EXAMINATION REPORT', 0, 1, 'C');
    
    $pdf->Ln(15);

    // Add class information
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 7, 'Faculty:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $faculty_name, 0, 1);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 7, 'Department:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $department_name, 0, 1);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 7, 'Class:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $class_name . ' (' . $study_mode . ')', 0, 1);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 7, 'Semester:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $semester, 0, 1);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 7, 'Academic Year:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $academic_year, 0, 1);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 7, 'Students Requiring Re-exam:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $total_students, 0, 1);

    $pdf->Ln(10);

    // Check if there are results and add table headers
    if ($total_students > 0) {
        // Add table headers
        $pdf->SetFillColor(200, 200, 200); // Gray background for header
        $pdf->SetTextColor(0, 0, 0); // Black text
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(15, 8, 'No.', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Student ID', 1, 0, 'C', true);
        $pdf->Cell(65, 8, 'Student Name', 1, 0, 'C', true);
        $pdf->Cell(55, 8, 'Subject Name', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Absences', 1, 1, 'C', true);

        // Reset text color
        $pdf->SetTextColor(0);
        
        // Add table content
        $pdf->SetFont('Arial', '', 8);
        $row_number = 1;
        $previous_student = '';

        foreach ($results as $student) {
            $current_student = $student['student_name'];
            
            // No fill color - white background for all rows
            $pdf->SetFillColor(255, 255, 255);

            // Show number only for first occurrence of student
            if ($current_student !== $previous_student) {
                $pdf->Cell(15, 7, $row_number, 1, 0, 'C', false);
                $row_number++;
            } else {
                $pdf->Cell(15, 7, '', 1, 0, 'C', false);
            }

            $pdf->Cell(25, 7, $student['student_id'], 1, 0, 'L', false);
            $pdf->Cell(65, 7, $student['student_name'], 1, 0, 'L', false);
            $pdf->Cell(55, 7, $student['subject_name'], 1, 0, 'L', false);
            $pdf->Cell(25, 7, $student['absence_count'] . ' times', 1, 1, 'C', false);

            $previous_student = $current_student;
        }

        // Add note
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(220, 53, 69);
        $pdf->Cell(0, 6, 'Students listed have missed 3 or more sessions in at least one subject.', 0, 1, 'L');
        $pdf->SetTextColor(0);
        
    } else {
        // If no data is found, display the message
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(40, 167, 69); // Green color
        $pdf->Cell(0, 10, 'Excellent! All students have good attendance.', 0, 1, 'C');
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, 'No students have missed 3 or more sessions in any subject.', 0, 1, 'C');
    }

    $pdf->Ln(10);
    
    // Add signature section
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, 'Authorized Signature:', 0, 1, 'L');
    $pdf->Ln(15);
    $pdf->Cell(50, 10, '____________________', 0, 1, 'L');
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(50, 10, 'Head of Department', 0, 1, 'L');
    
    // Add generation date
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 0, 'R');

    // Output PDF for download
    $pdf->Output('D', $class_name . '_(' . $study_mode . ')_re_exam_report.pdf');

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>