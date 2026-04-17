<?php
/**
 * Database Setup & Schema
 * Run this once to initialize the database with all tables
 * Access: http://localhost/gym_project/setup.php
 */

include('db.php');

$tables_created = 0;
$errors = [];

// Create Users Table (Authentication)
$users_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'manager', 'member') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $users_sql)) {
    $tables_created++;
    
    // Insert default admin user if not exists
    $check_admin = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE email = 'admin@fitcore.com'");
    $admin_count = mysqli_fetch_assoc($check_admin);
    
    if ($admin_count['count'] == 0) {
        $default_password = password_hash('Admin@123', PASSWORD_BCRYPT);
        $admin_insert = "INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@fitcore.com', '$default_password', 'admin')";
        mysqli_query($conn, $admin_insert);
    }
} else {
    $errors[] = "Users table: " . mysqli_error($conn);
}

// Create Members Table (Enhanced)
$members_sql = "CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(10) UNIQUE NOT NULL,
    address TEXT,
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    plan_type VARCHAR(100) NOT NULL,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    renewal_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $members_sql)) {
    $tables_created++;
} else {
    $errors[] = "Members table: " . mysqli_error($conn);
}

// Create Payments Table
$payments_sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('Cash', 'Card', 'UPI', 'Bank Transfer') DEFAULT 'Cash',
    status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    plan_type VARCHAR(100) NULL,
    transaction_id VARCHAR(100) UNIQUE,
    razorpay_order_id VARCHAR(100) NULL,
    razorpay_payment_id VARCHAR(100) NULL,
    razorpay_signature VARCHAR(200) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $payments_sql)) {
    $tables_created++;
} else {
    $errors[] = "Payments table: " . mysqli_error($conn);
}

// Create Membership Plans Table
$plans_sql = "CREATE TABLE IF NOT EXISTS membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    duration_days INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $plans_sql)) {
    $tables_created++;
    
    // Insert default plans if not exists
    $check_plans = mysqli_query($conn, "SELECT COUNT(*) as count FROM membership_plans");
    $plan_count = mysqli_fetch_assoc($check_plans);
    
    if ($plan_count['count'] == 0) {
        $insert_plans = "INSERT INTO membership_plans (name, duration_days, price, description) VALUES
            ('1 Month', 30, 1000, 'Monthly gym membership with full access'),
            ('2 Months', 60, 1800, '2-month gym membership with full access'),
            ('3 Months', 90, 2500, '3-month gym membership with full access'),
            ('Annual', 365, 5000, 'Annual gym membership with all premium features')";
        mysqli_query($conn, $insert_plans);
    }
} else {
    $errors[] = "Plans table: " . mysqli_error($conn);
}

// Create Attendance Table
$attendance_sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_out_time TIMESTAMP NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $attendance_sql)) {
    $tables_created++;
} else {
    $errors[] = "Attendance table: " . mysqli_error($conn);
}

// Create Audit Log Table
$audit_sql = "CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    performed_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $audit_sql)) {
    $tables_created++;
} else {
    $errors[] = "Audit log table: " . mysqli_error($conn);
}

// Create Workout Schedule/Messages Table
$messages_sql = "CREATE TABLE IF NOT EXISTS workout_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_type ENUM('admin', 'staff', 'member') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    scheduled_date DATE,
    target_type ENUM('all', 'specific_plan', 'specific_member') DEFAULT 'all',
    target_value VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $messages_sql)) {
    $tables_created++;
} else {
    $errors[] = "Workout schedules table: " . mysqli_error($conn);
}

// Create Course Orders Table (Online courses / workout plans)
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
    $tables_created++;
} else {
    $errors[] = "Course orders table: " . mysqli_error($conn);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitCore Pro | Database Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-emerald-600 mb-2"><i class="fa-solid fa-database mr-2"></i>Database Setup</h1>
                <p class="text-gray-600">FitCore Pro v1.0</p>
            </div>

            <?php if (empty($errors)): ?>
                <div class="bg-emerald-50 border-2 border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6">
                    <p class="font-bold text-lg"><i class="fa-solid fa-check-circle mr-2"></i>SUCCESS!</p>
                    <p class="text-sm mt-2"><?php echo $tables_created; ?> tables created/verified.</p>
                </div>

                <div class="space-y-2 mb-6 bg-gray-50 p-4 rounded-lg text-sm">
                    <p class="font-bold text-gray-800">Created Tables:</p>
                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>✓ users (Authentication & user management)</li>
                        <li>✓ members (Enhanced schema)</li>
                        <li>✓ payments (Transaction tracking)</li>
                        <li>✓ membership_plans (Plan management)</li>
                        <li>✓ attendance (Check-in/out logs)</li>
                        <li>✓ audit_log (Action logging)</li>
                        <li>✓ workout_schedules (Workout schedules & messages)</li>
                    </ul>
                </div>

                <a href="login.php" class="w-full bg-emerald-600 text-white p-3 rounded-lg font-bold text-center hover:bg-emerald-700 transition block">
                    <i class="fa-solid fa-sign-in-alt mr-2"></i>Go to Login
                </a>
            <?php else: ?>
                <div class="bg-red-50 border-2 border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <p class="font-bold"><i class="fa-solid fa-circle-exclamation mr-2"></i>Errors Found:</p>
                    <ul class="list-disc list-inside text-sm mt-2">
                        <?php foreach($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <p class="text-gray-600 text-sm mb-4">Check your database connection in <code>db.php</code></p>
                <a href="setup.php" class="w-full bg-blue-600 text-white p-3 rounded-lg font-bold text-center hover:bg-blue-700 transition block">
                    <i class="fa-solid fa-redo mr-2"></i>Retry Setup
                </a>
            <?php endif; ?>

            <hr class="my-6">
            <p class="text-xs text-gray-500 text-center">
                <i class="fa-solid fa-info-circle mr-1"></i>Run this setup page once to initialize all database tables.
            </p>
        </div>
    </div>
</body>
</html>
