<?php
// Use YOUR custom Excel file - clean and perfect!
$file = '../uploads/Student_template_real.xlsx';
$newFileName = 'student_import_template.xlsx';

// Check if the file exists
if (file_exists($file)) {
    // Set headers to force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $newFileName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));

    // Read the file and output its contents for download
    readfile($file);
    exit;
} else {
    echo "File not found! Looking for: " . realpath($file);
}
?>