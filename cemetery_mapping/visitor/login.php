<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['visitor_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, email, password, is_active FROM visitors WHERE email = ?");
            $stmt->execute([$email]);
            $visitor = $stmt->fetch();
            
            if ($visitor && password_verify($password, $visitor['password'])) {
                if ($visitor['is_active'] == 1) {
                    // Set session variables
                    $_SESSION['visitor_id'] = $visitor['id'];
                    $_SESSION['visitor_name'] = $visitor['full_name'];
                    $_SESSION['visitor_email'] = $visitor['email'];
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    $updateStmt = $pdo->prepare("UPDATE visitors SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$visitor['id']]);
                    
                    // Log activity
                    $logStmt = $pdo->prepare("INSERT INTO visitor_activity_log (visitor_id, activity_type, ip_address, user_agent) VALUES (?, 'login', ?, ?)");
                    $logStmt->execute([$visitor['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Your account has been deactivated. Please contact the administrator.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Login - Matinao Memorial Cemetery</title>
    
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
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .auth-card {
            background: rgba(10, 10, 20, 0.85);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 480px;
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
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
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
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
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
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .alert-enhanced {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
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
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="logo-icon">
                    <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                </div>
                <h2>Matinao Memorial</h2>
                <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.95rem;">Visitor Portal</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-enhanced alert-error">
                    <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <svg class="input-icon" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="input-field-enhanced" 
                        placeholder="Enter your email"
                        required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
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
                        placeholder="Enter your password"
                        required
                    >
                </div>
                
                <button type="submit" class="btn-login">
                    <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Sign In
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 25px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.9rem;">
                    Don't have an account? 
                    <a href="register.php" style="color: #667eea; font-weight: 600; text-decoration: none;">Register here</a>
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
</body>
</html>
