<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Suppress PHP warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Include the faculty session management
include "../../Account_users/session_faculty.php";

// Include database connection
include "../../connection/connect.php";

// Clear any unexpected output from includes
ob_clean();

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    $allocation_id = trim($_POST['id'] ?? '');

    // Validate input
    if (empty($allocation_id)) {
        throw new Exception("Allocation ID is required");
    }

    // Get current allocation details to check time-based logic
    $get_allocation_sql = "SELECT tsa.start_time, tsa.end_time, tsa.status 
                          FROM teacher_subject_allocation tsa 
                          JOIN classes c ON tsa.class_id = c.id 
                          WHERE tsa.id = ? AND c.faculty_id = ?";
    $get_allocation_stmt = $conn->prepare($get_allocation_sql);
    $get_allocation_stmt->execute([$allocation_id, $faculty_id]);
    $allocation = $get_allocation_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$allocation) {
        throw new Exception("Allocation not found or access denied");
    }

    // Get current time and class times
    $current_time = date('H:i:s');
    $start_time = $allocation['start_time'];
    $end_time = $allocation['end_time'];
    $current_status = $allocation['status'];
    
    // Convert times to comparable format (seconds since midnight)
    $current_time_seconds = strtotime($current_time);
    $start_time_seconds = strtotime($start_time);
    $end_time_seconds = strtotime($end_time);
    
    // Check time conditions
    $is_before_class = ($current_time_seconds < $start_time_seconds);
    $is_during_class = ($current_time_seconds >= $start_time_seconds && $current_time_seconds <= $end_time_seconds);
    $is_after_class = ($current_time_seconds > $end_time_seconds);
    
    // Implement time-based status cycling logic
    if ($current_status === 'pending') {
        if ($is_during_class) {
            // During class time: pending → approved
            $new_status = 'approved';
            $message = "Status updated to Approved (class is active now)";
        } else {
            // Before or after class time: pending → waiting
            $new_status = 'waiting';
            if ($is_before_class) {
                $message = "Status updated to Waiting (class will auto-approve at start time)";
            } else {
                $message = "Status updated to Waiting (class has ended)";
            }
        }
    } elseif ($current_status === 'waiting') {
        // Waiting always goes back to pending
        $new_status = 'pending';
        $message = "Status updated to Pending";
    } elseif ($current_status === 'approved') {
        // Approved always goes back to pending
        $new_status = 'pending';
        $message = "Status updated to Pending";
    } else {
        throw new Exception("Invalid current status");
    }

    // Validate the new status we determined
    if (!in_array($new_status, ['pending', 'waiting', 'approved'])) {
        throw new Exception("Invalid status value");
    }

    // Check if allocation exists and belongs to this faculty
    $check_sql = "SELECT tsa.id FROM teacher_subject_allocation tsa 
                  JOIN classes c ON tsa.class_id = c.id 
                  WHERE tsa.id = ? AND c.faculty_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$allocation_id, $faculty_id]);
    
    if ($check_stmt->rowCount() === 0) {
        throw new Exception("Allocation not found or access denied");
    }

    // Update status
    $update_sql = "UPDATE teacher_subject_allocation SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);

    if (!$update_stmt->execute([$new_status, $allocation_id])) {
        $errorInfo = $update_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => $message ?? "Allocation status updated successfully"
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