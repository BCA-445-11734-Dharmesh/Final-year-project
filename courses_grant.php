<?php
include('auth.php');
checkLogin();
include('db.php');

if (!isAdmin()) {
    header('Location: index.php?error=' . urlencode('Unauthorized'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: courses_admin.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    header('Location: courses_admin.php?error=' . urlencode('Invalid CSRF token'));
    exit;
}

$course_key = trim($_POST['course_key'] ?? '');
if (!$course_key) {
    header('Location: courses_admin.php?error=' . urlencode('Course key required'));
    exit;
}

// Get course info
$stmt = $conn->prepare('SELECT name, price FROM courses WHERE course_key = ? LIMIT 1');
$stmt->bind_param('s', $course_key);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

$course_name = $course['name'] ?? $course_key;
$course_price = isset($course['price']) ? (float)$course['price'] : 0.0;

// Fetch all active members
$members = [];
$mstmt = $conn->prepare("SELECT id, email FROM users WHERE role = 'member' AND (is_active = 1 OR is_active IS NULL)");
if ($mstmt) {
    $mstmt->execute();
    $res = $mstmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $members[] = $r;
    }
    $mstmt->close();
}


$granted = 0;
$skipped = 0;

$check_stmt = $conn->prepare('SELECT id FROM course_orders WHERE buyer_user_id = ? AND course_key = ? AND status = "Paid" LIMIT 1');

foreach ($members as $m) {
    $uid = (int)$m['id'];
    $email = $m['email'];

    // Check existing paid order
    $check_stmt->bind_param('is', $uid, $course_key);
    $check_stmt->execute();
    $res = $check_stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $skipped++;
        continue;
    }

    // Insert paid order (granted)
    $method = 'Cash';
    $status = 'Paid';
    $notes = 'Granted by admin';
    $insert = $conn->prepare('INSERT INTO course_orders (buyer_user_id, buyer_email, course_key, course_name, amount, payment_method, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    if ($insert) {
        // types: i (uid), s (email), s (course_key), s (course_name), d (amount), s (method), s (status), s (notes)
        $insert->bind_param('isssdsss', $uid, $email, $course_key, $course_name, $course_price, $method, $status, $notes);
        if ($insert->execute()) {
            $granted++;
        }
        $insert->close();
    }
}

$check_stmt->close();

header('Location: courses_admin.php?success=' . urlencode("Granted to {$granted} members (skipped {$skipped})"));
exit;
