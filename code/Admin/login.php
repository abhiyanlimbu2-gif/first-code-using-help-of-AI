<?php
require_once __DIR__ . '/../config.php';


// If already logged in
if (!empty($_SESSION['user_id'])) {
    if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        header('Location: dashboard.php'); exit;
    } else {
        // Signed-in but not an admin — send them to their dashboard with a message
        $_SESSION['access_error'] = 'Access denied: admin area. You were redirected to your dashboard.';
        header('Location: ../user/userdashboard.php'); exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT user_id, full_name, password, user_type FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $user = $res->fetch_assoc()) {
            if (verifyPassword($password, $user['password'])) {
                if ($user['user_type'] === 'admin') {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_type'] = $user['user_type'];
                    header('Location: dashboard.php'); exit;
                } else {
                    $error = 'Access denied: this account is not an admin.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin Login</title>
<link rel="stylesheet" href="../css/admin-style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <h2>Admin Sign In</h2>
    <p>Use your admin credentials to access the dashboard.</p>

    <?php if (!empty($_SESSION['access_error'])): ?>
      <div style="background:#fff3cd;color:#856404;padding:12px;border-radius:8px;margin-bottom:12px;border-left:4px solid #ffeeba;">
        <?php echo htmlspecialchars($_SESSION['access_error']); unset($_SESSION['access_error']); ?>
      </div>
    <?php elseif ($error): ?>
      <div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
      </div>

      <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <a href="../signup.php" class="small-link">Create account</a>
  </div>
</div>
</body>
</html>