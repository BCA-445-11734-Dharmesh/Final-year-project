<?php
include('db.php');
include('auth.php');

// Redirect if not a member
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'member') {
    header('Location: login.php');
    exit;
}

// Create table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS workout_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_type ENUM('admin', 'staff', 'member') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    scheduled_date DATE,
    target_type ENUM('all', 'specific_plan', 'specific_member') DEFAULT 'all',
    target_value VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_table);

// Get current user's member info
$user_query = "SELECT m.*, u.name
               FROM members m
               JOIN users u ON m.email = u.email
               WHERE u.email = ? AND u.role = 'member'
               LIMIT 1";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $_SESSION['user_email']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$member = mysqli_fetch_assoc($user_result);
$user_stmt->close();

// Fetch schedules for this member
// Include: all schedules, schedules for their plan, schedules for them specifically
$schedules_with_senders = [];
if ($member) {
    $schedules_query = "SELECT ws.*
                        FROM workout_schedules ws
                        WHERE ws.target_type = 'all'
                        OR (ws.target_type = 'specific_plan' AND ws.target_value = ?)
                        OR (ws.target_type = 'specific_member' AND ws.target_value = ?)
                        ORDER BY ws.created_at DESC";

    $schedules_stmt = $conn->prepare($schedules_query);
    $schedules_stmt->bind_param("si", $member['plan_type'], $member['id']);
    $schedules_stmt->execute();
    $schedules_result = $schedules_stmt->get_result();
    $schedules = [];
    while ($row = mysqli_fetch_assoc($schedules_result)) {
        $schedules[] = $row;
    }
    $schedules_stmt->close();

    // Fetch sender names
    foreach ($schedules as $schedule) {
        $sender_query = "SELECT name FROM users WHERE id = ?";
        $sender_stmt = $conn->prepare($sender_query);
        $sender_stmt->bind_param("i", $schedule['sender_id']);
        $sender_stmt->execute();
        $sender_result = $sender_stmt->get_result();
        $sender = mysqli_fetch_assoc($sender_result);
        $sender_stmt->close();

        $schedule['sender_name'] = $sender['name'] ?? 'Unknown';
        $schedules_with_senders[] = $schedule;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Schedule - FitCore Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gray-900 text-white font-sans">
    <nav class="bg-emerald-700 p-4 shadow-lg">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore Pro</h1>
            <div>
                <a href="member_dashboard.php" class="text-white hover:text-emerald-200 mr-4"><i class="fa-solid fa-home mr-1"></i>Dashboard</a>
                <a href="logout.php" class="text-white hover:text-emerald-200"><i class="fa-solid fa-sign-out-alt mr-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-4 mt-6">
        <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
            <h2 class="text-2xl font-bold mb-2 text-emerald-400"><i class="fa-solid fa-calendar-days mr-2"></i>Your Workout Schedules</h2>
            <p class="text-gray-400 mb-6">Latest workout routines and training schedules from our trainers</p>

            <div class="space-y-4">
                <?php if (empty($schedules_with_senders)): ?>
                    <div class="bg-gray-700 rounded-lg p-8 text-center">
                        <i class="fa-solid fa-inbox text-4xl text-gray-500 mb-4"></i>
                        <p class="text-gray-400">No workout schedules posted yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($schedules_with_senders as $schedule): ?>
                        <div class="bg-gray-700 rounded-lg p-6 border-l-4 border-emerald-500 hover:border-emerald-400 transition">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($schedule['title']); ?></h3>
                                    <p class="text-sm text-gray-400">
                                        <i class="fa-solid fa-user mr-1 text-emerald-400"></i>
                                        Posted by <span class="text-emerald-400"><?php echo htmlspecialchars($schedule['sender_name']); ?></span>
                                    </p>
                                </div>
                                <?php if ($schedule['scheduled_date']): ?>
                                    <div class="bg-emerald-900 text-emerald-100 px-3 py-1 rounded-full text-sm">
                                        <i class="fa-solid fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($schedule['scheduled_date'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="bg-gray-800 rounded-lg p-4 mb-4">
                                <p class="text-gray-200 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($schedule['message'])); ?></p>
                            </div>

                            <div class="text-xs text-gray-500">
                                <i class="fa-solid fa-clock mr-1"></i>Posted on <?php echo date('M d, Y at H:i', strtotime($schedule['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="member_attendance.php" class="bg-emerald-700 hover:bg-emerald-600 rounded-lg p-4 text-center transition">
                <i class="fa-solid fa-sign-in-alt text-2xl mb-2"></i>
                <p class="font-semibold">Check In</p>
            </a>
            <a href="member_payments.php" class="bg-blue-700 hover:bg-blue-600 rounded-lg p-4 text-center transition">
                <i class="fa-solid fa-credit-card text-2xl mb-2"></i>
                <p class="font-semibold">Payments</p>
            </a>
            <a href="member_dashboard.php" class="bg-purple-700 hover:bg-purple-600 rounded-lg p-4 text-center transition">
                <i class="fa-solid fa-user text-2xl mb-2"></i>
                <p class="font-semibold">Profile</p>
            </a>
        </div>
    </div>
</body>
</html>
