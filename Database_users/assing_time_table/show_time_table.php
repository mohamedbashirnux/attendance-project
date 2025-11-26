<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

include "../../connection/connect.php";

try {
    $faculty = $_SESSION['faculty'];

    // Select all timetable entries for this faculty
    $sql = "SELECT 
                department_name, class_name, study_mode, semester, faculty_name,
                academic_year, teacher_name, subject_name, day_of_week, time_start, time_end, location_hall
            FROM timetable
            WHERE faculty_name = :faculty";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':faculty', $faculty, PDO::PARAM_STR);
    $stmt->execute();

    $timetable = [];
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $timetable[] = [
                'department_name' => $row['department_name'],
                'class_name' => $row['class_name'],
                'study_mode' => $row['study_mode'],
                'semester' => $row['semester'],
                'faculty_name' => $row['faculty_name'],
                'academic_year' => $row['academic_year'],
                'teacher_name' => $row['teacher_name'],
                'subject_name' => $row['subject_name'],
                'day_of_week' => $row['day_of_week'],
                'time_start' => $row['time_start'],
                'time_end' => $row['time_end'],
                'location_hall' => $row['location_hall']
            ];
        }
    } else {
        $timetable = null;
    }

    echo json_encode([
        'timetable' => $timetable,
        'message' => ($timetable ? '' : 'No timetable entries found.')
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
