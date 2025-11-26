<?php
// delete_absents.php

include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the POST data
    $class_name = urldecode($_POST['class_name']);
    $study_mode = urldecode($_POST['study_mode']);
    $department_name = urldecode($_POST['department_name']);

    try {
        // Prepare the SQL statement to delete the absence records
        $sql = "DELETE FROM absents WHERE class_name = ? AND study_mode = ? AND department_name = ?";
        $stmt = $conn->prepare($sql);

        // Execute the statement with bound parameters
        if ($stmt->execute([$class_name, $study_mode, $department_name])) {
            echo 'success';
        } else {
            echo 'error';
        }

        // Close the statement
        $stmt = null;

    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }

    // Close the connection
    $conn = null;
}
?>
