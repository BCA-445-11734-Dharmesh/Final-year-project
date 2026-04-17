<?php
include('auth.php');
checkLogin();

include('db.php');

$current_user = getCurrentUser();

// Redirect if not a member
if ($current_user['role'] !== 'member') {
    header("Location: index.php");
    exit;
}

// Get member details
$stmt = $conn->prepare("SELECT * FROM members WHERE email = ?");
$stmt->bind_param("s", $current_user['email']);
$stmt->execute();
$result = $stmt->get_result();
$member_data = $result->fetch_assoc();
$stmt->close();

// Load plan prices for renewal UI
$renewal_plans = [];
$plans_stmt = $conn->prepare("SELECT name, price FROM membership_plans WHERE is_active = 1 ORDER BY duration_days ASC");
if ($plans_stmt) {
    $plans_stmt->execute();
    $plans_result = $plans_stmt->get_result();
    while ($p = $plans_result->fetch_assoc()) {
        $renewal_plans[] = $p;
    }
    $plans_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitCore Pro | Membership Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-emerald-900 to-slate-900 min-h-screen">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-900 border-r border-emerald-500/20 text-white p-6">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-emerald-400"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore</h1>
                <p class="text-gray-400 text-sm mt-1">Gym Management</p>
            </div>
            
            <div class="mb-8 bg-gradient-to-r from-emerald-600/20 to-teal-600/20 border border-emerald-500/30 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-300">Your Role</span>
                    <span class="px-3 py-1 bg-emerald-500 text-white text-xs font-bold rounded-full">
                        <i class="fa-solid fa-user-circle mr-1"></i>MEMBER
                    </span>
                </div>
            </div>

            <nav class="space-y-2">
                <a href="member_dashboard.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-home mr-3"></i>Dashboard
                </a>
                <a href="member_payments.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-receipt mr-3"></i>Payments
                </a>
                <a href="member_membership.php" class="block px-4 py-3 rounded-lg bg-emerald-600/30 text-emerald-300 transition">
                    <i class="fa-solid fa-calendar-alt mr-3"></i>Membership
                </a>
                <a href="member_attendance.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-clock mr-3"></i>Check In/Out
                </a>
                <a href="member_profile_edit.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-user-edit mr-3"></i>Edit Profile
                </a>
            </nav>

            <div class="mt-auto pt-8 border-t border-gray-700">
                <a href="logout.php" class="block px-4 py-3 rounded-lg hover:bg-red-600/20 transition text-gray-300 hover:text-red-400 text-center">
                    <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-y-auto membership-page">
            <div class="max-w-4xl">
                <h2 class="text-3xl font-bold text-white mb-8">Membership Details</h2>

                <?php if ($member_data): ?>
                    <!-- Active Membership Card -->
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border-2 border-emerald-400 rounded-2xl shadow-lg p-8 mb-8 membership-card">
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-emerald-800 mb-2"><?php echo htmlspecialchars($member_data['plan_type']); ?></h3>
                                <p class="text-white">Active Membership</p>
                            </div>
                            <div class="text-4xl text-emerald-600">
                                <i class="fa-solid fa-badge-check"></i>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-emerald-700 mb-1">Member Since</p>
                                <p class="text-lg font-bold text-emerald-900"><?php echo date('d M, Y', strtotime($member_data['join_date'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-emerald-700 mb-1">Status</p>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-emerald-600 text-white">
                                    <?php echo htmlspecialchars($member_data['status']); ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($member_data['renewal_date']): ?>
                            <div class="mt-6 pt-6 border-t-2 border-emerald-300">
                                <p class="text-sm text-emerald-700 mb-2">Renewal Date</p>
                                <p class="text-xl font-bold text-emerald-900"><?php echo date('d M, Y', strtotime($member_data['renewal_date'])); ?></p>
                                <p class="text-xs text-emerald-700 mt-2">
                                    <i class="fa-solid fa-info-circle mr-1"></i>
                                    Your membership will renew on this date
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Plan Info -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">Plan Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="border-l-4 border-emerald-500 pl-4">
                                <p class="text-gray-600 text-sm mb-2">Plan Type</p>
                                <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($member_data['plan_type']); ?></p>
                            </div>

                            <div class="border-l-4 border-blue-500 pl-4">
                                <p class="text-gray-600 text-sm mb-2">Status</p>
                                <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($member_data['status']); ?></p>
                            </div>

                            <div class="border-l-4 border-purple-500 pl-4">
                                <p class="text-gray-600 text-sm mb-2">Duration</p>
                                <p class="text-lg font-bold text-gray-800">
                                    <?php
                                    // Duration is stored in membership_plans; show it dynamically.
                                    $duration_days = null;
                                    $duration_stmt = $conn->prepare("SELECT duration_days FROM membership_plans WHERE name = ? LIMIT 1");
                                    if ($duration_stmt) {
                                        $duration_stmt->bind_param("s", $member_data['plan_type']);
                                        $duration_stmt->execute();
                                        $duration_result = $duration_stmt->get_result();
                                        $duration_row = $duration_result->fetch_assoc();
                                        $duration_days = $duration_row['duration_days'] ?? null;
                                        $duration_stmt->close();
                                    }
                                    echo $duration_days ? htmlspecialchars($duration_days . ' days') : 'Unknown';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Membership Benefits -->
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">Your Membership Benefits</h3>

                        <div class="space-y-3">
                            <div class="flex items-center p-4 bg-emerald-50 rounded-lg">
                                <i class="fa-solid fa-check-circle text-2xl text-emerald-600 mr-4"></i>
                                <div>
                                    <p class="font-bold text-gray-800">24/7 Gym Access</p>
                                    <p class="text-sm text-gray-600">Access to all gym facilities anytime</p>
                                </div>
                            </div>

                            <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                                <i class="fa-solid fa-check-circle text-2xl text-blue-600 mr-4"></i>
                                <div>
                                    <p class="font-bold text-gray-800">Equipment Usage</p>
                                    <p class="text-sm text-gray-600">Complete access to all gym equipment</p>
                                </div>
                            </div>

                            <div class="flex items-center p-4 bg-purple-50 rounded-lg">
                                <i class="fa-solid fa-check-circle text-2xl text-purple-600 mr-4"></i>
                                <div>
                                    <p class="font-bold text-gray-800">Locker Facility</p>
                                    <p class="text-sm text-gray-600">Secure locker for your belongings</p>
                                </div>
                            </div>

                            <div class="flex items-center p-4 bg-indigo-50 rounded-lg">
                                <i class="fa-solid fa-star text-2xl text-indigo-600 mr-4"></i>
                                <div>
                                    <p class="font-bold text-gray-800">Member Benefits</p>
                                    <p class="text-sm text-gray-600">Access to equipment, training support, and renewal reminders.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="bg-yellow-50 border-2 border-yellow-400 rounded-2xl p-8 text-center">
                        <i class="fa-solid fa-exclamation-circle text-5xl text-yellow-600 mb-4 block"></i>
                        <h3 class="text-2xl font-bold text-yellow-900 mb-2">No Membership Found</h3>
                        <p class="text-yellow-800 mb-4">You haven't been registered as a gym member yet.</p>
                        <p class="text-sm text-yellow-700">Please contact the gym staff to register and activate your membership.</p>
                    </div>
                <?php endif; ?>

                <?php if ($member_data): ?>
                    <!-- Renew Membership -->
                    <div class="mt-8 bg-white rounded-2xl shadow-lg p-8">
                        <div class="flex items-start justify-between gap-6 mb-6">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Renew Membership Online</h3>
                                <p class="text-gray-600 text-sm mt-2">Pay securely and extend your gym access instantly.</p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Accepted methods</div>
                                <div class="text-gray-800 font-bold">UPI</div>
                            </div>
                        </div>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-4">
                                <?php echo htmlspecialchars($_GET['success']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_GET['error'])): ?>
                            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="start_online_payment.php" class="space-y-4">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Select Plan</label>
                                <select name="plan" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition plan-select">
                                    <option value="" disabled selected>-- Choose a plan --</option>
                                    <?php if (!empty($renewal_plans)): ?>
                                        <?php foreach ($renewal_plans as $p): ?>
                                            <option value="<?php echo htmlspecialchars($p['name']); ?>">
                                                <?php echo htmlspecialchars($p['name']); ?> - ₹<?php echo htmlspecialchars($p['price']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="1 Month">1 Month - ₹1,000</option>
                                        <option value="2 Months">2 Months - ₹1,800</option>
                                        <option value="3 Months">3 Months - ₹2,500</option>
                                        <option value="Annual">Annual - ₹5,000</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-emerald-600 text-white p-4 rounded-xl font-bold shadow-lg hover:bg-emerald-700 transition transform hover:scale-105">
                                <i class="fa-solid fa-credit-card mr-2"></i>Pay Online & Renew
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
