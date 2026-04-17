<?php
/**
 * Fix user role enum + map empty roles to member.
 * Run once from browser or CLI if your `users.role` enum does not include `member`.
 */

include('db.php');

echo "<h1>Fix Roles</h1>";

// 1) Update enum to include `member`
$alterSql = "ALTER TABLE users MODIFY role ENUM('admin','staff','manager','member') DEFAULT 'staff'";
$alterOk = mysqli_query($conn, $alterSql);

if (!$alterOk) {
    die("ALTER TABLE failed: " . mysqli_error($conn));
}

// 2) Map empty roles to `member` so member logins work
$updateSql = "UPDATE users SET role = 'member' WHERE role IS NULL OR role = ''";
$updated = mysqli_query($conn, $updateSql);

if (!$updated) {
    die("UPDATE failed: " . mysqli_error($conn));
}

echo "<p style='color: green;'>Role enum updated and empty roles set to <b>member</b>.</p>";

echo "<p>Done. You can verify with <a href='debug_db.php'>debug_db.php</a>.</p>";
?>

