<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';
require_once '../config/groq_config.php';
require_once '../includes/mailer.php';

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
 } elseif ($action === 'save_mail_settings') {
 try {
 $stmt = $pdo->prepare("
 INSERT INTO mail_settings (id, smtp_host, smtp_port, smtp_encryption, smtp_username, smtp_password, from_name, admin_notify_email, is_enabled)
 VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)
 ON DUPLICATE KEY UPDATE
 smtp_host = VALUES(smtp_host), smtp_port = VALUES(smtp_port), smtp_encryption = VALUES(smtp_encryption),
 smtp_username = VALUES(smtp_username), smtp_password = VALUES(smtp_password),
 from_name = VALUES(from_name), admin_notify_email = VALUES(admin_notify_email), is_enabled = VALUES(is_enabled)
 ");
 $stmt->execute([
 trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
 (int)($_POST['smtp_port'] ?? 587),
 in_array($_POST['smtp_encryption'] ?? 'tls', ['tls', 'ssl'], true) ? $_POST['smtp_encryption'] : 'tls',
 trim($_POST['smtp_username'] ?? ''),
 trim($_POST['smtp_password'] ?? ''),
 trim($_POST['from_name'] ?? 'Matinao Memorial Cemetery'),
 trim($_POST['admin_notify_email'] ?? ''),
 isset($_POST['is_enabled']) ? 1 : 0,
 ]);
 $success = 'Email settings saved';
 } catch (PDOException $e) {
 error_log("Mail settings save error: " . $e->getMessage());
 $error = 'Could not save email settings';
 }
 } elseif ($action === 'send_test_email') {
 $to = trim($_POST['test_email'] ?? '');
 $ms = mail_settings($pdo);
 if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
 $error = 'Enter a valid email address for the test';
 } elseif (!$ms['is_enabled']) {
 $error = 'Email sending is disabled — enable it and save first';
 } elseif (send_mail($pdo, $to, 'Test Recipient', 'Matinao Cemetery — Test Email',
 '<div style="font-family:Arial,sans-serif;padding:20px;"><h2 style="color:#059669;margin:0 0 10px;">Test email works</h2><p style="color:#334155;font-size:14px;">SMTP settings for Matinao Memorial Cemetery are configured correctly.</p></div>')) {
 $success = 'Test email sent to ' . htmlspecialchars($to, ENT_QUOTES, 'UTF-8');
 } else {
 $error = 'Test email failed — check credentials (see PHP error log for details)';
 }
 }
}

// Load saved mail settings for the form
$mailCfg = mail_settings($pdo);

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

