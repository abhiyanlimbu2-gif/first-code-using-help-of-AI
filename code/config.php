<?php

define('DB_HOST','localhost'); // consider using '127.0.0.1' if you see socket/TCP issues
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','meroride_db'); // change to your DB name

if (session_status() === PHP_SESSION_NONE) session_start();

function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $init = mysqli_init();
        // short connect timeout (seconds)
        mysqli_options($init, MYSQLI_OPT_CONNECT_TIMEOUT, 3);

        // Suppress warnings and handle failure explicitly
        if (!@$init->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
            error_log('DB connection error: ' . mysqli_connect_error());
            http_response_code(503);
            // Friendly message for users, and fail fast instead of hanging
            die('Service temporarily unavailable (DB connection). Please try again later.');
        }

        $init->set_charset('utf8mb4');
        $conn = $init;
    }
    return $conn;
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}


function requireAdmin() {
    if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        // Signed in but not admin -> show explicit Access Denied when trying to access Admin folder
        if (!empty($_SESSION['user_id'])) {
            if (stripos($_SERVER['PHP_SELF'], '/Admin/') !== false || stripos($_SERVER['PHP_SELF'], '/admin/') !== false) {
                http_response_code(403);
                // Minimal inline-styled Access Denied page (keeps path-safe links)
                echo '<!doctype html><html><head><meta charset="utf-8"><title>Access Denied</title></head><body style="font-family:Arial,Helvetica,sans-serif;padding:28px;line-height:1.5;">';
                echo '<h1 style="color:#b12704;margin-bottom:6px;">Access Denied</h1>';
                echo '<p>You are signed in as a non-admin user and cannot access the admin area.</p>';
                echo '<p style="margin-top:18px;"><a href="../user/userdashboard.php" style="margin-right:14px;">Back to your dashboard</a>';
                echo '<a href="../user/userlogout.php" style="margin-right:14px;">Sign out</a>';
                echo '<a href="login.php">Admin Sign In</a></p>';
                echo '</body></html>';
                exit;
            } else {
                $_SESSION['access_error'] = 'Access denied: admin area.';
                header('Location: user/userdashboard.php');
                exit;
            }
        }

        // Not logged in -> send to admin login (if in Admin area) or admin login path
        if (stripos($_SERVER['PHP_SELF'], '/Admin/') !== false || stripos($_SERVER['PHP_SELF'], '/admin/') !== false) {
            header('Location: login.php');
        } else {
            header('Location: admin/login.php');
        }
        exit;
    }
} 

/* Utilities */
function sanitize($data) { return htmlspecialchars(stripslashes(trim($data))); }
function validateEmail($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); }
function validatePhone($phone) { return preg_match('/^[0-9]{10}$/', $phone); }
function hashPassword($password) { return password_hash($password, PASSWORD_DEFAULT); }
function verifyPassword($password, $hash) { return password_verify($password, $hash); }
?>
