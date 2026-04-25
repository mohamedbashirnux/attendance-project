<?php
// Include the FPDF library
require('../fpdf184/fpdf.php'); // Adjust the path to your FPDF library

// Include the database connection and session management
include "../connection/connect.php";
include "session_faculty.php";

// Initialize variables
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

try {
    // Get session info for faculty information
    $sessionInfo = getSessionInfo();
    $faculty_name = $sessionInfo['faculty_name'];

    // Fetch student information using the new normalized structure
    $stmt_student = $conn->prepare("
        SELECT s.student_id, s.full_name, d.department_name, c.class_name, c.id as class_id, c.study_mode, c.semester, c.academic_year
        FROM students s 
        JOIN classes c ON s.class_id = c.id
        JOIN departments d ON c.department_id = d.id
        WHERE s.student_id = :student_id
    ");
    $stmt_student->bindParam(':student_id', $student_id, PDO::PARAM_STR);
    $stmt_student->execute();
    $student_info = $stmt_student->fetch(PDO::FETCH_ASSOC);

    // Prepare the SQL statement for absences using the new structure
    $stmt = $conn->prepare("
        SELECT a.*, s.full_name as student_name, s.student_id as student_varchar_id, 
               sub.subject_name, c.class_name, c.study_mode, d.department_name,
               t.full_name as teacher_name, a.absence_date, a.excuse
        FROM absences a 
        JOIN students s ON a.student_id = s.id
        JOIN classes c ON a.class_id = c.id
        JOIN departments d ON c.department_id = d.id
        JOIN subject_class sc ON a.subject_class_id = sc.id
        JOIN subjects sub ON sc.subject_id = sub.id
        JOIN teachers t ON a.teacher_id = t.id
        WHERE s.student_id = :student_id
        ORDER BY sub.subject_name ASC, a.absence_date DESC
    ");

    // Bind the student_id parameter
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);

    // Execute the statement
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group the results by subject_name
    $grouped_results = [];
    foreach ($results as $student) {
        $grouped_results[$student['subject_name']][] = $student;
    }

    // Get the student's details from student_info or first result
    if ($student_info) {
        $id = $student_info['student_id'];
        $student_name = $student_info['full_name'];
        $department_name = $student_info['department_name'];
        $class_name = $student_info['class_name'];
        $study_mode = $student_info['study_mode'];
        $semester = $student_info['semester'];
        $academic_year = $student_info['academic_year'];
    } else {
        $id = !empty($results) ? $results[0]['student_varchar_id'] : 'Unknown';
        $student_name = !empty($results) ? $results[0]['student_name'] : 'Unknown';
        $department_name = !empty($results) ? $results[0]['department_name'] : 'Unknown';
        $class_name = !empty($results) ? $results[0]['class_name'] : 'Unknown';
        $study_mode = !empty($results) ? $results[0]['study_mode'] : 'Unknown';
        $semester = 'Unknown';
        $academic_year = 'Academic Year 2024-2025';
    }

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

// Sub-header with student information in the correct order
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 6, 'Student ID:', 0, 0);
$pdf->Cell(60, 6, $id, 0, 1);
$pdf->Cell(30, 6, 'Student Name:', 0, 0);
$pdf->Cell(60, 6, $student_name, 0, 1);
$pdf->Cell(30, 6, 'Faculty:', 0, 0);
$pdf->Cell(60, 6, $faculty_name, 0, 1);
$pdf->Cell(30, 6, 'Department:', 0, 0);
$pdf->Cell(60, 6, $department_name, 0, 1);
$pdf->Cell(30, 6, 'Semester:', 0, 0);
$pdf->Cell(60, 6, $semester, 0, 1);
$pdf->Cell(30, 6, 'Class Name:', 0, 0);
$pdf->Cell(60, 6, $class_name, 0, 1);
$pdf->Cell(30, 6, 'Study Mode:', 0, 0);
$pdf->Cell(60, 6, $study_mode, 0, 1);
$pdf->Cell(30, 6, 'Academic Year:', 0, 0);
$pdf->Cell(60, 6, $academic_year, 0, 1);
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
        $pdf->Cell(20, 6, $student['student_varchar_id'], 1);
        $pdf->Cell(90, 6, $student['student_name'], 1);
        $pdf->Cell(40, 6, $student['absence_date'], 1);
        $pdf->Cell(40, 6, $student['excuse'], 1);
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
