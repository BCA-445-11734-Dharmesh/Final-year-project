<?php 
include('auth.php');
checkLogin();

include('db.php');
// Count total members for the stats boxes
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM members");
$row_count = mysqli_fetch_assoc($count_query);
$total_members = $row_count['total'];

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitCore Pro | Gym Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="bg-gray-100 font-sans overflow-hidden">
    <div class="flex h-screen">
        <div class="w-64 bg-slate-900 text-white p-6 flex flex-col">
            <h1 class="text-2xl font-bold text-emerald-400 mb-10"><i class="fa-solid fa-dumbbell mr-2"></i>FitCore</h1>
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
                <a href="admin_course_orders.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition <?php echo !isAdmin() ? 'hidden' : ''; ?>">
                    <i class="fa-solid fa-book-open mr-2"></i>Course Orders
                </a>
                <a href="courses_admin.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition <?php echo !isAdmin() ? 'hidden' : ''; ?>">
                    <i class="fa-solid fa-chalkboard-user mr-2"></i>Manage Courses
                </a>
                <a href="complaints.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition <?php echo !isAdmin() ? 'hidden' : ''; ?>">
                    <i class="fa-solid fa-envelope-open-text mr-2"></i>Complaints
                </a>
                <a href="chat.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">
                    <i class="fa-solid fa-dumbbell mr-2"></i>Workout Schedule
                </a>
                <a href="about.php" class="block p-3 hover:bg-slate-800 rounded-lg text-gray-400 transition">
                    <i class="fa-solid fa-info-circle mr-2"></i>About / Contact
                </a>
            </nav>
            
            <!-- User Section -->
            <div class="border-t border-gray-700 pt-4">
                <div class="mb-3">
                    <p class="text-xs text-gray-400 mb-1">
                        <i class="fa-solid fa-user-circle mr-2"></i><?php echo htmlspecialchars($current_user['name']); ?>
                    </p>
                    <span class="inline-block px-2 py-1 rounded text-xs font-bold <?php echo isAdmin() ? 'bg-red-600 text-white' : 'bg-blue-600 text-white'; ?>">
                        <?php echo ucfirst($current_user['role']); ?>
                    </span>
                </div>
                <a href="logout.php" class="block w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-bold text-center transition">
                    <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
            
            <div class="text-xs text-gray-500 border-t border-gray-800 pt-4 mt-4">BCA Final Project 2026</div>
        </div>

        <div class="flex-1 p-10 overflow-y-auto <?php echo isAdmin() ? 'admin-page' : ''; ?>">
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
                    <div><i class="fa-solid fa-check-circle mr-3"></i><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <button onclick="this.parentElement.style.display='none'" class="text-emerald-700 hover:text-emerald-900 font-bold">×</button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
                    <div><i class="fa-solid fa-circle-exclamation mr-3"></i><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <button onclick="this.parentElement.style.display='none'" class="text-red-700 hover:text-red-900 font-bold">×</button>
                </div>
            <?php endif; ?>

            <div class="flex justify-between items-center mb-10">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Gym Dashboard</h2>
                    <p class="text-gray-500">Welcome to the Administration Panel</p>
                </div>
                <div class="mt-6 relative max-w-xl">
    <i class="fa-solid fa-magnifying-glass absolute left-4 top-4 text-gray-400"></i>
    <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search members by name..." 
    class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-200 outline-none focus:border-emerald-500 transition shadow-md">
