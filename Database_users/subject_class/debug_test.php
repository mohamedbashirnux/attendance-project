<?php
// Debug test file to identify cPanel issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Test for cPanel</h2>";

// Test 1: Session
echo "<h3>1. Session Test</h3>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✓ Session is working<br>";
    echo "Session ID: " . session_id() . "<br>";
} else {
    echo "✗ Session is NOT working<br>";
}

// Test 2: Database Connection
echo "<h3>2. Database Connection Test</h3>";
try {
    include "../../connection/connect.php";
    echo "✓ Database connection successful<br>";
    echo "Database: " . DB_NAME . "<br>";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Faculty Session
echo "<h3>3. Faculty Session Test</h3>";
try {
    include "../../Account_users/session_faculty.php";
    $sessionInfo = getSessionInfo();
    if ($sessionInfo) {
        echo "✓ Faculty session is working<br>";
        echo "Faculty ID: " . $sessionInfo['faculty_id'] . "<br>";
        echo "Faculty Name: " . $sessionInfo['faculty_name'] . "<br>";
    } else {
        echo "✗ Faculty session is NOT working (no session info)<br>";
    }
} catch (Exception $e) {
    echo "✗ Faculty session error: " . $e->getMessage() . "<br>";
}

// Test 4: Check if subjects table exists
echo "<h3>4. Database Tables Test</h3>";
try {
    $tables = ['subjects', 'classes', 'subject_class', 'departments'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ Table '$table' exists with $count records<br>";
    }
} catch (Exception $e) {
    echo "✗ Table check failed: " . $e->getMessage() . "<br>";
}

// Test 5: Check file paths
echo "<h3>5. File Path Test</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Test 6: JSON output test
echo "<h3>6. JSON Output Test</h3>";
ob_start();
header('Content-Type: application/json');
ob_clean();
$test_json = json_encode(['success' => true, 'message' => 'JSON test']);
echo "JSON output: " . $test_json . "<br>";
ob_end_flush();
?>
