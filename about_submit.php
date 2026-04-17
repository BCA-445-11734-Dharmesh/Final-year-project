<?php
include('auth.php');
checkLogin();
include('db.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: about.php?error=' . urlencode('Invalid request'));
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    header('Location: about.php?error=' . urlencode('Invalid CSRF token'));
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$subject || !$message) {
    header('Location: about.php?error=' . urlencode('Please fill all required fields'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: about.php?error=' . urlencode('Invalid email'));
    exit;
}

$member_id = null;
// Try to get member id from session email
if (!empty($_SESSION['user_email'])) {
    $stmt = $conn->prepare('SELECT id FROM members WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $_SESSION['user_email']);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r) $member_id = (int)$r['id'];
}

// Create complaints table if not exists (safe)
$create_sql = "CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create_sql);

$insert = $conn->prepare('INSERT INTO complaints (member_id, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)');
$insert->bind_param('isssss', $member_id, $name, $email, $phone, $subject, $message);
if (!$insert->execute()) {
    header('Location: about.php?error=' . urlencode('Failed to save your message'));
    exit;
}
$insert->close();

// Send email to site owner
$to = 'dharmeshbardhan@gmail.com';
$owner_phone = '7677601051';
$subject_mail = 'New contact / complaint: ' . $subject;
$body = '<p>You have a new message from the website contact form.</p>';
$body .= '<p><strong>Name:</strong> ' . htmlspecialchars($name) . '<br>';
$body .= '<strong>Email:</strong> ' . htmlspecialchars($email) . '<br>';
$body .= '<strong>Phone:</strong> ' . htmlspecialchars($phone) . '</p>';
$body .= '<p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>';

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: no-reply@yourdomain.com\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
@mail($to, $subject_mail, $body, $headers);

header('Location: about.php?success=' . urlencode('Message sent — we will contact you shortly'));
exit;
