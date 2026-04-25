<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if super admin is logged in
if (!isset($_SESSION['super_admin_logged_in']) || $_SESSION['super_admin_logged_in'] !== true) {
    // Super admin not logged in, redirect to login page
    header('Location: ../interval/Auth_super_admin.php');
    exit();
}

// Optional: You can also check session timeout (e.g., 5 minutes of inactivity)
$timeout_duration = 300; // 5 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session expired, destroy and redirect to super admin login
    session_unset();
    session_destroy();
    header('Location: ../interval/Auth_super_admin.php');
    exit();
}

// Update last activity time for super admin
$_SESSION['last_activity'] = time();
?>
