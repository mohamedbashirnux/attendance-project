<?php
// Start session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is not logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    // Redirect to login page
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'] ?? '';
?>  
<?php include_once 'user_include_dashboard.php'; ?>