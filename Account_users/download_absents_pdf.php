<?php
require('../fpdf184/fpdf.php');

// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

include "../connection/connect.php";

try {
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

    // Query to fetch absence data using the SAME logic as absents.php
    $stmt = $conn->prepare("
    SELECT 
        s.student_id as student_varchar_id,
        COALESCE(s.full_name, CONCAT('Student ID: ', s.student_id)) as student_name,
        COALESCE(subj.subject_name, 'Unknown Subject') as subject_name,
        COUNT(*) as absent_count,
        -- Calculate total sessions for this subject and class
        COALESCE((
            SELECT COUNT(*) 
            FROM attendance_sessions ats 
            WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
        ), 0) as total_sessions,
        -- Calculate attendance percentage: ((total_sessions - absent_count) / total_sessions) * 100
        CASE 
            WHEN COALESCE((
                SELECT COUNT(*) 
                FROM attendance_sessions ats 
                WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
            ), 0) > 0 THEN 
                ROUND(((COALESCE((
                    SELECT COUNT(*) 
                    FROM attendance_sessions ats 
                    WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
                ), 0) - COUNT(*)) * 100.0) / COALESCE((
                    SELECT COUNT(*) 
                    FROM attendance_sessions ats 
                    WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
                ), 1), 2)
            ELSE 
                100.00
        END as attendance_percentage
    FROM absences a
    JOIN students s ON a.student_id = s.id
    LEFT JOIN subject_class sc ON a.subject_class_id = sc.id
    LEFT JOIN subjects subj ON sc.subject_id = subj.id
    WHERE a.class_id = ?
    GROUP BY s.student_id, a.subject_class_id, subj.subject_name, sc.id
    HAVING COUNT(*) > 0
    ORDER BY subj.subject_name ASC, COALESCE(s.full_name, CONCAT('Student ID: ', s.student_id)) ASC
    ");

    // Execute query
    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total unique students
    $unique_students = [];
    foreach ($results as $student) {
        $unique_students[$student['student_varchar_id']] = $student['student_name'];
    }
    $total_students = count($unique_students);

    // Create PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add header to PDF
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Image('capital.png', $pdf->GetX(), $pdf->GetY(), 30);
    $pdf->Cell(0, 10, 'Absent Report', 0, 1, 'C');

    $pdf->SetTextColor(255, 0, 0); 
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, 'CLASS ABSENCE REPORT', 0, 1, 'C');
    $pdf->SetTextColor(0); 

    $pdf->Ln(20);

    // Add class information
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 6, 'Faculty:', 0, 0);
    $pdf->Cell(60, 6, $faculty_name, 0, 1);

    $pdf->Cell(30, 6, 'Department:', 0, 0);
    $pdf->Cell(60, 6, $department_name, 0, 1);

    $pdf->Cell(30, 6, 'Class:', 0, 0);
    $pdf->Cell(60, 6, $class_name . ' (' . $study_mode . ')', 0, 1);

    $pdf->Cell(30, 6, 'Semester:', 0, 0);
    $pdf->Cell(60, 6, $semester, 0, 1);

    $pdf->Cell(30, 6, 'Academic Year:', 0, 0);
    $pdf->Cell(60, 6, $academic_year, 0, 1);

    $pdf->Cell(30, 6, 'Total Students:', 0, 0);
    $pdf->Cell(60, 6, $total_students, 0, 1);

    $pdf->Ln(10);

    // Check if there are results and add table headers
    if ($total_students > 0) {
        // Add table headers
        $pdf->SetFillColor(255, 255, 0);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(15, 6, 'No.', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Student ID', 1, 0, 'L', true);
        $pdf->Cell(50, 6, 'Student Name', 1, 0, 'L', true);
        $pdf->Cell(40, 6, 'Subject Name', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Absent', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Total', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Attend %', 1, 1, 'L', true);

        // Add table content
        $pdf->SetFont('Arial', '', 7);
        $row_number = 1;
        $fill = false;

        foreach ($results as $student) {
            $pdf->SetFillColor($fill ? 240 : 255);
            $pdf->Cell(15, 6, $row_number, 1, 0, 'C', true);
            $pdf->Cell(20, 6, $student['student_varchar_id'], 1, 0, 'L', true);
            $pdf->Cell(50, 6, $student['student_name'], 1, 0, 'L', true);
            $pdf->Cell(40, 6, $student['subject_name'], 1, 0, 'L', true);
            $pdf->Cell(20, 6, $student['absent_count'], 1, 0, 'C', true);
            $pdf->Cell(20, 6, $student['total_sessions'], 1, 0, 'C', true);
            $pdf->Cell(20, 6, $student['attendance_percentage'] . '%', 1, 1, 'C', true);

            $row_number++;
            $fill = !$fill;
        }
    } else {
        // If no data is found, display the message
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'No absence data available.', 0, 1, 'C');
    }

    // Add generation date
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 0, 'R');

    // Output PDF for download
    $pdf->Output('D', $class_name . '_(' . $study_mode . ')_absence_report.pdf');

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
