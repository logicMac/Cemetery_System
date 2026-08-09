<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, email FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['last_activity'] = time();
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
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
    <title>Admin Login - Matinao Memorial Cemetery</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/theme.css?v=2">
    <link rel="stylesheet" href="../assets/css/admin.css?v=2">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css?v=2">
    
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
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            background: rgba(10, 10, 20, 0.85);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
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
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-container {
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
        
        .logo-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .logo-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.95rem;
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
            z-index: 2;
            pointer-events: none;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        .input-field-enhanced {
            width: 100%;
            padding: 14px 16px 14px 52px !important;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white !important;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .input-field-enhanced:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.12);
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .input-field-enhanced::placeholder {
            color: rgba(255, 255, 255, 0.35) !important;
            opacity: 1;
        }
        
        .input-field-enhanced::-webkit-input-placeholder {
            color: rgba(255, 255, 255, 0.35) !important;
            opacity: 1;
        }
        
        .input-field-enhanced::-moz-placeholder {
            color: rgba(255, 255, 255, 0.35) !important;
            opacity: 1;
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
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.85rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #667eea;
        }
        
        .alert-enhanced {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .credentials-hint {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-top: 25px;
            text-align: center;
        }
        
        .credentials-hint p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            margin: 4px 0;
        }
        
        .credentials-hint strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <div class="logo-icon">
                    <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                </div>
                <h1 class="logo-title">Admin Portal</h1>
                <p class="logo-subtitle">Matinao Memorial Cemetery</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-enhanced alert-error">
                    <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="input-field-enhanced" 
                        placeholder="Enter your username"
                        required
                        autocomplete="username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                </div>
                
                <div class="input-group">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="input-field-enhanced" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn-login">
                    <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Sign In to Dashboard
                </button>
            </form>
            
            
            <div class="back-link">
                <a href="../index.php">
                    <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 4px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/theme.js"></script>
</body>
</html>
