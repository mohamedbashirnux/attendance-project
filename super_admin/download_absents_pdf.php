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
        SELECT s.student_id as student_varchar_id,
               COALESCE(s.full_name, CONCAT('Student ID: ', s.student_id)) as student_name,
               COALESCE(subj.subject_name, 'Unknown Subject') as subject_name,
               COUNT(*) as absent_count,
               COALESCE((SELECT COUNT(*) FROM attendance_sessions ats WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id), 0) as total_sessions,
               CASE WHEN COALESCE((SELECT COUNT(*) FROM attendance_sessions ats WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id), 0) > 0
                    THEN ROUND(((COALESCE((SELECT COUNT(*) FROM attendance_sessions ats WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id), 0) - COUNT(*)) * 100.0) / COALESCE((SELECT COUNT(*) FROM attendance_sessions ats WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id), 1), 2)
                    ELSE 100.00 END as attendance_percentage
        FROM absences a
        JOIN students s ON a.student_id = s.id
        LEFT JOIN subject_class sc ON a.subject_class_id = sc.id
        LEFT JOIN subjects subj ON sc.subject_id = subj.id
        WHERE a.class_id = ?
        GROUP BY s.student_id, a.subject_class_id, subj.subject_name, sc.id
        HAVING COUNT(*) > 0
        ORDER BY subj.subject_name ASC, COALESCE(s.full_name, CONCAT('Student ID: ', s.student_id)) ASC
    ");
    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unique = [];
    foreach ($results as $r) $unique[$r['student_varchar_id']] = true;
    $total_students = count($unique);

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->Image('capital.png', $pdf->GetX(), $pdf->GetY(), 30);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(0, 10, 'CLASS ABSENCE REPORT', 0, 1, 'C');
    $pdf->SetTextColor(0);
    $pdf->Ln(20);

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 6, 'Faculty:', 0, 0);    $pdf->Cell(60, 6, $class_info['faculty_name'], 0, 1);
    $pdf->Cell(30, 6, 'Department:', 0, 0); $pdf->Cell(60, 6, $class_info['department_name'], 0, 1);
    $pdf->Cell(30, 6, 'Class:', 0, 0);      $pdf->Cell(60, 6, $class_info['class_name'] . ' (' . $class_info['study_mode'] . ')', 0, 1);
    $pdf->Cell(30, 6, 'Semester:', 0, 0);   $pdf->Cell(60, 6, $class_info['semester'], 0, 1);
    $pdf->Cell(30, 6, 'Academic Year:', 0, 0); $pdf->Cell(60, 6, $class_info['academic_year'], 0, 1);
    $pdf->Cell(30, 6, 'Total Students:', 0, 0); $pdf->Cell(60, 6, $total_students, 0, 1);
    $pdf->Ln(10);

    if ($total_students > 0) {
        $pdf->SetFillColor(255, 255, 0);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(15, 6, 'No.', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Student ID', 1, 0, 'L', true);
        $pdf->Cell(50, 6, 'Student Name', 1, 0, 'L', true);
        $pdf->Cell(40, 6, 'Subject Name', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Absent', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Total', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Attend %', 1, 1, 'L', true);

        $pdf->SetFont('Arial', '', 7);
        $n = 1; $fill = false;
        foreach ($results as $r) {
            $pdf->SetFillColor($fill ? 240 : 255);
            $pdf->Cell(15, 6, $n++, 1, 0, 'C', true);
            $pdf->Cell(20, 6, $r['student_varchar_id'], 1, 0, 'L', true);
            $pdf->Cell(50, 6, $r['student_name'], 1, 0, 'L', true);
            $pdf->Cell(40, 6, $r['subject_name'], 1, 0, 'L', true);
            $pdf->Cell(20, 6, $r['absent_count'], 1, 0, 'C', true);
            $pdf->Cell(20, 6, $r['total_sessions'], 1, 0, 'C', true);
            $pdf->Cell(20, 6, $r['attendance_percentage'] . '%', 1, 1, 'C', true);
            $fill = !$fill;
        }
    } else {
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'No absence data available.', 0, 1, 'C');
    }

    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 0, 'R');
    $pdf->Output('D', $class_info['class_name'] . '_absence_report.pdf');

} catch (Exception $e) { echo "Error: " . $e->getMessage(); }
?>