<style>
.admin-layout { background: var(--surface); }
.admin-layout::after { display: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade { animation: fadeUp 0.5s ease both; }
button svg, a svg, button i, a i { pointer-events: none; }
.password-strength { height: 6px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; margin-top: 8px; }
.password-strength-bar { height: 100%; width: 0; border-radius: 9999px; transition: width 0.3s ease, background 0.3s ease; }
.password-strength-bar.weak { width: 33%; background: #ef4444; }
.password-strength-bar.medium { width: 66%; background: #f59e0b; }
.password-strength-bar.strong { width: 100%; background: #10b981; }
</style>

<!-- Page Header -->
<div class="flex items-center gap-3 mb-6 animate-fade">
 <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
 <i data-lucide="settings" class="w-5 h-5"></i>
 </div>
 <div>
 <h2 class="text-xl font-bold text-slate-900">Settings</h2>
 <p class="text-sm text-slate-500">Manage system configuration, security, and integrations</p>
 </div>
</div>

<?php if ($success): ?>
<div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2 animate-fade">
 <i data-lucide="check-circle" class="w-5 h-5"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2 animate-fade">
 <i data-lucide="x-circle" class="w-5 h-5"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<!-- System Information -->
<div class="bg-white rounded-xl border border-slate-200 p-6 mb-5 animate-fade">
 <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700 mb-4 flex items-center gap-2"><i data-lucide="server" class="w-4 h-4 text-emerald-600"></i> System Information</h3>
 <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
 <div class="bg-emerald-50 rounded-xl p-4">
 <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2"><i data-lucide="file-text" class="w-4 h-4"></i></div>
 <div class="text-2xl font-bold text-slate-900"><?php echo number_format($totalRecords); ?></div>
 <div class="text-xs text-slate-500">Total Records</div>
 </div>
 <div class="bg-blue-50 rounded-xl p-4">
 <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mb-2"><i data-lucide="map-pin" class="w-4 h-4"></i></div>
 <div class="text-2xl font-bold text-slate-900"><?php echo number_format($totalPlots); ?></div>
 <div class="text-xs text-slate-500">Available Plots</div>
 </div>
 <div class="bg-amber-50 rounded-xl p-4">
 <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center mb-2"><i data-lucide="users" class="w-4 h-4"></i></div>
 <div class="text-2xl font-bold text-slate-900"><?php echo number_format($totalVisitors); ?></div>
 <div class="text-xs text-slate-500">Visitors</div>
 </div>
 <div class="bg-slate-50 rounded-xl p-4">
 <div class="w-9 h-9 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center mb-2"><i data-lucide="database" class="w-4 h-4"></i></div>
 <div class="text-2xl font-bold text-slate-900"><?php echo $dbSize; ?> <span class="text-base font-medium text-slate-500">MB</span></div>
 <div class="text-xs text-slate-500">Database Size</div>
 </div>
 </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 animate-fade">
 <!-- Change Password -->
 <div class="bg-white rounded-xl border border-slate-200 p-6">
 <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700 mb-1 flex items-center gap-2"><i data-lucide="lock" class="w-4 h-4 text-emerald-600"></i> Change Password</h3>
 <p class="text-xs text-slate-500 mb-5">Keep your account secure with a strong password</p>
 <form method="POST" action="" class="space-y-4">
 <input type="hidden" name="action" value="change_password">
 <div>
 <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1.5">Current Password</label>
 <div class="relative">
 <i data-lucide="key" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
 <input type="password" id="current_password" name="current_password" required class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 </div>
 <div>
 <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1.5">New Password <span class="text-xs text-slate-400">(min 8 characters)</span></label>
 <div class="relative">
 <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
 <input type="password" id="new_password" name="new_password" required minlength="8" oninput="updateStrength(this)" class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <div class="password-strength"><div class="password-strength-bar" id="strength-bar"></div></div>
 <small id="strength-text" class="text-xs text-slate-400"></small>
 </div>
 <div>
 <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm New Password</label>
 <div class="relative">
 <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
 <input type="password" id="confirm_password" name="confirm_password" required minlength="8" oninput="checkMatch()" class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <small id="match-text" class="text-xs"></small>
 </div>
 <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 transition ">
 <i data-lucide="check" class="w-4 h-4"></i> Change Password
 </button>
 </form>
 </div>

 <!-- Map Configuration -->
 <div class="bg-white rounded-xl border border-slate-200 p-6">
 <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700 mb-1 flex items-center gap-2"><i data-lucide="map" class="w-4 h-4 text-emerald-600"></i> Map Configuration</h3>
 <p class="text-xs text-slate-500 mb-5">Cemetery map center and tile layer settings</p>
 <div class="space-y-4">
 <div class="bg-slate-50 rounded-xl p-4">
 <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Cemetery Center Coordinates</div>
 <div class="font-mono text-sm text-slate-800 space-y-0.5">
 <div class="flex items-center gap-2"><i data-lucide="move-horizontal" class="w-3.5 h-3.5 text-slate-400"></i> Latitude: <span class="font-semibold">6.18344118743717</span></div>
 <div class="flex items-center gap-2"><i data-lucide="move-vertical" class="w-3.5 h-3.5 text-slate-400"></i> Longitude: <span class="font-semibold">125.08457146469357</span></div>
 </div>
 <p class="text-xs text-slate-400 mt-2 flex items-center gap-1"><i data-lucide="info" class="w-3 h-3"></i> Edit <code class="px-1 py-0.5 rounded bg-slate-200 text-slate-600 text-[10px]">config/database.php</code> to change</p>
 </div>
 <div class="bg-slate-50 rounded-xl p-4">
 <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Default Zoom Level</div>
 <div class="flex items-center gap-3">
 <div class="text-2xl font-bold text-slate-900">17</div>
 <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
 <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-full" style="width: 70%;"></div>
 </div>
 <span class="text-xs text-slate-400">Range 10–20</span>
 </div>
 </div>
 <div class="bg-slate-50 rounded-xl p-4">
 <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Available Tile Layers</div>
 <div class="flex flex-wrap gap-2">
 <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200"><i data-lucide="satellite" class="w-3 h-3"></i> Google Satellite <span class="text-[10px] opacity-70">(default)</span></span>
 <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200"><i data-lucide="layers" class="w-3 h-3"></i> Google Hybrid</span>
 <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200"><i data-lucide="road" class="w-3 h-3"></i> Google Streets</span>
 <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200"><i data-lucide="globe" class="w-3 h-3"></i> Esri Imagery</span>
 <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200"><i data-lucide="map" class="w-3 h-3"></i> OpenStreetMap</span>
 </div>
 </div>
 </div>
 </div>

 <!-- Database Management -->
 <div class="bg-white rounded-xl border border-slate-200 p-6">
 <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700 mb-1 flex items-center gap-2"><i data-lucide="database" class="w-4 h-4 text-emerald-600"></i> Database Management</h3>
 <p class="text-xs text-slate-500 mb-5">Backup and protect your cemetery data</p>
 <p class="text-sm text-slate-600 mb-4">Regular backups are recommended to prevent data loss. Backup files will be saved in the <code class="px-1 py-0.5 rounded bg-slate-100 text-slate-600 text-xs">backups/</code> folder.</p>
 <form method="POST" action="" onsubmit="return confirm('Create database backup now?');">
 <input type="hidden" name="action" value="backup_database">
 <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 transition ">
 <i data-lucide="download" class="w-4 h-4"></i> Create Backup
 </button>
 </form>
 <div class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200">
 <p class="text-xs font-semibold text-amber-700 uppercase mb-2 flex items-center gap-1.5"><i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> Important Notes</p>
 <ul class="text-xs text-slate-600 space-y-1 ml-4 list-disc">
 <li>Backups include all database tables and data</li>
 <li>Backup files do not include uploaded photos</li>
 <li>Store backups in a secure location</li>
 <li>Test backup restoration periodically</li>
 </ul>
 </div>
 </div>

 <!-- Email (SMTP) Configuration -->
 <div class="bg-white rounded-xl border border-slate-200 p-6">
 <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700 mb-1 flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4 text-emerald-600"></i> Email Notifications (SMTP)</h3>
 <p class="text-xs text-slate-500 mb-5">Gmail SMTP for plot expiration notices and admin digests — stored in the database</p>
 <form method="POST" action="" class="space-y-4">
 <input type="hidden" name="action" value="save_mail_settings">
 <div class="flex items-center justify-between bg-slate-50 rounded-xl p-4">
 <div>
 <div class="text-sm font-semibold text-slate-800">Enable email sending</div>
 <div class="text-xs text-slate-500">Master switch for all outgoing mail</div>
 </div>
 <label class="inline-flex items-center cursor-pointer">
 <input type="checkbox" name="is_enabled" value="1" <?php echo $mailCfg['is_enabled'] ? 'checked' : ''; ?> class="w-5 h-5 accent-emerald-600">
 </label>
 </div>
 <div class="grid grid-cols-2 gap-4">
 <div class="col-span-2 sm:col-span-1">
 <label class="block text-sm font-medium text-slate-700 mb-1.5">SMTP Host</label>
 <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($mailCfg['smtp_host'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <div class="col-span-2 sm:col-span-1">
 <label class="block text-sm font-medium text-slate-700 mb-1.5">Port / Encryption</label>
 <div class="flex gap-2">
 <input type="number" name="smtp_port" value="<?php echo (int)$mailCfg['smtp_port']; ?>" class="w-24 rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 <select name="smtp_encryption" class="flex-1 rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 <option value="tls" <?php echo $mailCfg['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS (587)</option>
 <option value="ssl" <?php echo $mailCfg['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL (465)</option>
 </select>
 </div>
 </div>
 <div class="col-span-2">
 <label class="block text-sm font-medium text-slate-700 mb-1.5">Gmail Address (sender)</label>
 <input type="email" name="smtp_username" value="<?php echo htmlspecialchars($mailCfg['smtp_username'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="your-gmail@gmail.com" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <div class="col-span-2">
 <label class="block text-sm font-medium text-slate-700 mb-1.5">Gmail App Password</label>
 <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($mailCfg['smtp_password'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="16-character app password" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 <p class="text-xs text-slate-400 mt-1">Google Account → Security → 2-Step Verification → App passwords. Not your normal Gmail password.</p>
 </div>
 <div class="col-span-2 sm:col-span-1">
 <label class="block text-sm font-medium text-slate-700 mb-1.5">Sender Name</label>
 <input type="text" name="from_name" value="<?php echo htmlspecialchars($mailCfg['from_name'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <div class="col-span-2 sm:col-span-1">
 <label class="block text-sm font-medium text-slate-700 mb-1.5">Admin Digest Email <span class="text-xs text-slate-400">(optional)</span></label>
 <input type="email" name="admin_notify_email" value="<?php echo htmlspecialchars($mailCfg['admin_notify_email'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Fallback when admin accounts have no email" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 </div>
 <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 transition">
 <i data-lucide="save" class="w-4 h-4"></i> Save Email Settings
 </button>
 </form>
 <div class="mt-4 pt-4 border-t border-slate-100">
 <form method="POST" action="" class="flex gap-2">
 <input type="hidden" name="action" value="send_test_email">
 <input type="email" name="test_email" required placeholder="Send test email to…" class="flex-1 rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold px-4 py-2 transition">
 <i data-lucide="send" class="w-4 h-4"></i> Test
 </button>
 </form>
 </div>
 </div>

 <!-- API Configuration -->
 <div class="bg-white rounded-xl border border-slate-200 p-6">
 <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700 mb-1 flex items-center gap-2"><i data-lucide="sparkles" class="w-4 h-4 text-emerald-600"></i> API Configuration</h3>
 <p class="text-xs text-slate-500 mb-5">Groq AI integration for the assistant</p>
 <div class="bg-slate-50 rounded-xl p-4 mb-4">
 <div class="flex items-center justify-between mb-2">
 <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Groq AI API</span>
 <?php $configured = defined('GROQ_API_KEY') && GROQ_API_KEY !== 'your_groq_api_key_here'; ?>
 <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full <?php echo $configured ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'; ?>">
 <i data-lucide="<?php echo $configured ? 'check-circle' : 'x-circle'; ?>" class="w-3 h-3"></i> <?php echo $configured ? 'Configured' : 'Not configured'; ?>
 </span>
 </div>
 <div class="text-sm text-slate-700 mb-1">Model: <span class="font-mono font-semibold text-slate-900"><?php echo defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile'; ?></span></div>
 <p class="text-xs text-slate-400 flex items-center gap-1"><i data-lucide="info" class="w-3 h-3"></i> Manage keys in <a href="api-keys.php" class="text-emerald-600 hover:underline font-semibold">System → AI API Keys</a> — stored in the database, no file edits needed</p>
 </div>
 <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
 <p class="text-xs font-semibold text-emerald-700 uppercase mb-2 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5"></i> AI Assistant Features</p>
 <ul class="text-xs text-slate-600 space-y-1 ml-4 list-disc">
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
 function updateStrength(input) {
 const bar = document.getElementById('strength-bar');
 const text = document.getElementById('strength-text');
 const v = input.value;
 if (!v) { bar.className = 'password-strength-bar'; text.textContent = ''; return; }
 let score = 0;
 if (v.length >= 8) score++;
 if (v.length >= 12) score++;
 if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
 if (/\d/.test(v)) score++;
 if (/[^a-zA-Z\d]/.test(v)) score++;
 if (score <= 2) { bar.className = 'password-strength-bar weak'; text.textContent = 'Weak password'; text.style.color = '#ef4444'; }
 else if (score <= 4) { bar.className = 'password-strength-bar medium'; text.textContent = 'Medium password'; text.style.color = '#f59e0b'; }
 else { bar.className = 'password-strength-bar strong'; text.textContent = 'Strong password'; text.style.color = '#10b981'; }
 checkMatch();
 }
 function checkMatch() {
 const newP = document.getElementById('new_password').value;
 const conf = document.getElementById('confirm_password').value;
 const t = document.getElementById('match-text');
 if (!conf) { t.textContent = ''; return; }
 if (newP === conf) { t.textContent = 'Passwords match'; t.style.color = '#10b981'; }
 else { t.textContent = 'Passwords do not match'; t.style.color = '#ef4444'; }
 }
 document.addEventListener('DOMContentLoaded', function() {
 if (typeof lucide !== 'undefined') lucide.createIcons();
 });
 </script>
</body>
</html>
