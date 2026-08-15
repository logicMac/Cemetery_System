<?php
/**
 * Admin User Creation Script
 * Creates default admin user with credentials: admin / admin123
 * Run this file once to create the admin account
 */

require_once 'config/database.php';

// Admin credentials
$username = 'adminCemetery';
$password = 'admin123';
$email = 'admin@gmail.com';

// Hash the password using bcrypt
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
try {
    // Check if admin already exists
    $checkStmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $checkStmt->execute([$username]);
    
    if ($checkStmt->fetch()) {
        $message = "Admin user already exists!";
        $status = "warning";
    } else {
        // Check if email column exists in admin_users table
        $columnsStmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'email'");
        $hasEmailColumn = $columnsStmt->fetch();
        
        if ($hasEmailColumn) {
            // Insert admin user with email
            $insertStmt = $pdo->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
            $insertStmt->execute([$username, $hashed_password, $email]);
        } else {
            // Insert admin user without email (for older schema)
            $insertStmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
            $insertStmt->execute([$username, $hashed_password]);
        }
        
        $message = "Admin user created successfully!";
        $status = "success";
    }
} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
    $status = "error";
    error_log("Admin creation error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - Matinao Memorial Cemetery</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        .icon.success { background: #d1fae5; color: #00c853; }
        .icon.warning { background: #fef3c7; color: #a68b52; }
        .icon.error { background: #fee2e2; color: #b55a5a; }
        h1 { color: #1f2937; margin-bottom: 10px; font-size: 1.8rem; }
        .message {
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            font-size: 1.1rem;
        }
        .message.success { background: #d1fae5; color: #065f46; }
        .message.warning { background: #fef3c7; color: #92400e; }
        .message.error { background: #fee2e2; color: #991b1b; }
        .credentials {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: left;
        }
        .credentials h3 { color: #374151; margin-bottom: 15px; }
        .credentials p { 
            margin: 8px 0; 
            color: #00e676;
            font-family: 'Courier New', monospace;
        }
        .credentials strong { color: #1f2937; }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin: 10px 5px;
            transition: transform 0.3s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-secondary {
            background: #00e676;
        }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #a68b52;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .warning-box p {
            color: #92400e;
            margin: 5px 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon <?php echo $status; ?>">
            <?php if ($status === 'success'): ?>
                ✓
            <?php elseif ($status === 'warning'): ?>
                ⚠
            <?php else: ?>
                ✗
            <?php endif; ?>
        </div>
        
        <h1>Admin User Setup</h1>
        
        <div class="message <?php echo $status; ?>">
            <?php echo $message; ?>
        </div>
        
        <div class="credentials">
            <h3>🔐 Admin Credentials</h3>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
            <p><strong>Email:</strong> admin@matinao-cemetery.com</p>
        </div>
        
        <div class="warning-box">
            <p><strong>⚠️ Security Notice:</strong></p>
            <p>• Change the default password after first login</p>
            <p>• Delete this file (create_admin.php) after use</p>
            <p>• Keep your credentials secure</p>
        </div>
        
        <div>
            <a href="login.php?role=admin" class="btn">Go to Admin Login</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p style="color: #00e676; font-size: 0.85rem;">
                Matinao Memorial Cemetery Management System
            </p>
        </div>
    </div>
</body>
</html>
