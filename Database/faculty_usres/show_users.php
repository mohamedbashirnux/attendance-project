<?php
header('Content-Type: application/json');

include "../../connection/connect.php";

try {
    // Check if action is to get faculties for dropdown
    if (isset($_GET['action']) && $_GET['action'] == 'get_faculties') {
        $sql = "SELECT id, faculty_name FROM faculty ORDER BY id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $data = [];
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = [
                    'id' => $row['id'],
                    'faculty_name' => $row['faculty_name']
                ];
            }
        }
        echo json_encode(['success' => true, 'faculties' => $data]);
        exit();
    }

    // Default: Get users list with faculty names
    $sql = "SELECT fu.id, fu.username, f.faculty_name, fu.created_at 
            FROM faculty_users fu 
            INNER JOIN faculty f ON fu.faculty_id = f.id 
            ORDER BY fu.id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $data = [];
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'id' => $row['id'],
                'faculty_name' => $row['faculty_name'],
                'username' => $row['username'],
                'created_at' => $row['created_at']
            ];
        }
    }

    echo json_encode(['success' => true, 'users' => $data]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching users: ' . $e->getMessage()]);
}

$conn = null;
?>
