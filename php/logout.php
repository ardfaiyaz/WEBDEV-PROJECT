<?php
session_start(); // Start the session to access session variables

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to the login page
// You might want to redirect to index.php or any other public page
header("Location: ../pages/login.php");
exit(); // Always exit after a header redirect
?>