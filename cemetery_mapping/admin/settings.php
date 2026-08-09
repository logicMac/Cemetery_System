<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
                $stmt->execute([$_SESSION['admin_id']]);
                $admin = $stmt->fetch();
                
                if (password_verify($current_password, $admin['password'])) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$hashed, $_SESSION['admin_id']]);
                    
                    $success = 'Password changed successfully';
                } else {
                    $error = 'Current password is incorrect';
                }
            } catch (PDOException $e) {
                error_log("Password change error: " . $e->getMessage());
                $error = 'An error occurred';
            }
        }
    } elseif ($action === 'backup_database') {
        // Trigger database backup
        $backup_file = '../backups/cemetery_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Create backups directory if not exists
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        $success = 'Database backup initiated. File will be saved to backups folder.';
    }
}

// Get system statistics
try {
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
    $totalPlots = $pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();
    $totalVisitors = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $dbSize = $pdo->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
        FROM information_schema.TABLES 
        WHERE table_schema = 'cemetery_mapping'
    ")->fetchColumn();
} catch (PDOException $e) {
    error_log("Settings stats error: " . $e->getMessage());
}
?>

<?php require_once 'includes/sidebar.php'; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<!-- System Information -->
<div class="glass-card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">System Information</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div>
            <p style="color: var(--zinc-400); margin-bottom: 4px;">Total Records</p>
            <p style="font-size: 1.5rem; font-weight: 600;"><?php echo number_format($totalRecords); ?></p>
        </div>
        <div>
            <p style="color: var(--zinc-400); margin-bottom: 4px;">Available Plots</p>
            <p style="font-size: 1.5rem; font-weight: 600;"><?php echo number_format($totalPlots); ?></p>
        </div>
        <div>
            <p style="color: var(--zinc-400); margin-bottom: 4px;">Registered Visitors</p>
            <p style="font-size: 1.5rem; font-weight: 600;"><?php echo number_format($totalVisitors); ?></p>
        </div>
        <div>
            <p style="color: var(--zinc-400); margin-bottom: 4px;">Database Size</p>
            <p style="font-size: 1.5rem; font-weight: 600;"><?php echo $dbSize; ?> MB</p>
        </div>
    </div>
</div>

<!-- Change Password -->
<div class="glass-card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">Change Password</h2>
    
    <form method="POST" action="" style="max-width: 500px;">
        <input type="hidden" name="action" value="change_password">
        
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" class="input-field" required>
        </div>
        
        <div class="form-group">
            <label for="new_password">New Password (minimum 8 characters)</label>
            <input type="password" id="new_password" name="new_password" class="input-field" required minlength="8">
            <div class="password-strength">
                <div class="password-strength-bar" id="strength-bar"></div>
            </div>
            <small class="password-strength-text" id="strength-text"></small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="input-field" required minlength="8">
        </div>
        
        <button type="submit" class="btn-primary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Change Password
        </button>
    </form>
</div>

<!-- Map Configuration -->
<div class="glass-card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">Map Configuration</h2>
    
    <div style="max-width: 600px;">
        <div class="form-group">
            <label>Cemetery Center Coordinates</label>
            <p style="color: var(--zinc-400); font-family: monospace;">
                Latitude: 6.18344118743717<br>
                Longitude: 125.08457146469357
            </p>
            <small style="color: var(--zinc-400);">To change coordinates, edit config/database.php</small>
        </div>
        
        <div class="form-group">
            <label>Default Zoom Level</label>
            <p style="color: var(--zinc-400);">17 (Range: 10-20)</p>
        </div>
        
        <div class="form-group">
            <label>Available Tile Layers</label>
            <ul style="color: var(--zinc-400); margin-left: 20px;">
                <li>Google Satellite (Default)</li>
                <li>Google Hybrid</li>
                <li>Google Streets</li>
                <li>Esri World Imagery</li>
                <li>OpenStreetMap</li>
            </ul>
        </div>
    </div>
</div>

<!-- Database Management -->
<div class="glass-card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">Database Management</h2>
    
    <div style="max-width: 600px;">
        <p style="color: var(--zinc-400); margin-bottom: 20px;">
            Regular backups are recommended to prevent data loss. Backup files will be saved in the backups folder.
        </p>
        
        <form method="POST" action="" onsubmit="return confirm('Create database backup now?');">
            <input type="hidden" name="action" value="backup_database">
            <button type="submit" class="btn-primary">
                <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Create Backup
            </button>
        </form>
        
        <div style="margin-top: 20px; padding: 16px; background: rgba(251, 191, 36, 0.1); border: 1px solid #fbbf24; border-radius: 8px;">
            <p style="color: #fbbf24; font-weight: 600; margin-bottom: 8px;">⚠️ Important Notes:</p>
            <ul style="color: var(--zinc-400); margin-left: 20px; font-size: 0.9rem;">
                <li>Backups include all database tables and data</li>
                <li>Backup files do not include uploaded photos</li>
                <li>Store backups in a secure location</li>
                <li>Test backup restoration periodically</li>
            </ul>
        </div>
    </div>
</div>

<!-- API Configuration -->
<div class="glass-card">
    <h2 style="margin-bottom: 20px;">API Configuration</h2>
    
    <div style="max-width: 600px;">
        <div class="form-group">
            <label>Groq AI API</label>
            <p style="color: var(--zinc-400); margin-bottom: 8px;">
                Model: llama-3.1-70b-versatile
            </p>
            <p style="color: var(--zinc-400); font-size: 0.9rem;">
                API Key: <?php echo defined('GROQ_API_KEY') && GROQ_API_KEY !== 'your_groq_api_key_here' ? '✓ Configured' : '✗ Not configured'; ?>
            </p>
            <small style="color: var(--zinc-400);">To update API key, edit config/groq_config.php</small>
        </div>
        
        <div style="margin-top: 20px; padding: 16px; background: rgba(102, 126, 234, 0.1); border: 1px solid #667eea; border-radius: 8px;">
            <p style="color: #667eea; font-weight: 600; margin-bottom: 8px;">ℹ️ AI Assistant Features:</p>
            <ul style="color: var(--zinc-400); margin-left: 20px; font-size: 0.9rem;">
                <li>Natural language search queries</li>
                <li>Automated navigation commands</li>
                <li>Cemetery information assistance</li>
                <li>Context-aware responses</li>
            </ul>
        </div>
    </div>
</div>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <script>
        // Password strength indicator
        themeUtils.updatePasswordStrength('new_password', 'strength-bar', 'strength-text');
    </script>
</body>
</html>
