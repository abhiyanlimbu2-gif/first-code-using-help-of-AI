<?php
require_once __DIR__ . '/../code/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: signup.php?redirect=testlocation.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Test Location</title></head>
<body>
<h1>Test Location</h1>
<p>You are signed in as <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>.</p>
</body>
</html>