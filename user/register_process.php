<?php
require_once __DIR__ . '/../code/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit;
}

$fullname = sanitize($_POST['fullname'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$redirect = $_POST['redirect'] ?? '';
if ($redirect && (strpos($redirect, '://') !== false || strpos($redirect, '..') !== false || preg_match('#^//#', $redirect))) {
    $redirect = '';
}

$errors = [];
if (!$fullname) $errors[] = "Full name is required.";
if (!preg_match('/^[a-zA-Z\s]{2,50}$/', $fullname)) $errors[] = "Name must be 2-50 letters/spaces.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
if ($password !== $confirm) $errors[] = "Passwords do not match.";

if (count($errors) === 0) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "An account with this email already exists.";
    $stmt->close();
}

if (count($errors) > 0) {
    $_SESSION['signup_error'] = implode(" ", $errors);
    $suffix = $redirect ? '?redirect=' . urlencode($redirect) : '';
    header("Location: signup.php" . $suffix);
    exit;
}

$pw_hash = password_hash($password, PASSWORD_DEFAULT);
$conn = getDBConnection();
$check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'is_verified'");
$hasIsVerified = ($check && $check->num_rows > 0);

if ($hasIsVerified) {
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type, is_verified) VALUES (?, ?, ?, 'user', 1)");
    $stmt->bind_param("sss", $fullname, $email, $pw_hash);
} else {
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type) VALUES (?, ?, ?, 'user')");
    $stmt->bind_param("sss", $fullname, $email, $pw_hash);
}

if ($stmt->execute()) {
    // Auto-login new user and redirect to homepage or provided safe redirect
    $newUserId = $stmt->insert_id;
    session_regenerate_id(true);
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['user_name'] = $fullname;
    $_SESSION['user_type'] = 'user';

    if (!empty($redirect)) {
        header('Location: ' . $redirect);
        exit;
    }

    header('Location: userdashboard.php');
    exit;
} else {
    $_SESSION['signup_error'] = "Unable to create account: " . $stmt->error;
    $suffix = $redirect ? '?redirect=' . urlencode($redirect) : '';
    header("Location: signup.php" . $suffix);
    exit;
}

// Validation
if (!$fullname) $errors[] = "Full name is required.";
if (!preg_match('/^[a-zA-Z\s]{2,50}$/', $fullname)) $errors[] = "Name must be 2-50 letters/spaces.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
if ($password !== $confirm) $errors[] = "Passwords do not match.";

if (count($errors) === 0) {
    $conn = getDBConnection();
    // check existing email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "An account with this email already exists.";
    $stmt->close();
}

if (count($errors) > 0) {
    $_SESSION['signup_error'] = implode(" ", $errors);
    header("Location: signup.php");
    exit;
}

// Insert user; handle presence/absence of is_verified column safely
$pw_hash = password_hash($password, PASSWORD_DEFAULT);
$conn = getDBConnection();
$check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'is_verified'");
$hasIsVerified = ($check && $check->num_rows > 0);

if ($hasIsVerified) {
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type, is_verified) VALUES (?, ?, ?, 'user', 1)");
    $stmt->bind_param("sss", $fullname, $email, $pw_hash);
} else {
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type) VALUES (?, ?, ?, 'user')");
    $stmt->bind_param("sss", $fullname, $email, $pw_hash);
}

if ($stmt->execute()) {
    $_SESSION['signup_success'] = "Account created. You can now log in.";
    header("Location: ../login.php");
    exit;
} else {
    $_SESSION['signup_error'] = "Unable to create account: " . $stmt->error;
    header("Location: signup.php");
    exit;
}