</div>
                <button onclick="openModal()" class="bg-emerald-600 text-white px-6 py-3 rounded-xl hover:bg-emerald-700 shadow-lg font-bold transition <?php echo !isAdmin() ? 'hidden' : ''; ?>">
                    <i class="fa-solid fa-plus mr-2"></i>Add Member
                </button>
                
                <?php if (!isAdmin()): ?>
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2 rounded-lg text-sm">
                        <i class="fa-solid fa-info-circle mr-2"></i>Staff View - Limited Access
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-2xl shadow-sm border-b-4 border-emerald-500">
                    <p class="text-gray-400 text-sm uppercase font-bold">Total Members</p>
                    <h3 class="text-4xl font-black text-gray-800"><?php echo $total_members; ?></h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-b-4 border-blue-500">
                    <p class="text-gray-400 text-sm uppercase font-bold">Gym Status</p>
                    <h3 class="text-xl font-bold text-emerald-500 italic">OPENING NOW</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-b-4 border-purple-500">
                    <p class="text-gray-400 text-sm uppercase font-bold">System Version</p>
                    <h3 class="text-xl font-bold text-gray-800">v1.0 Pro</h3>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-5 font-bold text-gray-600">Full Name</th>
                            <th class="p-5 font-bold text-gray-600">Phone</th>
                            <th class="p-5 font-bold text-gray-600">Plan</th>
                            <th class="p-5 font-bold text-gray-600">Status</th>
                            <th class="p-5 font-bold text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                        $result = mysqli_query($conn, "SELECT * FROM members ORDER BY id DESC");
                        while($row = mysqli_fetch_assoc($result)) {
                            echo "<tr class='hover:bg-gray-50 transition'>
                                <td class='p-5 font-medium text-gray-800'>{$row['name']}</td>
                                <td class='p-5 text-gray-600'>{$row['phone']}</td>
                                <td class='p-5 text-gray-600 font-semibold text-emerald-600'>{$row['plan_type']}</td>
                                <td class='p-5'><span class='bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold uppercase'>{$row['status']}</span></td>
                                <td class='p-5 text-right space-x-2'>";
                            
                            if (isAdmin()) {
                                echo "<a href='edit_member.php?id={$row['id']}' class='text-blue-400 hover:text-blue-600 transition inline-block'>
                                        <i class='fa-solid fa-edit'></i>
                                    </a>
                                    <a href='delete_member.php?id={$row['id']}' class='text-red-400 hover:text-red-600 transition inline-block' onclick='return confirm(\"Are you sure?\")'>
                                        <i class='fa-solid fa-trash'></i>
                                    </a>";
                            } else {
                                echo "<span class='text-gray-400 text-sm italic'>View Only</span>";
                            }
                            
                            echo "</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

            <div id="memberModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-3xl w-full max-w-md shadow-2xl transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Register Member</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <form action="save_member.php" method="POST" onsubmit="return validateForm(this)" class="space-y-4">
                <div id="formError" class="hidden bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm"></div>
                <input type="text" name="name" placeholder="Full Name (min 2 chars)" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                <input type="email" name="email" placeholder="Email Address" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                <input type="text" name="phone" placeholder="Phone Number (10 digits)" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition" maxlength="10">
                <input type="password" name="password" placeholder="Password (min 6 chars)" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                <select name="plan" class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl outline-none appearance-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                    <option value="">Select a plan</option>
                    <option value="1 Month">1 Month (₹1000)</option>
                    <option value="2 Months">2 Months (₹1800)</option>
                    <option value="3 Months">3 Months (₹2500)</option>
                    <option value="Annual">Annual (₹5000)</option>
                </select>
                <button type="submit" class="w-full bg-emerald-600 text-white p-4 rounded-xl font-bold shadow-lg hover:bg-emerald-700 transition transform hover:scale-105"><i class="fa-solid fa-plus mr-2"></i>ADD MEMBER</button>
            </form>
        </div>
    </div>

    <script>
        const isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>;
        
        function openModal() { 
            if (!isAdmin) {
                alert('Only admins can add members');
                return;
            }
            document.getElementById('memberModal').classList.remove('hidden'); 
        }
        function closeModal() { document.getElementById('memberModal').classList.add('hidden'); }

        function validateForm(form) {
            const name = form.name.value.trim();
            const email = form.email.value.trim();
            const phone = form.phone.value.trim();
            const plan = form.plan.value;
            const password = form.password.value.trim();
            const confirmPassword = form.confirm_password.value.trim();
            const errorDiv = document.getElementById('formError');
            
            if (name.length < 2) {
                errorDiv.textContent = 'Name must be at least 2 characters';
                errorDiv.classList.remove('hidden');
                return false;
            }
            if (!/^\S+@\S+\.\S+$/.test(email)) {
                errorDiv.textContent = 'Please enter a valid email address';
                errorDiv.classList.remove('hidden');
                return false;
            }
            if (!/^[0-9]{10}$/.test(phone)) {
                errorDiv.textContent = 'Phone must be exactly 10 digits';
                errorDiv.classList.remove('hidden');
                return false;
            }
            if (password.length < 6) {
                errorDiv.textContent = 'Password must be at least 6 characters';
                errorDiv.classList.remove('hidden');
                return false;
            }
            if (password !== confirmPassword) {
                errorDiv.textContent = 'Passwords do not match';
                errorDiv.classList.remove('hidden');
                return false;
            }
            if (!plan) {
                errorDiv.textContent = 'Please select a membership plan';
                errorDiv.classList.remove('hidden');
                return false;
            }
            errorDiv.classList.add('hidden');
            return true;
        }

        function searchTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll("tbody tr");
            
            rows.forEach(row => {
                let name = row.querySelector("td").textContent.toLowerCase();
                if (name.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>