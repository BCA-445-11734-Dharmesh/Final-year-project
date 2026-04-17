<?php
include('auth.php');
checkLogin();
include('db.php');

if (!isAdmin()) {
    header('Location: index.php?error=' . urlencode('Unauthorized'));
    exit;
}

// Ensure courses table exists
$create_sql = "CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_key VARCHAR(191) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    content_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create_sql);

$res = mysqli_query($conn, "SELECT * FROM courses ORDER BY id DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Courses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="assets/site.css">
    <style>label{display:block;margin-top:8px}</style>
</head>
<body class="bg-gray-100 p-8 admin-page">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Courses — Admin</h1>

        <div class="bg-white p-6 rounded shadow mb-6">
            <form action="courses_save.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                <label>Course Key (unique, short identifier)
                    <input name="course_key" required class="w-full p-2 border rounded" placeholder="e.g. fat_loss_4w">
                </label>
                <label>Title
                    <input name="name" required class="w-full p-2 border rounded" placeholder="Course title">
                </label>
                <label>Price (INR)
                    <input name="price" required type="number" step="0.01" class="w-full p-2 border rounded" value="499">
                </label>
                <label>Description
                    <textarea name="description" class="w-full p-2 border rounded" rows="4"></textarea>
                </label>
                <label>Upload content (PDF/ZIP/video)
                    <input type="file" name="content_file" accept=".pdf,.zip,video/*">
                </label>
                <label><input type="checkbox" name="active" checked> Active</label>
                <div class="mt-4">
                    <button class="bg-emerald-600 text-white px-4 py-2 rounded">Save Course</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Key</th>
                        <th class="p-3">Title</th>
                        <th class="p-3">Price</th>
                        <th class="p-3">Active</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($res)): ?>
                        <tr class="border-t">
                            <td class="p-3"><?php echo (int)$row['id']; ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($row['course_key']); ?></td>
                            <td class="p-3"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="p-3">₹<?php echo number_format((float)$row['price'],2); ?></td>
                            <td class="p-3"><?php echo $row['active'] ? 'Yes' : 'No'; ?></td>
                            <td class="p-3 text-right">
                                <a href="courses_edit.php?id=<?php echo (int)$row['id']; ?>" class="px-3 py-1 bg-blue-500 text-white rounded">Edit</a>
                                <a href="courses_delete.php?id=<?php echo (int)$row['id']; ?>&amp;csrf=<?php echo urlencode(get_csrf_token()); ?>" onclick="return confirm('Delete course?')" class="px-3 py-1 bg-red-500 text-white rounded">Delete</a>
                                <form action="courses_grant.php" method="POST" style="display:inline; margin-left:6px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
                                    <input type="hidden" name="course_key" value="<?php echo htmlspecialchars($row['course_key']); ?>">
                                    <button onclick="return confirm('Grant this course to ALL members?')" class="px-3 py-1 bg-emerald-600 text-white rounded">Grant to All</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <p class="mt-6"><a href="index.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>
