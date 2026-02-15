<?php
// Simple auth landing / redirector
// Sends the user to the main signup / login page with a 'redirect' value
$redirect = $_GET['redirect'] ?? 'homepage.php';

// Safety: disallow absolute URLs and traversal
if (strpos($redirect, '://') !== false || strpos($redirect, '..') !== false) {
    $redirect = 'homepage.php';
}

header('Location: signup.php?redirect=' . urlencode($redirect));
exit;