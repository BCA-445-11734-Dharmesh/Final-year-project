<?php
include('auth.php');
checkLogin();

include('db.php');

$current_user = getCurrentUser();

// Redirect if not a member
if ($current_user['role'] !== 'member') {
    header("Location: index.php");
    exit;
}

// Get member details
$member_data = null;
$stmt = $conn->prepare("SELECT id FROM members WHERE email = ?");
$stmt->bind_param("s", $current_user['email']);
$stmt->execute();
$result = $stmt->get_result();
$member_data = $result->fetch_assoc();
$stmt->close();

$total_paid = 0;
$last_payment = null;

if ($member_data) {
    // Summary: total paid and last payment date
    $summary_stmt = $conn->prepare("
        SELECT
            COALESCE(SUM(amount), 0) AS total_paid,
            MAX(payment_date) AS last_payment_date
        FROM payments
        WHERE member_id = ?
    ");
    $summary_stmt->bind_param("i", $member_data['id']);
    $summary_stmt->execute();
    $summary_result = $summary_stmt->get_result();
    $summary_row = $summary_result->fetch_assoc();
    $total_paid = $summary_row['total_paid'] ?? 0;
    $summary_stmt->close();

    // Last payment row for the summary card
    $last_stmt = $conn->prepare("
        SELECT amount, payment_method, status, transaction_id, payment_date
        FROM payments
        WHERE member_id = ?
        ORDER BY payment_date DESC
        LIMIT 1
    ");
    $last_stmt->bind_param("i", $member_data['id']);
    $last_stmt->execute();
    $last_result = $last_stmt->get_result();
    $last_payment = $last_result->fetch_assoc();
    $last_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitCore Pro | My Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-emerald-900 to-slate-900 min-h-screen">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-900 border-r border-emerald-500/20 text-white p-6">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-emerald-400"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore</h1>
                <p class="text-gray-400 text-sm mt-1">Gym Management</p>
            </div>
            
            <div class="mb-8 bg-gradient-to-r from-emerald-600/20 to-teal-600/20 border border-emerald-500/30 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-300">Your Role</span>
                    <span class="px-3 py-1 bg-emerald-500 text-white text-xs font-bold rounded-full">
                        <i class="fa-solid fa-user-circle mr-1"></i>MEMBER
                    </span>
                </div>
            </div>

            <nav class="space-y-2">
                <a href="member_dashboard.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-home mr-3"></i>Dashboard
                </a>
                <a href="member_payments.php" class="block px-4 py-3 rounded-lg bg-emerald-600/30 text-emerald-300 transition">
                    <i class="fa-solid fa-receipt mr-3"></i>Payments
                </a>
                <a href="member_membership.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-calendar-alt mr-3"></i>Membership
                </a>
                <a href="member_attendance.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-clock mr-3"></i>Check In/Out
                </a>
                <a href="member_profile_edit.php" class="block px-4 py-3 rounded-lg hover:bg-emerald-600/20 transition text-gray-300 hover:text-white">
                    <i class="fa-solid fa-user-edit mr-3"></i>Edit Profile
                </a>
            </nav>

            <div class="mt-auto pt-8 border-t border-gray-700">
                <a href="logout.php" class="block px-4 py-3 rounded-lg hover:bg-red-600/20 transition text-gray-300 hover:text-red-400 text-center">
                    <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-y-auto payments-page">
            <div class="max-w-4xl">
                <h2 class="text-3xl font-bold text-white mb-8">My Payment History</h2>

                <!-- Payment Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <div class="mb-2">
                            <p class="text-gray-600 text-sm mb-1">Total Paid</p>
                            <p class="text-3xl font-bold text-emerald-600">₹<?php echo number_format((float)$total_paid, 2); ?></p>
                        </div>
                        <p class="text-xs text-gray-500">All payments combined</p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <div class="mb-2">
                            <p class="text-gray-600 text-sm mb-1">Last Payment</p>
                            <p class="text-3xl font-bold text-blue-600">
                                <?php echo $last_payment ? '₹' . number_format((float)$last_payment['amount'], 2) : '-'; ?>
                            </p>
                        </div>
                        <p class="text-xs text-gray-500">
                            <?php
                                if ($last_payment && !empty($last_payment['payment_date'])) {
                                    echo 'on ' . date('d M, Y', strtotime($last_payment['payment_date']));
                                } else {
                                    echo 'No payments yet';
                                }
                            ?>
                        </p>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="p-6 bg-emerald-50 border-b border-emerald-100 text-emerald-800 font-bold uppercase tracking-wider text-sm">
                        <i class="fa-solid fa-list mr-2"></i>Payment Records
                    </div>

                    <div class="p-6">
                        <?php if ($member_data): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="border-b bg-gray-50">
                                        <tr>
                                            <th class="p-3 font-bold text-gray-600">Date</th>
                                            <th class="p-3 font-bold text-gray-600">Amount</th>
                                            <th class="p-3 font-bold text-gray-600">Method</th>
                                            <th class="p-3 font-bold text-gray-600">Status</th>
                                            <th class="p-3 font-bold text-gray-600">Transaction ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $payments_stmt = $conn->prepare("
                                            SELECT
                                                payment_date,
                                                amount,
                                                payment_method,
                                                status,
                                                transaction_id
                                            FROM payments
                                            WHERE member_id = ?
                                            ORDER BY payment_date DESC
                                        ");
                                        $payments_stmt->bind_param("i", $member_data['id']);
                                        $payments_stmt->execute();
                                        $payments_result = $payments_stmt->get_result();

                                        if ($payments_result && mysqli_num_rows($payments_result) > 0) {
                                            while ($payment = mysqli_fetch_assoc($payments_result)) {
                                                echo "<tr class='border-b hover:bg-gray-50'>
                                                    <td class='p-3 text-gray-800'>" . date('d M, Y', strtotime($payment['payment_date'])) . "</td>
                                                    <td class='p-3 font-bold text-emerald-600'>₹" . number_format($payment['amount'], 2) . "</td>
                                                    <td class='p-3 text-gray-600'>" . htmlspecialchars($payment['payment_method']) . "</td>
                                                    <td class='p-3'><span class='px-2 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-700'>" . htmlspecialchars($payment['status']) . "</span></td>
                                                    <td class='p-3 text-gray-500 text-sm'>" . htmlspecialchars($payment['transaction_id'] ?? 'N/A') . "</td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='p-6 text-center text-gray-500'>
                                                <i class='fa-solid fa-inbox text-3xl mb-3 block opacity-50'></i>
                                                No payment records found.
                                            </td></tr>";
                                        }
                                        $payments_stmt->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fa-solid fa-exclamation-circle text-4xl text-gray-400 mb-3 block"></i>
                                <p class="text-gray-600">No membership found. Please contact the gym staff.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
