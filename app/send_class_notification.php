<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

$class_id = filterRequest('class_id');
$title = filterRequest('title');
$body = filterRequest('body');

if (empty($class_id) || empty($title) || empty($body)) {
    echo json_encode([
        "status" => "fail",
        "message" => "Class ID, title, and message are required"
    ]);
    exit;
}

try {
    // First, verify the class exists and get class info for logging
    $classCheck = $conn->prepare("SELECT class_name, study_mode FROM classes WHERE id = ?");
    $classCheck->execute([$class_id]);
    $classInfo = $classCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$classInfo) {
        echo json_encode([
            "status" => "fail",
            "message" => "Class not found with ID: " . $class_id
        ]);
        exit;
    }
    
    // Log for debugging
    error_log("Sending notification to class_id: $class_id ({$classInfo['class_name']})");
    
    // Get all device tokens for students in this class
    $stmt = $conn->prepare("
        SELECT dt.device_token, s.student_id, s.full_name, s.class_id
        FROM device_tokens dt
        JOIN students s ON dt.student_id = s.student_id
        WHERE s.class_id = ? AND s.status = 'approved'
    ");
    $stmt->execute([$class_id]);
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the students found
    error_log("Found " . count($tokens) . " students in class_id $class_id");
    foreach ($tokens as $token) {
        error_log("Student: {$token['student_id']} ({$token['full_name']}) - class_id: {$token['class_id']}");
    }

    if (empty($tokens)) {
        echo json_encode([
            "status" => "success",
            "message" => "No students with device tokens found in this class",
            "sent_count" => 0,
            "total_students" => 0,
            "class_info" => $classInfo
        ]);
        exit;
    }

    // Load service account JSON
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    
    if (!file_exists($serviceAccountPath)) {
        error_log("Firebase service account file not found at: " . $serviceAccountPath);
        echo json_encode([
            "status" => "fail",
            "message" => "Firebase service account file not found at: " . $serviceAccountPath
        ]);
        exit;
    }
    
    // Check if file is readable
    if (!is_readable($serviceAccountPath)) {
        error_log("Firebase service account file is not readable: " . $serviceAccountPath);
        echo json_encode([
            "status" => "fail",
            "message" => "Firebase service account file exists but is not readable. Check file permissions."
        ]);
        exit;
    }

    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    
    if (!$serviceAccount) {
        error_log("Failed to parse Firebase service account JSON");
        echo json_encode([
            "status" => "fail",
            "message" => "Failed to parse Firebase service account JSON. File may be corrupted."
        ]);
        exit;
    }
    
    if (!isset($serviceAccount['project_id'])) {
        error_log("Firebase service account JSON missing project_id");
        echo json_encode([
            "status" => "fail",
            "message" => "Firebase service account JSON is invalid (missing project_id)"
        ]);
        exit;
    }
    
    $projectId = $serviceAccount['project_id'];

    // Get OAuth2 access token
    $accessToken = getAccessToken($serviceAccount);

    if (!$accessToken) {
        error_log("Failed to get Firebase OAuth2 access token");
        echo json_encode([
            "status" => "fail",
            "message" => "Failed to get Firebase access token. Check service account credentials."
        ]);
        exit;
    }

    $sentCount = 0;
    $failedCount = 0;
    $failedStudents = [];

    foreach ($tokens as $tokenData) {
        $deviceToken = $tokenData['device_token'];
        $studentId = $tokenData['student_id'];
        $studentName = $tokenData['full_name'];
        
        // Prepare FCM V1 message
        $message = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'class_announcement',
                    'class_id' => (string)$class_id,
                    'title' => $title,
                    'message' => $body,
                    'timestamp' => date('Y-m-d H:i:s')
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'color' => '#4CAF50',
                        'icon' => 'ic_launcher'
                    ]
                ]
            ]
        ];

        // Send to FCM V1 API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $sentCount++;
        } else {
            $failedCount++;
            $failedStudents[] = [
                'student_id' => $studentId,
                'student_name' => $studentName,
                'error' => $result
            ];
            error_log("FCM Error for student $studentId ($studentName) - HTTP $httpCode - Response: $result");
        }
    }

    // Log the notification to database (optional - for history)
    try {
        $logSql = "INSERT INTO notification_logs (class_id, title, message, sent_count, failed_count, sent_at) 
                   VALUES (?, ?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logSql);
        $logStmt->execute([$class_id, $title, $body, $sentCount, $failedCount]);
    } catch (PDOException $e) {
        // Log error but don't fail the request
        error_log("Failed to log notification: " . $e->getMessage());
    }

    $response = [
        "status" => "success",
        "message" => "Notifications sent",
        "sent_count" => $sentCount,
        "failed_count" => $failedCount,
        "total_students" => count($tokens),
        "class_info" => $classInfo,
        "class_id" => $class_id
    ];

    if ($failedCount > 0) {
        $response['failed_students'] = $failedStudents;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
    ]);
}

// Function to get OAuth2 access token
function getAccessToken($serviceAccount) {
    $now = time();
    $expiration = $now + 3600; // 1 hour

    // Create JWT header
    $header = [
        'alg' => 'RS256',
        'typ' => 'JWT'
    ];

    // Create JWT claim set
    $claimSet = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $expiration
    ];

    // Encode header and claim set
    $headerEncoded = base64UrlEncode(json_encode($header));
    $claimSetEncoded = base64UrlEncode(json_encode($claimSet));

    // Create signature
    $signatureInput = $headerEncoded . '.' . $claimSetEncoded;
    $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
    openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $signatureEncoded = base64UrlEncode($signature);

    // Create JWT
    $jwt = $signatureInput . '.' . $signatureEncoded;

    // Exchange JWT for access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);
    
    // Log OAuth errors
    if (!isset($responseData['access_token'])) {
        error_log("OAuth2 Error - HTTP Code: $httpCode");
        error_log("OAuth2 Response: " . $response);
    }
    
    return $responseData['access_token'] ?? null;
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>
