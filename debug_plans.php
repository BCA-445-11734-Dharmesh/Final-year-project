<?php
/**
 * Debug Plans - Check what's in the database
 * Run: http://localhost/gym_project/debug_plans.php
 */

include('db.php');

echo "<h1>Database Plans Debug</h1>";

// Check if table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'membership_plans'");
if (mysqli_num_rows($result) == 0) {
    echo "<p style='color: red;'>❌ membership_plans table does NOT exist!</p>";
    echo "<p>Run setup.php first: <a href='setup.php'>setup.php</a></p>";
    exit;
}

echo "<p style='color: green;'>✅ membership_plans table exists</p>";

// Check plans
$plans = mysqli_query($conn, "SELECT * FROM membership_plans");
$plan_count = mysqli_num_rows($plans);

echo "<p>Found $plan_count plans in database:</p>";

if ($plan_count > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Duration</th><th>Price</th></tr>";
    while ($plan = mysqli_fetch_assoc($plans)) {
        echo "<tr>";
        echo "<td>{$plan['id']}</td>";
        echo "<td>{$plan['name']}</td>";
        echo "<td>{$plan['duration_days']} days</td>";
        echo "<td>₹{$plan['price']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No plans found! Run fix_plans.php</p>";
}

echo "<hr>";
echo "<p><a href='fix_plans.php'>Run Fix Plans →</a></p>";
echo "<p><a href='register.php'>Try Register →</a></p>";

mysqli_close($conn);
?>