<?php
include('auth.php');
checkLogin();
requireAdmin();
include('db.php');

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.buyer_email,
        c.course_name,
        c.amount,
        c.status,
        c.payment_date,
        c.transaction_id
    FROM course_orders c
    ORDER BY c.payment_date DESC
    LIMIT 200
");
$stmt->execute();
$result = $stmt->get_result();
$orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Online Course Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <div class="w-64 bg-slate-900 text-white p-6 flex flex-col">
            <h1 class="text-2xl font-bold text-emerald-400 mb-10">
                <i class="fa-solid fa-dumbbell mr-2"></i>FitCore
            </h1>
            <nav class="space-y-4 flex-1">
                <a href="index.php" class="block p-3 bg-emerald-600 rounded-lg shadow-md font-bold text-white">
                    <i class="fa-solid fa-house mr-2"></i>Dashboard
                </a>
                <a href="members.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">
                    <i class="fa-solid fa-users mr-2"></i>Members
                </a>
                <a href="payments.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">
                    <i class="fa-solid fa-credit-card mr-2"></i>Payments
                </a>
                <a href="admin_course_orders.php" class="block p-3 bg-blue-600 rounded-lg shadow-md font-bold text-white">
                    <i class="fa-solid fa-book-open mr-2"></i>Course Orders
                </a>
            </nav>
            <div class="border-t border-gray-700 pt-4">
                <a href="logout.php" class="block w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-bold text-center transition">
                    <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <div class="flex-1 p-10 overflow-y-auto admin-page">
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-800">Online Course Orders</h2>
                <p class="text-gray-500 mt-1">Manage and verify course purchases.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-5 font-bold text-gray-600">Buyer</th>
                            <th class="p-5 font-bold text-gray-600">Course</th>
                            <th class="p-5 font-bold text-gray-600">Amount</th>
                            <th class="p-5 font-bold text-gray-600">Status</th>
                            <th class="p-5 font-bold text-gray-600">Payment Date</th>
                            <th class="p-5 font-bold text-gray-600 text-right">Txn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-5 font-medium text-gray-800"><?php echo htmlspecialchars($o['buyer_email']); ?></td>
                                <td class="p-5 text-gray-700 font-semibold text-emerald-700"><?php echo htmlspecialchars($o['course_name']); ?></td>
                                <td class="p-5 text-gray-900 font-bold">₹<?php echo number_format((float)$o['amount'], 2); ?></td>
                                <td class="p-5">
                                    <?php
                                        $status = $o['status'];
                                        $class = $status === 'Paid' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-700');
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td class="p-5 text-gray-600"><?php echo htmlspecialchars(date('d M, Y', strtotime($o['payment_date']))); ?></td>
                                <td class="p-5 text-right text-gray-600 text-sm"><?php echo htmlspecialchars($o['transaction_id'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="p-10 text-center text-gray-500">
                                    No course orders yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

