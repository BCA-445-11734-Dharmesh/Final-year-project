<?php
include('auth.php');
checkLogin();
include('db.php');

$config = include('config.php');
$DEMO_MODE = (bool)($config['DEMO_MODE'] ?? true);
$RAZORPAY_KEY_ID = $config['RAZORPAY_KEY_ID'] ?? '';
$RAZORPAY_KEY_SECRET = $config['RAZORPAY_KEY_SECRET'] ?? '';
$RAZORPAY_CURRENCY = $config['RAZORPAY_CURRENCY'] ?? 'INR';
$UPI_VPA = $config['UPI_VPA'] ?? '';

if ($_SESSION['user_role'] !== 'member') {
    header("Location: login.php?error=Members only");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: member_membership.php?error=Invalid request");
    exit;
}

$plan = trim($_POST['plan'] ?? '');

$allowedPlans = ['1 Month', '2 Months', '3 Months', 'Annual'];
if (!in_array($plan, $allowedPlans, true)) {
    header("Location: member_membership.php?error=Invalid plan selected");
    exit;
}

// Current member
$member_email = $_SESSION['user_email'] ?? $_SESSION['user_email'] ?? null;
$member_stmt = $conn->prepare("SELECT id, name, phone, email, renewal_date, plan_type FROM members WHERE email = ?");
$member_stmt->bind_param("s", $member_email);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member = $member_result->fetch_assoc();
$member_stmt->close();

if (!$member) {
    header("Location: member_membership.php?error=Member record not found");
    exit;
}

// Plan price + duration
$plan_stmt = $conn->prepare("SELECT duration_days, price FROM membership_plans WHERE name = ? LIMIT 1");
$plan_stmt->bind_param("s", $plan);
$plan_stmt->execute();
$plan_result = $plan_stmt->get_result();
$plan_data = $plan_result->fetch_assoc();
$plan_stmt->close();

if (!$plan_data) {
    header("Location: member_membership.php?error=Invalid plan selected");
    exit;
}

$duration_days = (int)$plan_data['duration_days'];
$price = (float)$plan_data['price'];
$amount_paise = (int)round($price * 100);

