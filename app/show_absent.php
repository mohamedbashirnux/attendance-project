<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

// Function to filter and sanitize user input


// Get the student ID and subject name from the request
$student_id = filterRequest('student_id');
$subject_name = filterRequest('subject_name');

try {
    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT `absent_date` FROM `absents` WHERE student_id = :student_id AND subject_name = :subject_name ORDER BY absent_date desc");

    // Bind the parameters
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':subject_name', $subject_name, PDO::PARAM_STR);

    // Execute the statement
    $stmt->execute();

    // Fetch results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if any results were found and respond accordingly
    if ($results) {
        echo json_encode(array("status" => "success", "details" => $results));
    } else {
        echo json_encode(array("status" => "fail", "message" => "No details found for the specified student ID"));
    }

} catch (PDOException $e) {
    // Handle any errors during the database interaction
    echo json_encode(array("status" => "error", "message" => "Database query error: " . $e->getMessage()));
}
?>
