<?php
// Include database connection
include '../../connection/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Prepare and execute the SQL statement
        $stmt = $conn->prepare('INSERT INTO notifications (title, body, created_at) VALUES (:title, :body, NOW())');
        $stmt->execute([
            ':title' => $_POST['notificationTitle'],
            ':body' => $_POST['notificationBody']
        ]);

        echo json_encode(array("status" => "success", "message" => "Notification added successfully!"));
    } catch (PDOException $e) {
        echo json_encode(array("status" => "fail", "message" => "Database error: " . $e->getMessage()));
    }
}
?>
