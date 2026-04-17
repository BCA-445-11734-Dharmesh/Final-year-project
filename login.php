<?php
// Force clear any existing session when accessing login page
if (isset($_SESSION['user_id'])) {
    session_destroy();
    setcookie('PHPSESSID', '', time() - 3600, '/');
}

include('auth.php');

// If somehow still logged in after auth.php loads, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

function safeNextUrl($next) {
    $next = trim((string)$next);
    if ($next === '') return '';
    // Only allow relative paths inside this app.
    if (preg_match('/^https?:\/\//i', $next)) return '';
    if (strpos($next, '..') !== false) return '';
    // Remove leading slash so Location: works as relative.
    $next = ltrim($next, '/');
    if (!preg_match('/^[a-zA-Z0-9_\/\.-]+\.php(\?.*)?$/', $next)) return '';
    return $next;
}
$setup_needed = false;

// Check if users table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (!$check_table || mysqli_num_rows($check_table) == 0) {
    $setup_needed = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$setup_needed) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = loginUser($email, $password);
    
    if ($result['success']) {
        // Redirect based on user role
        $next = safeNextUrl($_GET['next'] ?? '');
        if ($next) {
            header("Location: " . $next);
            exit;
        }
        if ($_SESSION['user_role'] === 'member') {
            header("Location: member_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitCore Pro | Login</title>
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
                <p class="text-emerald-100">Gym Management System</p>
            </div>

            <!-- Content -->
            <div class="px-8 py-10">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome Back</h2>
                <p class="text-gray-600 text-sm mb-8">Sign in to your account to continue</p>

                <?php if ($setup_needed): ?>
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-4 rounded-lg mb-6">
                        <p class="font-bold mb-2"><i class="fa-solid fa-exclamation-triangle mr-2"></i>Setup Required!</p>
                        <p class="text-sm mb-3">The database tables need to be initialized first.</p>
                        <a href="setup.php" class="block w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 rounded text-center transition">
                            <i class="fa-solid fa-gear mr-2"></i>Run Setup
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 flex items-start">
                        <i class="fa-solid fa-check-circle mr-3 mt-0.5"></i>
                        <div>You have been logged out successfully. Please login again.</div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start">
                        <i class="fa-solid fa-circle-exclamation mr-3 mt-0.5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5" <?php echo $setup_needed ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                        <input type="email" name="email" required placeholder="admin@fitcore.com" <?php echo $setup_needed ? 'disabled' : ''; ?>
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required placeholder="••••••••" <?php echo $setup_needed ? 'disabled' : ''; ?>
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 text-white py-3 rounded-lg font-bold hover:bg-emerald-700 transition transform hover:scale-105 shadow-lg">
                        <i class="fa-solid fa-sign-in-alt mr-2"></i>Sign In
                    </button>
                </form>

                <hr class="my-6 border-gray-200">

                <p class="text-center text-gray-600 text-sm">
                    Don't have an account? 
                    <a href="register.php" class="text-emerald-600 font-bold hover:text-emerald-700">
                        Create one here
                    </a>
                </p>

                <!-- Demo credentials -->
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-xs font-bold text-blue-900 mb-2"><i class="fa-solid fa-info-circle mr-1"></i>Demo Credentials</p>
                    <p class="text-xs text-blue-800">Email: <code class="bg-white px-2 py-1 rounded">admin@fitcore.com</code></p>
                    <p class="text-xs text-blue-800">Password: <code class="bg-white px-2 py-1 rounded">Admin@123</code></p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-white text-xs mt-6 opacity-75">
            FitCore Pro v1.0 | BCA Final Project 2026
        </p>
    </div>
</body>
</html>
