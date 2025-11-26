<?php
session_start();
include "../../connection/connect.php";

// Check for required POST data
if (!isset($_POST['teacher_id']) || !isset($_POST['department_name']) || !isset($_POST['class_name']) || !isset($_POST['c_id']) || !isset($_POST['study_mode']) || !isset($_POST['subject_name']) || !isset($_POST['faculty_name']) || !isset($_POST['start_time']) || !isset($_POST['end_time'])) {
    echo json_encode(['status' => 'error', 'message' => 'Incomplete data']);
    exit();
}

$teacherId = $_POST['teacher_id'];
$departmentName = $_POST['department_name'];
$className = $_POST['class_name'];
$c_id = $_POST['c_id'];
$studyMode = $_POST['study_mode'];
$subjectName = $_POST['subject_name'];
$facultyName = $_POST['faculty_name'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$status = "pending";

// Check if teacher exists
$sql = "SELECT * FROM teachertable WHERE tid = :teacher_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unknown teacher. Please check the teacher ID.']);
    $stmt->closeCursor();
    $conn = null;
    exit();
}

// Check if the subject is already allocated to a different teacher
$sql = "SELECT tid FROM allocate_teacher_subject WHERE department_name = :department_name AND class_name = :class_name AND study_mode = :study_mode AND subject_name = :subject_name";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
$stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
$stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
$stmt->bindParam(':subject_name', $subjectName, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $existingTeacherId = $stmt->fetchColumn();

    if ($existingTeacherId != $teacherId) {
        echo json_encode(['status' => 'error', 'message' => 'Macalin ayaa horay uqaatay maadadan']);
        $stmt->closeCursor();
        $conn = null;
        exit();
    }
}

$stmt->closeCursor();

// Check for duplicate entries
$sql = "SELECT * FROM allocate_teacher_subject WHERE tid = :teacher_id AND department_name = :department_name AND class_name = :class_name AND study_mode = :study_mode AND subject_name = :subject_name";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
$stmt->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
$stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
$stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
$stmt->bindParam(':subject_name', $subjectName, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'macalinkan horay ayaa loosiiyay maadadan']);
    $stmt->closeCursor();
    $conn = null;
    exit();
}

// Insert data
$sql = "INSERT INTO allocate_teacher_subject (tid, teacher_name, department_name, class_name, c_id, study_mode, subject_name, faculty_name, status, start_time, end_time) VALUES (:tid, :teacher_name, :department_name, :class_name, :c_id, :study_mode, :subject_name, :faculty_name, :status, :start_time, :end_time)";
$stmt = $conn->prepare($sql);
$teacherName = $_POST['teacher_name']; // Use the value from the form input
$stmt->bindParam(':tid', $teacherId, PDO::PARAM_INT);
$stmt->bindParam(':teacher_name', $teacherName, PDO::PARAM_STR);
$stmt->bindParam(':department_name', $departmentName, PDO::PARAM_STR);
$stmt->bindParam(':class_name', $className, PDO::PARAM_STR);
$stmt->bindParam(':c_id', $c_id, PDO::PARAM_STR);
$stmt->bindParam(':study_mode', $studyMode, PDO::PARAM_STR);
$stmt->bindParam(':subject_name', $subjectName, PDO::PARAM_STR);
$stmt->bindParam(':faculty_name', $facultyName, PDO::PARAM_STR);
$stmt->bindParam(':status', $status, PDO::PARAM_STR);
$stmt->bindParam(':start_time', $start_time, PDO::PARAM_STR);
$stmt->bindParam(':end_time', $end_time, PDO::PARAM_STR);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Macalinkan si sax ah ayaa loosiiyay maadada.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to allocate subject.']);
}

$stmt->closeCursor();
$conn = null;
?>
