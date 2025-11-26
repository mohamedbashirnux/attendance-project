<?php
include "../../connection/connect.php";

$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate inputs
    if (empty($username) || empty($password)) {
        $response['success'] = false;
        $response['message'] = 'Username and Password cannot be empty!';
    } else {
        try {
            // Check if username already exists
            $check_query = "SELECT COUNT(*) AS count FROM admintable WHERE username = :username";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $check_stmt->execute();
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $existing_count = $row['count'];

            if ($existing_count > 0) {
                $response['success'] = false;
                $response['message'] = 'Username already exists!';
            } else {
                // Insert new admin
                $insert_query = "INSERT INTO admintable (username, password) VALUES (:username, :password)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $insert_stmt->bindParam(':password', $password, PDO::PARAM_STR); // Store password as plaintext

                if ($insert_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Admin created successfully';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Error: ' . $insert_stmt->errorInfo()[2];
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
