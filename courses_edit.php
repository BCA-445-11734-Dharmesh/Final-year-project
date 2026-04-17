<?php
include('auth.php');
checkLogin();
include('db.php');

if (!isAdmin()) {
    header('Location: index.php?error=' . urlencode('Unauthorized'));
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$course = null;
if ($id) {
    $stmt = $conn->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Course</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="assets/site.css">
</head>
<body class="p-8 bg-gray-100">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4"><?php echo $course ? 'Edit Course' : 'New Course'; ?></h1>
        <form action="courses_save.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo (int)($course['id'] ?? 0); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
            <label>Course Key
                <input name="course_key" required value="<?php echo htmlspecialchars($course['course_key'] ?? ''); ?>" class="w-full p-2 border rounded">
            </label>
            <label>Title
                <input name="name" required value="<?php echo htmlspecialchars($course['name'] ?? ''); ?>" class="w-full p-2 border rounded">
            </label>
            <label>Price
                <input name="price" required type="number" step="0.01" value="<?php echo htmlspecialchars($course['price'] ?? '0'); ?>" class="w-full p-2 border rounded">
            </label>
            <label>Description
                <textarea name="description" class="w-full p-2 border rounded"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
            </label>
            <label><input type="checkbox" name="active" <?php echo (!isset($course['active']) || $course['active']) ? 'checked' : ''; ?>> Active</label>
            <label>Current Content:
                <?php if (!empty($course['content_path'])): ?>
                    <div><a href="<?php echo htmlspecialchars($course['content_path']); ?>" target="_blank">View file</a></div>
                <?php else: ?>
                    <div class="text-sm text-gray-500">No file uploaded</div>
                <?php endif; ?>
            </label>
            <label>Upload new content (optional)
                <input type="file" name="content_file" accept=".pdf,.zip,video/*">
            </label>
            <div class="mt-4">
                <button class="bg-emerald-600 text-white px-4 py-2 rounded">Save</button>
                <a href="courses_admin.php" class="ml-3 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
