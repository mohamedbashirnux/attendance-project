<?php
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the admin is not logged in
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     // Redirect to login page if not logged in
//     header("Location: auth_admin.php");
//     exit();
// }

// Check if a file is uploaded
if (!isset($_FILES['teacherFile'])) {
    die("No file uploaded");
}

// Include PhpSpreadsheet autoload file
// require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = $_FILES['teacherFile']['tmp_name'];

try {
    // Load the uploaded Excel file
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Database connection parameters
    include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

    // Prepare SQL statement for inserting data into teachertable
    $stmt = $conn->prepare("INSERT INTO teachertable (tid, teacher_name, username, password) VALUES (:tid, :teacher_name, :username, :password)");

    // Iterate through each row of data from the spreadsheet
    foreach ($rows as $index => $row) {
        if ($index == 0) continue; // Skip header row if there is one

        $tid = (int) $row[0]; // Assuming the first column contains tid
        $teacher_name = $row[1] ?? ''; // Assuming the second column contains teacher_name
        $username = $row[2] ?? ''; // Assuming the third column contains username
        $password = $row[3] ?? ''; // Assuming the fourth column contains password

        // Bind parameters to the SQL statement and execute it
        $stmt->bindParam(':tid', $tid, PDO::PARAM_INT);
        $stmt->bindParam(':teacher_name', $teacher_name);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
            echo "Data inserted successfully: tid=$tid, teacher_name=$teacher_name, username=$username, password=$password<br>";
        } else {
            echo "Error executing statement: " . $stmt->errorInfo()[2] . "<br>";
        }
    }

    // Close the database connection
    $conn = null; // Use null to close the PDO connection

    // Output success message
    echo "Teachers imported successfully";

} catch (Exception $e) {
    // Display error message if any exception occurs
    echo "Error loading file: " . $e->getMessage();
}
?>
