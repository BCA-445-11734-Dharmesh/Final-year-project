<?php
include('auth.php');
checkLogin();
include('db.php');

if ($_SESSION['user_role'] !== 'member') {
    header("Location: index.php");
    exit;
}

$buyer_user_id = (int)$_SESSION['user_id'];

// Ensure `content_path` column exists so SELECT won't fail on older schemas
// Check INFORMATION_SCHEMA for the column and add it only if missing
$colCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'content_path'");
if ($colCheck) {
    $colCheck->execute();
    $cres = $colCheck->get_result()->fetch_assoc();
    $colCheck->close();
    if (intval($cres['cnt']) === 0) {
        try {
            $conn->query("ALTER TABLE courses ADD COLUMN content_path VARCHAR(255) NULL");
        } catch (mysqli_sql_exception $e) {
            // ignore; another process may have added it concurrently
        }
    }
}

$stmt = $conn->prepare("
    SELECT o.course_key, o.course_name, o.amount, o.payment_date, o.status, c.content_path
    FROM course_orders o
    LEFT JOIN courses c ON o.course_key = c.course_key
    WHERE o.buyer_user_id = ? AND o.status = 'Paid'
    ORDER BY o.payment_date DESC
");
$stmt->bind_param("i", $buyer_user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$coursesPaid = [];
foreach ($orders as $o) {
    $coursesPaid[$o['course_key']] = $o;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Courses | FitCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-emerald-900 to-slate-900 min-h-screen text-gray-100">
    <div class="flex h-screen">
        <div class="w-64 bg-slate-900 border-r border-emerald-500/20 text-white p-6">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-emerald-400"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore</h1>
                <p class="text-gray-400 text-sm mt-1">Gym Management</p>
            </div>

            <nav class="space-y-2">
                <a href="member_dashboard.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-home mr-3"></i>Dashboard
                </a>
                <a href="member_membership.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-calendar-alt mr-3"></i>Membership
                </a>
                <a href="member_payments.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-receipt mr-3"></i>Payments
                </a>
                <a href="course_access.php" class="block px-4 py-3 rounded-lg bg-emerald-600/30 text-emerald-300 transition">
                    <i class="fa-solid fa-book-open mr-3"></i>Online Courses
                </a>
            </nav>

            <div class="mt-auto pt-8 border-t border-gray-700">
                <a href="logout.php" class="block px-4 py-3 rounded-lg hover:bg-red-600/20 transition text-gray-300 hover:text-red-400 text-center">
                    <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <div class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-3xl font-bold text-white mb-6">Your Online Courses</h2>
                <p class="text-gray-400 mb-8">Unlock workout plans after successful payment.</p>

                <?php if (empty($coursesPaid)): ?>
                    <div class="bg-white/5 border border-white/10 rounded-2xl p-8 text-center">
                        <i class="fa-solid fa-lock text-4xl text-emerald-400 mb-4 block"></i>
                        <h3 class="text-xl font-bold mb-2">No courses purchased yet</h3>
                        <p class="text-gray-400 mb-6">Go to the homepage to buy an online workout plan.</p>
                        <a href="welcome.php" class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl transition">
                            Buy Online Plans
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($coursesPaid as $c): ?>
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 flex items-start justify-between gap-6">
                                <div>
                                    <h3 class="text-lg font-bold text-white mb-2"><?php echo htmlspecialchars($c['course_name']); ?></h3>
                                    <p class="text-gray-400 text-sm">Paid: ₹<?php echo htmlspecialchars(number_format((float)$c['amount'], 2)); ?> • <?php echo date('d M, Y', strtotime($c['payment_date'])); ?></p>
                                    <?php if (!empty($c['content_path'])): ?>
                                        <p class="text-gray-300 text-sm mt-3">Your course content is available below.</p>
                                    <?php else: ?>
                                        <p class="text-gray-300 text-sm mt-3">Content will be available here once uploaded by admin.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right flex flex-col items-end gap-2">
                                    <?php if (!empty($c['content_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($c['content_path']); ?>" target="_blank" class="inline-block bg-emerald-600 text-white px-4 py-2 rounded">View Content</a>
                                    <?php endif; ?>
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        Unlocked
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

