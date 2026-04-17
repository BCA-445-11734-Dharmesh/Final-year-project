<?php
include('auth.php');
checkLogin();
include('db.php');

if (!isAdmin()) {
    header('Location: index.php?error=' . urlencode('Unauthorized'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: courses_admin.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    header('Location: courses_admin.php?error=' . urlencode('Invalid CSRF token'));
    exit;
}
$course_key = trim($_POST['course_key'] ?? '');
$name = trim($_POST['name'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$description = trim($_POST['description'] ?? '');
$active = isset($_POST['active']) ? 1 : 0;

if (!$course_key || !$name) {
    header('Location: courses_admin.php?error=' . urlencode('Key and name required'));
    exit;
}

// Ensure table exists (include content_path so new installations have the column)
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

// If the column is missing on older schemas, add it only if missing
$colCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'content_path'");
if ($colCheck) {
    $colCheck->execute();
    $cres = $colCheck->get_result()->fetch_assoc();
    $colCheck->close();
    if (intval($cres['cnt']) === 0) {
        try {
            $conn->query("ALTER TABLE courses ADD COLUMN content_path VARCHAR(255) NULL");
        } catch (mysqli_sql_exception $e) {
            // ignore potential race condition or permission issues
        }
    }
}

// Use simple insert/update with fallback
if ($id) {
    // Update including content_path so uploaded files are saved on update
    $stmt = $conn->prepare('UPDATE courses SET course_key = ?, name = ?, description = ?, price = ?, active = ?, content_path = ? WHERE id = ?');
    $stmt->bind_param('sssdisi', $course_key, $name, $description, $price, $active, $final_content, $id);
    // Fetch existing content_path
    $existing_content = null;
    $get_stmt = $conn->prepare('SELECT content_path FROM courses WHERE id = ? LIMIT 1');
    if ($get_stmt) {
        $get_stmt->bind_param('i', $id);
        $get_stmt->execute();
        $resr = $get_stmt->get_result()->fetch_assoc();
        $existing_content = $resr['content_path'] ?? null;
        $get_stmt->close();
    }

    // Handle file upload if present
    $final_content = $existing_content;
    if (!empty($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
        $u = $_FILES['content_file'];
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'courses' . DIRECTORY_SEPARATOR . $course_key;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($u['name']));
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($u['tmp_name'], $dest)) {
            $final_content = 'uploads/courses/' . $course_key . '/' . $safeName;
        }
    }

    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
    if ($ok) {
        header('Location: courses_admin.php?success=' . urlencode('Course updated'));
    } else {
        header('Location: courses_admin.php?error=' . urlencode('Failed to update course: ' . $err));
    }
    exit;
} else {
    // Handle file upload if present for new course
    $final_content = null;
    if (!empty($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
        $u = $_FILES['content_file'];
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'courses' . DIRECTORY_SEPARATOR . $course_key;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($u['name']));
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($u['tmp_name'], $dest)) {
            $final_content = 'uploads/courses/' . $course_key . '/' . $safeName;
        }
    }
    $stmt = $conn->prepare('INSERT INTO courses (course_key, name, description, price, active, content_path) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssdis', $course_key, $name, $description, $price, $active, $final_content);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
    if ($ok) {
        header('Location: courses_admin.php?success=' . urlencode('Course created'));
    } else {
        header('Location: courses_admin.php?error=' . urlencode('Failed to create course: ' . $err));
    }
    exit;
}
