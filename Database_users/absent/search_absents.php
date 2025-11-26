<?php
include "../../connection/connect.php";

$class_name = $_GET['class_name'];
$department_name = $_GET['department_name'];
$study_mode = $_GET['study_mode'];
$faculty = $_GET['faculty'];
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

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
        COUNT(DISTINCT CONCAT(subject_name, class_name)) AS total_days
    FROM
        absents
    WHERE
        class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode
    " . ($student_id ? " AND student_id LIKE :student_id" : "") . "
    GROUP BY 
        student_name, subject_name
) AS total_days_table 
ON absents.student_name = total_days_table.student_name AND absents.subject_name = total_days_table.subject_name
GROUP BY 
    absents.student_name, absents.subject_name, absents.statuses
");

$stmt->bindParam(':class_name', $class_name);
$stmt->bindParam(':department_name', $department_name);
$stmt->bindParam(':study_mode', $study_mode);
if ($student_id) {
    $student_id_param = "%{$student_id}%";
    $stmt->bindParam(':student_id', $student_id_param);
}

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($results)) {
    foreach ($results as $student) {
        $percentage_value = floatval(str_replace(['Absent- ', '%'], '', $student['absence_percentage']));
        $badge_color = 'success'; // Default to green

        if ($percentage_value == 10) {
            $badge_color = 'success'; // Green
        } elseif ($percentage_value == 20) {
            $badge_color = 'warning'; // Yellow/Orange (Warning)
        } elseif ($percentage_value >= 30) {
            $badge_color = 'danger'; // Red
        }

        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
        echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';
        echo '<td>' . htmlspecialchars($student['class_name']) . '</td>';
        echo '<td><span class="badge bg-' . $badge_color . '">' . htmlspecialchars($student['absence_percentage']) . '</span></td>';
        echo '<td class="text-end">
                <button class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . htmlspecialchars($student['student_name']) . '" 
                        data-subject="' . htmlspecialchars($student['subject_name']) . '"
                        data-class="' . htmlspecialchars($student['class_name']) . '"
                        data-department="' . htmlspecialchars($department_name) . '"
                        data-study-mode="' . htmlspecialchars($study_mode) . '"
                        data-faculty="' . htmlspecialchars($faculty) . '"
                        data-date="' .  htmlspecialchars($student['absent_date'])  . '"
                        >Remove Absent</button>
            </td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center">No data for Students available</td></tr>';
}
?>
