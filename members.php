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
    <title>FitCore Pro | Member Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <div class="w-64 bg-slate-900 text-white p-6 flex flex-col shadow-xl">
            <h1 class="text-2xl font-bold text-emerald-400 mb-10"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore</h1>
            <nav class="space-y-4 flex-1">
                <a href="index.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">Dashboard</a>
                <a href="members.php" class="block p-3 bg-emerald-600 rounded-lg shadow-md font-bold text-white">Members</a>
                <a href="payments.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">Payments</a>
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
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Member Directory</h2>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b text-gray-400 uppercase text-sm">
                            <th class="p-4">Name</th>
                            <th class="p-4">Contact</th>
                            <th class="p-4">Plan</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = mysqli_query($conn, "SELECT * FROM members ORDER BY id DESC");
                        while($row = mysqli_fetch_assoc($res)) {
                            echo "<tr class='border-b hover:bg-gray-50 transition'>
                                <td class='p-4 font-bold text-gray-700'>{$row['name']}</td>
                                <td class='p-4 text-gray-600'>{$row['phone']}</td>
                                <td class='p-4 text-emerald-600'>{$row['plan_type']}</td>
                                <td class='p-4'><span class='bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-xs'>{$row['status']}</span></td>
                                <td class='p-4 text-right space-x-2'>
                                    <a href='edit_member.php?id={$row['id']}' class='text-blue-500 hover:text-blue-700 inline-block'>
                                        <i class='fa-solid fa-edit'></i>
                                    </a>
                                    <a href='delete_member.php?id={$row['id']}' class='text-red-500 hover:text-red-700 inline-block' onclick='return confirm(\"Are you sure?\")'>
                                        <i class='fa-solid fa-trash'></i>
                                    </a>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>