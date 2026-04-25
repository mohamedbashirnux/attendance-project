<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include_once "conn.php"; // This already includes function.php, so we don't need to include it again

try {
    $username = filterRequest('username');
    $password = filterRequest('password');

    // Validate input
    if (empty($username) || empty($password)) {
        echo json_encode(array("status" => "fail", "message" => "Username and password are required"));
        exit();
    }

    // Prepare and execute the SQL statement to get teacher by username
    $stmt = $conn->prepare("SELECT * FROM `teachers` WHERE `username` = ?");
    $stmt->execute(array($username));

    // Fetch results as associative array
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if teacher exists and verify password
    if ($teacher && password_verify($password, $teacher['password'])) {
        // Remove password from response for security
        unset($teacher['password']);
        echo json_encode(array("status" => "success", "teacher" => $teacher));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Invalid username or password"));
    }

} catch (Exception $e) {
    echo json_encode(array("status" => "error", "message" => "Database error: " . $e->getMessage()));
}
?>
