<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

$success = '';
$error = '';

// Handle CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $action = $_POST['action'] ?? '';

 if ($action === 'add' || $action === 'edit') {
 $id = (int)($_POST['id'] ?? 0);
 $provider = trim($_POST['provider'] ?? 'groq');
 $label = trim($_POST['label'] ?? '');
 $apiKey = trim($_POST['api_key'] ?? '');
 $model = trim($_POST['model'] ?? '');
 $apiUrl = trim($_POST['api_url'] ?? '');
 $setActive = isset($_POST['is_active']) ? 1 : 0;

 if ($label === '' || $apiKey === '') {
 $error = 'Label and API key are required.';
 } else {
 try {
 if ($action === 'add') {
 $stmt = $pdo->prepare("INSERT INTO ai_api_keys (provider, label, api_key, model, api_url, is_active) VALUES (?, ?, ?, ?, ?, 0)");
 $stmt->execute([$provider, $label, $apiKey, $model ?: null, $apiUrl ?: null]);
 $newId = (int)$pdo->lastInsertId();
 if ($setActive) {
 $pdo->prepare("UPDATE ai_api_keys SET is_active = 0 WHERE provider = ?")->execute([$provider]);
 $pdo->prepare("UPDATE ai_api_keys SET is_active = 1 WHERE id = ?")->execute([$newId]);
 }
 $success = 'API key added successfully.';
 } else {
 $stmt = $pdo->prepare("UPDATE ai_api_keys SET provider = ?, label = ?, api_key = ?, model = ?, api_url = ? WHERE id = ?");
 $stmt->execute([$provider, $label, $apiKey, $model ?: null, $apiUrl ?: null, $id]);
 if ($setActive) {
 $pdo->prepare("UPDATE ai_api_keys SET is_active = 0 WHERE provider = ? AND id != ?")->execute([$provider, $id]);
 $pdo->prepare("UPDATE ai_api_keys SET is_active = 1 WHERE id = ?")->execute([$id]);
 }
 $success = 'API key updated successfully.';
 }
 } catch (PDOException $e) {
 error_log('API key save error: ' . $e->getMessage());
 $error = 'Failed to save API key.';
 }
 }
 } elseif ($action === 'activate') {
 $id = (int)($_POST['id'] ?? 0);
 try {
 $stmt = $pdo->prepare("SELECT provider FROM ai_api_keys WHERE id = ?");
 $stmt->execute([$id]);
 $row = $stmt->fetch();
 if ($row) {
 $pdo->prepare("UPDATE ai_api_keys SET is_active = 0 WHERE provider = ?")->execute([$row['provider']]);
 $pdo->prepare("UPDATE ai_api_keys SET is_active = 1 WHERE id = ?")->execute([$id]);
 $success = 'API key activated.';
 } else {
 $error = 'Key not found.';
 }
 } catch (PDOException $e) {
 error_log('API key activate error: ' . $e->getMessage());
 $error = 'Failed to activate key.';
 }
 } elseif ($action === 'delete') {
 $id = (int)($_POST['id'] ?? 0);
 try {
 $pdo->prepare("DELETE FROM ai_api_keys WHERE id = ?")->execute([$id]);
 $success = 'API key deleted.';
 } catch (PDOException $e) {
 error_log('API key delete error: ' . $e->getMessage());
 $error = 'Failed to delete key.';
 }
 }
}

// Fetch all keys
try {
 $keys = $pdo->query("SELECT * FROM ai_api_keys ORDER BY provider, is_active DESC, id DESC")->fetchAll();
} catch (PDOException $e) {
 $keys = [];
 $error = 'ai_api_keys table is missing. Run the add_ai_api_keys.sql migration.';
}

$activeKey = null;
foreach ($keys as $k) {
 if ($k['provider'] === 'groq' && $k['is_active']) { $activeKey = $k; break; }
}

