<?php
// Clean version with explicit CORS handling
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Only allow POST for actual submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "fail",
        "message" => "Method not allowed. Only POST requests are accepted.",
        "method" => $_SERVER['REQUEST_METHOD']
    ]);
    exit();
}

include "conn.php";

// Get input
$class_id = filterRequest('class_id');
$subject_class_id = filterRequest('subject_class_id');
$teacher_id = filterRequest('teacher_id');
$session_date = filterRequest('session_date');
$absent_students = filterRequest('absent_students'); // JSON array of absent student data
$notes = filterRequest('notes'); // Optional notes

// Validate required fields
if(empty($class_id) || empty($subject_class_id) || empty($teacher_id) || empty($session_date)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Required fields missing: class_id, subject_class_id, teacher_id, session_date",
        "received_data" => [
            "class_id" => $class_id,
            "subject_class_id" => $subject_class_id,
            "teacher_id" => $teacher_id,
            "session_date" => $session_date
        ]
    ]);
    exit();
}

try {
    // Get the internal teacher ID (auto-increment)
    $teacher_check = $conn->prepare("SELECT id FROM Teachers WHERE teacher_id = ? OR id = ?");
    $teacher_check->execute([$teacher_id, $teacher_id]);
    $teacher_record = $teacher_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher_record) {
        echo json_encode([
            "status" => "fail",
            "message" => "Teacher not found with ID: " . $teacher_id
        ]);
        exit();
    }
    
    $internal_teacher_id = $teacher_record['id'];

    // Get total students in the class
    $total_students_query = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE class_id = ?");
    $total_students_query->execute([$class_id]);
    $total_students = $total_students_query->fetch(PDO::FETCH_ASSOC)['total'];

    // Parse absent students data
    $absent_students_data = [];
    if (!empty($absent_students)) {
        // First, decode HTML entities
        $html_decoded = html_entity_decode($absent_students, ENT_QUOTES, 'UTF-8');
        
        // Try to JSON decode
        $absent_students_data = json_decode($html_decoded, true);
        $json_error = json_last_error();
        
        if ($json_error !== JSON_ERROR_NONE) {
            // Try URL decode then JSON decode as fallback
            $url_decoded = urldecode($html_decoded);
            $absent_students_data = json_decode($url_decoded, true);
            $json_error = json_last_error();
            
            if ($json_error !== JSON_ERROR_NONE) {
                echo json_encode([
                    "status" => "fail",
                    "message" => "Invalid absent_students JSON format",
                    "json_error" => json_last_error_msg(),
                    "received_data" => $absent_students
                ]);
                exit();
            }
        }
    }

    $absent_count = count($absent_students_data);
    $present_count = $total_students - $absent_count;
    $attendance_percentage = $total_students > 0 ? round(($present_count / $total_students) * 100, 2) : 0;

    // Create combined datetime (current date and time)
    $session_datetime = $session_date . ' ' . date('H:i:s');

    // Start transaction
    $conn->beginTransaction();

    // Only include notes if user provided them
    $final_notes = (!empty($notes) && trim($notes) !== '') ? trim($notes) : null;

    // Always create a new session (allow multiple sessions per day)
    $session_stmt = $conn->prepare("INSERT INTO attendance_sessions (class_id, subject_class_id, teacher_id, session_datetime, total_students, absent_students, present_students, attendance_percentage, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $session_stmt->execute([
        $class_id, $subject_class_id, $internal_teacher_id, $session_datetime,
        $total_students, $absent_count, $present_count, $attendance_percentage,
        $final_notes
    ]);

    $session_action = "created";

    // Insert absence records for absent students
    if (!empty($absent_students_data)) {
        $absence_stmt = $conn->prepare("INSERT INTO absences (student_id, class_id, subject_class_id, teacher_id, absence_date, excuse) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($absent_students_data as $absent_student) {
            $student_id = $absent_student['student_id'] ?? '';
            $excuse = $absent_student['excuse'] ?? 'No Excuse';
            
            if (empty($student_id)) {
                continue; // Skip invalid entries
            }

            // Get internal student ID
            $student_check = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR id = ?");
            $student_check->execute([$student_id, $student_id]);
            $student_record = $student_check->fetch(PDO::FETCH_ASSOC);
            
            if ($student_record) {
                $internal_student_id = $student_record['id'];
                
                // Create unique absence record with timestamp
                $absence_datetime = $session_date . ' ' . date('H:i:s');
                
                try {
                    $absence_stmt->execute([
                        $internal_student_id, $class_id, $subject_class_id,
                        $internal_teacher_id, $absence_datetime, $excuse
                    ]);
                } catch (PDOException $e) {
                    // If duplicate, just continue (student already marked absent for this exact time)
                    if ($e->getCode() != 23000) {
                        throw $e; // Re-throw if it's not a duplicate key error
                    }
                }
            }
        }
    }

    // Commit transaction
    $conn->commit();

    // ============================================
    // Send notifications to absent students
    // ============================================
    if (!empty($absent_students_data)) {
        try {
            // Get subject name for notifications
            $subject_query = $conn->prepare("SELECT s.subject_name FROM subject_class sc JOIN subjects s ON sc.subject_id = s.id WHERE sc.id = ?");
            $subject_query->execute([$subject_class_id]);
            $subject_data = $subject_query->fetch(PDO::FETCH_ASSOC);
            $subject_name = $subject_data['subject_name'] ?? 'Unknown Subject';
            
            foreach ($absent_students_data as $absent_student) {
                $student_id = $absent_student['student_id'] ?? '';
                $excuse = $absent_student['excuse'] ?? 'No Excuse';
                
                if (empty($student_id)) {
                    continue;
                }
                
                // Send notification (don't let it fail the whole process)
                try {
                    // Build the full URL to the notification script
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $host = $_SERVER['HTTP_HOST'];
                    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
                    $notificationUrl = $protocol . "://" . $host . $scriptDir . "/send_absence_notification.php";
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $notificationUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                        'student_id' => $student_id,
                        'subject_name' => $subject_name,
                        'absence_date' => $session_date,
                        'excuse' => $excuse
                    ]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    $notificationResult = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    // Log notification result
                    error_log("Absence notification URL: " . $notificationUrl);
                    error_log("Absence notification HTTP code: " . $httpCode);
                    error_log("Absence notification result for student $student_id: " . $notificationResult);
                } catch (Exception $e) {
                    error_log("Notification error for student $student_id: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
        }
    }
    // ============================================
    // END OF NOTIFICATION CODE
    // ============================================

    echo json_encode([
        "status" => "success",
        "message" => "Attendance session " . $session_action . " successfully",
        "data" => [
            "total_students" => $total_students,
            "present_students" => $present_count,
            "absent_students" => $absent_count,
            "attendance_percentage" => $attendance_percentage,
            "session_datetime" => $session_datetime,
            "action" => $session_action
        ]
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        "status" => "fail",
        "message" => "Database error: " . $e->getMessage(),
        "error_code" => $e->getCode()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "General error: " . $e->getMessage()
    ]);
}
?>
