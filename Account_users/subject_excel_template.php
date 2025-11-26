<?php
// This block checks if the button is clicked and processes the file download
if (isset($_POST['download'])) {
    // File path of the file you want to download
    $file = '../uploads/subject_data.xlsx';  // Change to your actual file path
    $newFileName = 'subject_import_template.xlsx';

    // Check if the file exists
    if (file_exists($file)) {
        // Set headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $newFileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));


        // Read the file and output its contents for download
        readfile($file);
        exit;
    } else {
        echo "File not found!";
    }
}
?>