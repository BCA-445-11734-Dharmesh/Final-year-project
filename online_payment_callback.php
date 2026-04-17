<?php
include('auth.php');
checkLogin();
include('db.php');
$config = include('config.php');

$RAZORPAY_KEY_ID = $config['RAZORPAY_KEY_ID'] ?? '';
$RAZORPAY_KEY_SECRET = $config['RAZORPAY_KEY_SECRET'] ?? '';

if ($_SESSION['user_role'] !== 'member') {
    header("Location: login.php?error=Members only");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: member_membership.php?error=Invalid callback request");
    exit;
}

$payment_id = (int)($_POST['payment_id'] ?? 0);
$razorpay_order_id = trim($_POST['razorpay_order_id'] ?? '');
$razorpay_payment_id = trim($_POST['razorpay_payment_id'] ?? '');
$razorpay_signature = trim($_POST['razorpay_signature'] ?? '');

if (!$payment_id || !$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
    header("Location: member_membership.php?error=Payment details missing");
    exit;
}
// Support two flows:
// 1) Razorpay callback (razorpay_order_id + razorpay_payment_id + razorpay_signature)
// 2) Manual UPI submission (upi_txn_ref)
$upi_txn_ref = trim($_POST['upi_txn_ref'] ?? '');

if ($upi_txn_ref) {
    // UPI manual submission: require payment_id and upi_txn_ref
    if (!$payment_id) {
        header("Location: member_membership.php?error=Payment details missing");
        exit;
    }
} else {
    // Razorpay flow
    if (!$payment_id || !$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
        header("Location: member_membership.php?error=Payment details missing");
        exit;
    }
}
// Fetch payment + member
$stmt = $conn->prepare("
    SELECT p.id, p.member_id, p.plan_type, p.status, m.email
    FROM payments p
    JOIN members m ON m.id = p.member_id
    WHERE p.id = ? AND p.razorpay_order_id = ?
    LIMIT 1
");
$stmt->bind_param("is", $payment_id, $razorpay_order_id);
$stmt->execute();
$payment_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment_row) {
    header("Location: member_membership.php?error=Payment not found");
    exit;
}
if ($upi_txn_ref) {
    $stmt = $conn->prepare("SELECT p.id, p.member_id, p.plan_type, p.status, m.email FROM payments p JOIN members m ON m.id = p.member_id WHERE p.id = ? LIMIT 1");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$payment_row) {
        header("Location: member_membership.php?error=Payment not found");
        exit;
    }
} else {
    $stmt = $conn->prepare("SELECT p.id, p.member_id, p.plan_type, p.status, m.email FROM payments p JOIN members m ON m.id = p.member_id WHERE p.id = ? AND p.razorpay_order_id = ? LIMIT 1");
    $stmt->bind_param("is", $payment_id, $razorpay_order_id);
    $stmt->execute();
    $payment_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$payment_row) {
        header("Location: member_membership.php?error=Payment not found");
        exit;
    }
}

// Ensure the logged-in member matches the payment's member_id
$current_member_stmt = $conn->prepare("SELECT id FROM members WHERE email = ? LIMIT 1");
$current_member_stmt->bind_param("s", $_SESSION['user_email']);
$current_member_stmt->execute();
$current_member = $current_member_stmt->get_result()->fetch_assoc();
$current_member_stmt->close();

if (!$current_member || (int)$current_member['id'] !== (int)$payment_row['member_id']) {
    header("Location: member_membership.php?error=Unauthorized payment access");
    exit;
}

if ($payment_row['status'] !== 'Pending') {
    header("Location: member_membership.php?error=Payment already processed");
    exit;
}

if (empty($RAZORPAY_KEY_SECRET)) {
    header("Location: member_membership.php?error=Razorpay secret not configured");
    exit;
}

