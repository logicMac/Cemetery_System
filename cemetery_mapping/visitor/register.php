<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['visitor_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle registration form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM visitors WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                // Hash password using bcrypt
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new visitor
                $insertStmt = $pdo->prepare("INSERT INTO visitors (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
                $insertStmt->execute([$full_name, $email, $phone, $hashed_password]);
                
                $success = 'Registration successful! You can now log in.';
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred during registration. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Registration - Matinao Memorial Cemetery</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://files01.pna.gov.ph/category-list/2019/10/23/cemeteries.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.08;
            filter: grayscale(50%);
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 30%, rgba(102, 126, 234, 0.1) 0%, transparent 50%);
            z-index: 0;
        }
        
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }
        
        .auth-card {
            background: rgba(10, 10, 20, 0.85);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.6s ease;
            position: relative;
            z-index: 1;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .auth-logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .auth-logo h2 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            z-index: 1;
            pointer-events: none;
        }
        
        .input-field-enhanced {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .input-field-enhanced:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.12);
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .input-field-enhanced::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .alert-enhanced {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }
        
        .password-strength {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background-color: #ef4444;
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background-color: #fbbf24;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background-color: #22c55e;
        }
        
        .password-strength-text {
            font-size: 0.8rem;
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        small {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="logo-icon">
                    <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" style="width: 55px; height: 55px; border-radius: 50%; object-fit: cover;">
                </div>
                <h2>Create Account</h2>
                <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.95rem;">Join Matinao Memorial Cemetery</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-enhanced alert-error">
                    <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert-enhanced alert-success">
                    <strong>✓ Success:</strong> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    <a href="login.php" style="display: block; margin-top: 10px; font-weight: 600; color: #22c55e;">Go to Login →</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="input-group">
                    <svg class="input-icon" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="input-field-enhanced" 
                        placeholder="Full Name *"
                        required
                        value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                </div>
                
                <div class="input-group">
                    <svg class="input-icon" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="input-field-enhanced" 
                        placeholder="Email Address *"
                        required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                    <small id="email-status" style="display: block; margin-top: 6px;"></small>
                </div>
                
                <div class="input-group">
                    <svg class="input-icon" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="input-field-enhanced" 
                        placeholder="Phone Number (Optional)"
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                </div>
                
                <div class="input-group">
                    <svg class="input-icon" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="input-field-enhanced" 
                        placeholder="Password * (min 8 characters)"
                        required
                        minlength="8"
                    >
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strength-bar"></div>
                    </div>
                    <small class="password-strength-text" id="strength-text"></small>
                </div>
                
                <div class="input-group">
                    <svg class="input-icon" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="input-field-enhanced" 
                        placeholder="Confirm Password *"
                        required
                        minlength="8"
                    >
                    <small id="match-status" style="display: block; margin-top: 6px;"></small>
                </div>
                
                <button type="submit" class="btn-register">
                    <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Create Account
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 25px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.9rem;">
                    Already have an account? 
                    <a href="login.php" style="color: #667eea; font-weight: 600; text-decoration: none;">Sign in here</a>
                </p>
                <p style="color: rgba(255, 255, 255, 0.5); font-size: 0.9rem; margin-top: 15px;">
                    <a href="../index.php" style="color: rgba(255, 255, 255, 0.5); text-decoration: none; transition: color 0.3s;">
                        <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 4px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Home
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/theme.js"></script>
    <script>
        // Password strength indicator
        themeUtils.updatePasswordStrength('password', 'strength-bar', 'strength-text');
        
        // Password match validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchStatus = document.getElementById('match-status');
        
        confirmPassword.addEventListener('input', () => {
            if (confirmPassword.value === '') {
                matchStatus.textContent = '';
                return;
            }
            
            if (password.value === confirmPassword.value) {
                matchStatus.textContent = '✓ Passwords match';
                matchStatus.style.color = '#22c55e';
            } else {
                matchStatus.textContent = '✗ Passwords do not match';
                matchStatus.style.color = '#ef4444';
            }
        });
        
        // Email uniqueness check (async)
        const emailInput = document.getElementById('email');
        const emailStatus = document.getElementById('email-status');
        let emailCheckTimeout;
        
        emailInput.addEventListener('input', () => {
            clearTimeout(emailCheckTimeout);
            const email = emailInput.value;
            
            if (!themeUtils.validateEmail(email)) {
                emailStatus.textContent = '';
                return;
            }
            
            emailStatus.textContent = 'Checking...';
            emailStatus.style.color = 'var(--zinc-400)';
            
            emailCheckTimeout = setTimeout(() => {
                fetch('../api/check_email.php?email=' + encodeURIComponent(email))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            emailStatus.textContent = '✗ Email already registered';
                            emailStatus.style.color = '#ef4444';
                        } else {
                            emailStatus.textContent = '✓ Email available';
                            emailStatus.style.color = '#22c55e';
                        }
                    })
                    .catch(() => {
                        emailStatus.textContent = '';
                    });
            }, 500);
        });
    </script>
</body>
</html>
