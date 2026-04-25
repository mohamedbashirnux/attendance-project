<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];

    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        exit();
    }

    $sql = "DELETE FROM super_admin WHERE username = :username";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting admin']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting admin: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn = null;
?>
