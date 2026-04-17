<?php
include('db.php');
include('auth.php');

// Redirect if not admin or staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
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

// Fetch all schedules
$schedules_query = "SELECT ws.*, u.name as sender_name 
                    FROM workout_schedules ws 
                    JOIN users u ON ws.sender_id = u.id 
                    ORDER BY ws.created_at DESC";
$schedules_result = mysqli_query($conn, $schedules_query);

$schedules = [];
while ($row = mysqli_fetch_assoc($schedules_result)) {
    $schedules[] = $row;
}

// Fetch all plans for target options
$plans_query = "SELECT DISTINCT plan_type FROM members WHERE status = 'Active'";
$plans_result = mysqli_query($conn, $plans_query);
$plans = [];
while ($row = mysqli_fetch_assoc($plans_result)) {
    $plans[] = $row['plan_type'];
}

// Fetch all active members for specific target
$members_query = "SELECT id, name, phone FROM members WHERE status = 'Active' ORDER BY name";
$members_result = mysqli_query($conn, $members_query);
$members = [];
while ($row = mysqli_fetch_assoc($members_result)) {
    $members[] = $row;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_schedule'])) {
        $title = trim($_POST['schedule_title'] ?? '');
        $content = trim($_POST['schedule_content'] ?? '');
        $scheduled_date = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : '';
        $target_type = $_POST['target_type'] ?? 'all';
        $target_value = $target_type === 'all' ? '' : ($_POST['target_value'] ?? '');
        $sender_id = $_SESSION['user_id'];
        $sender_type = $_SESSION['user_role'];

        if (empty($title) || empty($content)) {
            $error = 'Title and content are required';
        } else {
            $stmt = $conn->prepare("INSERT INTO workout_schedules (sender_id, sender_type, title, message, scheduled_date, target_type, target_value) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issssss", $sender_id, $sender_type, $title, $content, $scheduled_date, $target_type, $target_value);
                if ($stmt->execute()) {
                    $message = 'Workout schedule posted successfully!';
                    $_POST = [];
                    // Refresh schedules
                    $schedules_result = mysqli_query($conn, "SELECT ws.*, u.name as sender_name FROM workout_schedules ws JOIN users u ON ws.sender_id = u.id ORDER BY ws.created_at DESC");
                    $schedules = [];
                    while ($row = mysqli_fetch_assoc($schedules_result)) {
                        $schedules[] = $row;
                    }
                } else {
                    $error = 'Failed to post schedule: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        }
    } elseif (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);
        $schedule = mysqli_fetch_assoc(mysqli_query($conn, "SELECT sender_id FROM workout_schedules WHERE id = $delete_id"));
        
        if ($schedule && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_id'] === $schedule['sender_id'])) {
            if (mysqli_query($conn, "DELETE FROM workout_schedules WHERE id = $delete_id")) {
                $message = 'Schedule deleted successfully';
            } else {
                $error = 'Failed to delete schedule';
            }
        }
    }
}

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
                <a href="index.php" class="text-white hover:text-emerald-200 mr-4"><i class="fa-solid fa-home mr-1"></i>Home</a>
                <a href="logout.php" class="text-white hover:text-emerald-200"><i class="fa-solid fa-sign-out-alt mr-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-4 mt-6">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-emerald-900 border-2 border-emerald-500 text-emerald-100 px-4 py-3 rounded-lg mb-6">
                <i class="fa-solid fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-900 border-2 border-red-500 text-red-100 px-4 py-3 rounded-lg mb-6">
                <i class="fa-solid fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Post New Schedule Form -->
            <div class="lg:col-span-1">
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4 text-emerald-400"><i class="fa-solid fa-pen-to-square mr-2"></i>Post Workout Schedule</h2>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Schedule Title</label>
                            <input type="text" name="schedule_title" placeholder="e.g., Weekly Abs Workout" 
                                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:border-emerald-500" required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Workout Details</label>
                            <textarea name="schedule_content" rows="4" placeholder="Describe the workout..." 
                                      class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:border-emerald-500" required></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Scheduled Date</label>
                            <input type="date" name="scheduled_date" 
                                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">Send To</label>
                            <select name="target_type" id="target_type" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500" onchange="updateTargetOptions()">
                                <option value="all">All Members</option>
                                <option value="specific_plan">Specific Plan</option>
                                <option value="specific_member">Specific Member</option>
                            </select>
                        </div>

                        <div id="target_value_container" style="display: none;">
                            <label class="block text-sm font-semibold mb-2">Target</label>
                            <select name="target_value" id="target_value" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500">
                            </select>
                        </div>

                        <button type="submit" name="send_schedule" value="1" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition">
                            <i class="fa-solid fa-paper-plane mr-2"></i>Post Schedule
                        </button>
                    </form>
                </div>
            </div>

            <!-- Schedule Feed -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                    <h2 class="text-xl font-bold mb-4 text-emerald-400"><i class="fa-solid fa-list mr-2"></i>Recent Schedules</h2>
                    
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php if (empty($schedules)): ?>
                            <p class="text-gray-400 text-center py-8">No schedules posted yet</p>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): ?>
                                <div class="bg-gray-700 rounded-lg p-4 border-l-4 border-emerald-500">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="font-bold text-lg text-white"><?php echo htmlspecialchars($schedule['title']); ?></h3>
                                            <p class="text-sm text-gray-400">Posted by <span class="text-emerald-400"><?php echo htmlspecialchars($schedule['sender_name']); ?></span></p>
                                        </div>
                                        <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_id'] === $schedule['sender_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delete_id" value="<?php echo $schedule['id']; ?>">
                                                <button type="submit" class="text-red-400 hover:text-red-300"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-gray-300 mb-3"><?php echo nl2br(htmlspecialchars($schedule['message'])); ?></p>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>
                                            <?php if ($schedule['target_type'] === 'all'): ?>
                                                <i class="fa-solid fa-users mr-1"></i>All Members
                                            <?php elseif ($schedule['target_type'] === 'specific_plan'): ?>
                                                <i class="fa-solid fa-list mr-1"></i><?php echo htmlspecialchars($schedule['target_value']); ?>
                                            <?php else: ?>
                                                <i class="fa-solid fa-user mr-1"></i>Specific Member
                                            <?php endif; ?>
                                        </span>
                                        <span><?php echo date('M d, Y H:i', strtotime($schedule['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateTargetOptions() {
            const targetType = document.getElementById('target_type').value;
            const container = document.getElementById('target_value_container');
            const targetValue = document.getElementById('target_value');
            targetValue.innerHTML = '';

            if (targetType === 'all') {
                container.style.display = 'none';
            } else if (targetType === 'specific_plan') {
                container.style.display = 'block';
                const plans = <?php echo json_encode($plans); ?>;
                plans.forEach(plan => {
                    const option = document.createElement('option');
                    option.value = plan;
                    option.textContent = plan;
                    targetValue.appendChild(option);
                });
            } else if (targetType === 'specific_member') {
                container.style.display = 'block';
                const members = <?php echo json_encode($members); ?>;
                members.forEach(member => {
                    const option = document.createElement('option');
                    option.value = member.id;
                    option.textContent = member.name + ' (' + member.phone + ')';
                    targetValue.appendChild(option);
                });
            }
        }
    </script>
</body>
</html>
