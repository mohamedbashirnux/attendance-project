<?php
session_start();

// Include database connection
include '../../connection/connect.php';// Adjust the path as necessary

// Fetch notifications from the database
try {
    // Using the $conn variable defined in conn.php
    $stmt = $conn->query('SELECT id, title, body, created_at FROM notifications ORDER BY created_at DESC');
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output notifications as HTML
    if ($notifications) {
        foreach ($notifications as $notification) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($notification['title']) . '</td>';
            echo '<td>' . nl2br(htmlspecialchars($notification['body'])) . '</td>';
            echo '<td>' . date('F j, Y, g:i a', strtotime($notification['created_at'])) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="3">No notifications found</td></tr>';
    }
} catch (PDOException $e) {
    echo '<tr><td colspan="3">Database error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
    exit();
}
?>