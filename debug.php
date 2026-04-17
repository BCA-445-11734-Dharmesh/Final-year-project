<?php
/**
 * Debug/Test Page - Check system status
 * Access: http://localhost/gym_project/debug.php
 */

include('db.php');

$status = [
    'database' => false,
    'users_table' => false,
    'admin_user' => false,
    'password_hash' => false
];

echo "<pre style='background: #f5f5f5; padding: 20px; font-family: monospace; border-radius: 5px;'>";
echo "<h2>🔍 FitCore Debug Status</h2>\n";

// 1. Check Database Connection
if ($conn) {
    $status['database'] = true;
    echo "✅ Database Connected\n";
} else {
    echo "❌ Database Connection Failed: " . mysqli_connect_error() . "\n";
}

// 2. Check Users Table
if ($status['database']) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if ($check_table && mysqli_num_rows($check_table) > 0) {
        $status['users_table'] = true;
        echo "✅ Users Table Exists\n";
        
        // Check columns
        $columns = mysqli_query($conn, "DESCRIBE users");
        echo "\nTable Structure:\n";
        while ($col = mysqli_fetch_assoc($columns)) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "❌ Users Table NOT Found - Run setup.php\n";
    }
}

// 3. Check Admin User
if ($status['users_table']) {
    $admin = mysqli_query($conn, "SELECT * FROM users WHERE email = 'admin@fitcore.com'");
    if ($admin && mysqli_num_rows($admin) > 0) {
        $status['admin_user'] = true;
        $admin_data = mysqli_fetch_assoc($admin);
        echo "✅ Admin User Exists\n";
        echo "   Email: {$admin_data['email']}\n";
        echo "   Role: {$admin_data['role']}\n";
        echo "   Active: " . ($admin_data['is_active'] ? 'Yes' : 'No') . "\n";
        echo "   Password Hash: {$admin_data['password']}\n";
        
        // Test password verification
        $test_password = 'Admin@123';
        if (password_verify($test_password, $admin_data['password'])) {
            $status['password_hash'] = true;
            echo "✅ Default Password Works (Admin@123)\n";
        } else {
            echo "❌ Default Password Verification Failed\n";
            echo "   Try: " . password_hash($test_password, PASSWORD_BCRYPT) . "\n";
        }
    } else {
        echo "❌ Admin User NOT Found\n";
    }
}

// 4. Test Login Function
echo "\n--- Testing Login Function ---\n";
if ($status['database']) {
    include('auth.php');
    $result = loginUser('admin@fitcore.com', 'Admin@123');
    if ($result['success']) {
        echo "✅ Login Test Successful\n";
        echo "   Auto-redirecting in 2 seconds...\n";
        echo "</pre>";
        echo "<script>setTimeout(() => window.location.href = 'index.php', 2000);</script>";
    } else {
        echo "❌ Login Test Failed: " . $result['message'] . "\n";
    }
}

echo "\n--- Summary ---\n";
$all_ok = array_reduce($status, fn($carry, $item) => $carry && $item, true);
if ($all_ok) {
    echo "🎉 All systems operational! You can login now.\n";
} else {
    echo "⚠️  Issues detected. Run setup.php if needed.\n";
    echo "   Step 1: http://localhost/gym_project/setup.php\n";
    echo "   Step 2: http://localhost/gym_project/login.php\n";
}

echo "</pre>";
?>
