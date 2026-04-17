<?php
include('db.php');

echo "<h1>Database Plans Check</h1>";

$result = mysqli_query($conn, "SELECT name, price FROM membership_plans");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<p><strong>Current plans in database:</strong></p>";
    echo "<ul>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>{$row['name']} - ₹{$row['price']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>No plans found!</strong> Run fix_plans.php</p>";
}

echo "<hr>";
echo "<p><a href='fix_plans.php'>Run Fix Plans</a> | <a href='register.php'>Try Register</a></p>";

mysqli_close($conn);
?>