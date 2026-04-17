<?php
/**
 * Fix/extend payments schema for online payments.
 * Run this after upgrading code (safe for existing data).
 */
include('db.php');

echo "<h1>Fix Online Payments Schema</h1>";

$alterStatements = [
    // plan_type to store which plan was purchased/renewed
    "ALTER TABLE payments ADD COLUMN plan_type VARCHAR(100) NULL",
    // Razorpay fields
    "ALTER TABLE payments ADD COLUMN razorpay_order_id VARCHAR(100) NULL",
    "ALTER TABLE payments ADD COLUMN razorpay_payment_id VARCHAR(100) NULL",
    "ALTER TABLE payments ADD COLUMN razorpay_signature VARCHAR(200) NULL",
];

foreach ($alterStatements as $sql) {
    // Some installs may already have these columns; ignore "duplicate column" errors.
    $res = mysqli_query($conn, $sql);
    if ($res) {
        echo "<p style='color: green;'>✓ Applied: " . htmlspecialchars($sql) . "</p>";
    } else {
        $msg = mysqli_error($conn);
        // MySQL error codes for duplicate column vary; we just show non-fatal messages lightly.
        if (stripos($msg, 'duplicate') !== false || stripos($msg, 'exists') !== false) {
            echo "<p style='color: orange;'>~ Skipped (already exists): " . htmlspecialchars($sql) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed: " . htmlspecialchars($sql) . "<br/>" . htmlspecialchars($msg) . "</p>";
        }
    }
}

echo "<p>Done. Verify in phpMyAdmin and then try membership renewal.</p>";

