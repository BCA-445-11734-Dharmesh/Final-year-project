<?php
include('auth.php');
checkLogin();
include('db.php');

if (!isAdmin()) {
    header('Location: index.php?error=' . urlencode('Unauthorized'));
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM complaints ORDER BY created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Complaints — Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Member Complaints / Messages</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded mb-4"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result) === 0): ?>
            <div class="bg-white p-6 rounded shadow">No complaints found.</div>
        <?php else: ?>
            <div class="bg-white rounded shadow overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">From</th>
                            <th class="p-3">Contact</th>
                            <th class="p-3">Subject</th>
                            <th class="p-3">Message</th>
                            <th class="p-3">Received</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="border-t">
                                <td class="p-3 align-top"><?php echo (int)$row['id']; ?></td>
                                <td class="p-3 align-top">
                                    <?php echo htmlspecialchars($row['name']); ?><br>
                                    <small class="text-gray-500"><?php echo $row['member_id'] ? 'Member ID: ' . (int)$row['member_id'] : ''; ?></small>
                                </td>
                                <td class="p-3 align-top">
                                    <?php echo htmlspecialchars($row['email']); ?><br>
                                    <?php echo htmlspecialchars($row['phone']); ?>
                                </td>
                                <td class="p-3 align-top font-semibold"><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td class="p-3 align-top"><div style="max-width:420px;white-space:pre-wrap;overflow:auto;"><?php echo htmlspecialchars($row['message']); ?></div></td>
                                <td class="p-3 align-top text-sm text-gray-500"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td class="p-3 align-top text-right">
                                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="inline-block px-3 py-1 bg-blue-500 text-white rounded mr-2">Reply</a>
                                    <a href="complaints_delete.php?id=<?php echo (int)$row['id']; ?>&amp;csrf=<?php echo urlencode(get_csrf_token()); ?>" onclick="return confirm('Delete this complaint?')" class="inline-block px-3 py-1 bg-red-500 text-white rounded">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="mt-6"><a href="index.php" class="text-sm text-gray-600">← Back to Dashboard</a></p>
    </div>
</body>
</html>
