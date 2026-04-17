<?php
include('auth.php');
checkLogin();
include('db.php');

$config = include('config.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$member_email = $_SESSION['user_email'] ?? '';
$member_name = '';
$member_phone = '';

if ($member_email) {
    $stmt = $conn->prepare("SELECT name, phone FROM members WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $member_email);
    $stmt->execute();
    $m = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($m) {
        $member_name = $m['name'] ?? '';
        $member_phone = $m['phone'] ?? '';
    }
}

// Show form
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Contact / Complaints</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="py-12">
    <div class="container-max">
        <div class="max-w-3xl mx-auto">
            <div class="card mb-6">
                <h1 class="text-2xl font-bold mb-2">Contact / Complaints</h1>
                <p class="muted small">If you need to contact us, call <strong>7677601051</strong> or email <strong>dharmeshbardhan@gmail.com</strong>.</p>
            </div>

            <?php if (!empty($_GET['success'])): ?>
                <div class="card mb-4" style="border-left:4px solid #10b981">
                    <p style="color:#10b981;margin:0"><?php echo htmlspecialchars($_GET['success']); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div class="card mb-4" style="border-left:4px solid #ef4444">
                    <p style="color:#ef4444;margin:0"><?php echo htmlspecialchars($_GET['error']); ?></p>
                </div>
            <?php endif; ?>

            <div class="card">
                <form method="POST" action="about_submit.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">

                    <div>
                        <label class="small">Name</label>
                        <input name="name" value="<?php echo htmlspecialchars($member_name); ?>" required class="p-3 w-full">
                    </div>

                    <div>
                        <label class="small">Email</label>
                        <input name="email" type="email" value="<?php echo htmlspecialchars($member_email); ?>" required class="p-3 w-full">
                    </div>

                    <div>
                        <label class="small">Phone</label>
                        <input name="phone" value="<?php echo htmlspecialchars($member_phone); ?>" class="p-3 w-full">
                    </div>

                    <div>
                        <label class="small">Subject</label>
                        <input name="subject" required class="p-3 w-full">
                    </div>

                    <div>
                        <label class="small">Message</label>
                        <textarea name="message" rows="6" required class="p-3 w-full"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn-primary">Send Message</button>
                        <a href="member_dashboard.php" class="btn-ghost">Back to dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
