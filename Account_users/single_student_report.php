<?php
// Include the FPDF library
require('../fpdf184/fpdf.php'); // Adjust the path to your FPDF library

// Include the database connection
include "../connection/connect.php";

// Initialize variables
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

try {
    // Prepare the SQL statement
    $stmt = $conn->prepare("
    SELECT * FROM `absents` 
    WHERE student_id = :student_id 
    ORDER BY subject_name ASC;
    ");

    // Bind the student_id parameter
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group the results by subject_name
    $grouped_results = [];
    foreach ($results as $student) {
        $grouped_results[$student['subject_name']][] = $student;
    }

    // Get the student's details from the first result
    $id = !empty($results) ? $results[0]['student_id'] : 'unknown_student';
    $student_name = !empty($results) ? $results[0]['student_name'] : 'unknown_student';
    $department_name = !empty($results) ? $results[0]['department_name'] : 'unknown_student';
    $class_name = !empty($results) ? $results[0]['class_name'] : 'unknown_student';
    $study_mode = !empty($results) ? $results[0]['study_mode'] : 'unknown_student';
    $semester = !empty($results) ? $results[0]['semester'] : 'unknown_student';
    $semester = !empty($results) ? $results[0]['academic'] : 'unknown_student';

} catch (PDOException $e) {
    // Handle any errors
    die("Error: " . $e->getMessage());
}

// Create a new PDF document
$pdf = new FPDF();
$pdf->AddPage();

// Set header and title
$pdf->SetFont('Arial', 'B', 12);
$pdf->Image('capital.png', $pdf->GetX(), $pdf->GetY(), 30);
$pdf->Cell(0, 10, 'Absent Report', 0, 1, 'C');

$pdf->SetTextColor(255, 0, 0); 
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(0, 10, 'A STUDENT REPORT', 0, 1, 'C');
$pdf->SetTextColor(0); 

$pdf->Ln(20);

// Sub-header with student and faculty information
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 6, 'Student Id:', 0, 0);
$pdf->Cell(60, 6, $id, 0, 1);
$pdf->Cell(30, 6, 'Student Name:', 0, 0);
$pdf->Cell(60, 6, $student_name, 0, 1);
$pdf->Cell(30, 6, 'Department Name:', 0, 0);
$pdf->Cell(60, 6, $department_name, 0, 1);
$pdf->Cell(30, 6, 'Class Name:', 0, 0);
$pdf->Cell(60, 6, $class_name . ' ('.$study_mode.')', 0, 1);
$pdf->Cell(30, 6, 'Semester:', 0, 0);
$pdf->Cell(60, 6, $semester, 0, 1);
$pdf->Ln(10);

// Loop through the grouped results and add them to the PDF
foreach ($grouped_results as $subject => $students) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(255, 255, 0);
    $pdf->Cell(0, 6, 'Subject: ' . $subject, 1, 1, 'L', true);
    
    // Column headers
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 6, 'Student ID', 1);
    $pdf->Cell(90, 6, 'Student Name', 1);
    $pdf->Cell(40, 6, 'Absent Date', 1);
    $pdf->Cell(40, 6, 'Excuses', 1);
    $pdf->Ln();

    // Data rows
    $pdf->SetFont('Arial', '', 7);
    foreach ($students as $student) {
        $pdf->Cell(20, 6, $student['student_id'], 1);
        $pdf->Cell(90, 6, $student['student_name'], 1);
        $pdf->Cell(40, 6, $student['absent_date'], 1);
        $pdf->Cell(40, 6, $student['excuses'], 1);
        $pdf->Ln();
    }
    $pdf->Ln(5);
}
$pdf->Ln(10);

// Add a signature section
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, 'Authorized Signature:', 0, 1, 'L');
$pdf->Ln(15);
$pdf->Cell(50, 10, '____________________', 0, 1, 'L');
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(50, 10, 'Head of Department', 0, 1, 'L');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d'), 0, 0, 'R');

// Sanitize the student's name for the filename
$student_name_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', $student_name);

// Output the PDF with the student's name as the file name
$pdf->Output('D', $student_name_sanitized . '.pdf'); // 'D' forces download

?>
