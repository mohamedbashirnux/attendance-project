<?php
require('../fpdf184/fpdf.php');
include 'seassion_super-admin.php';
include '../connection/connect.php';

try {
    $class_id = $_GET['class_id'] ?? '';
    if (empty($class_id)) throw new Exception('Missing class_id');

    $class_stmt = $conn->prepare("
        SELECT c.class_name, c.study_mode, c.semester, c.academic_year,
               d.department_name, f.faculty_name
        FROM classes c
        JOIN departments d ON c.department_id = d.id
        JOIN faculty f ON c.faculty_id = f.id
        WHERE c.id = ?
    ");
    $class_stmt->execute([$class_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class_info) throw new Exception('Class not found');

    $stmt = $conn->prepare("
        SELECT s.student_id, s.full_name as student_name, subj.subject_name, COUNT(a.id) AS absence_count
        FROM absences a
        INNER JOIN students s ON a.student_id = s.id
        INNER JOIN subject_class sc ON a.subject_class_id = sc.id
        INNER JOIN subjects subj ON sc.subject_id = subj.id
        WHERE a.class_id = ?
        GROUP BY s.id, s.student_id, s.full_name, subj.subject_name
        HAVING absence_count >= 3
        ORDER BY s.full_name ASC, subj.subject_name ASC
    ");
    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unique = [];
    foreach ($results as $r) $unique[$r['student_id']] = true;
    $total_students = count($unique);

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->Image('capital.png', 10, 10, 25);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetY(15);
    $pdf->Cell(0, 8, 'RE-EXAMINATION REPORT', 0, 1, 'C');
    $pdf->Ln(15);

    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(50, 7, 'Faculty:', 0, 0);
    $pdf->SetFont('Arial', '', 10);  $pdf->Cell(0, 7, $class_info['faculty_name'], 0, 1);
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(50, 7, 'Department:', 0, 0);
    $pdf->SetFont('Arial', '', 10);  $pdf->Cell(0, 7, $class_info['department_name'], 0, 1);
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(50, 7, 'Class:', 0, 0);
    $pdf->SetFont('Arial', '', 10);  $pdf->Cell(0, 7, $class_info['class_name'] . ' (' . $class_info['study_mode'] . ')', 0, 1);
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(50, 7, 'Semester:', 0, 0);
    $pdf->SetFont('Arial', '', 10);  $pdf->Cell(0, 7, $class_info['semester'], 0, 1);
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(50, 7, 'Academic Year:', 0, 0);
    $pdf->SetFont('Arial', '', 10);  $pdf->Cell(0, 7, $class_info['academic_year'], 0, 1);
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(50, 7, 'Students Requiring Re-exam:', 0, 0);
    $pdf->SetFont('Arial', '', 10);  $pdf->Cell(0, 7, $total_students, 0, 1);
    $pdf->Ln(10);

    if ($total_students > 0) {
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(15, 8, 'No.', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Student ID', 1, 0, 'C', true);
        $pdf->Cell(65, 8, 'Student Name', 1, 0, 'C', true);
        $pdf->Cell(55, 8, 'Subject Name', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Absences', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 8);
        $n = 1; $prev = '';
        foreach ($results as $r) {
            if ($r['student_name'] !== $prev) { $pdf->Cell(15, 7, $n++, 1, 0, 'C'); } else { $pdf->Cell(15, 7, '', 1, 0, 'C'); }
            $pdf->Cell(25, 7, $r['student_id'], 1, 0, 'L');
            $pdf->Cell(65, 7, $r['student_name'], 1, 0, 'L');
            $pdf->Cell(55, 7, $r['subject_name'], 1, 0, 'L');
            $pdf->Cell(25, 7, $r['absence_count'] . ' times', 1, 1, 'C');
            $prev = $r['student_name'];
        }

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(220, 53, 69);
        $pdf->Cell(0, 6, 'Students listed have missed 3 or more sessions in at least one subject.', 0, 1, 'L');
        $pdf->SetTextColor(0);
    } else {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(40, 167, 69);
        $pdf->Cell(0, 10, 'Excellent! All students have good attendance.', 0, 1, 'C');
        $pdf->SetTextColor(0);
    }

    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(0, 10, 'Authorized Signature:', 0, 1, 'L');
    $pdf->Ln(15);
    $pdf->Cell(50, 10, '____________________', 0, 1, 'L');
    $pdf->SetFont('Arial', 'I', 8); $pdf->Cell(50, 10, 'Head of Department', 0, 1, 'L');
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 0, 'R');
    $pdf->Output('D', $class_info['class_name'] . '_re_exam_report.pdf');

} catch (Exception $e) { echo "Error: " . $e->getMessage(); }
?>
