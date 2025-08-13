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

// Handle login
if ($_POST && checkRateLimit('login')) {
    if (!Auth::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            if (Auth::login($email, $password)) {
                logActivity('login_success', $email);
                header('Location: ' . ($_GET['redirect'] ?? '/'));
                exit;
            } else {
                $error = 'Invalid email or password.';
                logActivity('login_failed', $email);
            }
        }
    }
} elseif ($_POST && !checkRateLimit('login')) {
    $error = 'Too many login attempts. Please try again later.';
}

$pageTitle = 'Sign In - StreamFlix Pro';
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
        }
        
        .auth-container {
            max-width: 400px;
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
                <p>Sign in to your account</p>
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
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <label for="email">Email address</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password">Password</label>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-auth">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <div class="text-center">
                <p class="text-muted mb-3">Don't have an account?</p>
                <a href="/register.php" class="btn btn-outline-light w-100">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </a>
            </div>
            
            <div class="text-center mt-4">
                <a href="/forgot-password.php" class="text-muted text-decoration-none">
                    <small>Forgot your password?</small>
                </a>
            </div>
            
            <!-- Demo Account Info -->
            <div class="mt-4 p-3 bg-info bg-opacity-10 border border-info rounded">
                <h6 class="text-info mb-2">Demo Account</h6>
                <small class="text-muted">
                    Email: admin@streamflix.com<br>
                    Password: admin123
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus first input
        document.getElementById('email').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return;
            }
            
            if (!email.includes('@')) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    </script>
</body>
</html>