function maskKey($key) {
 $len = strlen($key);
 if ($len <= 12) return str_repeat('*', $len);
 return substr($key, 0, 8) . str_repeat('*', $len - 12) . substr($key, -4);
}
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
.admin-layout { background: var(--surface); }
.admin-layout::after { display: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade { animation: fadeUp 0.5s ease both; }
button svg, a svg, button i, a i { pointer-events: none; }
.key-value { font-family: ui-monospace, monospace; font-size: 0.8rem; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 50; display: none; align-items: center; justify-content: center; padding: 16px; }
.modal-backdrop.open { display: flex; }
.modal-card { background: var(--surface); border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
</style>

<!-- Page Header -->
<div class="flex items-center gap-3 mb-6 animate-fade">
 <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
 <i data-lucide="key" class="w-5 h-5"></i>
 </div>
 <div class="flex-1">
 <h2 class="text-xl font-bold text-slate-900">AI API Keys</h2>
 <p class="text-sm text-slate-500">Manage API credentials used by the AI assistant — stored in the database</p>
 </div>
 <button onclick="openKeyModal()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition ">
 <i data-lucide="plus" class="w-4 h-4"></i> Add API Key
 </button>
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

<!-- Active key status -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-5 animate-fade">
 <div class="flex items-center gap-3">
 <div class="w-10 h-10 rounded-xl <?php echo $activeKey ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-600'; ?> flex items-center justify-center">
 <i data-lucide="<?php echo $activeKey ? 'check-circle' : 'alert-triangle'; ?>" class="w-5 h-5"></i>
 </div>
 <div>
 <?php if ($activeKey): ?>
 <p class="text-sm font-semibold text-slate-900">Active Groq key: <?php echo htmlspecialchars($activeKey['label']); ?> <span class="key-value text-slate-500">(<?php echo maskKey($activeKey['api_key']); ?>)</span></p>
 <p class="text-xs text-slate-500">Model: <span class="font-mono"><?php echo htmlspecialchars($activeKey['model'] ?: 'default'); ?></span> — used by the admin & visitor AI assistants</p>
 <?php else: ?>
 <p class="text-sm font-semibold text-slate-900">No active Groq key</p>
 <p class="text-xs text-slate-500">The AI assistant will not work until a key is activated</p>
 <?php endif; ?>
 </div>
 </div>
</div>

<!-- Keys table -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden animate-fade">
 <div class="overflow-x-auto">
 <table class="w-full text-sm">
 <thead>
 <tr class="border-b border-slate-200 bg-slate-50 text-left">
 <th class="px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">Provider</th>
 <th class="px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">Label</th>
 <th class="px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">API Key</th>
 <th class="px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">Model</th>
 <th class="px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
 <th class="px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 text-right">Actions</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100">
 <?php if (empty($keys)): ?>
 <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No API keys stored yet.</td></tr>
 <?php else: foreach ($keys as $k): ?>
 <tr class="<?php echo $k['is_active'] ? 'bg-emerald-50/40' : ''; ?>">
 <td class="px-5 py-3">
 <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
 <i data-lucide="cpu" class="w-3 h-3"></i> <?php echo htmlspecialchars($k['provider']); ?>
 </span>
 </td>
 <td class="px-5 py-3 font-medium text-slate-800"><?php echo htmlspecialchars($k['label']); ?></td>
 <td class="px-5 py-3 key-value text-slate-600"><?php echo maskKey($k['api_key']); ?></td>
 <td class="px-5 py-3 text-slate-600 font-mono text-xs"><?php echo htmlspecialchars($k['model'] ?: '—'); ?></td>
 <td class="px-5 py-3">
 <?php if ($k['is_active']): ?>
 <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200"><i data-lucide="check-circle" class="w-3 h-3"></i> Active</span>
 <?php else: ?>
 <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">Inactive</span>
 <?php endif; ?>
 </td>
 <td class="px-5 py-3">
 <div class="flex items-center justify-end gap-2">
 <?php if (!$k['is_active']): ?>
 <form method="POST" class="inline">
 <input type="hidden" name="action" value="activate">
 <input type="hidden" name="id" value="<?php echo (int)$k['id']; ?>">
 <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">Activate</button>
 </form>
 <?php endif; ?>
 <button type="button" onclick='openKeyModal(<?php echo json_encode([
 'id' => (int)$k['id'],
 'provider' => $k['provider'],
 'label' => $k['label'],
 'api_key' => $k['api_key'],
 'model' => $k['model'],
 'api_url' => $k['api_url'],
 'is_active' => (int)$k['is_active'],
 ]); ?>)' class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Edit</button>
 <form method="POST" class="inline" onsubmit="return confirm('Delete this API key?');">
 <input type="hidden" name="action" value="delete">
 <input type="hidden" name="id" value="<?php echo (int)$k['id']; ?>">
 <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 transition">Delete</button>
 </form>
 </div>
 </td>
 </tr>
 <?php endforeach; endif; ?>
 </tbody>
 </table>
 </div>
