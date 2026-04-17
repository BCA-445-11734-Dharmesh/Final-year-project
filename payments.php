<?php 
include('auth.php');
checkLogin();

include('db.php');

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitCore Pro | Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gray-100 font-sans text-gray-800">
    <div class="flex h-screen">
        <div class="w-64 bg-slate-900 text-white p-6 flex flex-col shadow-xl">
            <h1 class="text-2xl font-bold text-emerald-400 mb-10"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore</h1>
            <nav class="space-y-4 flex-1">
                <a href="index.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">Dashboard</a>
                <a href="members.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">Members</a>
                <a href="payments.php" class="block p-3 bg-emerald-600 rounded-lg shadow-md font-bold text-white">Payments</a>
            </nav>
            
            <!-- User Section -->
            <div class="border-t border-gray-700 pt-4">
                <div class="text-xs text-gray-400 mb-3 truncate">
                    <i class="fa-solid fa-user-circle mr-2"></i><?php echo htmlspecialchars($current_user['name']); ?>
                </div>
                <a href="logout.php" class="block w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-bold text-center transition">
                    <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <div class="flex-1 p-10 overflow-y-auto">
            <h2 class="text-3xl font-bold mb-6 text-gray-800">Recent Transactions</h2>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 bg-emerald-50 border-b border-emerald-100 text-emerald-800 font-bold uppercase tracking-wider text-sm">
                    Verified Revenue Logs
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php
                        $payments = mysqli_query(
                            $conn,
                            "SELECT p.payment_date, p.amount, p.payment_method, p.status, p.transaction_id, m.name
                             FROM payments p
                             JOIN members m ON m.id = p.member_id
                             ORDER BY p.payment_date DESC
                             LIMIT 20"
                        );

                        if ($payments && mysqli_num_rows($payments) > 0) {
                            while ($payment = mysqli_fetch_assoc($payments)) {
                                $txn = $payment['transaction_id'] ?? 'N/A';
                                echo "<div class='flex justify-between items-center p-4 bg-gray-50 rounded-xl border border-gray-200'>
                                    <div>
                                        <p class='font-bold'>{$payment['name']}</p>
                                        <p class='text-xs text-gray-400'>Transaction ID: " . htmlspecialchars($txn) . "</p>
                                    </div>
                                    <div class='text-right'>
                                        <div class='text-emerald-600 font-black'>₹" . number_format((float)$payment['amount'], 2) . "</div>
                                        <div class='text-[10px] text-gray-400 font-normal italic'>
                                            " . htmlspecialchars($payment['payment_method']) . " • " . htmlspecialchars($payment['status']) . "
                                        </div>
                                    </div>
                                </div>";
                            }
                        } else {
                            echo "<p class='text-gray-500'>No payment records found yet. Add members to generate payments.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>