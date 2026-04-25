<?php
// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Starting...<br>";

// Test session include
try {
    echo "Step 2: Including session_faculty.php...<br>";
    include 'session_faculty.php';
    echo "Step 3: Session file included successfully!<br>";
} catch (Exception $e) {
    die("ERROR at Step 2: " . $e->getMessage());
}

// Test getting session info
try {
    echo "Step 4: Getting session info...<br>";
    $sessionInfo = getSessionInfo();
    echo "Step 5: Session info retrieved!<br>";
    
    if (!$sessionInfo) {
        die("ERROR: Session info is empty!");
    }
    
    $faculty = $sessionInfo['faculty_name'];
    $faculty_id = $sessionInfo['faculty_id'];
    
    echo "Step 6: Faculty Name: " . htmlspecialchars($faculty) . "<br>";
    echo "Step 7: Faculty ID: " . htmlspecialchars($faculty_id) . "<br>";
} catch (Exception $e) {
    die("ERROR at Step 4-7: " . $e->getMessage());
}

echo "<br><strong style='color: green;'>✅ ALL STEPS PASSED!</strong><br>";
echo "<br>Now loading the actual department page...<br>";

// If we get here, everything works, so load the real page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Department Debug</title>
</head>
<body>
    <h1>Debug Complete - Session Works!</h1>
    <p>Faculty: <?php echo htmlspecialchars($faculty); ?></p>
    <p>Faculty ID: <?php echo htmlspecialchars($faculty_id); ?></p>
    <p><a href="department.php">Go to Department Page</a></p>
</body>
</html>
