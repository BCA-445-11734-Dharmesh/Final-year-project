<?php
/**
 * Fix Plans - Update old plan names to new ones
 * Run once: http://localhost/gym_project/fix_plans.php
 */

include('db.php');

// Delete old plans
$delete = "DELETE FROM membership_plans";
mysqli_query($conn, $delete);

// Insert new plans
$insert_plans = "INSERT INTO membership_plans (name, duration_days, price, description) VALUES
    ('1 Month', 30, 1000, 'Monthly gym membership with full access'),
    ('2 Months', 60, 1800, '2-month gym membership with full access'),
    ('3 Months', 90, 2500, '3-month gym membership with full access'),
    ('Annual', 365, 5000, 'Annual gym membership with all premium features')";

if (mysqli_query($conn, $insert_plans)) {
    echo "<h2 style='color: green;'>✓ Plans Updated Successfully!</h2>";
    echo "<p>New plans:</p>";
    echo "<ul>";
    echo "<li>1 Month - ₹1,000</li>";
    echo "<li>2 Months - ₹1,800</li>";
    echo "<li>3 Months - ₹2,500</li>";
    echo "<li>Annual - ₹5,000</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Go to Login →</a></p>";
} else {
    echo "<h2 style='color: red;'>✗ Error updating plans</h2>";
    echo "<p>" . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);
?>