</div>

<div class="mt-5 p-4 rounded-xl bg-blue-50 border border-blue-100 text-xs text-slate-600 animate-fade">
 <p class="font-semibold text-blue-800 mb-1 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5"></i> How it works</p>
 Only one key per provider can be <strong>Active</strong> at a time — activating a key deactivates the others.
 The active Groq key, model and endpoint are loaded by <code class="px-1 py-0.5 rounded bg-blue-100 text-blue-800">config/groq_config.php</code>
 on every request, so changes take effect immediately with no file edits.
</div>

<!-- Add/Edit Modal -->
<div class="modal-backdrop" id="keyModal">
 <div class="modal-card p-6">
 <div class="flex items-center justify-between mb-4">
 <h3 class="text-base font-bold text-slate-900" id="keyModalTitle">Add API Key</h3>
 <button type="button" onclick="closeKeyModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition">
 <i data-lucide="x" class="w-4 h-4"></i>
 </button>
 </div>
 <form method="POST" class="space-y-4">
 <input type="hidden" name="action" id="keyAction" value="add">
 <input type="hidden" name="id" id="keyId" value="">
 <div class="grid grid-cols-2 gap-4">
 <div>
 <label class="block text-sm font-medium text-slate-700 mb-1.5">Provider</label>
 <input type="text" name="provider" id="keyProvider" value="groq" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <div>
 <label class="block text-sm font-medium text-slate-700 mb-1.5">Label</label>
 <input type="text" name="label" id="keyLabel" required placeholder="e.g. Main Groq Key" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 </div>
 <div>
 <label class="block text-sm font-medium text-slate-700 mb-1.5">API Key</label>
 <input type="text" name="api_key" id="keyValue" required placeholder="gsk_..." class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <div>
 <label class="block text-sm font-medium text-slate-700 mb-1.5">Model <span class="text-xs text-slate-400">(optional)</span></label>
 <input type="text" name="model" id="keyModel" placeholder="openai/gpt-oss-120b" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <div>
 <label class="block text-sm font-medium text-slate-700 mb-1.5">API URL <span class="text-xs text-slate-400">(optional)</span></label>
 <input type="text" name="api_url" id="keyUrl" placeholder="https://api.groq.com/openai/v1/chat/completions" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
 </div>
 <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
 <input type="checkbox" name="is_active" id="keyActive" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
 Set as active key for this provider
 </label>
 <div class="flex justify-end gap-2 pt-2">
 <button type="button" onclick="closeKeyModal()" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-slate-200 transition">Cancel</button>
 <button type="submit" class="px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition">Save Key</button>
 </div>
 </form>
 </div>
</div>

 </main>
 </div>

 <script src="../assets/js/theme.js"></script>
 <script>
 function openKeyModal(data) {
 const modal = document.getElementById('keyModal');
 if (data) {
 document.getElementById('keyModalTitle').textContent = 'Edit API Key';
 document.getElementById('keyAction').value = 'edit';
 document.getElementById('keyId').value = data.id;
 document.getElementById('keyProvider').value = data.provider || 'groq';
 document.getElementById('keyLabel').value = data.label || '';
 document.getElementById('keyValue').value = data.api_key || '';
 document.getElementById('keyModel').value = data.model || '';
 document.getElementById('keyUrl').value = data.api_url || '';
 document.getElementById('keyActive').checked = !!data.is_active;
 } else {
 document.getElementById('keyModalTitle').textContent = 'Add API Key';
 document.getElementById('keyAction').value = 'add';
 document.getElementById('keyId').value = '';
 document.getElementById('keyProvider').value = 'groq';
 document.getElementById('keyLabel').value = '';
 document.getElementById('keyValue').value = '';
 document.getElementById('keyModel').value = '';
 document.getElementById('keyUrl').value = '';
 document.getElementById('keyActive').checked = false;
 }
 modal.classList.add('open');
 }
 function closeKeyModal() {
 document.getElementById('keyModal').classList.remove('open');
 }
 document.getElementById('keyModal').addEventListener('click', function(e) {
 if (e.target === this) closeKeyModal();
 });
 document.addEventListener('DOMContentLoaded', function() {
 if (typeof lucide !== 'undefined') lucide.createIcons();
 });
 </script>
</body>
</html>
