<?php
session_start(); // Start the session

// Destroy all session data
session_unset();   // Unset all session variables
session_destroy(); // Destroy the session itself

// Redirect to the login page or another desired page
header("Location: ../interval/interval_screen.php"); // Replace with the path to your login page
exit;
