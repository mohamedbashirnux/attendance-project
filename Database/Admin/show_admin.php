<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

try {
    $sql = "SELECT id, username FROM super_admin ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $data = [];
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'id' => $row['id'],
                'username' => $row['username']
            ];
        }
    }

    echo json_encode(['success' => true, 'admins' => $data]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching admins: ' . $e->getMessage()]);
}

$conn = null;
?>
