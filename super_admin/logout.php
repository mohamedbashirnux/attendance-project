<?php
// Start session
session_start();

// Unset all super admin session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to super admin login page
header('Location: ../interval/Auth_super_admin.php');
exit();
?>
