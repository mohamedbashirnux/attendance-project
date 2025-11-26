<?php
include "../../connection/connect.php";

$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameToDelete = $_POST['username'];

    if (empty($usernameToDelete)) {
        $response['success'] = false;
        $response['message'] = 'Username cannot be empty!';
    } else {
        try {
            // Delete the admin record
            $delete_query = "DELETE FROM admintable WHERE username = :username";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bindParam(':username', $usernameToDelete, PDO::PARAM_STR);

            if ($delete_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Admin deleted successfully';
            } else {
                $response['success'] = false;
                $response['message'] = 'Error: ' . $delete_stmt->errorInfo()[2];
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
