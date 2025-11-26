<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];
include "../../connection/connect.php";

try {
    // Retrieve and sanitize input data
    $original_student_id = filter_var($_POST['originalStudentId'], FILTER_SANITIZE_NUMBER_INT);
    $new_student_id = filter_var($_POST['editStudentId'], FILTER_SANITIZE_NUMBER_INT);
    $student_name = filter_var($_POST['editStudentName'], FILTER_SANITIZE_STRING);
    $department_name = filter_var($_POST['editDepartmentName'], FILTER_SANITIZE_STRING);
    $class_name = filter_var($_POST['editClassName'], FILTER_SANITIZE_STRING);
    $study_mode = filter_var($_POST['editStudyMode'], FILTER_SANITIZE_STRING);
    $tell = filter_var($_POST['editStudentnumber'], FILTER_SANITIZE_STRING);
    $new_password = $_POST['editPassword'];

    // Begin transaction to ensure atomicity
    $conn->beginTransaction();

    // Update the students table with non-password fields
    $stmt = $conn->prepare("
        UPDATE students 
        SET student_id = :new_student_id, student_name = :student_name, department_name = :department_name, 
        class_name = :class_name, study_mode = :study_mode, faculty_name = :faculty, tell = :tell
        WHERE student_id = :original_student_id
    ");

    $stmt->bindParam(':new_student_id', $new_student_id, PDO::PARAM_INT);
    $stmt->bindParam(':student_name', $student_name, PDO::PARAM_STR);
    $stmt->bindParam(':department_name', $department_name, PDO::PARAM_STR);
    $stmt->bindParam(':class_name', $class_name, PDO::PARAM_STR);
    $stmt->bindParam(':study_mode', $study_mode, PDO::PARAM_STR);
    $stmt->bindParam(':faculty', $faculty, PDO::PARAM_STR);
    $stmt->bindParam(':tell', $tell, PDO::PARAM_STR);
    $stmt->bindParam(':original_student_id', $original_student_id, PDO::PARAM_INT);

    if (!$stmt->execute()) {
        throw new Exception("Update failed for students.");
    }

    // If a new password is provided, update it (WITHOUT HASHING for existing students)
    if (!empty($new_password)) {
        // Fetch the current password of the student
        $stmt2 = $conn->prepare("SELECT password FROM students WHERE student_id = :new_student_id");
        $stmt2->bindParam(':new_student_id', $new_student_id, PDO::PARAM_INT);
        $stmt2->execute();
        $current_password = $stmt2->fetchColumn();

        // If there's no current password, just save the new password as plain text
        if (empty($current_password)) {
            $password_to_save = $new_password; // Store new password as plain text
        } else {
            // If there's an existing password, just update the existing password (do not hash)
            $password_to_save = $new_password; // Keep the new password as plain text for existing users too
        }

        // Update the password in the database
        $stmt3 = $conn->prepare("
            UPDATE students 
            SET password = :password_to_save
            WHERE student_id = :new_student_id
        ");

        $stmt3->bindParam(':password_to_save', $password_to_save, PDO::PARAM_STR);
        $stmt3->bindParam(':new_student_id', $new_student_id, PDO::PARAM_INT);

        if (!$stmt3->execute()) {
            throw new Exception("Password update failed.");
        }
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
