<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

// Firebase V1 API Configuration
$projectId = 'attendance-system-98935';
$serviceAccountKeyPath = 'service-account-key.json';

// Get the POST parameters
$student_id = $_POST['student_id'] ?? '';
$teacher_id = $_POST['teacher_id'] ?? '';
$subject_name = $_POST['subject_name'] ?? '';
$class_name = $_POST['class_name'] ?? '';
$notification_type = $_POST['notification_type'] ?? 'absence';

try {
    if (empty($student_id)) {
        echo json_encode([
            'status' => 'fail',
            'message' => 'Student ID is required'
        ]);
        exit;
    }

    // Get student's device token
    $studentQuery = "SELECT device_token, student_name FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || empty($student['device_token'])) {
        echo json_encode([
            'status' => 'fail',
            'message' => 'Student device token not found'
        ]);
        exit;
    }

    // Get teacher's name for the notification
    $teacherName = 'Teacher'; // Default fallback
    if (!empty($teacher_id)) {
        try {
            $teacherQuery = "SELECT teacher_name FROM teachertable WHERE tid = ?";
            $teacherStmt = $conn->prepare($teacherQuery);
            $teacherStmt->execute([$teacher_id]);
            $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
            if ($teacher && !empty($teacher['teacher_name'])) {
                $teacherName = $teacher['teacher_name'];
            }
        } catch (Exception $e) {
            error_log("Teacher lookup failed: " . $e->getMessage());
        }
    }

    // Create notification message with custom title
    $title = 'Capital University'; // Custom university name
    $body = "You were marked absent in $subject_name by $teacherName";

    // Log notification attempt
    error_log("=== REAL FCM NOTIFICATION ===");
    error_log("Student ID: $student_id");
    error_log("Teacher ID: $teacher_id");
    error_log("Teacher Name: $teacherName");
    error_log("Device Token: " . substr($student['device_token'], 0, 20) . "...");
    error_log("Title: $title");
    error_log("Body: $body");

    // Send REAL FCM notification
    $notificationResult = sendFCMMessageV1(
        $student['device_token'],
        $title,
        $body,
        [
            'student_id' => $student_id,
            'teacher_id' => $teacher_id,
            'subject_name' => $subject_name,
            'class_name' => $class_name,
            'notification_type' => $notification_type,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        $projectId,
        $serviceAccountKeyPath
    );

    // Log the notification
    $logQuery = "INSERT INTO notification_logs 
                 (student_id, teacher_id, subject_name, class_name, notification_type, message, device_token, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($logQuery);
    $stmt->execute([
        $student_id,
        $teacher_id,
        $subject_name,
        $class_name,
        $notification_type,
        $body,
        $student['device_token'],
        $notificationResult ? 'sent' : 'failed'
    ]);

    if ($notificationResult) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Notification sent successfully',
            'title' => $title,
            'body' => $body
        ]);
    } else {
        echo json_encode([
            'status' => 'fail',
            'message' => 'Failed to send notification'
        ]);
    }

} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    echo json_encode([
        'status' => 'fail',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

function sendFCMMessageV1($token, $title, $body, $data, $projectId, $serviceAccountKeyPath) {
    error_log("sendFCMMessageV1 called with token: " . substr($token, 0, 20) . "...");
    
    // Get access token from service account
    $accessToken = getAccessToken($serviceAccountKeyPath);
    if (!$accessToken) {
        error_log("Failed to get access token");
        return false;
    }
    
    error_log("Access token obtained successfully");
    
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    
    // TRY: Use a basic icon name that Android should recognize
    $message = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'data' => $data,
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'icon' => 'ic_launcher', // Use the app launcher icon
                    'color' => '#2962FF' // Your project color
                ]
            ]
        ]
    ];
    
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("FCM Response HTTP Code: $httpCode");
    error_log("FCM Response Body: $result");
    
    if ($httpCode == 200) {
        $response = json_decode($result, true);
        $success = isset($response['name']) && !empty($response['name']);
        error_log("FCM notification sent successfully: " . ($success ? 'YES' : 'NO'));
        return $success;
    }
    
    error_log("FCM notification failed with HTTP code: $httpCode");
    return false;
}

function getAccessToken($serviceAccountKeyPath) {
    // Read service account key file
    $serviceAccount = json_decode(file_get_contents($serviceAccountKeyPath), true);
    if (!$serviceAccount) {
        return false;
    }
    
    // Create JWT token
    $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
    $now = time();
    $payload = json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = '';
    $signingInput = $base64Header . '.' . $base64Payload;
    
    // Sign with private key
    $privateKey = $serviceAccount['private_key'];
    openssl_sign($signingInput, $signature, $privateKey, 'SHA256');
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $signingInput . '.' . $base64Signature;
    
    // Exchange JWT for access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $response = json_decode($result, true);
        return $response['access_token'] ?? false;
    }
    
    return false;
}
?>
