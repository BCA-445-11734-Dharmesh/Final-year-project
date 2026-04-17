<?php
// Start session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear session cookie
if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 3600, '/');
}

// Force redirect to login
header("Location: login.php?logout=success");
exit;
?>
