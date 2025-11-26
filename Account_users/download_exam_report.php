<?php
require('../fpdf184/fpdf.php');
include "../connection/connect.php";

$class_name = $_GET['class_name'];
$department_name = $_GET['department_name'];
$study_mode = $_GET['study_mode'];
$semester = $_GET['semester'];
$academic = $_GET['academic'];
$faculty = $_GET['faculty'];

// SQL query to fetch absent students, sorted by student name
$stmt = $conn->prepare("
SELECT 
    absents.*,
    CONCAT('Absent- ', FORMAT((COUNT(*) / total_days_table.total_days * 10), 1), '%') AS absence_percentage
FROM 
    absents
INNER JOIN (
    SELECT 
        student_name,
        subject_name,
        COUNT(DISTINCT student_name) AS total_days
    FROM
        absents
    WHERE
        class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode
    GROUP BY 
        student_name, subject_name
) AS total_days_table 
ON absents.student_name = total_days_table.student_name AND absents.subject_name = total_days_table.subject_name
GROUP BY 
    absents.subject_name, absents.student_name
HAVING 
    (COUNT(*) / (SELECT total_days FROM (SELECT student_name, subject_name, COUNT(DISTINCT student_name) AS total_days  FROM absents WHERE class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode GROUP BY student_name, subject_name) AS total_days_table_internal WHERE total_days_table_internal.student_name = absents.student_name AND total_days_table_internal.subject_name = absents.subject_name) * 10) >= 30
ORDER BY absents.student_name ASC;  // Sort the results by student name
");

$stmt->bindParam(':class_name', $class_name);
$stmt->bindParam(':department_name', $department_name);
$stmt->bindParam(':study_mode', $study_mode);
$stmt->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create an array to track unique student IDs for the total count
$unique_students = array_unique(array_column($results, 'student_id'));
$total_students = count($unique_students); // Count of unique students

$pdf = new FPDF();
$pdf->AddPage();

// Header of the PDF
$pdf->SetFont('Arial', 'B', 12);
$pdf->Image('capital.png', $pdf->GetX(), $pdf->GetY(), 30);
$pdf->Cell(0, 10, 'Absent Report', 0, 1, 'C');

$pdf->SetTextColor(255, 0, 0); 
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(0, 10, 'Students Who Will Miss the Exam', 0, 1, 'C'); // Title indicating students missing exam
$pdf->SetTextColor(0); 

$pdf->Ln(20);

$pdf->SetFillColor(255, 255, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 6, 'Department:', 0, 0);
$pdf->Cell(60, 6, $department_name, 0, 1);
$pdf->Cell(30, 6, 'Class:', 0, 0);
$pdf->Cell(60, 6, $class_name . ' ('.$study_mode.')', 0, 1);
$pdf->Cell(30, 6, 'Semester:', 0, 0);
$pdf->Cell(60, 6, $semester, 0, 1);
$pdf->Cell(30, 6, 'academic:', 0, 0);
$pdf->Cell(60, 6, $academic, 0, 1);
$pdf->Cell(30, 6, 'Total Students:', 0, 0);
$pdf->Cell(60, 6, $total_students, 0, 1);

$pdf->Ln(10);
$pdf->SetFillColor(255, 255, 0);
$pdf->SetFont('Arial', 'B', 8);

// Added Column Number Header
$pdf->Cell(10, 6, 'No.', 1, 0, 'L', true); // Column for Row Number
$pdf->Cell(30, 6, 'Student ID', 1, 0, 'L', true);
$pdf->Cell(60, 6, 'Student Name', 1, 0, 'L', true);
$pdf->Cell(60, 6, 'Subject Name', 1, 0, 'L', true);
$pdf->Cell(40, 6, 'Absence Percentage', 1, 1, 'L', true);

$pdf->SetFont('Arial', '', 7);

$row_number = 1; // Initialize row number
$printed_students = []; // Array to track printed students

foreach ($results as $student) {
    // Check if student was already printed
    if (!in_array($student['student_id'], $printed_students)) {
        // First instance of the student, print row number and details
        $pdf->Cell(10, 6, $row_number, 1); // Display row number
        $printed_students[] = $student['student_id']; // Mark student as printed
        $row_number++; // Increment row number
    } else {
        // Subsequent instance of the student, leave row number blank
        $pdf->Cell(10, 6, '', 1); // Leave column for row number blank
    }

    $pdf->Cell(30, 6, $student['student_id'], 1);
    $pdf->Cell(60, 6, $student['student_name'], 1);
    $pdf->Cell(60, 6, $student['subject_name'], 1);
    $pdf->Cell(40, 6, $student['absence_percentage'], 1);
    $pdf->Ln();
}

$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d'), 0, 0, 'R');

$pdf->Output('D', 'Exam_Report.pdf');
?>
