<?php
include 'session_faculty.php';
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Department - Simple </title>
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
</head>
<body>
    <h1>Department Management (Simple Version)</h1>
    <p>Faculty: <?php echo htmlspecialchars($faculty); ?></p>
    <p>Faculty ID: <?php echo htmlspecialchars($faculty_id); ?></p>
    <p>If you see this, the session works!</p>
    <p><strong>The problem is somewhere in the full department.php HTML/JavaScript</strong></p>
</body>
</html>
