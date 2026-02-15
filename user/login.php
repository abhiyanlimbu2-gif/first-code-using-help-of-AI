<?php
require_once __DIR__ . '/../code/config.php';

// Redirect if already logged in
$show_admin_signout = false; // will be true if an admin is signed in and the user explicitly requested user-login (as=user)
$show_user_signedin = false;
$requestedAs = $_GET['as'] ?? '';
if (!empty($_SESSION['user_id'])) {
    if ($requestedAs === 'user') {
        // The user intentionally wanted to see the user login page — render it and show a helpful notice instead of redirecting.
        if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            // Admin is signed in; prompt admin to sign out if they want to use the user login.
            $show_admin_signout = true;
        } else {
            // A regular user is signed in — show a notice and allow them to sign out to access the login form for a different account.
            $show_user_signedin = true;
            // Do not redirect; render login page below with the notice
        }
    } else {
        // Standard behavior (no ?as=user): redirect to the appropriate dashboard
        if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            header('Location: ../Admin/dashboard.php');
        } else {
            header('Location: userdashboard.php');
        }
        exit;
    }
}

$error = '';
$redirect = '';
// Session-provided error (from other login endpoints)
$session_login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

if (!empty($_GET['redirect'])) {
    $r = $_GET['redirect'];
    if (strpos($r, '://') === false && strpos($r, "\n") === false && strpos($r, "\r") === false) {
        $redirect = $r;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $postRedirect = $_POST['redirect'] ?? '';

    // Basic safety for redirect
    if ($postRedirect && (strpos($postRedirect, '://') !== false || strpos($postRedirect, '..') !== false || preg_match('#^//#', $postRedirect))) {
        $postRedirect = '';
    } else if ($postRedirect) {
        $redirect = $postRedirect;
    }

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT user_id, full_name, password, user_type FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$user || !verifyPassword($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            if ($user['user_type'] === 'admin') {
                // Provide an actionable message with a link to the admin login
                $error_html = 'This account is an admin. Please sign in via the <a href="../Admin/login.php">Admin login</a>.';
            } else {
                // Login success — prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];

                if (!empty($redirect)) {
                    header('Location: ' . $redirect);
                    exit;
                }

                header('Location: userdashboard.php');
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Sign In</title>
<link rel="stylesheet" href="../code/css/signup.css">
<style>
.login-card { max-width:420px; margin:40px auto; background:#fff; padding:28px; border-radius:12px; box-shadow:0 8px 30px rgba(10,10,10,0.06); }
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
  <div class="login-card">
    <h2>Sign in to your account</h2>
    <p style="color:#666;margin-bottom:14px;">Enter your credentials to access your account.</p>

    <?php if (!empty($session_login_error)): ?>
      <div class="alert error"><?php echo htmlspecialchars($session_login_error); ?></div>
    <?php elseif (!empty($error_html)): ?>
      <div class="alert error"><?php echo $error_html; ?></div>
    <?php elseif (!empty($error)): ?>
      <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($show_admin_signout) && $show_admin_signout === true): ?>
      <div style="background:#fff3cd;color:#856404;padding:12px;border-radius:8px;margin:12px 0;border-left:4px solid #ffeeba;">
        You are currently signed in as <strong>admin</strong>. To sign in as a regular user please
        <a href="userlogout.php?redirect=<?php echo urlencode('login.php?as=user'); ?>" style="font-weight:700;color:#0b5ed7;margin-left:6px;">Sign out and continue to User Login</a>
        or
        <a href="../Admin/dashboard.php" style="font-weight:700;color:#0b5ed7;margin-left:6px;">Go to Admin Dashboard</a>.
      </div>
    <?php endif; ?>

    <?php if (!empty($show_user_signedin) && $show_user_signedin === true): ?>
      <div style="background:#e9f7ef;color:#0b8a45;padding:12px;border-radius:8px;margin:12px 0;border-left:4px solid #c7f0d6;">
        You are currently signed in as <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'user'); ?></strong>. To sign in as a different user please
        <a href="userlogout.php?redirect=<?php echo urlencode('login.php?as=user'); ?>" style="font-weight:700;color:#0b5ed7;margin-left:6px;">Sign out and continue to User Login</a>
        or
        <a href="userdashboard.php" style="font-weight:700;color:#0b5ed7;margin-left:6px;">Continue to your Dashboard</a>.
      </div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
      <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div style="position:relative;">
          <input id="password" name="password" type="password" required style="padding-right:40px;width:100%;">
          <i class="fa fa-eye" id="toggle-pass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#666"></i>
        </div>
      </div>

      <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <a href="signup.php" class="small-link">Create account</a>

    <div class="admin-access" style="margin-top:16px;padding:12px;border-radius:8px;background:#f6f8fb;border:1px solid #e9eef6;text-align:center;">
      <div style="font-weight:700;color:#333;margin-bottom:6px;">Admin access</div>
      <div style="color:#666;font-size:0.95rem;margin-bottom:8px;">If you are an admin, use the admin sign-in to manage the site.</div>
      <a href="../Admin/login.php" style="display:inline-block;padding:8px 12px;border-radius:6px;background:#4a6cf7;color:#fff;text-decoration:none;font-weight:700;">Go to Admin Login</a>
    </div>
  </div>

<script>
  // Small client-side helpers
  document.getElementById('toggle-pass').addEventListener('click', function(){
    var p = document.getElementById('password');
    if (p.type === 'password') { p.type = 'text'; this.classList.remove('fa-eye'); this.classList.add('fa-eye-slash'); }
    else { p.type = 'password'; this.classList.remove('fa-eye-slash'); this.classList.add('fa-eye'); }
  });
</script>
</body>
</html>
