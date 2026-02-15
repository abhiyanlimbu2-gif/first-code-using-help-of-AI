<?php
require_once __DIR__ . '/../code/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';

// Basic safety: disallow scheme, traversal, double slashes
if ($redirect && (strpos($redirect, '://') !== false || strpos($redirect, '..') !== false || preg_match('#^//#', $redirect))) {
    $redirect = '';
}

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Email and password are required.';
    $loc = 'login.php' . ($redirect ? '?redirect=' . urlencode($redirect) : '');
    header('Location: ' . $loc);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT user_id, full_name, password, user_type FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user || !verifyPassword($password, $user['password'])) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    $loc = 'login.php' . ($redirect ? '?redirect=' . urlencode($redirect) : '');
    header('Location: ' . $loc);
    exit;
}

// Block admin login through the public signup page; send them to the Admin login instead
if ($user['user_type'] === 'admin') {
    $_SESSION['login_error'] = 'This account is an admin. Please sign in via the Admin login.';
    header('Location: ../Admin/login.php');
    exit;
}

// Login success for regular users
session_regenerate_id(true);
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_type'] = $user['user_type'];

// Non-admin: go to requested redirect if present, else homepage
if (!empty($redirect)) {
    header('Location: ' . $redirect);
    exit;
}
header('Location: userdashboard.php');
exit;