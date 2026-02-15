<?php
require_once __DIR__ . '/../code/config.php';

// Load and clear flash messages from session
$signup_success = $_SESSION['signup_success'] ?? '';
$signup_error   = $_SESSION['signup_error']   ?? '';
$login_error    = $_SESSION['login_error']    ?? '';
unset($_SESSION['signup_success'], $_SESSION['signup_error'], $_SESSION['login_error']);

// Capture a safe redirect parameter (only relative paths, no protocol)
$redirect = '';
if (!empty($_GET['redirect'])) {
    $r = $_GET['redirect'];
    if (strpos($r, '://') === false && strpos($r, "\n") === false && strpos($r, "\r") === false) {
        $redirect = $r;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Sign Up | abhiyan_company</title>
    <link rel="stylesheet" href="../code/css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .error-message {
            color: #ff4444;
            font-size: 0.85em;
            margin-top: 5px;
            display: block;
        }
        .input-group.error input {
            border-color: #ff4444;
        }
        .input-group.success input {
            border-color: #4CAF50;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 0.85em;
        }
        .strength-weak { color: #ff4444; }
        .strength-medium { color: #ffa500; }
        .strength-strong { color: #4CAF50; }
    </style>
</head>
<body class="auth-page">

<div class="auth-container">
    <!-- Login is handled on the separate page `login.php` -->

    <!-- SIGNUP BOX -->
    <div class="auth-box" id="signup-box">
        <div class="auth-header">
            <h2>Create <span>Account</span></h2>
            <p>Join the adventure today</p>

        </div>

<?php if (!empty($signup_success)): ?>
  <div style="background:#d4edda;color:#155724;padding:12px;border-radius:8px;margin:0 0 14px;">
    <?php echo htmlspecialchars($signup_success); ?>
  </div>
<?php endif; ?>

<?php if (!empty($login_error)): ?>
  <div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin:0 0 14px;">
    <?php echo htmlspecialchars($login_error); ?>
  </div>
<?php endif; ?>

        <form action="register_process.php" method="POST" id="signup-form" novalidate>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            <div class="input-group" id="signup-name-group">
                <i class="fa fa-user"></i>
                <input type="text" name="fullname" id="signup-name" placeholder="Full Name" required>
                <span class="error-message" id="signup-name-error"></span>
            </div>
            <div class="input-group" id="signup-email-group">
                <i class="fa fa-envelope"></i>
                <input type="email" name="email" id="signup-email" placeholder="Email Address" required>
                <span class="error-message" id="signup-email-error"></span>
            </div>
            <div class="input-group" id="signup-password-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="password" id="signup-password" placeholder="Create Password" required>
                <i class="fa fa-eye toggle-password" onclick="togglePassword('signup-password', this)"></i>
                <span class="error-message" id="signup-password-error"></span>
                <span class="password-strength" id="password-strength"></span>
            </div>
            <div class="input-group" id="signup-confirm-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="confirm_password" id="confirm-password" placeholder="Confirm Password" required>
                <i class="fa fa-eye toggle-password" onclick="togglePassword('confirm-password', this)"></i>
                <span class="error-message" id="confirm-password-error"></span>
            </div>
            <button type="submit" class="btn-auth signup-btn">Register Now</button>
        </form>
        <p class="toggle-text">Already a member? <a href="login.php?as=user">Login here</a></p>
    </div>
</div>

<script>


    // Toggle password visibility
    function togglePassword(inputId, clickedIcon) {
        const input = document.getElementById(inputId);
        const icon = clickedIcon || (typeof event !== 'undefined' ? event.target : null);
        
        if (input.type === "password") {
            input.type = "text";
            if (icon) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        } else {
            input.type = "password";
            if (icon) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    }

    // Validation functions
    function validateEmail(email) {
        const re = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).toLowerCase());
    }

    function validateName(name) {
        // At least 2 characters, only letters and spaces
        const re = /^[a-zA-Z\s]{2,50}$/;
        return re.test(name);
    }

    function validatePassword(password) {
        // At least 8 characters, contains letter and number
        return password.length >= 8;
    }

    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        return strength;
    }

    function showError(inputId, message) {
        const errorElement = document.getElementById(inputId + '-error');
        const inputGroup = document.getElementById(inputId + '-group');
        
        errorElement.textContent = message;
        inputGroup.classList.add('error');
        inputGroup.classList.remove('success');
    }

    function showSuccess(inputId) {
        const errorElement = document.getElementById(inputId + '-error');
        const inputGroup = document.getElementById(inputId + '-group');
        
        errorElement.textContent = '';
        inputGroup.classList.remove('error');
        inputGroup.classList.add('success');
    }

    function clearAllErrors() {
        const errorMessages = document.querySelectorAll('.error-message');
        const inputGroups = document.querySelectorAll('.input-group');
        
        errorMessages.forEach(el => el.textContent = '');
        inputGroups.forEach(el => {
            el.classList.remove('error');
            el.classList.remove('success');
        });
    }

    // Real-time validation for signup form
    document.getElementById('signup-name').addEventListener('blur', function() {
        if (!this.value) {
            showError('signup-name', 'Name is required');
        } else if (!validateName(this.value)) {
            showError('signup-name', 'Please enter a valid name (letters only, 2-50 characters)');
        } else {
            showSuccess('signup-name');
        }
    });

    document.getElementById('signup-email').addEventListener('blur', function() {
        if (!this.value) {
            showError('signup-email', 'Email is required');
        } else if (!validateEmail(this.value)) {
            showError('signup-email', 'Please enter a valid email address');
        } else {
            showSuccess('signup-email');
        }
    });

    document.getElementById('signup-password').addEventListener('input', function() {
        const password = this.value;
        const strengthIndicator = document.getElementById('password-strength');
        
        if (password.length === 0) {
            strengthIndicator.textContent = '';
            return;
        }
        
        const strength = checkPasswordStrength(password);
        
        if (strength <= 2) {
            strengthIndicator.textContent = 'Weak password';
            strengthIndicator.className = 'password-strength strength-weak';
        } else if (strength <= 3) {
            strengthIndicator.textContent = 'Medium password';
            strengthIndicator.className = 'password-strength strength-medium';
        } else {
            strengthIndicator.textContent = 'Strong password';
            strengthIndicator.className = 'password-strength strength-strong';
        }
    });

    document.getElementById('signup-password').addEventListener('blur', function() {
        if (!this.value) {
            showError('signup-password', 'Password is required');
        } else if (!validatePassword(this.value)) {
            showError('signup-password', 'Password must be at least 8 characters long');
        } else {
            showSuccess('signup-password');
        }
    });

    document.getElementById('confirm-password').addEventListener('blur', function() {
        const password = document.getElementById('signup-password').value;
        if (!this.value) {
            showError('confirm-password', 'Please confirm your password');
        } else if (this.value !== password) {
            showError('confirm-password', 'Passwords do not match');
        } else {
            showSuccess('confirm-password');
        }
    });

    // Login is handled on `login.php` — login-related JS removed from signup page to avoid phantom element errors.

    document.getElementById('signup-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const name = document.getElementById('signup-name').value;
        const email = document.getElementById('signup-email').value;
        const password = document.getElementById('signup-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        let isValid = true;
        
        if (!name) {
            showError('signup-name', 'Name is required');
            isValid = false;
        } else if (!validateName(name)) {
            showError('signup-name', 'Please enter a valid name (letters only, 2-50 characters)');
            isValid = false;
        }
        
        if (!email) {
            showError('signup-email', 'Email is required');
            isValid = false;
        } else if (!validateEmail(email)) {
            showError('signup-email', 'Please enter a valid email address');
            isValid = false;
        }
        
        if (!password) {
            showError('signup-password', 'Password is required');
            isValid = false;
        } else if (!validatePassword(password)) {
            showError('signup-password', 'Password must be at least 8 characters long');
            isValid = false;
        }
        
        if (!confirmPassword) {
            showError('confirm-password', 'Please confirm your password');
            isValid = false;
        } else if (password !== confirmPassword) {
            showError('confirm-password', 'Passwords do not match');
            isValid = false;
        }
        
        if (isValid) {
            this.submit();
        }
    });
</script>

</body>
</html>