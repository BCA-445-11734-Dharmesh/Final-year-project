<?php
include('auth.php');

// If logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'member') {
        header("Location: member_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitCore Pro | Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-emerald-900 to-slate-900 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-slate-900/50 backdrop-blur-md border-b border-emerald-500/20 px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">
                <i class="fa-solid fa-dumbbell mr-2 text-emerald-400"></i>FitCore Pro
            </h1>
            <div class="space-x-4">
                <a href="login.php" class="px-4 py-2 rounded-lg text-white hover:bg-slate-800 transition">
                    Login
                </a>
                <a href="register.php" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                    Register
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <div class="max-w-6xl mx-auto px-6 py-20 text-center text-white">
        <h2 class="text-5xl font-bold mb-6">Welcome to FitCore Pro</h2>
        <p class="text-xl text-gray-300 mb-12">Professional Gym Management System</p>
    </div>

    <!-- Role Cards -->
    <div class="max-w-6xl mx-auto px-6 py-12">
        <h3 class="text-3xl font-bold text-white text-center mb-12">Choose Your Access Level</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Admin Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="h-32 bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center">
                    <i class="fa-solid fa-crown text-5xl text-white"></i>
                </div>
                <div class="p-8">
                    <h4 class="text-2xl font-bold text-gray-800 mb-4">Admin</h4>
                    <p class="text-gray-600 mb-6">Complete system control and member management.</p>
                    <ul class="space-y-2 mb-6 text-left">
                        <li class="text-gray-700"><i class="fa-solid fa-check text-red-600 mr-2"></i>Add/Edit/Delete Members</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-red-600 mr-2"></i>View All Payments</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-red-600 mr-2"></i>Manage Staff</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-red-600 mr-2"></i>System Reports</li>
                    </ul>
                    <p class="text-sm text-gray-500 mb-4">Demo: admin@fitcore.com / Admin@123</p>
                    <a href="login.php" class="block w-full bg-red-600 text-white py-2 rounded-lg font-bold hover:bg-red-700 transition">
                        Admin Login
                    </a>
                </div>
            </div>

            <!-- Staff Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="h-32 bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-user-tie text-5xl text-white"></i>
                </div>
                <div class="p-8">
                    <h4 class="text-2xl font-bold text-gray-800 mb-4">Staff</h4>
                    <p class="text-gray-600 mb-6">Limited access for gym staff members.</p>
                    <ul class="space-y-2 mb-6 text-left">
                        <li class="text-gray-700"><i class="fa-solid fa-check text-blue-600 mr-2"></i>View Members</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-blue-600 mr-2"></i>View Payments</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-blue-600 mr-2"></i>Read-Only Access</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-blue-600 mr-2"></i>No Edit/Delete</li>
                    </ul>
                    <p class="text-sm text-gray-500 mb-4">Created by admin</p>
                    <a href="login.php" class="block w-full bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700 transition">
                        Staff Login
                    </a>
                </div>
            </div>

            <!-- Member Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="h-32 bg-gradient-to-br from-emerald-600 to-emerald-700 flex items-center justify-center">
                    <i class="fa-solid fa-user text-5xl text-white"></i>
                </div>
                <div class="p-8">
                    <h4 class="text-2xl font-bold text-gray-800 mb-4">Member</h4>
                    <p class="text-gray-600 mb-6">Access to personal membership details.</p>
                    <ul class="space-y-2 mb-6 text-left">
                        <li class="text-gray-700"><i class="fa-solid fa-check text-emerald-600 mr-2"></i>View Your Profile</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-emerald-600 mr-2"></i>Payment History</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-emerald-600 mr-2"></i>Membership Status</li>
                        <li class="text-gray-700"><i class="fa-solid fa-check text-emerald-600 mr-2"></i>Benefits Info</li>
                    </ul>
                    <p class="text-sm text-gray-500 mb-4">Self-register or admin adds you</p>
                    <a href="register.php" class="block w-full bg-emerald-600 text-white py-2 rounded-lg font-bold hover:bg-emerald-700 transition">
                        Join Now
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="bg-slate-800/50 backdrop-blur mt-20 py-16">
        <div class="max-w-6xl mx-auto px-6">
            <h3 class="text-3xl font-bold text-white text-center mb-12">Platform Features</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-slate-900 p-6 rounded-xl">
                    <i class="fa-solid fa-lock text-emerald-400 text-3xl mb-3 block"></i>
                    <h4 class="font-bold text-white mb-2">Secure</h4>
                    <p class="text-gray-400 text-sm">Password hashing & role-based access</p>
                </div>
                <div class="bg-slate-900 p-6 rounded-xl">
                    <i class="fa-solid fa-database text-emerald-400 text-3xl mb-3 block"></i>
                    <h4 class="font-bold text-white mb-2">Database</h4>
                    <p class="text-gray-400 text-sm">MySQL with prepared statements</p>
                </div>
                <div class="bg-slate-900 p-6 rounded-xl">
                    <i class="fa-solid fa-mobile text-emerald-400 text-3xl mb-3 block"></i>
                    <h4 class="font-bold text-white mb-2">Responsive</h4>
                    <p class="text-gray-400 text-sm">Works on all devices seamlessly</p>
                </div>
                <div class="bg-slate-900 p-6 rounded-xl">
                    <i class="fa-solid fa-chart-line text-emerald-400 text-3xl mb-3 block"></i>
                    <h4 class="font-bold text-white mb-2">Analytics</h4>
                    <p class="text-gray-400 text-sm">Payment & member tracking</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div class="max-w-6xl mx-auto px-6 py-16">
        <h3 class="text-3xl font-bold text-white text-center mb-12">Membership Plans</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            // Fetch plans from DB (public pricing)
            include('db.php');
            $plans_public = [];
            $pub_stmt = $conn->prepare("SELECT name, price, duration_days, description FROM membership_plans WHERE is_active = 1 ORDER BY duration_days ASC");
            if ($pub_stmt) {
                $pub_stmt->execute();
                $pub_result = $pub_stmt->get_result();
                while ($p = $pub_result->fetch_assoc()) {
                    $plans_public[] = $p;
                }
                $pub_stmt->close();
            }
            ?>
            <?php if (!empty($plans_public)): ?>
                <?php foreach ($plans_public as $p): ?>
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                        <div class="h-16 bg-gradient-to-br from-emerald-600 to-emerald-500"></div>
                        <div class="p-8">
                            <h4 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($p['name']); ?></h4>
                            <div class="text-3xl font-black text-emerald-600 mb-4">₹<?php echo htmlspecialchars($p['price']); ?></div>
                            <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars((int)$p['duration_days']); ?> days access</p>
                            <p class="text-sm text-gray-500 mb-6"><?php echo htmlspecialchars($p['description'] ?? 'Full access'); ?></p>
                            <a href="register.php" class="block w-full bg-emerald-600 text-white text-center py-2 rounded-lg font-bold hover:bg-emerald-700 transition">
                                Get Started
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-white text-center col-span-full">
                    <p>No plans found yet. Run <a href="setup.php" class="text-emerald-200 font-bold underline">setup.php</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Online Workout Plans -->
    <div class="max-w-6xl mx-auto px-6 py-16">
        <?php
        // Load active courses from DB if available
        include('db.php');
        $online_courses = [];
        $cstmt = $conn->prepare("SELECT course_key, name, price, description FROM courses WHERE active = 1 ORDER BY created_at DESC");
        if ($cstmt) {
            $cstmt->execute();
            $cres = $cstmt->get_result();
            while ($r = $cres->fetch_assoc()) {
                $online_courses[$r['course_key']] = [
                    'name' => $r['name'],
                    'price' => (float)$r['price'],
                    'tag' => $r['description'] ?: ''
                ];
            }
            $cstmt->close();
        }

        // Fallback static if none found
        if (empty($online_courses)) {
            $online_courses = [
                'fat_loss_4w' => [
                    'name' => 'Fat Loss Bootcamp (4 Weeks)',
                    'price' => 499,
                    'tag' => 'Beginner friendly',
                ],
                'strength_8w' => [
                    'name' => 'Strength Builder (8 Weeks)',
                    'price' => 899,
                    'tag' => 'Progressive training',
                ],
                'nutrition_masterclass' => [
                    'name' => 'Nutrition Masterclass (Live + Recordings)',
                    'price' => 1299,
                    'tag' => 'Meal plans + habits',
                ],
            ];
        }
        ?>
        <h3 class="text-3xl font-bold text-white text-center mb-12">Online Workout Plans</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($online_courses as $key => $c): ?>
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-emerald-600 to-emerald-500"></div>
                    <div class="p-8">
                        <h4 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($c['name']); ?></h4>
                        <p class="text-sm text-gray-500 mb-4"><?php echo htmlspecialchars($c['tag']); ?></p>
                        <div class="text-3xl font-black text-emerald-600 mb-4">₹<?php echo htmlspecialchars($c['price']); ?></div>
                        <p class="text-sm text-gray-600 mb-6">Instant unlock after successful payment.</p>
                        <a href="course_checkout.php?course_key=<?php echo urlencode($key); ?>"
                           class="block w-full bg-emerald-600 text-white text-center py-2 rounded-lg font-bold hover:bg-emerald-700 transition">
                           Buy Now
                        </a>
                        <p class="text-xs text-gray-500 mt-3">Login required to purchase.</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-emerald-500/20 bg-slate-900 text-gray-400 py-8">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <p>FitCore Pro v1.0 | BCA Final Project 2026</p>
        </div>
    </footer>
</body>
</html>
