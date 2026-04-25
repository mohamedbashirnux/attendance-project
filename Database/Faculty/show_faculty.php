<?php
header('Content-Type: application/json');

// Database connection using PDO
include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

try {
    // SQL query to fetch faculty members
    $sql = "SELECT * FROM faculty ORDER BY id ASC";

    // Execute the query
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Prepare data for JSON response
    $data = [];
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'fid' => $row['id'],
                'faculty_name' => $row['faculty_name']
            ];
        }
    }

    // Send JSON response
    echo json_encode(['success' => true, 'faculties' => $data]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching faculty members: ' . $e->getMessage()]);
}

// Close connection
$conn = null;
?>
