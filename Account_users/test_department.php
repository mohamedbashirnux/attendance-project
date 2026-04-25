<?php
// Simple test file
echo "Test 1: PHP is working<br>";

// Test session
include 'session_faculty.php';
echo "Test 2: Session file included<br>";

// Test session info
$sessionInfo = getSessionInfo();
echo "Test 3: Session info retrieved<br>";
echo "Faculty: " . $sessionInfo['faculty_name'] . "<br>";
echo "Faculty ID: " . $sessionInfo['faculty_id'] . "<br>";

echo "<br><strong>All tests passed! The issue is not with session or includes.</strong>";
?>
