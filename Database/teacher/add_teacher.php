<?php
include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $id = $_POST['id'];
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Check if a teacher with the same ID or username already exists
        $checkQuery = "SELECT * FROM teachertable WHERE tid = :tid OR username = :username";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':tid', $id);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $existingId = false;
        $existingUsername = false;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['tid'] == $id) {
                $existingId = true;
            }
            if ($row['username'] == $username) {
                $existingUsername = true;
            }
        }

        if ($existingId && $existingUsername) {
            echo json_encode(['success' => false, 'error' => 'both_exists']);
        } elseif ($existingId) {
            echo json_encode(['success' => false, 'error' => 'id_exists']);
        } elseif ($existingUsername) {
            echo json_encode(['success' => false, 'error' => 'username_exists']);
        } else {
            // Prepare and execute SQL statement to insert teacher data
            $sql = "INSERT INTO teachertable (tid, teacher_name, username, password) VALUES (:tid, :fullname, :username, :password)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':tid', $id);
            $stmt->bindParam(':fullname', $fullname);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Teacher added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding teacher']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Close connection
$conn = null;
?>
