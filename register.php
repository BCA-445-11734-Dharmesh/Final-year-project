<?php
include('auth.php');
include('db.php');

// If already logged in, redirect appropriately
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff') {
        header("Location: index.php");
    } else {
        header("Location: member_dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $plan = trim($_POST['plan'] ?? 'Monthly Basic');

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone must be exactly 10 digits';
    } else {
        // Register user and create member record
        $role = 'member';
        $result = registerUser($name, $email, $password, $role);
        
        if ($result['success']) {
            // Create member record linked to email
            $status = "Active";
            $renewal_date = date('Y-m-d', strtotime("+30 days")); // Default 30 days
            
            // Get plan duration
            $plan_stmt = $conn->prepare("SELECT duration_days, price FROM membership_plans WHERE name = ?");
            $plan_stmt->bind_param("s", $plan);
            $plan_stmt->execute();
            $plan_result = $plan_stmt->get_result();
            $plan_data = $plan_result->fetch_assoc();
            $plan_stmt->close();
            
            if ($plan_data) {
                $renewal_date = date('Y-m-d', strtotime("+{$plan_data['duration_days']} days"));
                $price = $plan_data['price'];
                
                // Insert member record
                $member_stmt = $conn->prepare("INSERT INTO members (name, email, phone, plan_type, status, renewal_date) VALUES (?, ?, ?, ?, ?, ?)");
                $member_stmt->bind_param("ssssss", $name, $email, $phone, $plan, $status, $renewal_date);
                
                if ($member_stmt->execute()) {
                    $member_id = $member_stmt->insert_id;
                    $member_stmt->close();

                    // User account is already created by registerUser() above.
                    // Create initial payment record for the newly created member.
                    $payment_method = "Cash";
                    $payment_status = "Paid";
                    $transaction_id = "REG-" . date('Ymd') . "-" . $member_id;

                    $payment_stmt = $conn->prepare("INSERT INTO payments (member_id, amount, payment_method, status, transaction_id, notes) VALUES (?, ?, ?, ?, ?, ?)");
                    $notes = "Registration payment for " . $plan;
                    $payment_stmt->bind_param("idssss", $member_id, $price, $payment_method, $payment_status, $transaction_id, $notes);
                    $payment_stmt->execute();
                    $payment_stmt->close();

                    $success = 'Registration successful! Redirecting to login...';
                    $_POST = [];
                } else {
                    $error = 'Failed to create member record';
                }
            } else {
                $error = 'Invalid membership plan selected';
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitCore Pro | Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-emerald-900 to-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 px-8 py-12 text-center">
                <h1 class="text-4xl font-bold text-white mb-2"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore</h1>
                <p class="text-emerald-100">Create Your Account</p>
            </div>

            <!-- Content -->
            <div class="px-8 py-10">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Get Started</h2>
                <p class="text-gray-600 text-sm mb-8">Join FitCore and manage your gym efficiently</p>

                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start">
                        <i class="fa-solid fa-circle-exclamation mr-3 mt-0.5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 flex items-start">
                        <i class="fa-solid fa-check-circle mr-3 mt-0.5"></i>
                        <div>
                            <p class="font-bold"><?php echo htmlspecialchars($success); ?></p>
                            <p class="text-sm mt-2">Redirecting to login...</p>
                            <script>
                                setTimeout(() => {
                                    window.location.href = 'login.php';
                                }, 2000);
                            </script>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="name" required placeholder="John Doe"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                        <input type="email" name="email" required placeholder="john@example.com"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Phone (10 digits)</label>
                        <input type="text" name="phone" required placeholder="9876543210" maxlength="10"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Select Plan</label>
                        <select name="plan" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                            <option value="">-- Choose a plan --</option>
                            <option value="1 Month">1 Month - ₹1,000</option>
                            <option value="2 Months">2 Months - ₹1,800</option>
                            <option value="3 Months">3 Months - ₹2,500</option>
                            <option value="Annual">Annual - ₹5,000</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required placeholder="Minimum 6 characters"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 text-white py-3 rounded-lg font-bold hover:bg-emerald-700 transition transform hover:scale-105 shadow-lg">
                        <i class="fa-solid fa-user-plus mr-2"></i>Create Account
                    </button>
                </form>

                <hr class="my-6 border-gray-200">

                <p class="text-center text-gray-600 text-sm">
                    Already have an account? 
                    <a href="login.php" class="text-emerald-600 font-bold hover:text-emerald-700">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-white text-xs mt-6 opacity-75">
            FitCore Pro v1.0 | BCA Final Project 2026
        </p>
    </div>
</body>
</html>
