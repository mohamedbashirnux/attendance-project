<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "conn.php";

// attendanceproject1


$username = filterRequest('username');
$password = filterRequest('password');

// Prepare and execute the SQL statement
$stmt = $conn->prepare("SELECT * FROM `teachertable` WHERE `username` = ? AND `password` = ?");
$stmt->execute(array($username, $password));

// Fetch results as associative array
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if any results were found and respond accordingly
if ($results) {
    echo json_encode(array("status" => "success", "users" => $results));
} else {
    echo json_encode(array("status" => "fail", "message" => "No users found"));
}

?>
