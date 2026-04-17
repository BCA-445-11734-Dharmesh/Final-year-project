<?php
include('db.php');

echo "<h1>Fix Courses Schema</h1>";

$course_orders_sql = "CREATE TABLE IF NOT EXISTS course_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_user_id INT NOT NULL,
    buyer_email VARCHAR(255) NOT NULL,
    course_key VARCHAR(100) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('Cash', 'Card', 'UPI', 'Bank Transfer') DEFAULT 'UPI',
    status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    razorpay_order_id VARCHAR(100) NULL,
    razorpay_payment_id VARCHAR(100) NULL,
    razorpay_signature VARCHAR(200) NULL,
    transaction_id VARCHAR(100) UNIQUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $course_orders_sql)) {
    echo "<p style='color: green;'>✓ course_orders table ready.</p>";
} else {
    echo "<p style='color: red;'>✗ Failed: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
}

echo "<p>Now open <a href='welcome.php'>welcome.php</a> and buy a plan (login required).</p>";
?>

