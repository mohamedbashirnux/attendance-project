<?php
require('../fpdf184/fpdf.php');
include "../connection/connect.php";

try {
    // Fetch query parameters
    $class_name = $_GET['class_name'];
    $department_name = $_GET['department_name'];
    $study_mode = $_GET['study_mode'];
    $semester = $_GET['semester'];
    $academic = $_GET['academic'];
    $subject_name = $_GET['subject_name'];

    // Get date range parameters (optional)
    $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : null;

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

    // Count total students
    $total_students = count($results);

    // Create PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add header to PDF
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Image('capital.png', $pdf->GetX(), $pdf->GetY(), 30);
    $pdf->Cell(0, 10, 'Absent Report', 0, 1, 'C');

    $pdf->SetTextColor(0, 0, 0); 
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, 'A Subject REPORT', 0, 1, 'C');
    
    $pdf->Ln(20);

    // Add Department, Class, Semester, Subject Name, and Total Students
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 6, 'Department:', 0, 0);
    $pdf->Cell(60, 6, $department_name, 0, 1);
    $pdf->Cell(30, 6, 'Class:', 0, 0);
    $pdf->Cell(60, 6, $class_name . ' ('.$study_mode.')', 0, 1);
    $pdf->Cell(30, 6, 'Semester:', 0, 0);
    $pdf->Cell(60, 6, $semester, 0, 1);
    $pdf->Cell(30, 6, 'Academic:', 0, 0);
    $pdf->Cell(60, 6, $academic, 0, 1);
    $pdf->Cell(30, 6, 'Subject Name:', 0, 0);
    $pdf->Cell(60, 6, $subject_name, 0, 1);
    
    // Add date range information if dates are filtered
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

    // Add table headers with yellow background
    $pdf->SetFillColor(255, 255, 0); // Set fill color to yellow
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(10, 6, 'No.', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Student ID', 1, 0, 'C', true);
    $pdf->Cell(80, 6, 'Student Name', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Absence Count', 1, 0, 'C', true);
    $pdf->Cell(40, 6, 'Absent Dates', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 7);

    // Add table content
    $row_number = 1;
    foreach ($results as $student) {
        $absence_count = (int)$student['absence_count'];

        // Set fill color for rows with absence count of 3 or more
        if ($absence_count >= 3) {
            $pdf->SetFillColor(255, 200, 200); // Soft red color for high absence
        } else {
            $pdf->SetFillColor(255, 255, 255); // White for normal absence
        }

        // Print row
        $pdf->Cell(10, 6, $row_number, 1, 0, 'C', true);
        $pdf->Cell(30, 6, $student['student_id'], 1, 0, '', true);
        $pdf->Cell(80, 6, $student['student_name'], 1, 0, '', true);
        $pdf->Cell(30, 6, $absence_count, 1, 0, 'C', true);
        
        // Handle absent dates - truncate if too long
        $absent_dates = $student['absent_dates'] ?? 'No dates available';
        if (strlen($absent_dates) > 25) {
            $absent_dates = substr($absent_dates, 0, 22) . '...';
        }
        $pdf->Cell(40, 6, $absent_dates, 1, 1, '', true);
        
        $row_number++;
    }

    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d'), 0, 0, 'R');

    // Output PDF for download
    $subject_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', $subject_name);
    $pdf->Output('D', $subject_name_sanitized . '_report.pdf'); 

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
