<?php
include('auth.php');
checkLogin();
include('db.php');

if (!isAdmin()) {
    header('Location: index.php?error=' . urlencode('Unauthorized'));
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: courses_admin.php?error=' . urlencode('Invalid id'));
    exit;
}

$csrf = $_GET['csrf'] ?? '';
if (!verify_csrf_token($csrf)) {
    header('Location: courses_admin.php?error=' . urlencode('Invalid CSRF token'));
    exit;
}

$stmt = $conn->prepare('DELETE FROM courses WHERE id = ?');
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    $stmt->close();
    header('Location: courses_admin.php?success=' . urlencode('Course deleted'));
    exit;
} else {
    $stmt->close();
    header('Location: courses_admin.php?error=' . urlencode('Failed to delete'));
    exit;
}
