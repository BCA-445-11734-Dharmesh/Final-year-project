<?php
include('auth.php');
checkLogin();

if ($_SESSION['user_role'] !== 'member') {
    header("Location: login.php?error=Members only");
    exit;
}

include('db.php');

// Get current user's member ID
$email = $_SESSION['user_email'];
$member_stmt = $conn->prepare("SELECT id, name, status FROM members WHERE email = ?");
$member_stmt->bind_param("s", $email);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member = $member_result->fetch_assoc();
$member_stmt->close();

if (!$member) {
    header("Location: login.php?error=Member record not found");
    exit;
}

// Check if already checked in
$today = date('Y-m-d');
$check_in_stmt = $conn->prepare("SELECT id, check_in_time, check_out_time FROM attendance WHERE member_id = ? AND DATE(check_in_time) = ?");
$check_in_stmt->bind_param("is", $member['id'], $today);
$check_in_stmt->execute();
$check_in_result = $check_in_stmt->get_result();
$today_attendance = $check_in_result->fetch_assoc();
$check_in_stmt->close();

$message = '';
$error = '';

// Handle check-in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'check_in' && !$today_attendance) {
        $stmt = $conn->prepare("INSERT INTO attendance (member_id, check_in_time) VALUES (?, NOW())");
        $stmt->bind_param("i", $member['id']);
        if ($stmt->execute()) {
            $message = 'Check-in successful!';
            $today_attendance = ['id' => $stmt->insert_id, 'check_in_time' => date('Y-m-d H:i:s'), 'check_out_time' => null];
        } else {
            $error = 'Check-in failed. Please try again.';
        }
        $stmt->close();
    } elseif ($_POST['action'] == 'check_out' && $today_attendance && !$today_attendance['check_out_time']) {
        $stmt = $conn->prepare("UPDATE attendance SET check_out_time = NOW() WHERE id = ?");
        $stmt->bind_param("i", $today_attendance['id']);
        if ($stmt->execute()) {
            $message = 'Check-out successful!';
            $today_attendance['check_out_time'] = date('Y-m-d H:i:s');
        } else {
            $error = 'Check-out failed. Please try again.';
        }
        $stmt->close();
    }
}

// Get attendance history
$history_stmt = $conn->prepare("SELECT check_in_time, check_out_time FROM attendance WHERE member_id = ? ORDER BY check_in_time DESC LIMIT 10");
$history_stmt->bind_param("i", $member['id']);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
$history = $history_result->fetch_all(MYSQLI_ASSOC);
$history_stmt->close();

// Get this month's attendance count
$month = date('m');
$year = date('Y');
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE member_id = ? AND MONTH(check_in_time) = ? AND YEAR(check_in_time) = ?");
$count_stmt->bind_param("iii", $member['id'], $month, $year);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();
$count_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check In/Out | FitCore</title>
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
                <a href="member_membership.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-calendar-alt mr-3"></i>Membership
                </a>
                <a href="member_attendance.php" class="block px-4 py-3 rounded-lg bg-emerald-600/30 text-emerald-300 transition">
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
        <div class="flex-1 overflow-auto p-8 member-page">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-4xl font-bold text-white mb-2">Check In / Check Out</h2>
                    <p class="text-gray-400">Track your gym visits</p>
                </div>

                <!-- Status Messages -->
                <?php if ($error): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fa-solid fa-exclamation-circle mr-3"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-300 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fa-solid fa-check-circle mr-3"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Check In/Out Card -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Today's Status -->
                    <div class="md:col-span-1 bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-6">
                        <h3 class="text-white font-bold text-lg mb-4">Today's Status</h3>
                        <?php if ($today_attendance): ?>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-gray-400 text-sm">Check In Time</p>
                                    <p class="text-white text-lg font-bold">
                                        <?php echo date('H:i', strtotime($today_attendance['check_in_time'])); ?>
                                    </p>
                                </div>
                                <?php if ($today_attendance['check_out_time']): ?>
                                    <div>
                                        <p class="text-gray-400 text-sm">Check Out Time</p>
                                        <p class="text-white text-lg font-bold">
                                            <?php echo date('H:i', strtotime($today_attendance['check_out_time'])); ?>
                                        </p>
                                    </div>
                                    <div class="pt-2 border-t border-gray-700">
                                        <p class="text-gray-400 text-sm">Duration</p>
                                        <p class="text-emerald-400 font-bold">
                                            <?php
                                                $check_in = new DateTime($today_attendance['check_in_time']);
                                                $check_out = new DateTime($today_attendance['check_out_time']);
                                                $diff = $check_in->diff($check_out);
                                                echo $diff->format('%h hrs %i mins');
                                            ?>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" class="pt-4">
                                        <input type="hidden" name="action" value="check_out">
                                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-bold transition">
                                            <i class="fa-solid fa-sign-out-alt mr-2"></i>Check Out
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-400 text-sm mb-4">Not checked in yet</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="check_in">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-lg font-bold transition">
                                    <i class="fa-solid fa-sign-in-alt mr-2"></i>Check In
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- This Month Stats -->
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-6">
                        <h3 class="text-white font-bold text-lg mb-4">This Month</h3>
                        <div class="text-center">
                            <p class="text-5xl font-bold text-emerald-400"><?php echo $count_data['count']; ?></p>
                            <p class="text-gray-400 text-sm mt-2">Gym Visits</p>
                        </div>
                    </div>

                    <!-- Member Info -->
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-6">
                        <h3 class="text-white font-bold text-lg mb-4">Member Info</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-gray-400 text-sm">Name</p>
                                <p class="text-white font-bold"><?php echo htmlspecialchars($member['name']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">Status</p>
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?php echo $member['status'] === 'Active' ? 'bg-emerald-500/30 text-emerald-300' : 'bg-red-500/30 text-red-300'; ?>">
                                    <?php echo htmlspecialchars($member['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance History -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-6">
                    <h3 class="text-white font-bold text-lg mb-6">Recent Check-Ins</h3>
                    
                    <?php if (count($history) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-700">
                                        <th class="text-left py-3 px-4 text-gray-400 font-semibold">Date</th>
                                        <th class="text-left py-3 px-4 text-gray-400 font-semibold">Check In</th>
                                        <th class="text-left py-3 px-4 text-gray-400 font-semibold">Check Out</th>
                                        <th class="text-left py-3 px-4 text-gray-400 font-semibold">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $record): ?>
                                        <tr class="border-b border-gray-700/50 hover:bg-emerald-600/10 transition">
                                            <td class="py-3 px-4 text-white">
                                                <?php echo date('M d, Y', strtotime($record['check_in_time'])); ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-300">
                                                <?php echo date('H:i', strtotime($record['check_in_time'])); ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-300">
                                                <?php echo $record['check_out_time'] ? date('H:i', strtotime($record['check_out_time'])) : '<span class="text-gray-500">-</span>'; ?>
                                            </td>
                                            <td class="py-3 px-4 text-emerald-400">
                                                <?php 
                                                    if ($record['check_out_time']) {
                                                        $check_in = new DateTime($record['check_in_time']);
                                                        $check_out = new DateTime($record['check_out_time']);
                                                        $diff = $check_in->diff($check_out);
                                                        echo $diff->format('%h h %i m');
                                                    } else {
                                                        echo '<span class="text-gray-500">-</span>';
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-400 text-center py-8">No check-in records yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
