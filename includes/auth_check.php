<?php
// auth_check.php
session_start();

// Check if the user is not logged in
if (!isset($_SESSION['admin_user'])) {
    // Redirect to the login page
    header('Location: ../html/auth_admin.php');
    exit(); // Ensure that the script stops executing after redirection
}
?>
