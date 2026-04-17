<?php
include('auth.php');
checkLogin();
requireAdmin(); // Only admins can add members

include('db.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $plan = trim($_POST['plan'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $status = "Active";

    // Validation
    if (empty($name) || strlen($name) < 2) {
        header("Location: index.php?error=Name must be at least 2 characters");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?error=Invalid email format");
        exit;
    }
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        header("Location: index.php?error=Phone must be exactly 10 digits");
        exit;
    }
    if (strlen($password) < 6) {
        header("Location: index.php?error=Password must be at least 6 characters");
        exit;
    }
    if ($password !== $confirm_password) {
        header("Location: index.php?error=Passwords do not match");
        exit;
    }
    if (!in_array($plan, ['1 Month', '2 Months', '3 Months', 'Annual'])) {
        header("Location: index.php?error=Invalid plan selected");
        exit;
    }

    // Everything below must succeed together:
    // - create login user
    // - create member record (with email)
    // - record initial payment
    mysqli_begin_transaction($conn);
    try {
        $register_result = registerUser($name, $email, $password, 'member');
        if (!$register_result['success']) {
            mysqli_rollback($conn);
            header("Location: index.php?error=" . urlencode($register_result['message']));
            exit;
        }

        // Calculate renewal date and get price
        $plan_stmt = $conn->prepare("SELECT duration_days, price FROM membership_plans WHERE name = ?");
        $plan_stmt->bind_param("s", $plan);
        $plan_stmt->execute();
        $plan_result = $plan_stmt->get_result();
        $plan_data = $plan_result->fetch_assoc();
        $plan_stmt->close();

        if (!$plan_data) {
            throw new Exception("Invalid membership plan selected");
        }

        $renewal_date = date('Y-m-d', strtotime("+{$plan_data['duration_days']} days"));
        $price = $plan_data['price'];

        // Insert member
        $stmt = $conn->prepare("INSERT INTO members (name, email, phone, plan_type, status, renewal_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $plan, $status, $renewal_date);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Failed to add member: " . $stmt->error);
        }
        $member_id = $stmt->insert_id;
        $stmt->close();

        // Create payment record
        $payment_method = "Cash";
        $payment_status = "Paid";
        $transaction_id = "MEM-" . date('Ymd') . "-" . $member_id;

        $payment_stmt = $conn->prepare("INSERT INTO payments (member_id, amount, payment_method, status, transaction_id, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $notes = "New member registration - " . $plan;
        $payment_stmt->bind_param("idssss", $member_id, $price, $payment_method, $payment_status, $transaction_id, $notes);
        if (!$payment_stmt->execute()) {
            $payment_stmt->close();
            throw new Exception("Failed to create payment: " . $payment_stmt->error);
        }
        $payment_stmt->close();

        mysqli_commit($conn);
        header("Location: index.php?success=Member added successfully with login, membership, and payment recorded");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: index.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}
?>