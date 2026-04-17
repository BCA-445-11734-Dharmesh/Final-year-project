<?php
include('auth.php');
checkLogin();
requireAdmin(); // Only admins can edit members

include('db.php');

$current_user = getCurrentUser();
$member = null;
$error = '';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    $stmt->close();

    if (!$member) {
        header("Location: index.php?error=Member not found");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $plan = trim($_POST['plan'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    $id = intval($_POST['id']);

    // Validation
    if (empty($name) || strlen($name) < 2) {
        $error = "Name must be at least 2 characters";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone must be exactly 10 digits";
    } elseif (!in_array($plan, ['1 Month', '2 Months', '3 Months', 'Annual'])) {
        $error = "Invalid plan selected";
    } elseif (!in_array($status, ['Active', 'Inactive', 'Suspended'])) {
        $error = "Invalid status";
    } else {
        $stmt = $conn->prepare("UPDATE members SET name = ?, phone = ?, plan_type = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $phone, $plan, $status, $id);
        if ($stmt->execute()) {
            header("Location: index.php?success=Member updated successfully");
            exit;
        } else {
            $error = "Failed to update member";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitCore Pro | Edit Member</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <div class="w-64 bg-slate-900 text-white p-6 flex flex-col shadow-xl">
            <h1 class="text-2xl font-bold text-emerald-400 mb-10"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore</h1>
            <nav class="space-y-4 flex-1">
                <a href="index.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">Dashboard</a>
                <a href="members.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">Members</a>
                <a href="payments.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">Payments</a>
            </nav>
            
            <!-- User Section -->
            <div class="border-t border-gray-700 pt-4">
                <div class="text-xs text-gray-400 mb-3 truncate">
                    <i class="fa-solid fa-user-circle mr-2"></i><?php echo htmlspecialchars($current_user['name']); ?>
                </div>
                <a href="logout.php" class="block w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-bold text-center transition">
                    <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <div class="flex-1 p-10 overflow-y-auto admin-page">
            <div class="max-w-2xl">
                <a href="index.php" class="text-emerald-600 hover:text-emerald-700 mb-6 inline-flex items-center">
                    <i class="fa-solid fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6">Edit Member</h2>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                            <i class="fa-solid fa-circle-exclamation mr-3"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($member['name']); ?>" required 
                                class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required 
                                class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition" placeholder="10 digits">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Membership Plan</label>
                            <select name="plan" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none appearance-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                                <option value="1 Month" <?php echo ($member['plan_type'] === '1 Month') ? 'selected' : ''; ?>>1 Month (₹1000)</option>
                                <option value="2 Months" <?php echo ($member['plan_type'] === '2 Months') ? 'selected' : ''; ?>>2 Months (₹1800)</option>
                                <option value="3 Months" <?php echo ($member['plan_type'] === '3 Months') ? 'selected' : ''; ?>>3 Months (₹2500)</option>
                                <option value="Annual" <?php echo ($member['plan_type'] === 'Annual') ? 'selected' : ''; ?>>Annual (₹5000)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none appearance-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                                <option value="Active" <?php echo ($member['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($member['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Suspended" <?php echo ($member['status'] === 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="submit" class="flex-1 bg-emerald-600 text-white p-4 rounded-xl font-bold shadow-lg hover:bg-emerald-700 transition transform hover:scale-105">
                                <i class="fa-solid fa-save mr-2"></i>Save Changes
                            </button>
                            <a href="index.php" class="flex-1 bg-gray-300 text-gray-800 p-4 rounded-xl font-bold shadow-lg hover:bg-gray-400 transition text-center">
                                <i class="fa-solid fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
