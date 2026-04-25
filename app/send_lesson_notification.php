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
        "message" => "Class ID, title, and body are required"
    ]);
    exit;
}

try {
    // Get all device tokens for students in this class
    $stmt = $conn->prepare("
        SELECT dt.device_token, s.full_name 
        FROM device_tokens dt
        JOIN students s ON dt.student_id = s.student_id
        WHERE s.class_id = ? AND s.status = 'approved'
    ");
    $stmt->execute([$class_id]);
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tokens)) {
        echo json_encode([
            "status" => "success",
            "message" => "No students with device tokens found in this class",
            "sent_count" => 0
        ]);
        exit;
    }

    // Load service account JSON
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    
    if (!file_exists($serviceAccountPath)) {
        echo json_encode([
            "status" => "fail",
            "message" => "Firebase service account file not found. Please upload firebase-service-account.json"
        ]);
        exit;
    }

    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    $projectId = $serviceAccount['project_id'];

    // Get OAuth2 access token
    $accessToken = getAccessToken($serviceAccount);

    if (!$accessToken) {
        echo json_encode([
            "status" => "fail",
            "message" => "Failed to get access token"
        ]);
        exit;
    }

    $sentCount = 0;
    $failedCount = 0;

    foreach ($tokens as $tokenData) {
        $deviceToken = $tokenData['device_token'];
        
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
                    'type' => 'lesson',
                    'class_id' => (string)$class_id
                ],
                'android' => [
                    'priority' => 'high'
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
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $sentCount++;
        } else {
            $failedCount++;
            error_log("FCM Error for token: $deviceToken - Response: $result");
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Notifications sent",
        "sent_count" => $sentCount,
        "failed_count" => $failedCount,
        "total_students" => count($tokens)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
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
    
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    return $responseData['access_token'] ?? null;
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>
