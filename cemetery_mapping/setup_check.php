<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup Check - Matinao Memorial Cemetery</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2rem; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .check-item {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .check-item.success { background: #d1fae5; border-left: 4px solid #00c853; }
        .check-item.error { background: #fee2e2; border-left: 4px solid #b55a5a; }
        .check-item.warning { background: #fef3c7; border-left: 4px solid #a68b52; }
        .status {
            font-weight: 600;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .status.success { background: #00c853; color: white; }
        .status.error { background: #b55a5a; color: white; }
        .status.warning { background: #a68b52; color: white; }
        .info { background: #e0e7ff; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .info h3 { color: #4338ca; margin-bottom: 10px; }
        .info ul { margin-left: 20px; }
        .info li { margin: 5px 0; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.3s;
        }
        .btn:hover { transform: translateY(-2px); }
        .footer {
            text-align: center;
            padding: 20px;
            background: #f9fafb;
            color: #00e676;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 System Setup Check</h1>
            <p>Matinao Memorial Cemetery Management System</p>
        </div>
        
        <div class="content">
            <?php
            $checks = [];
            $allPassed = true;
            
            // Check PHP Version
            $phpVersion = phpversion();
            $phpOk = version_compare($phpVersion, '7.4.0', '>=');
            $checks[] = [
                'name' => 'PHP Version',
                'status' => $phpOk ? 'success' : 'error',
                'message' => "PHP $phpVersion " . ($phpOk ? '✓' : '✗ (Requires 7.4+)')
            ];
            if (!$phpOk) $allPassed = false;
            
            // Check Database Connection
            $dbFile = __DIR__ . '/config/database.php';
            if (file_exists($dbFile)) {
                require_once $dbFile;
                try {
                    $pdo->query("SELECT 1");
                    $checks[] = [
                        'name' => 'Database Connection',
                        'status' => 'success',
                        'message' => 'Connected to ' . DB_NAME . ' ✓'
                    ];
                } catch (PDOException $e) {
                    $checks[] = [
                        'name' => 'Database Connection',
                        'status' => 'error',
                        'message' => 'Failed: ' . $e->getMessage()
                    ];
                    $allPassed = false;
                }
            } else {
                $checks[] = [
                    'name' => 'Database Config',
                    'status' => 'error',
                    'message' => 'config/database.php not found'
                ];
                $allPassed = false;
            }
            
            // Check Required Extensions
            $extensions = ['pdo', 'pdo_mysql', 'curl', 'gd', 'mbstring'];
            foreach ($extensions as $ext) {
                $loaded = extension_loaded($ext);
                $checks[] = [
                    'name' => "PHP Extension: $ext",
                    'status' => $loaded ? 'success' : 'error',
                    'message' => $loaded ? 'Loaded ✓' : 'Not loaded ✗'
                ];
                if (!$loaded) $allPassed = false;
            }
            
            // Check Directories
            $dirs = [
                'uploads/photos' => 'Photo uploads directory',
                'uploads/plots' => 'Plot uploads directory',
                'config' => 'Configuration directory'
            ];
            
            foreach ($dirs as $dir => $desc) {
                $path = __DIR__ . '/' . $dir;
                $exists = is_dir($path);
                $writable = $exists && is_writable($path);
                
                if ($exists && $writable) {
                    $checks[] = [
                        'name' => $desc,
                        'status' => 'success',
                        'message' => 'Exists and writable ✓'
                    ];
                } elseif ($exists) {
                    $checks[] = [
                        'name' => $desc,
                        'status' => 'warning',
                        'message' => 'Exists but not writable ⚠'
                    ];
                } else {
                    $checks[] = [
                        'name' => $desc,
                        'status' => 'error',
                        'message' => 'Does not exist ✗'
                    ];
                    $allPassed = false;
                }
            }
            
            // Check Groq API Configuration
            $groqFile = __DIR__ . '/config/groq_config.php';
            if (file_exists($groqFile)) {
                require_once $groqFile;
                $groqConfigured = defined('GROQ_API_KEY') && GROQ_API_KEY !== 'your_groq_api_key_here';
                $checks[] = [
                    'name' => 'Groq AI API',
                    'status' => $groqConfigured ? 'success' : 'warning',
                    'message' => $groqConfigured ? 'Configured ✓' : 'Not configured (AI features disabled) ⚠'
                ];
            }
            
            // Check .htaccess
            $htaccess = __DIR__ . '/.htaccess';
            $checks[] = [
                'name' => '.htaccess Security',
                'status' => file_exists($htaccess) ? 'success' : 'warning',
                'message' => file_exists($htaccess) ? 'Present ✓' : 'Missing ⚠'
            ];
            
            // Display Results
            foreach ($checks as $check) {
                echo '<div class="check-item ' . $check['status'] . '">';
                echo '<div><strong>' . $check['name'] . '</strong><br><small>' . $check['message'] . '</small></div>';
                echo '<span class="status ' . $check['status'] . '">' . strtoupper($check['status']) . '</span>';
                echo '</div>';
            }
            ?>
            
            <?php if ($allPassed): ?>
                <div class="info" style="background: #d1fae5; border-left: 4px solid #00c853;">
                    <h3 style="color: #065f46;">✅ System Ready!</h3>
                    <p>All critical checks passed. Your system is ready to use.</p>
                    <a href="index.php" class="btn">Go to Landing Page →</a>
                    <a href="login.php" class="btn" style="background: #00e676;">Admin Login →</a>
                </div>
            <?php else: ?>
                <div class="info" style="background: #fee2e2; border-left: 4px solid #b55a5a;">
                    <h3 style="color: #991b1b;">⚠️ Setup Required</h3>
                    <p>Please fix the errors above before using the system.</p>
                    <ul>
                        <li>Import database.sql into MySQL</li>
                        <li>Configure config/database.php</li>
                        <li>Set proper file permissions</li>
                        <li>Install required PHP extensions</li>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="info">
                <h3>📚 Quick Links</h3>
                <ul>
                    <li><a href="README.md" target="_blank">README Documentation</a></li>
                    <li><a href="INSTALLATION_GUIDE.md" target="_blank">Installation Guide</a></li>
                    <li><a href="SYSTEM_COMPLETE.md" target="_blank">System Completion Status</a></li>
                </ul>
            </div>
            
            <div class="info">
                <h3>🔐 Default Credentials</h3>
                <ul>
                    <li><strong>Admin Username:</strong> admin</li>
                    <li><strong>Admin Password:</strong> admin123</li>
                    <li><strong>Visitor:</strong> Register new account</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>Matinao Memorial Cemetery Management System v1.0.0</p>
            <p>© 2026 - All Rights Reserved</p>
        </div>
    </div>
</body>
</html>
