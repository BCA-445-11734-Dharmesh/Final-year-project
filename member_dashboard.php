<?php
include('auth.php');
checkLogin();

include('db.php');

$current_user = getCurrentUser();

// Get member details if user is a member
$member_data = null;
if ($current_user['role'] === 'member') {
    $stmt = $conn->prepare("SELECT * FROM members WHERE email = ?");
    $stmt->bind_param("s", $current_user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $member_data = $result->fetch_assoc();
    $stmt->close();

    // Auto-check and update membership status (expiry logic)
    if ($member_data && $member_data['renewal_date']) {
        $renewal_date = new DateTime($member_data['renewal_date']);
        $today = new DateTime();
        
        // If renewal date has passed, update status to Suspended
        if ($today > $renewal_date && $member_data['status'] === 'Active') {
            $new_status = 'Suspended';
            $update_stmt = $conn->prepare("UPDATE members SET status = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_status, $member_data['id']);
            $update_stmt->execute();
            $update_stmt->close();
            $member_data['status'] = 'Suspended';
        }
    }
}

// Redirect if not a member
if ($current_user['role'] !== 'member') {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitCore Pro | My Profile</title>
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
                <a href="member_dashboard.php" class="block px-4 py-3 rounded-lg bg-emerald-600/30 text-emerald-300 transition">
                    <i class="fa-solid fa-home mr-3"></i>Dashboard
                </a>
                <a href="member_payments.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-receipt mr-3"></i>Payments
                </a>
                <a href="member_membership.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-calendar-alt mr-3"></i>Membership
                </a>
                <a href="member_attendance.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-clock mr-3"></i>Check In/Out
                </a>
                <a href="workout_schedule.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-dumbbell mr-3"></i>Workout Schedule
                </a>
                <a href="member_profile_edit.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-user-edit mr-3"></i>Edit Profile
                </a>
            </nav>

                <!-- About / Contact -->
                <div class="mt-4">
                    <a href="about.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                        <i class="fa-solid fa-info-circle mr-3"></i>About / Contact
                    </a>
                </div>

            <div class="mt-auto pt-8 border-t border-gray-700">
                <a href="logout.php" class="block px-4 py-3 rounded-lg hover:bg-red-600/20 transition text-gray-300 hover:text-red-400 text-center">
                    <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8 member-page">
            <div class="max-w-4xl">
                <h2 class="text-4xl font-bold text-white mb-8">My Profile</h2>

                <!-- Expiry Warning (if applicable) -->
                <?php if ($member_data && $member_data['renewal_date']): 
                    $renewal_date = new DateTime($member_data['renewal_date']);
                    $today = new DateTime();
                    $days_left = $renewal_date->diff($today)->days;
                    $is_expired = $today > $renewal_date;
                ?>
                    <?php if ($is_expired): ?>
                        <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-6 py-4 rounded-lg mb-6 flex items-center">
                            <i class="fa-solid fa-exclamation-triangle mr-3 text-lg"></i>
                            <div>
                                <p class="font-bold">Membership Expired</p>
                                <p class="text-sm">Your membership expired on <?php echo date('d M, Y', strtotime($member_data['renewal_date'])); ?>. Please renew your membership to regain access.</p>
                            </div>
                        </div>
                    <?php elseif ($days_left <= 7): ?>
                        <div class="bg-yellow-500/20 border border-yellow-500/50 text-yellow-300 px-6 py-4 rounded-lg mb-6 flex items-center">
                            <i class="fa-solid fa-clock mr-3 text-lg"></i>
                            <div>
                                <p class="font-bold">Membership Expiring Soon</p>
                                <p class="text-sm">Your membership expires in <?php echo $days_left; ?> days (<?php echo date('d M, Y', strtotime($member_data['renewal_date'])); ?>). Renew now to avoid service interruption.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Profile Card -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center text-white text-3xl">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="ml-6">
                            <h3 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($current_user['name']); ?></h3>
                            <p class="text-gray-400"><?php echo htmlspecialchars($current_user['email']); ?></p>
                        </div>
                    </div>

                    <hr class="my-6 border-gray-700">

                    <?php if ($member_data): ?>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-400 mb-2">Phone</label>
                                <p class="text-white bg-slate-700/50 p-3 rounded"><?php echo htmlspecialchars($member_data['phone']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-400 mb-2">Current Plan</label>
                                <p class="text-emerald-400 font-bold bg-slate-700/50 p-3 rounded"><?php echo htmlspecialchars($member_data['plan_type']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-400 mb-2">Status</label>
                                <span class="inline-block px-3 py-2 rounded text-sm font-bold <?php echo $member_data['status'] === 'Active' ? 'bg-emerald-500/30 text-emerald-300' : 'bg-red-500/30 text-red-300'; ?>">
                                    <?php echo htmlspecialchars($member_data['status']); ?>
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-400 mb-2">Member Since</label>
                                <p class="text-white bg-slate-700/50 p-3 rounded"><?php echo date('d M, Y', strtotime($member_data['join_date'])); ?></p>
                            </div>
                        </div>

                        <?php if ($member_data['renewal_date']): ?>
                            <div class="mt-6 p-4 bg-emerald-500/20 border border-emerald-500/50 rounded">
                                <p class="text-sm text-emerald-300">
                                    <i class="fa-solid fa-calendar-check mr-2"></i>
                                    <strong>Renewal Date:</strong> <?php echo date('d M, Y', strtotime($member_data['renewal_date'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 rounded">
                            <i class="fa-solid fa-exclamation-circle mr-2"></i>
                            <strong>No membership found.</strong> Please contact the gym staff to register as a member.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($current_user['role'] === 'member'): ?>
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <a href="member_membership.php" class="bg-gradient-to-br from-emerald-600/20 to-emerald-500/10 border border-emerald-500/30 rounded-xl p-6 hover:shadow-lg transition">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                                    <i class="fa-solid fa-credit-card text-emerald-300 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-emerald-200">Renew Online</p>
                                    <p class="text-gray-300 text-sm mt-1">Pay securely and extend your membership</p>
                                </div>
                            </div>
                        </a>

                        <a href="member_payments.php" class="bg-gradient-to-br from-blue-600/20 to-blue-500/10 border border-blue-500/30 rounded-xl p-6 hover:shadow-lg transition">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
                                    <i class="fa-solid fa-receipt text-blue-300 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-blue-200">View Payments</p>
                                    <p class="text-gray-300 text-sm mt-1">See your payment history and receipts</p>
                                </div>
                            </div>
                        </a>

                        <a href="course_access.php" class="bg-gradient-to-br from-purple-600/20 to-purple-500/10 border border-purple-500/30 rounded-xl p-6 hover:shadow-lg transition">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center">
                                    <i class="fa-solid fa-book-open text-purple-200 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-purple-200">Online Courses</p>
                                    <p class="text-gray-300 text-sm mt-1">Your purchased workout plans</p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-6 text-center">
                        <div class="text-4xl font-bold text-emerald-400 mb-2">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <p class="text-gray-400 text-sm mb-1">Membership Status</p>
                        <p class="font-bold text-white"><?php echo $member_data ? htmlspecialchars($member_data['status']) : 'Inactive'; ?></p>
                    </div>

                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-6 text-center">
                        <div class="text-4xl font-bold text-blue-400 mb-2">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <p class="text-gray-400 text-sm mb-1">Plan Type</p>
                        <p class="font-bold text-white"><?php echo $member_data ? htmlspecialchars($member_data['plan_type']) : 'None'; ?></p>
                    </div>

                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-6 text-center">
                        <div class="text-4xl font-bold text-purple-400 mb-2">
                            <i class="fa-solid fa-shield"></i>
                        </div>
                        <p class="text-gray-400 text-sm mb-1">Account Type</p>
                        <p class="font-bold text-white">Member</p>
                    </div>
                </div>

                <!-- Member Guidelines -->
                <div class="mt-8 bg-gradient-to-r from-emerald-600/20 to-teal-600/20 border border-emerald-500/30 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-emerald-300 mb-4"><i class="fa-solid fa-lightbulb mr-2"></i>Member Benefits</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li><i class="fa-solid fa-check text-emerald-400 mr-2"></i>Access to all gym facilities during operating hours</li>
                        <li><i class="fa-solid fa-check text-emerald-400 mr-2"></i>View your payment history and membership status</li>
                        <li><i class="fa-solid fa-check text-emerald-400 mr-2"></i>Check in/out and track gym visits</li>
                        <li><i class="fa-solid fa-check text-emerald-400 mr-2"></i>Edit your profile information anytime</li>
                        <li><i class="fa-solid fa-check text-emerald-400 mr-2"></i>Track membership renewal dates</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
