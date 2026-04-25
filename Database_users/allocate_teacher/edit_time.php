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
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    // Validate input
    if (empty($allocation_id) || empty($start_time) || empty($end_time)) {
        throw new Exception("Allocation ID, start time, and end time are required");
    }

    // Normalize time format - add leading zeros if needed
    $start_time = date('H:i', strtotime($start_time));
    $end_time = date('H:i', strtotime($end_time));

    if ($start_time >= $end_time) {
        throw new Exception("End time must be after start time");
    }

    // Check if allocation exists and belongs to this faculty
    $check_sql = "SELECT tsa.id FROM teacher_subject_allocation tsa 
                  JOIN classes c ON tsa.class_id = c.id 
                  WHERE tsa.id = ? AND c.faculty_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$allocation_id, $faculty_id]);
    $allocation = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$allocation) {
        throw new Exception("Allocation not found or access denied");
    }

    // Update time
    $update_sql = "UPDATE teacher_subject_allocation SET start_time = ?, end_time = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);

    if (!$update_stmt->execute([$start_time, $end_time, $allocation_id])) {
        $errorInfo = $update_stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    // After updating time, recalculate status based on new time
    $current_time = date('H:i:s');
    $new_status = null;
    
    // Get current status
    $status_check_sql = "SELECT status FROM teacher_subject_allocation WHERE id = ?";
    $status_check_stmt = $conn->prepare($status_check_sql);
    $status_check_stmt->execute([$allocation_id]);
    $current_status = $status_check_stmt->fetchColumn();
    
    // Recalculate status based on new time
    if ($current_time < $start_time) {
        // Class hasn't started yet - should be PENDING
        $new_status = 'pending';
    }
    elseif ($current_time >= $start_time && $current_time <= $end_time) {
        // During class time
        if ($current_status === 'waiting') {
            // If waiting and during class time - auto approve
            $new_status = 'approved';
        }
        // If already approved or pending, keep current status
    }
    elseif ($current_time > $end_time) {
        // Class has ended - should be PENDING
        if ($current_status === 'approved' || $current_status === 'waiting') {
            $new_status = 'pending';
        }
    }
    
    // Update status if needed
    if ($new_status !== null && $new_status !== $current_status) {
        $status_update_sql = "UPDATE teacher_subject_allocation SET status = ? WHERE id = ?";
        $status_update_stmt = $conn->prepare($status_update_sql);
        $status_update_stmt->execute([$new_status, $allocation_id]);
    }

    ob_clean();
    echo json_encode([
        "success" => true, 
        "message" => "Time updated successfully"
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