// Verify signature per Razorpay server verification
$expected = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $RAZORPAY_KEY_SECRET);
if (!hash_equals($expected, $razorpay_signature)) {
    // Mark failed
    $fail_stmt = $conn->prepare("
        UPDATE payments
        SET status = 'Failed', razorpay_payment_id = ?, razorpay_signature = ?
        WHERE id = ?
    ");
    $fail_stmt->bind_param("ssi", $razorpay_payment_id, $razorpay_signature, $payment_id);
    $fail_stmt->execute();
    $fail_stmt->close();

    header("Location: member_membership.php?error=Payment verification failed");
    exit;
}
// Two separate completion flows
if ($upi_txn_ref) {
    // Manual UPI verification submitted by the user
    mysqli_begin_transaction($conn);
    try {
        $plan_type = $payment_row['plan_type'] ?: null;
        $plan_duration_days = null;

        if ($plan_type) {
            $plan_stmt = $conn->prepare("SELECT duration_days, price FROM membership_plans WHERE name = ? LIMIT 1");
            $plan_stmt->bind_param("s", $plan_type);
            $plan_stmt->execute();
            $plan_data = $plan_stmt->get_result()->fetch_assoc();
            $plan_stmt->close();
            $plan_duration_days = (int)($plan_data['duration_days'] ?? 0);
        }

        $member_stmt = $conn->prepare("SELECT id, renewal_date FROM members WHERE id = ?");
        $member_stmt->bind_param("i", $payment_row['member_id']);
        $member_stmt->execute();
        $member = $member_stmt->get_result()->fetch_assoc();
        $member_stmt->close();

        if (!$member) {
            throw new Exception("Member record not found");
        }

        $today = new DateTime('today');
        $base = !empty($member['renewal_date']) ? new DateTime($member['renewal_date']) : null;
        if (!$base || $base < $today) {
            $base = clone $today;
        }

        if (!$plan_duration_days) {
            $plan_duration_days = 30;
        }

        $new_renewal = clone $base;
        $new_renewal->modify("+{$plan_duration_days} days");

        $update_stmt = $conn->prepare("UPDATE payments SET status = 'Paid', payment_method = 'UPI', transaction_id = ? WHERE id = ?");
        $update_stmt->bind_param("si", $upi_txn_ref, $payment_id);
        $update_stmt->execute();
        $update_stmt->close();

        $new_renewal_str = $new_renewal->format('Y-m-d');
        $update_member_stmt = $conn->prepare("UPDATE members SET plan_type = ?, status = 'Active', renewal_date = ? WHERE id = ?");
        $update_member_stmt->bind_param("ssi", $plan_type, $new_renewal_str, $payment_row['member_id']);
        $update_member_stmt->execute();
        $update_member_stmt->close();

        mysqli_commit($conn);

        // Send email receipt to member (best-effort)
        $to = $payment_row['email'] ?? '';
        if ($to) {
            $subject = 'Payment receipt - FitCore Pro';
            $message = '<p>Hi,</p>' .
                '<p>We received your payment for ' . htmlspecialchars($plan_type) . '.</p>' .
                '<p>Transaction reference: ' . htmlspecialchars($upi_txn_ref) . '</p>' .
                '<p>Your membership is now valid until ' . $new_renewal->format('d M, Y') . '.</p>' .
                '<p>Thank you.</p>';
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: no-reply@yourdomain.com\r\n";
            @mail($to, $subject, $message, $headers);
        }

        header("Location: member_membership.php?success=" . urlencode("Payment submitted for verification. Membership updated until " . $new_renewal->format('d M, Y')));
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: member_membership.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Razorpay verification flow
    if (empty($RAZORPAY_KEY_SECRET)) {
        header("Location: member_membership.php?error=Razorpay secret not configured");
        exit;
    }

    $expected = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $RAZORPAY_KEY_SECRET);
    if (!hash_equals($expected, $razorpay_signature)) {
        $fail_stmt = $conn->prepare("UPDATE payments SET status = 'Failed', razorpay_payment_id = ?, razorpay_signature = ? WHERE id = ?");
        $fail_stmt->bind_param("ssi", $razorpay_payment_id, $razorpay_signature, $payment_id);
        $fail_stmt->execute();
        $fail_stmt->close();

        header("Location: member_membership.php?error=Payment verification failed");
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        $plan_type = $payment_row['plan_type'] ?: null;
        $plan_duration_days = null;

        if ($plan_type) {
            $plan_stmt = $conn->prepare("SELECT duration_days, price FROM membership_plans WHERE name = ? LIMIT 1");
            $plan_stmt->bind_param("s", $plan_type);
            $plan_stmt->execute();
            $plan_data = $plan_stmt->get_result()->fetch_assoc();
            $plan_stmt->close();
            $plan_duration_days = (int)($plan_data['duration_days'] ?? 0);
        }

        $member_stmt = $conn->prepare("SELECT id, renewal_date FROM members WHERE id = ?");
        $member_stmt->bind_param("i", $payment_row['member_id']);
        $member_stmt->execute();
        $member = $member_stmt->get_result()->fetch_assoc();
        $member_stmt->close();

        if (!$member) {
            throw new Exception("Member record not found");
        }

        $today = new DateTime('today');
        $base = !empty($member['renewal_date']) ? new DateTime($member['renewal_date']) : null;
        if (!$base || $base < $today) {
            $base = clone $today;
        }

        if (!$plan_duration_days) {
            $plan_duration_days = 30;
        }

        $new_renewal = clone $base;
        $new_renewal->modify("+{$plan_duration_days} days");

        $update_stmt = $conn->prepare("UPDATE payments SET status = 'Paid', payment_method = 'Razorpay', transaction_id = ?, razorpay_payment_id = ?, razorpay_signature = ? WHERE id = ?");
        $update_stmt->bind_param("sssi", $razorpay_payment_id, $razorpay_payment_id, $razorpay_signature, $payment_id);
        $update_stmt->execute();
        $update_stmt->close();

        $new_renewal_str = $new_renewal->format('Y-m-d');
        $update_member_stmt = $conn->prepare("UPDATE members SET plan_type = ?, status = 'Active', renewal_date = ? WHERE id = ?");
        $update_member_stmt->bind_param("ssi", $plan_type, $new_renewal_str, $payment_row['member_id']);
        $update_member_stmt->execute();
        $update_member_stmt->close();

        mysqli_commit($conn);

        // Send email receipt to member (best-effort)
        $to = $payment_row['email'] ?? '';
        if ($to) {
            $subject = 'Payment receipt - FitCore Pro';
            $message = '<p>Hi,</p>' .
                '<p>We received your payment for ' . htmlspecialchars($plan_type) . '.</p>' .
                '<p>Transaction ID: ' . htmlspecialchars($razorpay_payment_id) . '</p>' .
                '<p>Your membership is now valid until ' . $new_renewal->format('d M, Y') . '.</p>' .
                '<p>Thank you.</p>';
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: no-reply@yourdomain.com\r\n";
            @mail($to, $subject, $message, $headers);
        }

        header("Location: member_membership.php?success=" . urlencode("Payment successful! Membership renewed until " . $new_renewal->format('d M, Y')));
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: member_membership.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

mysqli_begin_transaction($conn);
try {
    // Fetch plan duration
    $plan_type = $payment_row['plan_type'] ?: null;
    $plan_duration_days = null;
    $plan_price = null;

    if ($plan_type) {
        $plan_stmt = $conn->prepare("SELECT duration_days, price FROM membership_plans WHERE name = ? LIMIT 1");
        $plan_stmt->bind_param("s", $plan_type);
        $plan_stmt->execute();
        $plan_result = $plan_stmt->get_result();
        $plan_data = $plan_result->fetch_assoc();
        $plan_stmt->close();

        $plan_duration_days = (int)($plan_data['duration_days'] ?? 0);
        $plan_price = (float)($plan_data['price'] ?? 0);
    }

    // Member existing renewal date
    $member_stmt = $conn->prepare("SELECT id, renewal_date, status FROM members WHERE id = ?");
    $member_stmt->bind_param("i", $payment_row['member_id']);
    $member_stmt->execute();
    $member = $member_stmt->get_result()->fetch_assoc();
    $member_stmt->close();

    if (!$member) {
        throw new Exception("Member record not found");
    }

    $today = new DateTime('today');
    $base = !empty($member['renewal_date']) ? new DateTime($member['renewal_date']) : null;
    if (!$base || $base < $today) {
        $base = clone $today;
    }

    if (!$plan_duration_days) {
        // If plan_type mapping missing, fallback to 30 days.
        $plan_duration_days = 30;
        $plan_type = $plan_type ?: $payment_row['plan_type'];
    }

    $new_renewal = clone $base;
    $new_renewal->modify("+{$plan_duration_days} days");

    // Update payment row
    $update_stmt = $conn->prepare("
        UPDATE payments
        SET status = 'Paid',
            payment_method = 'UPI',
            transaction_id = ?,
            razorpay_payment_id = ?,
            razorpay_signature = ?
        WHERE id = ?
    ");
    $razorpay_txn = $razorpay_payment_id;
    $update_stmt->bind_param("sssi", $razorpay_txn, $razorpay_payment_id, $razorpay_signature, $payment_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Update membership
    $update_member_stmt = $conn->prepare("
        UPDATE members
        SET plan_type = ?,
            status = 'Active',
            renewal_date = ?
        WHERE id = ?
    ");
    $new_renewal_str = $new_renewal->format('Y-m-d');
    $update_member_stmt->bind_param("ssi", $plan_type, $new_renewal_str, $payment_row['member_id']);
    $update_member_stmt->execute();
    $update_member_stmt->close();

    mysqli_commit($conn);
    header("Location: member_membership.php?success=" . urlencode("Payment successful! Membership renewed until " . $new_renewal->format('d M, Y')));
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: member_membership.php?error=" . urlencode($e->getMessage()));
    exit;
}

