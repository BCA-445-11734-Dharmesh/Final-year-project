<?php
/**
 * Landing/Home Page - Redirect to login or dashboard
 * This is the first page users see
 * Access: http://localhost/gym_project/
 */

session_start();

// If user is logged in, go to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit;
}

// Otherwise, clear any stale session and go to public welcome page
session_destroy();
setcookie('PHPSESSID', '', time() - 3600, '/');

header("Location: welcome.php");
exit;
?>
