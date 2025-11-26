<?php
// Database connection using PDO
include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

try {
    // Fetch faculties
    $query = "SELECT faculty_name FROM facultytable";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    // Generate options for select dropdown
    $options = '';
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $options .= '<option value="' . htmlspecialchars($row['faculty_name'], ENT_QUOTES) . '">' . htmlspecialchars($row['faculty_name'], ENT_QUOTES) . '</option>';
        }
    }

    echo $options;

} catch (PDOException $e) {
    die("Error executing query: " . $e->getMessage());
}

// Close connection
$conn = null;
?>
