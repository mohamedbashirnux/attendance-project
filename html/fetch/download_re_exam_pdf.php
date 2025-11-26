<?php
require('../../fpdf184/fpdf.php');
include "../../connection/connect.php";

try {
    // Fetch query parameters
    $class_name = $_GET['class_name'] ?? '';
    $department_name = $_GET['department_name'] ?? '';
    $study_mode = $_GET['study_mode'] ?? '';
    $semester = $_GET['semester'] ?? '';
    $faculty = $_GET['faculty'] ?? '';
    $academic = $_GET['academic'] ?? '';

    // Validate required parameters
    if (empty($class_name) || empty($department_name) || empty($study_mode)) {
        header("Location: ../selection_Absents.php");
        exit();
    }

    // Prepare and execute the SQL query for the exam report (students with 30%+ absence)
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
    WHERE
        absents.class_name = :class_name AND absents.department_name = :department_name AND absents.study_mode = :study_mode
    GROUP BY 
        absents.subject_name, absents.student_name
    HAVING 
        (COUNT(*) / (SELECT total_days FROM (SELECT student_name, subject_name, COUNT(DISTINCT student_name) AS total_days  FROM absents WHERE class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode GROUP BY student_name, subject_name) AS total_days_table_internal WHERE total_days_table_internal.student_name = absents.student_name AND total_days_table_internal.subject_name = absents.subject_name) * 10) >= 30
    ORDER BY 
        absents.student_name ASC
    ");

    // Bind query parameters
    $stmt->bindParam(':class_name', $class_name);
    $stmt->bindParam(':department_name', $department_name);
    $stmt->bindParam(':study_mode', $study_mode);

    $stmt->execute();

    // Fetch results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unique students
    $unique_students = [];
    foreach ($results as $student) {
        $unique_students[$student['student_id']] = $student['student_name'];
    }
    $total_students = count($unique_students);

    // Create PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Add header to PDF
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Image('capital.png', $pdf->GetX(), $pdf->GetY(), 30);
    $pdf->Cell(0, 10, 'Re-Exam Report', 0, 1, 'C');

    $pdf->SetTextColor(255, 0, 0); 
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, 'RE-EXAM REPORT', 0, 1, 'C');
    $pdf->SetTextColor(0); 

    $pdf->Ln(20);

    // Add Department, Class, Semester, Academic Year, Faculty, and Total Students
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 6, 'Department:', 0, 0);
    $pdf->Cell(60, 6, $department_name, 0, 1);

    $pdf->Cell(30, 6, 'Class:', 0, 0);
    $pdf->Cell(60, 6, $class_name . ' (' . $study_mode . ')', 0, 1);

    $pdf->Cell(30, 6, 'Semester:', 0, 0);
    $pdf->Cell(60, 6, $semester, 0, 1);

    $pdf->Cell(30, 6, 'Academic Year:', 0, 0);
    $pdf->Cell(60, 6, $academic, 0, 1);

    $pdf->Cell(30, 6, 'Faculty:', 0, 0);
    $pdf->Cell(60, 6, $faculty, 0, 1);

    $pdf->Cell(30, 6, 'Total Students (30%+ Absence):', 0, 0);
    $pdf->Cell(60, 6, $total_students, 0, 1);

    $pdf->Ln(10);

    // Check if there are results and add table headers
    if ($total_students > 0) {
        // Add table headers
        $pdf->SetFillColor(255, 255, 0);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(15, 6, 'No.', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Student ID', 1, 0, 'L', true);
        $pdf->Cell(70, 6, 'Student Name', 1, 0, 'L', true);
        $pdf->Cell(50, 6, 'Subject Name', 1, 0, 'L', true);
        $pdf->Cell(30, 6, 'Absence Percentage', 1, 1, 'L', true);

        // Add table content
        $pdf->SetFont('Arial', '', 7);
        $row_number = 1;
        $printed_students = [];
        $fill = false;

        foreach ($results as $student) {
            // Check if student was already printed
            if (!in_array($student['student_id'], $printed_students)) {
                // First instance of the student, print row number and student details
                $pdf->SetFillColor($fill ? 240 : 255);
                $pdf->Cell(15, 6, $row_number, 1, 0, 'C', true);
                $pdf->Cell(20, 6, $student['student_id'], 1, 0, 'L', true);
                $pdf->Cell(70, 6, $student['student_name'], 1, 0, 'L', true);
                $pdf->Cell(50, 6, $student['subject_name'], 1, 0, 'L', true);
                $pdf->Cell(30, 6, $student['absence_percentage'], 1, 1, 'L', true);

                // Mark student as printed
                $printed_students[] = $student['student_id'];
                $row_number++;
                $fill = !$fill;
            } else {
                // Subsequent instance of the student, do not print row number
                $pdf->SetFillColor($fill ? 240 : 255);
                $pdf->Cell(15, 6, '', 1, 0, 'C', true);
                $pdf->Cell(20, 6, $student['student_id'], 1, 0, 'L', true);
                $pdf->Cell(70, 6, $student['student_name'], 1, 0, 'L', true);
                $pdf->Cell(50, 6, $student['subject_name'], 1, 0, 'L', true);
                $pdf->Cell(30, 6, $student['absence_percentage'], 1, 1, 'L', true);

                $fill = !$fill;
            }
        }
    } else {
        // If no data is found, display the message
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'No students found with 30% or more absence.', 0, 1, 'C');
    }

    // Add generation date
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d'), 0, 0, 'R');

    // Output PDF for download
    $pdf->Output('D', $class_name . '(' . $study_mode . ')_re_exam_report.pdf');

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
