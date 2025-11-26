<?php
require('../fpdf184/fpdf.php');
include "../connection/connect.php";

try {
    // Fetch query parameters
    $class_name = $_GET['class_name'];
    $department_name = $_GET['department_name'];
    $study_mode = $_GET['study_mode'];
    $semester = $_GET['semester'];
    $faculty = $_GET['faculty'];

    // Query to fetch distinct student data, ordered by student name ASC
    $stmt = $conn->prepare("
    SELECT 
        absents.student_id,
        absents.student_name,
        absents.subject_name,
        CONCAT('Absent- ', FORMAT((COUNT(*) / total_days_table.total_days * 10), 1), '%') AS absence_percentage
    FROM 
        absents
    INNER JOIN (
        SELECT 
            student_name,
            subject_name,
            COUNT(DISTINCT CONCAT(subject_name, class_name)) AS total_days
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
        absents.student_id, absents.student_name, absents.subject_name
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

    // Count total unique students
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
    $pdf->Cell(0, 10, 'Absent Report', 0, 1, 'C');

    $pdf->SetTextColor(255, 0, 0); 
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, 'A Class REPORT', 0, 1, 'C');
    $pdf->SetTextColor(0); 

    $pdf->Ln(20);

    // Add Department, Class, Semester, and Total Students
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 6, 'Department:', 0, 0);
    $pdf->Cell(60, 6, $department_name, 0, 1);

    $pdf->Cell(30, 6, 'Class:', 0, 0);
    $pdf->Cell(60, 6, $class_name . ' (' . $study_mode . ')', 0, 1);

    $pdf->Cell(30, 6, 'Semester:', 0, 0);
    $pdf->Cell(60, 6, $semester, 0, 1);

    $pdf->Cell(30, 6, 'Total Students:', 0, 0);
    $pdf->Cell(60, 6, $total_students, 0, 1);

    $pdf->Ln(10);

    // Check if there are results and add table headers
    if ($total_students > 0) {
        // Add table headers
        $pdf->SetFillColor(255, 255, 0);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(15, 6, 'No.', 1, 0, 'L', true);       // Smaller column for No.
        $pdf->Cell(20, 6, 'Student ID', 1, 0, 'L', true); // Smaller column for Student ID
        $pdf->Cell(70, 6, 'Student Name', 1, 0, 'L', true); // Increased size for Student Name
        $pdf->Cell(50, 6, 'Subject Name', 1, 0, 'L', true); // Adjusted Subject Name column
        $pdf->Cell(30, 6, 'Absence Percentage', 1, 1, 'L', true);

        // Add table content
        $pdf->SetFont('Arial', '', 7);
        $row_number = 1;
        $printed_students = [];
        $fill = false; // Alternating row colors

        foreach ($results as $student) {
            // Check if student was already printed
            if (!in_array($student['student_id'], $printed_students)) {
                // First instance of the student, print row number and student details
                $pdf->SetFillColor($fill ? 240 : 255); // Alternate row color
                $pdf->Cell(15, 6, $row_number, 1, 0, 'C', true);  // Smaller column for No.
                $pdf->Cell(20, 6, $student['student_id'], 1, 0, 'L', true); // Smaller column for Student ID
                $pdf->Cell(70, 6, $student['student_name'], 1, 0, 'L', true); // Increased Student Name column size
                $pdf->Cell(50, 6, $student['subject_name'], 1, 0, 'L', true); // Adjusted Subject Name column size
                $pdf->Cell(30, 6, $student['absence_percentage'], 1, 1, 'L', true);

                // Mark student as printed
                $printed_students[] = $student['student_id'];
                $row_number++;  // Increment row number
                $fill = !$fill; // Toggle row color
            } else {
                // Subsequent instance of the student, do not print row number
                $pdf->SetFillColor($fill ? 240 : 255); // Alternate row color
                $pdf->Cell(15, 6, '', 1, 0, 'C', true);  // Leave column for row number blank
                $pdf->Cell(20, 6, $student['student_id'], 1, 0, 'L', true);
                $pdf->Cell(70, 6, $student['student_name'], 1, 0, 'L', true); // Increased Student Name column size
                $pdf->Cell(50, 6, $student['subject_name'], 1, 0, 'L', true);
                $pdf->Cell(30, 6, $student['absence_percentage'], 1, 1, 'L', true);

                $fill = !$fill; // Toggle row color
            }
        }
    } else {
        // If no data is found, display the message
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'No data available.', 0, 1, 'C');
    }

    // Add generation date
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d'), 0, 0, 'R');

    // Output PDF for download
    $pdf->Output('D', $class_name . '(' . $study_mode . ')_report.pdf');

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
