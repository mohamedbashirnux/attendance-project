<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

// Get the POST parameters
$device_token = $_POST['device_token'] ?? '';
$user_id = $_POST['user_id'] ?? '';
$user_type = $_POST['user_type'] ?? '';

// Debug logging
error_log("=== DEVICE TOKEN UPDATE DEBUG ===");
error_log("Device Token: " . substr($device_token, 0, 20) . "...");
error_log("User ID: $user_id");
error_log("User Type: $user_type");

try {
    if (empty($device_token) || empty($user_id) || empty($user_type)) {
        error_log("ERROR: Missing required parameters");
        echo json_encode([
            'status' => 'fail',
            'message' => 'Device token, user ID, and user type are required'
        ]);
        exit;
    }

    // Determine the correct table name based on user type
    $table_name = '';
    $id_column = '';
    
    if ($user_type === 'student') {
        $table_name = 'students';
        $id_column = 'student_id';
    } elseif ($user_type === 'teacher') {
        $table_name = 'teachertable';
        $id_column = 'tid';
    } else {
        error_log("ERROR: Invalid user type: $user_type");
        echo json_encode([
            'status' => 'fail',
            'message' => 'Invalid user type. Must be student or teacher'
        ]);
        exit;
    }

    error_log("Table: $table_name, ID Column: $id_column");

    // Update device token in the appropriate table
    $query = "UPDATE $table_name 
              SET device_token = ?, 
                  last_token_update = NOW() 
              WHERE $id_column = ?";
    
    error_log("SQL Query: $query");
    error_log("Parameters: $device_token, $user_id");
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$device_token, $user_id]);

    error_log("Query executed: " . ($result ? 'YES' : 'NO'));
    error_log("Rows affected: " . $stmt->rowCount());

    if ($result && $stmt->rowCount() > 0) {
        error_log("SUCCESS: Device token updated");
        echo json_encode([
            'status' => 'success',
            'message' => 'Device token updated successfully',
            'table' => $table_name,
            'user_id' => $user_id,
            'user_type' => $user_type
        ]);
    } else {
        error_log("FAILED: No rows affected");
        echo json_encode([
            'status' => 'fail',
            'message' => 'Failed to update device token. User not found or no changes made.',
            'table' => $table_name,
            'user_id' => $user_id,
            'user_type' => $user_type
        ]);
    }

} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
    echo json_encode([
        'status' => 'fail',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>