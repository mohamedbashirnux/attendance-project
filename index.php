<?php
// Start the session
session_start();

// Check if the user is logged in; if not, redirect to the login page
if (isset($_SESSION['username']) && isset($_SESSION['faculty'])) {
    header("Location: Account_users/dashboard.php");
    exit();
} 
elseif(isset($_SESSION['admin_logged_in'])) {
    header('Location: html/admin_dashboard.php');
    exit();
} 
else {
    header("Location: interval/interval_screen.php");
    exit();
}
?>

