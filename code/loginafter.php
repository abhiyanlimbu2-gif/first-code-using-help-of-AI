<?php
require_once 'config.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        header('Location: admin/dashboard.php'); exit;
    } else {
        header('Location: index.php'); exit;
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
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                if ($user['user_type'] === 'admin') {
                    header('Location: admin/dashboard.php'); exit;
                } else {
                    header('Location: user/homepage.php'); exit;
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
<style>
.login-page { display:flex; align-items:center; justify-content:center; min-height:80vh; }
.login-card { width:380px; background:#fff; padding:28px; border-radius:12px; box-shadow:0 8px 30px rgba(10,10,10,0.06); }
.login-card h2 { margin-bottom:8px; font-size:1.4rem; color:#111; }
.form-group { margin-bottom:14px; }
.form-group label { display:block; font-weight:600; margin-bottom:6px; color:#222; }
.form-group input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid #e4e4e4; }
.btn-submit { width:100%; padding:12px; background:#25D366; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
.alert { padding:12px; border-radius:8px; margin-bottom:14px; }
.alert.error { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
.small-link { display:block; margin-top:10px; text-align:right; color:#555; text-decoration:none; font-size:0.9rem; }
</style>
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <h2>Sign in to your account</h2>
    <p style="color:#666;margin-bottom:14px;">Enter your admin credentials to access the dashboard.</p>

    <?php if ($error): ?>
      <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
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
<?php
$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<?php if ($login_error): ?>
  <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:6px;margin-bottom:12px;"><?php echo htmlspecialchars($login_error); ?></div>
<?php endif; ?>

    <a href="signup.php" class="small-link">Create account</a>
  </div>
</div>
</body>
</html>
