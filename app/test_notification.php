<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conn.php";

// Test notification for student 3920
$student_id = '3920';
$teacher_id = '50';
$subject_name = 'Test Subject';
$class_name = 'B8';
$notification_type = 'absence';

echo "<h2>=== TESTING NOTIFICATION ===</h2>";
echo "Student ID: $student_id<br>";
echo "Teacher ID: $teacher_id<br>";
echo "Subject: $subject_name<br>";
echo "Class: $class_name<br><br>";

// Check if student exists and has device token
$studentQuery = "SELECT device_token, student_name FROM students WHERE student_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student && !empty($student['device_token'])) {
    echo "✅ Student found: " . $student['student_name'] . "<br>";
    echo "✅ Device token: " . substr($student['device_token'], 0, 20) . "...<br><br>";
    
    // Check service account file
    $serviceAccountPath = 'service-account-key.json';
    echo "🔍 Service Account File Check:<br>";
    echo "File exists: " . (file_exists($serviceAccountPath) ? 'YES' : 'NO') . "<br>";
    echo "File readable: " . (is_readable($serviceAccountPath) ? 'YES' : 'NO') . "<br>";
    echo "File size: " . (file_exists($serviceAccountPath) ? filesize($serviceAccountPath) . ' bytes' : 'N/A') . "<br><br>";
    
    // Try to call send_notification.php
    $notificationData = [
        'student_id' => $student_id,
        'teacher_id' => $teacher_id,
        'subject_name' => $subject_name,
        'class_name' => $class_name,
        'notification_type' => $notification_type
    ];
    
    echo "📱 Calling send_notification.php...<br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://192.168.100.27/attendanceproject1/App/send_notification.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($notificationData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📱 Notification API Response:<br>";
    echo "HTTP Code: $httpCode<br>";
    echo "Response: $result<br>";
    
} else {
    echo "❌ Student not found or no device token<br>";
}
?>