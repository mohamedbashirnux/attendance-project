<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendanceproject";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require('library/php-excel-reader/excel_reader2.php');
require('library/SpreadsheetReader.php');

if (isset($_POST['Submit'])) {
    $allowedMimes = [
        'application/vnd.ms-excel', 
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/xls', 
        'text/xlsx', 
        'application/vnd.oasis.opendocument.spreadsheet'
    ];

    $fileType = $_FILES["file"]["type"];

    if (in_array($fileType, $allowedMimes)) {
        $uploadFilePath = 'uploads/' . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], $uploadFilePath);

        $Reader = new SpreadsheetReader($uploadFilePath);
        $totalSheet = count($Reader->sheets());

        echo "You have total " . $totalSheet . " sheets<br>";

        $Reader->ChangeSheet(0);
        echo "count=" . count($Reader) . " added <br>";
        $count = 0;

        foreach ($Reader as $Row) {
            $count++;
            $subjectName = isset($Row[0]) ? $Row[0] : '';
            $department = isset($Row[1]) ? $Row[1] : '';
            $semester = isset($Row[2]) ? $Row[2] : '';
            $faculty = isset($Row[3]) ? $Row[3] : '';

            if ($count == 1) continue; // skips titles from excel file while inserting

            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, department_name, semester, faculty_name) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssss', $subjectName, $department, $semester, $faculty);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
            }
        }

        echo "<br />Data Inserted in database";

    } else {
        die("<br/>Sorry, File type error. Only Excel file allowed.");
    }

    $conn->close();
}
?>
