<?php
// Auto-approve waiting allocations that have reached their start time (including next day)
// Auto-revert approved allocations that have passed their end time
// This can be called via AJAX or cron job

// Suppress PHP warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Start output buffering to catch any unexpected output
ob_start();

// Include database connection
include "../../connection/connect.php";

// Clear any unexpected output from includes
ob_clean();

// Set content type to JSON
header('Content-Type: application/json');

try {
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    $current_datetime = date('Y-m-d H:i:s');
    
    // 1. Auto-approve waiting allocations ONLY when current time is BETWEEN start_time and end_time (during class)
    $approve_sql = "UPDATE teacher_subject_allocation 
                   SET status = 'approved' 
                   WHERE status = 'waiting' 
                   AND TIME(start_time) <= TIME(?)
                   AND TIME(end_time) >= TIME(?)";
    
    $approve_stmt = $conn->prepare($approve_sql);
    $approve_stmt->execute([$current_time, $current_time]);
    $approved_count = $approve_stmt->rowCount();
    
    // 2. Auto-revert approved allocations that have passed their end time (class ended)
    $revert_sql = "UPDATE teacher_subject_allocation 
                  SET status = 'pending' 
                  WHERE status = 'approved' 
                  AND TIME(end_time) < TIME(?)";
    
    $revert_stmt = $conn->prepare($revert_sql);
    $revert_stmt->execute([$current_time]);
    $reverted_count = $revert_stmt->rowCount();
    
    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Auto-approved {$approved_count} waiting allocations and reverted {$reverted_count} ended classes to pending",
        "approved_count" => $approved_count,
        "reverted_count" => $reverted_count,
        "current_time" => $current_time,
        "current_datetime" => $current_datetime
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

ob_end_flush();
?>