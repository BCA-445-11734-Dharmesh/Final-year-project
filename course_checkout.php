<?php
include('auth.php');
include('db.php');

$config = include('config.php');
$DEMO_MODE = (bool)($config['DEMO_MODE'] ?? true);
$RAZORPAY_KEY_ID = $config['RAZORPAY_KEY_ID'] ?? '';
$RAZORPAY_KEY_SECRET = $config['RAZORPAY_KEY_SECRET'] ?? '';
$RAZORPAY_CURRENCY = $config['RAZORPAY_CURRENCY'] ?? 'INR';
$UPI_VPA = $config['UPI_VPA'] ?? '';

$course_key = trim($_GET['course_key'] ?? '');

// Try to load course from DB if table present
$course = null;
$stmt = $conn->prepare("SELECT course_key, name, price, description FROM courses WHERE course_key = ? AND active = 1 LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $course_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $course = [
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'description' => $row['description'] ?? ''
        ];
    }
}

// Fallback static courses if not found in DB
if (!$course) {
    $courses = [
        'fat_loss_4w' => [
            'name' => 'Fat Loss Bootcamp (4 Weeks)',
            'price' => 499,
        ],
        'strength_8w' => [
            'name' => 'Strength Builder (8 Weeks)',
            'price' => 899,
        ],
        'nutrition_masterclass' => [
            'name' => 'Nutrition Masterclass (Live + Recordings)',
            'price' => 1299,
        ],
    ];

    if (!isset($courses[$course_key])) {
        header("Location: welcome.php?error=Invalid course selected");
        exit;
    }
    $course = $courses[$course_key];
}

// Require login for course checkout
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: login.php?next=" . urlencode("course_checkout.php?course_key=" . $course_key));
    exit;
}

// Only allow members to buy online courses
if (($_SESSION['user_role'] ?? '') !== 'member') {
    header("Location: index.php?error=Members only for online plans");
    exit;
}

$amount = (float)$course['price'];
$amount_paise = (int)round($amount * 100);

// Buyer
$buyer_user_id = (int)($_SESSION['user_id'] ?? 0);
$buyer_email = $_SESSION['user_email'] ?? '';

mysqli_begin_transaction($conn);
try {
    $method = 'UPI';
    $status = 'Pending';
    $notes = "Course purchase - " . $course['name'];

    $insert = $conn->prepare("
        INSERT INTO course_orders (buyer_user_id, buyer_email, course_key, course_name, amount, payment_method, status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    // i i s s d s s s
    $bindOk = $insert->bind_param(
        "iissdsss",
        $buyer_user_id,
        $buyer_email,
        $course_key,
        $course['name'],
        $amount,
        $method,
        $status,
        $notes
    );
    if ($bindOk === false || !$insert->execute()) {
        throw new Exception("Failed to create course order");
    }

    $order_row_id = $insert->insert_id;
    $insert->close();

    if ($DEMO_MODE) {
        // In real mode, DEMO_MODE should be false.
        $update = $conn->prepare("
            UPDATE course_orders
            SET status = 'Paid', payment_method = ?, transaction_id = ?
            WHERE id = ?
        ");
        $demo_txn = "DEMO-" . $order_row_id;
        $update->bind_param("ssi", $method, $demo_txn, $order_row_id);
        $update->execute();
        $update->close();
        mysqli_commit($conn);
        header("Location: course_access.php?success=" . urlencode("Course unlocked: " . $course['name']));
        exit;
    }

    if (empty($RAZORPAY_KEY_ID) || empty($RAZORPAY_KEY_SECRET)) {
        throw new Exception("Razorpay keys are not configured. Set them in config.php and set DEMO_MODE=false.");
    }

    // Create Razorpay order server-side
    $receipt = (string)$order_row_id;
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

    $upd = $conn->prepare("UPDATE course_orders SET razorpay_order_id = ?, status = 'Pending' WHERE id = ?");
    $upd->bind_param("si", $razorpay_order_id, $order_row_id);
    $upd->execute();
    $upd->close();

    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: welcome.php?error=" . urlencode($e->getMessage()));
    exit;
}

$callback_url = 'course_payment_callback.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo htmlspecialchars($course['name']); ?></title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <form id="rzpCourseCallbackForm" method="POST" action="<?php echo htmlspecialchars($callback_url); ?>">
        <input type="hidden" name="course_order_id" value="<?php echo (int)$order_row_id; ?>">
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
            description: <?php echo json_encode('Online course purchase - ' . $course['name'] . ($UPI_VPA ? ' (UPI: ' . $UPI_VPA . ')' : '')); ?>,
            order_id: <?php echo json_encode($razorpay_order_id); ?>,
            prefill: {
                email: <?php echo json_encode($buyer_email); ?>,
            },
            config: {
                display: {
                    blocks: {
                        banks: {
                            name: 'Pay via UPI',
                            instruments: [{ method: 'upi' }]
                        }
                    }
                },
                sequence: ['block.banks'],
                preferences: { show_default_blocks: false }
            },
            handler: function (response) {
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('razorpay_signature').value = response.razorpay_signature;
                document.getElementById('rzpCourseCallbackForm').submit();
            },
            modal: {
                ondismiss: function(){
                    window.location.href = 'course_access.php?error=' + encodeURIComponent('Payment cancelled');
                }
            },
            theme: { color: '#10b981' }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    </script>
</body>
</html>

