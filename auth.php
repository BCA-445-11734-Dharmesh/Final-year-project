<?php
/**
 * Authentication Helper Functions
 * Handles login, logout, and session management
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Harden session cookie params
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

include('db.php');

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/**
 * Check if user is logged in
 * Redirect to login if not authenticated
 */
function checkLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Get current logged-in user
 */
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        global $conn;
        $user_id = intval($_SESSION['user_id']);
        $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
        $stmt->close();
    }
    return null;
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    setcookie('user_session', '', time() - 3600, '/');
    header("Location: login.php");
    exit;
}

/**
 * Register new user
 */
function registerUser($name, $email, $password, $role = 'staff') {
    global $conn;
    
    // Validation
    if (strlen($name) < 2) {
        return ['success' => false, 'message' => 'Name must be at least 2 characters'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $check_stmt->close();
        return ['success' => false, 'message' => 'Email already registered'];
    }
    $check_stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Registration successful! Please login.'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Registration failed. Try again.'];
    }
}

/**
 * Login user with email and password
 */
function loginUser($email, $password) {
    global $conn;

    // Validate input
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please enter email and password'];
    }

    // Get user from database
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    // Prevent session fixation
    session_regenerate_id(true);

    return ['success' => true, 'message' => 'Login successful!'];
}

/**
 * CSRF helper: return or create token
 */
function get_csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'manager'], true);
}

/**
 * Require admin access
 */
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: index.php?error=You do not have access to this page");
        exit;
    }
}
?>
