<?php
session_start();
// Clear session data and cookie
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();
// regenerate id to avoid fixation
session_regenerate_id(true);

// Optional redirect after logout (only allow relative/local paths)
$redirect = 'login.php';
if (!empty($_GET['redirect'])) {
    $r = $_GET['redirect'];
    // Safety: only allow simple relative paths that don't contain protocol, double-slash or parent traversal
    if (strpos($r, '://') === false && strpos($r, "//") === false && strpos($r, '..') === false && strpos($r, "\n") === false && strpos($r, "\r") === false) {
        $redirect = $r;
    }
}
header("Location: " . $redirect);
exit();
?>