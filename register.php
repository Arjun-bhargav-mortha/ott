<?php
require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::init();

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

// Handle registration
if ($_POST && checkRateLimit('register')) {
    if (!Auth::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $result = Auth::register($email, $password, $firstName, $lastName);
            if ($result['success']) {
                $success = $result['message'] . ' You can now sign in.';
                logActivity('registration_success', $email);
            } else {
                $error = $result['message'];
                logActivity('registration_failed', $email);
            }
        }
    }
} elseif ($_POST && !checkRateLimit('register')) {
    $error = 'Too many registration attempts. Please try again later.';
}

$pageTitle = 'Sign Up - StreamFlix Pro';
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="csrf-token" content="<?= Auth::generateCSRFToken() ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/main.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #141414 0%, #000000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .auth-container {
            max-width: 450px;
            width: 100%;
            padding: 0 1rem;
        }
        
        .auth-card {
            background: rgba(47, 47, 47, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }
        
        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo h1 {
            color: var(--primary-color);
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .auth-logo p {
            color: var(--text-muted);
            margin: 0;
        }
        
        .form-floating .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-floating .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        
        .btn-auth {
            padding: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-fair { background: #fd7e14; }
        .strength-good { background: #20c997; }
        .strength-strong { background: #198754; }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: var(--text-muted);
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .divider span {
            background: var(--card-bg);
            padding: 0 1rem;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <h1><i class="bi bi-play-circle-fill"></i> StreamFlix Pro</h1>
                <p>Create your account</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   placeholder="First Name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                            <label for="first_name">First Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   placeholder="Last Name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                            <label for="last_name">Last Name</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <label for="email">Email address</label>
                    <div class="form-text">We'll never share your email with anyone else.</div>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password">Password</label>
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="form-text" id="passwordHelp">Minimum 6 characters</div>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm Password" required>
                    <label for="confirm_password">Confirm Password</label>
                    <div class="form-text" id="confirmHelp"></div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="/terms.php" class="text-primary">Terms of Service</a> 
                        and <a href="/privacy.php" class="text-primary">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-auth" id="submitBtn">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <div class="text-center">
                <p class="text-muted mb-3">Already have an account?</p>
                <a href="/login.php" class="btn btn-outline-light w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordHelp = document.getElementById('passwordHelp');
            const confirmHelp = document.getElementById('confirmHelp');
            const submitBtn = document.getElementById('submitBtn');
            
            // Auto-focus first input
            document.getElementById('first_name').focus();
            
            // Password strength checker
            password.addEventListener('input', function() {
                const value = this.value;
                const strength = getPasswordStrength(value);
                
                passwordStrength.className = 'password-strength strength-' + strength.level;
                passwordStrength.style.width = strength.percentage + '%';
                passwordHelp.textContent = strength.message;
                passwordHelp.className = 'form-text text-' + strength.color;
                
                validatePasswords();
            });
            
            // Confirm password checker
            confirmPassword.addEventListener('input', validatePasswords);
            
            function getPasswordStrength(password) {
                if (password.length < 6) {
                    return { level: 'weak', percentage: 25, message: 'Too short', color: 'danger' };
                }
                
                let score = 0;
                if (password.length >= 8) score++;
                if (/[a-z]/.test(password)) score++;
                if (/[A-Z]/.test(password)) score++;
                if (/[0-9]/.test(password)) score++;
                if (/[^A-Za-z0-9]/.test(password)) score++;
                
                if (score < 2) {
                    return { level: 'weak', percentage: 25, message: 'Weak password', color: 'danger' };
                } else if (score < 3) {
                    return { level: 'fair', percentage: 50, message: 'Fair password', color: 'warning' };
                } else if (score < 4) {
                    return { level: 'good', percentage: 75, message: 'Good password', color: 'info' };
                } else {
                    return { level: 'strong', percentage: 100, message: 'Strong password', color: 'success' };
                }
            }
            
            function validatePasswords() {
                const pwd = password.value;
                const confirmPwd = confirmPassword.value;
                
                if (confirmPwd.length === 0) {
                    confirmHelp.textContent = '';
                    confirmHelp.className = 'form-text';
                } else if (pwd === confirmPwd) {
                    confirmHelp.textContent = 'Passwords match';
                    confirmHelp.className = 'form-text text-success';
                } else {
                    confirmHelp.textContent = 'Passwords do not match';
                    confirmHelp.className = 'form-text text-danger';
                }
            }
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const firstName = document.getElementById('first_name').value.trim();
                const lastName = document.getElementById('last_name').value.trim();
                const email = document.getElementById('email').value.trim();
                const pwd = password.value;
                const confirmPwd = confirmPassword.value;
                const terms = document.getElementById('terms').checked;
                
                if (!firstName || !lastName || !email || !pwd || !confirmPwd) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return;
                }
                
                if (!email.includes('@')) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                    return;
                }
                
                if (pwd.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                    return;
                }
                
                if (pwd !== confirmPwd) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return;
                }
                
                if (!terms) {
                    e.preventDefault();
                    alert('You must agree to the Terms of Service and Privacy Policy.');
                    return;
                }
                
                // Disable submit button to prevent double submission
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Creating...';
            });
        });
    </script>
</body>
</html>