<?php
include('auth.php');
checkLogin();
include('db.php');

$config = include('config.php');
$RAZORPAY_KEY_SECRET = $config['RAZORPAY_KEY_SECRET'] ?? '';

if ($_SESSION['user_role'] !== 'member') {
    header("Location: login.php?error=Members only");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: course_access.php?error=Invalid callback request");
    exit;
}

$course_order_id = (int)($_POST['course_order_id'] ?? 0);
$razorpay_order_id = trim($_POST['razorpay_order_id'] ?? '');
$razorpay_payment_id = trim($_POST['razorpay_payment_id'] ?? '');
$razorpay_signature = trim($_POST['razorpay_signature'] ?? '');

if (!$course_order_id || !$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
    header("Location: course_access.php?error=Payment details missing");
    exit;
}

$stmt = $conn->prepare("
    SELECT id, buyer_user_id, status
    FROM course_orders
    WHERE id = ? AND razorpay_order_id = ?
    LIMIT 1
");
$stmt->bind_param("is", $course_order_id, $razorpay_order_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header("Location: course_access.php?error=Course order not found");
    exit;
}

if ((int)$course['buyer_user_id'] !== (int)$_SESSION['user_id']) {
    header("Location: course_access.php?error=Unauthorized payment access");
    exit;
}

if ($course['status'] !== 'Pending') {
    header("Location: course_access.php?error=Payment already processed");
    exit;
}

if (empty($RAZORPAY_KEY_SECRET)) {
    header("Location: course_access.php?error=Razorpay secret not configured");
    exit;
}

$expected = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $RAZORPAY_KEY_SECRET);
if (!hash_equals($expected, $razorpay_signature)) {
    $fail = $conn->prepare("
        UPDATE course_orders
        SET status = 'Failed', razorpay_payment_id = ?, razorpay_signature = ?, transaction_id = ?
        WHERE id = ?
    ");
    $transaction_id = $razorpay_payment_id;
    $fail->bind_param("sssi", $razorpay_payment_id, $razorpay_signature, $transaction_id, $course_order_id);
    $fail->execute();
    $fail->close();

    header("Location: course_access.php?error=Payment verification failed");
    exit;
}

mysqli_begin_transaction($conn);
try {
    $tx = $razorpay_payment_id;
    $update = $conn->prepare("
        UPDATE course_orders
        SET status = 'Paid',
            razorpay_payment_id = ?,
            razorpay_signature = ?,
            transaction_id = ?,
            payment_method = 'UPI',
            payment_date = NOW()
        WHERE id = ?
    ");
    $update->bind_param("sssi", $razorpay_payment_id, $razorpay_signature, $tx, $course_order_id);
    $update->execute();
    $update->close();

    mysqli_commit($conn);
    header("Location: course_access.php?success=" . urlencode("Course unlocked successfully!"));
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: course_access.php?error=" . urlencode($e->getMessage()));
    exit;
}

