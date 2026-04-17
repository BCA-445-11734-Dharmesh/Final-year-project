<?php
/**
 * Reset Database - Drop and recreate all tables
 * Run: http://localhost/gym_project/reset_db.php
 * WARNING: This will delete all data!
 */

include('db.php');

echo "<h1>Database Reset</h1>";
echo "<p style='color: red; font-weight: bold;'>⚠️ WARNING: This will delete ALL data!</p>";

// Drop tables in reverse order (due to foreign keys)
$drop_queries = [
    "DROP TABLE IF EXISTS audit_log",
    "DROP TABLE IF EXISTS attendance",
    "DROP TABLE IF EXISTS payments",
    "DROP TABLE IF EXISTS members",
    "DROP TABLE IF EXISTS membership_plans",
    "DROP TABLE IF EXISTS users"
];

echo "<h2>Dropping tables...</h2>";
foreach ($drop_queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p>✓ Dropped: " . str_replace("DROP TABLE IF EXISTS ", "", $query) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: " . mysqli_error($conn) . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Now run setup.php to recreate tables:</strong></p>";
echo "<p><a href='setup.php' style='background: green; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Run Setup Now →</a></p>";

mysqli_close($conn);
?>