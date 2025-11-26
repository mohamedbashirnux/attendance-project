<?php
include "../../connection/connect.php";

$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $originalUsername = $_POST['originalUsername'];
    $newUsername = $_POST['username'];
    $newPassword = $_POST['password'];

    // Validate inputs
    if (empty($newUsername) || empty($newPassword)) {
        $response['success'] = false;
        $response['message'] = 'Username and Password cannot be empty!';
    } else {
        try {
            // Check if the new username already exists (except for the current one)
            $check_query = "SELECT COUNT(*) AS count FROM admintable WHERE username = :newUsername AND username != :originalUsername";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindParam(':newUsername', $newUsername, PDO::PARAM_STR);
            $check_stmt->bindParam(':originalUsername', $originalUsername, PDO::PARAM_STR);
            $check_stmt->execute();
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $existing_count = $row['count'];

            if ($existing_count > 0) {
                $response['success'] = false;
                $response['message'] = 'Username already exists!';
            } else {
                // Update the admin data
                $update_query = "UPDATE admintable SET username = :newUsername, password = :newPassword WHERE username = :originalUsername";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':newUsername', $newUsername, PDO::PARAM_STR);
                $update_stmt->bindParam(':newPassword', $newPassword, PDO::PARAM_STR);
                $update_stmt->bindParam(':originalUsername', $originalUsername, PDO::PARAM_STR);

                if ($update_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Admin updated successfully';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Error: ' . $update_stmt->errorInfo()[2];
                }
            }
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    }
}

$conn = null; // Close the connection

header('Content-Type: application/json');
echo json_encode($response);
?>
