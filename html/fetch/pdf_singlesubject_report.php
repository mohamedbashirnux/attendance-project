<?php
require('../../fpdf184/fpdf.php');
include "../../connection/connect.php";

try {
    // Fetch query parameters
    $class_name = $_GET['class_name'] ?? '';
    $department_name = $_GET['department_name'] ?? '';
    $study_mode = $_GET['study_mode'] ?? '';
    $subject_name = $_GET['subject_name'] ?? '';
    $semester = $_GET['semester'] ?? '';
    $academic = $_GET['academic'] ?? '';
    $faculty = $_GET['faculty'] ?? '';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    // Validate required parameters
    if (empty($class_name) || empty($department_name) || empty($study_mode) || empty($subject_name)) {
        header("Location: ../selection_Absents.php");
        exit();
    }

    // Build date condition for the query  
    $date_condition = "";
    $params = [];

    // Handle date filtering for format like "Thu-02-20-2025"
    if ($start_date || $end_date) {
        if ($start_date && $end_date) {
            // Both start and end dates provided
            $date_condition = " AND (
                STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') >= STR_TO_DATE(?, '%Y-%m-%d') 
                AND STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') <= STR_TO_DATE(?, '%Y-%m-%d')
            )";
            $params = [$start_date, $end_date];
        } elseif ($start_date) {
            // Only start date provided
            $date_condition = " AND STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') >= STR_TO_DATE(?, '%Y-%m-%d')";
            $params = [$start_date];
        } elseif ($end_date) {
            // Only end date provided
            $date_condition = " AND STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') <= STR_TO_DATE(?, '%Y-%m-%d')";
            $params = [$end_date];
        }
    }

    // Build the complete SQL query
    $sql = "
    SELECT 
        students.student_id,
        students.student_name,
        students.class_name,
        students.department_name,
        students.study_mode,
        absents.subject_name,
        COUNT(absents.student_id) AS absence_count,
        CONCAT(COUNT(absents.student_id), ' times') AS absence_display,
        GROUP_CONCAT(absents.absent_date ORDER BY STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') ASC SEPARATOR ', ') AS absent_dates
    FROM
        students
    INNER JOIN absents ON students.student_id = absents.student_id 
        AND students.class_name = absents.class_name 
        AND absents.subject_name = ?
        AND absents.study_mode = ?
        AND absents.department_name = ?
        " . $date_condition . "
    WHERE 
        students.class_name = ?
    GROUP BY 
        students.student_id, students.student_name, absents.subject_name
    HAVING 
        COUNT(absents.student_id) > 0
    ORDER BY 
        students.student_name ASC
    ";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Build parameters array in correct order
    $all_params = [
        $subject_name,    // for absents.subject_name filter
        $study_mode,      // for absents.study_mode filter  
        $department_name  // for absents.department_name filter
    ];

    // Add date parameters if they exist
    $all_params = array_merge($all_params, $params);

    // Add final parameter
    $all_params[] = $class_name;  // for students.class_name

    // Execute with parameters
    $stmt->execute($all_params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unique students
    $total_students = count($results);

    // Create PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add header to PDF
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Image('capital.png', $pdf->GetX(), $pdf->GetY(), 30);
    $pdf->Cell(0, 10, 'Subject Absent Report', 0, 1, 'C');

    $pdf->SetTextColor(255, 0, 0); 
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, 'SINGLE SUBJECT REPORT', 0, 1, 'C');
    $pdf->SetTextColor(0); 

    $pdf->Ln(20);

    // Add Department, Class, Subject, Semester, Academic Year, Faculty, and Total Students
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 6, 'Department:', 0, 0);
    $pdf->Cell(60, 6, $department_name, 0, 1);

    $pdf->Cell(30, 6, 'Class:', 0, 0);
    $pdf->Cell(60, 6, $class_name . ' (' . $study_mode . ')', 0, 1);

    $pdf->Cell(30, 6, 'Subject:', 0, 0);
    $pdf->Cell(60, 6, $subject_name, 0, 1);

    $pdf->Cell(30, 6, 'Semester:', 0, 0);
    $pdf->Cell(60, 6, $semester, 0, 1);

    $pdf->Cell(30, 6, 'Academic Year:', 0, 0);
    $pdf->Cell(60, 6, $academic, 0, 1);

    $pdf->Cell(30, 6, 'Faculty:', 0, 0);
    $pdf->Cell(60, 6, $faculty, 0, 1);

    if ($start_date || $end_date) {
        $pdf->Cell(30, 6, 'Date Range:', 0, 0);
        if ($start_date && $end_date) {
            $pdf->Cell(60, 6, $start_date . ' to ' . $end_date, 0, 1);
        } elseif ($start_date) {
            $pdf->Cell(60, 6, 'From ' . $start_date, 0, 1);
        } elseif ($end_date) {
            $pdf->Cell(60, 6, 'Until ' . $end_date, 0, 1);
        }
    }

    $pdf->Cell(30, 6, 'Total Students:', 0, 0);
    $pdf->Cell(60, 6, $total_students, 0, 1);

    $pdf->Ln(10);

    // Check if there are results and add table headers
    if ($total_students > 0) {
        // Add table headers
        $pdf->SetFillColor(255, 255, 0);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(15, 6, 'No.', 1, 0, 'L', true);
        $pdf->Cell(25, 6, 'Student ID', 1, 0, 'L', true);
        $pdf->Cell(60, 6, 'Student Name', 1, 0, 'L', true);
        $pdf->Cell(40, 6, 'Subject Name', 1, 0, 'L', true);
        $pdf->Cell(25, 6, 'Absences', 1, 0, 'L', true);
        $pdf->Cell(30, 6, 'Absent Dates', 1, 1, 'L', true);

        // Add table content
        $pdf->SetFont('Arial', '', 7);
        $row_number = 1;
        $fill = false; // Alternating row colors

        foreach ($results as $student) {
            $absence_count = intval($student['absence_count']);
            $pdf->SetFillColor($fill ? 240 : 255); // Alternate row color
            
            $pdf->Cell(15, 6, $row_number, 1, 0, 'C', true);
            $pdf->Cell(25, 6, $student['student_id'], 1, 0, 'L', true);
            $pdf->Cell(60, 6, $student['student_name'], 1, 0, 'L', true);
            $pdf->Cell(40, 6, $student['subject_name'], 1, 0, 'L', true);
            $pdf->Cell(25, 6, $student['absence_display'], 1, 0, 'L', true);
            $pdf->Cell(30, 6, substr($student['absent_dates'] ?? 'N/A', 0, 20) . '...', 1, 1, 'L', true);

            $row_number++;
            $fill = !$fill; // Toggle row color
        }
    } else {
        // If no data is found, display the message
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'No absences found for the selected criteria.', 0, 1, 'C');
    }

    // Add generation date
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d'), 0, 0, 'R');

    // Output PDF for download
    $filename = $subject_name . '_' . $class_name . '(' . $study_mode . ')_subject_report.pdf';
    $pdf->Output('D', $filename);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