// Create payment record (Pending)
mysqli_begin_transaction($conn);
try {
    $payment_method = 'UPI';
    $payment_status = 'Pending';
    $transaction_id = null;
    $notes = "Membership renewal - " . $plan;

    $insert_stmt = $conn->prepare("
        INSERT INTO payments (member_id, amount, payment_method, status, plan_type, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insert_stmt->bind_param("idssss", $member['id'], $price, $payment_method, $payment_status, $plan, $notes);

    if (!$insert_stmt->execute()) {
        throw new Exception("Failed to create payment record");
    }
    $payment_id = $insert_stmt->insert_id;
    $insert_stmt->close();

    if ($DEMO_MODE) {
        // Simulate successful payment for development.
        $today = new DateTime('today');
        $base = null;
        if (!empty($member['renewal_date'])) {
            $base = new DateTime($member['renewal_date']);
        }
        if (!$base || $base < $today) {
            $base = clone $today;
        }
        $new_renewal = clone $base;
        $new_renewal->modify("+{$duration_days} days");

        $update_payment = $conn->prepare("
            UPDATE payments
            SET status = 'Paid', transaction_id = ?, payment_method = ?, payment_date = NOW()
            WHERE id = ?
        ");
        $demo_txn = "DEMO-" . $payment_id;
        $update_payment->bind_param("ssi", $demo_txn, $payment_method, $payment_id);
        $update_payment->execute();
        $update_payment->close();

        $update_member = $conn->prepare("
            UPDATE members
            SET plan_type = ?, status = 'Active', renewal_date = ?
            WHERE id = ?
        ");
        $new_renewal_str = $new_renewal->format('Y-m-d');
        $update_member->bind_param("ssi", $plan, $new_renewal_str, $member['id']);
        $update_member->execute();
        $update_member->close();

        mysqli_commit($conn);
        header("Location: member_membership.php?success=" . urlencode("Payment successful! Membership renewed until " . $new_renewal->format('d M, Y')));
        exit;
    }

    if (empty($RAZORPAY_KEY_ID) || empty($RAZORPAY_KEY_SECRET)) {
        // If Razorpay isn't configured but UPI VPA is provided, render a UPI payment page
        if (!empty($UPI_VPA)) {
            mysqli_commit($conn);

            $upi_vpa_esc = htmlspecialchars($UPI_VPA, ENT_QUOTES);
            $amount_str = number_format($price, 2, '.', '');
            $payment_ref = (int)$payment_id;
            $merchant_name = 'FitCore Pro';
            $tn = 'Membership payment #' . $payment_ref;
            $upi_link = 'upi://pay?pa=' . rawurlencode($UPI_VPA) . '&pn=' . rawurlencode($merchant_name) . '&am=' . rawurlencode($amount_str) . '&tn=' . rawurlencode($tn);
            $qr_data = rawurlencode($upi_link);

            // Render styled UPI payment page using site stylesheet
            ?>
            <!doctype html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width,initial-scale=1">
                <title>Pay via UPI</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                <link rel="stylesheet" href="assets/site.css">
            </head>
            <body class="py-12">
                <div class="container-max">
                    <div class="max-w-2xl mx-auto">
                        <div class="card text-center">
                            <h2 class="text-2xl font-bold mb-2">Pay via UPI</h2>
                            <p class="muted small mb-4">Amount: <strong>₹<?php echo htmlspecialchars($amount_str); ?> <?php echo htmlspecialchars($RAZORPAY_CURRENCY); ?></strong></p>
                            <p class="mb-4">UPI ID: <strong><?php echo $upi_vpa_esc; ?></strong></p>

                            <a href="<?php echo htmlspecialchars($upi_link, ENT_QUOTES); ?>" class="btn-primary mb-4 inline-block">Open UPI App</a>

                            <div class="my-4">
                                <img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?php echo $qr_data; ?>" alt="UPI QR">
                            </div>

                            <p class="muted small">After completing the payment in your UPI app, paste the transaction reference below to verify and complete the order.</p>

                            <form method="POST" action="online_payment_callback.php" class="mt-4">
                                <input type="hidden" name="payment_id" value="<?php echo $payment_ref; ?>">
                                <div class="mb-4">
                                    <input type="text" name="upi_txn_ref" required placeholder="UPI Transaction Reference" class="p-3 w-full">
                                </div>
                                <div class="flex gap-3 justify-center">
                                    <button type="submit" class="btn-primary">Submit Transaction Reference</button>
                                    <a href="member_membership.php" class="btn-ghost">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }

        throw new Exception("Razorpay keys are not configured. Either set keys in config.php or keep DEMO_MODE=true.");
    }

    // Create Razorpay order server-side
    $receipt = (string)$payment_id;
    $payload = json_encode([
        'amount' => $amount_paise,
        'currency' => $RAZORPAY_CURRENCY,
        'receipt' => $receipt,
        'payment_capture' => 1,
    ]);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $RAZORPAY_KEY_ID . ":" . $RAZORPAY_KEY_SECRET,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Razorpay order creation failed: " . ($err ?: $resp));
    }

    $order = json_decode($resp, true);
    $razorpay_order_id = $order['id'] ?? null;
    if (!$razorpay_order_id) {
        throw new Exception("Razorpay order id missing in response.");
    }

    $update_order_stmt = $conn->prepare("
        UPDATE payments
        SET razorpay_order_id = ?, status = 'Pending'
        WHERE id = ?
    ");
    $update_order_stmt->bind_param("si", $razorpay_order_id, $payment_id);
    $update_order_stmt->execute();
    $update_order_stmt->close();

    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: member_membership.php?error=" . urlencode($e->getMessage()));
    exit;
}

// Render Razorpay Checkout
if ($DEMO_MODE) {
    exit;
}

$callback_url = 'online_payment_callback.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="py-12">
    <div class="container-max">
        <div class="max-w-3xl mx-auto">
            <div class="card text-center">
                <h2 class="text-2xl font-bold mb-2">Redirecting to payment gateway</h2>
                <p class="muted small">A secure payment window will open shortly. If it doesn't, please ensure pop-ups are allowed.</p>
            </div>
        </div>
    </div>
    <form id="rzpCallbackForm" method="POST" action="<?php echo htmlspecialchars($callback_url); ?>">
        <input type="hidden" name="payment_id" value="<?php echo (int)$payment_id; ?>">
        <input type="hidden" name="razorpay_order_id" value="<?php echo htmlspecialchars($razorpay_order_id); ?>">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
    </form>

    <script>
        const options = {
            key: <?php echo json_encode($RAZORPAY_KEY_ID); ?>,
            amount: <?php echo (int)$amount_paise; ?>,
            currency: <?php echo json_encode($RAZORPAY_CURRENCY); ?>,
            name: 'FitCore Pro',
            description: 'Membership renewal - <?php echo htmlspecialchars($plan, ENT_QUOTES); ?><?php echo $UPI_VPA ? ' (UPI: ' . htmlspecialchars($UPI_VPA, ENT_QUOTES) . ')' : ''; ?>',
            order_id: <?php echo json_encode($razorpay_order_id); ?>,
            prefill: {
                name: <?php echo json_encode($member['name']); ?>,
                email: <?php echo json_encode($member['email']); ?>,
                contact: <?php echo json_encode($member['phone']); ?>
            },
            config: {
                display: {
                    blocks: {
                        banks: {
                            name: 'Pay via UPI',
                            instruments: [
                                { method: 'upi' }
                            ]
                        }
                    }
                },
                sequence: ['block.banks'],
                preferences: {
                    show_default_blocks: false
                }
            },
            handler: function (response) {
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('razorpay_signature').value = response.razorpay_signature;
                document.getElementById('rzpCallbackForm').submit();
            },
            modal: {
                ondismiss: function(){
                    window.location.href = 'member_membership.php?error=' + encodeURIComponent('Payment cancelled');
                }
            },
            theme: { color: '#10b981' }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    </script>
</body>
</html>

