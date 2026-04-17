<?php
include('auth.php');
checkLogin();

if ($_SESSION['user_role'] !== 'member') {
    header("Location: login.php?error=Members only");
    exit;
}

include('db.php');

// Get current user's member record
$email = $_SESSION['user_email'];
$member_stmt = $conn->prepare("SELECT id, name, phone, address, dob, gender FROM members WHERE email = ?");
$member_stmt->bind_param("s", $email);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member = $member_result->fetch_assoc();
$member_stmt->close();

if (!$member) {
    header("Location: login.php?error=Member record not found");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    // Validation
    if (empty($name) || strlen($name) < 2) {
        $error = 'Name must be at least 2 characters';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone must be exactly 10 digits';
    } else {
        // Update member record
        $stmt = $conn->prepare("UPDATE members SET name = ?, phone = ?, address = ?, dob = ?, gender = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $phone, $address, $dob, $gender, $member['id']);
        
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            // Update the member array
            $member['name'] = $name;
            $member['phone'] = $phone;
            $member['address'] = $address;
            $member['dob'] = $dob;
            $member['gender'] = $gender;
        } else {
            $error = 'Failed to update profile';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | FitCore</title>
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
                <a href="member_attendance.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-clock mr-3"></i>Check In/Out
                </a>
                <a href="member_profile_edit.php" class="block px-4 py-3 rounded-lg bg-emerald-600/30 text-emerald-300 transition">
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
            <div class="max-w-2xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-4xl font-bold text-white mb-2">Edit Profile</h2>
                    <p class="text-gray-400">Update your personal information</p>
                </div>

                <!-- Status Messages -->
                <?php if ($error): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fa-solid fa-exclamation-circle mr-3"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-300 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fa-solid fa-check-circle mr-3"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Profile Form -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-emerald-500/20 rounded-xl p-8">
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-bold text-gray-300 mb-2">Full Name *</label>
                                <input type="text" name="name" required 
                                    class="w-full px-4 py-3 bg-slate-700/50 border border-gray-600 rounded-lg text-white placeholder-gray-500 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition"
                                    value="<?php echo htmlspecialchars($member['name']); ?>">
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-bold text-gray-300 mb-2">Phone (10 digits) *</label>
                                <input type="text" name="phone" required maxlength="10"
                                    class="w-full px-4 py-3 bg-slate-700/50 border border-gray-600 rounded-lg text-white placeholder-gray-500 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition"
                                    value="<?php echo htmlspecialchars($member['phone']); ?>">
                            </div>
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-bold text-gray-300 mb-2">Address</label>
                            <textarea name="address" rows="3"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-gray-600 rounded-lg text-white placeholder-gray-500 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition"
                                placeholder="123 Gym Street, City"><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Date of Birth -->
                            <div>
                                <label class="block text-sm font-bold text-gray-300 mb-2">Date of Birth</label>
                                <input type="date" name="dob"
                                    class="w-full px-4 py-3 bg-slate-700/50 border border-gray-600 rounded-lg text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition"
                                    value="<?php echo htmlspecialchars($member['dob'] ?? ''); ?>">
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-bold text-gray-300 mb-2">Gender</label>
                                <select name="gender"
                                    class="w-full px-4 py-3 bg-slate-700/50 border border-gray-600 rounded-lg text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition">
                                    <option value="">-- Select --</option>
                                    <option value="Male" <?php echo ($member['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($member['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($member['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Email (Read-only) -->
                        <div>
                            <label class="block text-sm font-bold text-gray-300 mb-2">Email (Cannot be changed)</label>
                            <input type="email" disabled
                                class="w-full px-4 py-3 bg-slate-700/50 border border-gray-600 rounded-lg text-gray-400 cursor-not-allowed"
                                value="<?php echo htmlspecialchars($email); ?>">
                            <p class="text-xs text-gray-500 mt-1">Contact admin to change email address</p>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-4 pt-6 border-t border-gray-700">
                            <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-lg font-bold transition">
                                <i class="fa-solid fa-save mr-2"></i>Save Changes
                            </button>
                            <a href="member_dashboard.php" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-3 rounded-lg font-bold transition text-center">
                                <i class="fa-solid fa-arrow-left mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
