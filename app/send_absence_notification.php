<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

$student_id = filterRequest('student_id');
$subject_name = filterRequest('subject_name');
$absence_date = filterRequest('absence_date');
$excuse = filterRequest('excuse');

if (empty($student_id) || empty($subject_name) || empty($absence_date)) {
    echo json_encode([
        "status" => "fail",
        "message" => "student_id, subject_name, and absence_date are required"
    ]);
    exit;
}

try {
    // Get device token for this student
    $stmt = $conn->prepare("SELECT device_token FROM device_tokens WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData || empty($tokenData['device_token'])) {
        echo json_encode([
            "status" => "success",
            "message" => "No device token found for student",
            "sent_count" => 0
        ]);
        exit;
    }

    $deviceToken = $tokenData['device_token'];

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

    // Format the date nicely
    $formattedDate = date('M d, Y', strtotime($absence_date));
    
    // Prepare notification
    $title = "Absence Recorded";
    $body = "You were marked absent for $subject_name on $formattedDate";

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
                'type' => 'absence',
                'student_id' => (string)$student_id,
                'subject_name' => $subject_name,
                'absence_date' => $absence_date,
                'excuse' => $excuse
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'color' => '#FF0000',
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
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        echo json_encode([
            "status" => "success",
            "message" => "Absence notification sent successfully",
            "student_id" => $student_id
        ]);
    } else {
        error_log("FCM Error for student $student_id - Response: $result");
        echo json_encode([
            "status" => "error",
            "message" => "Failed to send notification",
            "http_code" => $httpCode,
            "response" => json_decode($result, true)
        ]);
    }

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
