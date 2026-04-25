<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];

    if (empty($user_id)) {
        echo json_encode(['success' => false, 'error' => 'User ID is required']);
        exit();
    }

    $sql = "DELETE FROM faculty_users WHERE id = :user_id";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error deleting user']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error deleting user: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn = null;
?>
