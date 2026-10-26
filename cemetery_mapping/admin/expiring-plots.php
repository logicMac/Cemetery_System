<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
 header('Location: ../login.php');
 exit;
}

$current_page = 'expiring-plots';

$today = new DateTime();
$thirtyDays = (clone $today)->modify('+30 days')->format('Y-m-d');

// Burial records with expiration dates
$burialStmt = $pdo->prepare("
 SELECT b.id, b.decedent_name, b.plot_number, b.expiration_date, b.renewal_count,
 b.is_fenced, b.is_relocated, v.full_name AS visitor_name, v.email AS visitor_email,
 'burial' AS plot_type
 FROM burial_records b
 LEFT JOIN visitors v ON v.id = b.visitor_id
 WHERE b.expiration_date IS NOT NULL
 ORDER BY b.expiration_date ASC
");
$burialStmt->execute();
$burialPlots = $burialStmt->fetchAll();

// Available plots with expiration dates
$availableStmt = $pdo->prepare("
 SELECT ap.id, ap.plot_number, ap.notes, ap.expiration_date, ap.renewal_count,
 0 AS is_relocated,
 'available' AS plot_type,
 NULL AS visitor_name, NULL AS visitor_email
 FROM available_plots ap
 WHERE ap.expiration_date IS NOT NULL
 ORDER BY ap.expiration_date ASC
");
$availableStmt->execute();
$availablePlots = $availableStmt->fetchAll();

$allPlots = array_merge($burialPlots, $availablePlots);

usort($allPlots, function($a, $b) {
 return strtotime($a['expiration_date']) <=> strtotime($b['expiration_date']);
});

$expired = array_filter($allPlots, function($p) use ($today) {
 return (new DateTime($p['expiration_date'])) < $today;
});
$expiring = array_filter($allPlots, function($p) use ($today, $thirtyDays) {
 $d = (new DateTime($p['expiration_date']));
 return $d >= $today && $d->format('Y-m-d') <= $thirtyDays;
});
$active = array_filter($allPlots, function($p) use ($thirtyDays) {
 return (new DateTime($p['expiration_date']))->format('Y-m-d') > $thirtyDays;
});

// Saved relocation area (holding zone for expired, unrenewed plots)
$relocLat = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'relocation_lat'")->fetchColumn();
$relocLng = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'relocation_lng'")->fetchColumn();
$relocationSet = ($relocLat !== false && $relocLng !== false && $relocLat !== '' && $relocLng !== '');
$relocLat = $relocationSet ? (float)$relocLat : null;
$relocLng = $relocationSet ? (float)$relocLng : null;
?>
<?php require_once 'includes/sidebar.php'; ?>

<style>
.admin-layout { background: var(--surface); }
.admin-layout::after { display: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade { animation: fadeUp 0.5s ease both; }
button svg, a svg, button i, a i { pointer-events: none; }
</style>

<div class="space-y-6">
 <!-- Page Header -->
 <div class="flex items-center justify-between flex-wrap gap-4 mb-2 animate-fade">
 <div class="flex items-center gap-3">
 <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
 </div>
 <div>
 <h1 class="text-xl font-bold text-slate-900">Expiring Plots</h1>
 <p class="text-xs text-slate-500">All plots with expiration dates and renewal status</p>
 </div>
 </div>
 </div>

 <!-- Stats -->
 <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 animate-fade">
 <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4 ">
 <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
 </div>
 <div><div class="text-2xl font-bold text-slate-900"><?php echo count($expired); ?></div><div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Expired</div></div>
 </div>
 <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4 ">
 <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
 </div>
 <div><div class="text-2xl font-bold text-slate-900"><?php echo count($expiring); ?></div><div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Expiring (30d)</div></div>
 </div>
 <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4 ">
 <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
 </div>
 <div><div class="text-2xl font-bold text-slate-900"><?php echo count($active); ?></div><div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active</div></div>
 </div>
 </div>

 <!-- Relocation Area -->
 <div class="bg-white rounded-xl border border-slate-200 p-5 animate-fade">
 <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
 <div>
 <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2"><i data-lucide="move" class="w-4 h-4 text-rose-500"></i> Relocation Area</h3>
 <p class="text-xs text-slate-500 mt-0.5">Where expired, unrenewed plots are moved on the map. Click the map to set the point.</p>
 </div>
 <span id="relocStatus" class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full <?php echo $relocationSet ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
 <i data-lucide="<?php echo $relocationSet ? 'check-circle' : 'alert-circle'; ?>" class="w-3.5 h-3.5"></i>
 <span id="relocStatusText"><?php echo $relocationSet ? 'Set' : 'Not set'; ?></span>
 </span>
 <button type="button" id="relocateAllBtn" onclick="relocateAllExpired()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition"><i data-lucide="move" class="w-3.5 h-3.5"></i> Relocate All Expired</button>
 </div>
 <div id="relocMap" style="height: 240px; border-radius: 10px; border: 1px solid #e2e8f0; z-index: 1;"></div>
 <p class="text-xs text-slate-400 mt-2 flex items-center gap-1"><i data-lucide="mouse-pointer-click" class="w-3 h-3"></i> <span id="relocCoords"><?php echo $relocationSet ? 'Current: ' . $relocLat . ', ' . $relocLng : 'Click anywhere on the map to place the relocation point'; ?></span></p>
 </div>

 <!-- Table -->
 <div class="bg-white rounded-xl border border-slate-200 animate-fade overflow-hidden">
 <div class="overflow-x-auto">
 <table class="w-full">
 <thead>
 <tr class="bg-slate-50 border-b border-slate-200 text-left">
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Status</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Type</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Plot / Decedent</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Owner / Visitor</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Expiration</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Renewals</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Actions</th>
 </tr>
 </thead>
 <tbody>
 <?php if (empty($allPlots)): ?>
 <tr><td colspan="7" class="text-center py-10 text-slate-400 text-sm">No plots have expiration dates set yet.</td></tr>
 <?php else: ?>
 <?php foreach ($allPlots as $p):
 $exp = new DateTime($p['expiration_date']);
 $diff = $today->diff($exp);
 $days = $diff->invert ? -$diff->days : $diff->days;

 if (!empty($p['is_relocated'])):
 $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">Relocated</span>';
 $daysText = 'Expired ' . abs($days) . ' days ago — moved to relocation area';
 elseif ($days < 0):
 $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Expired</span>';
 $daysText = 'Expired ' . abs($days) . ' days ago';
 elseif ($days <= 30):
 $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Expiring</span>';
 $daysText = 'Expires in ' . $days . ' days';
 else:
 $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Active</span>';
 $daysText = 'Active, expires in ' . $days . ' days';
 endif;

 $typeBadge = $p['plot_type'] === 'burial'
 ? '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">Burial Record</span>'
 : '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">Available Plot</span>';

 $name = $p['plot_type'] === 'burial'
 ? ($p['decedent_name'] . ($p['plot_number'] ? ' · Plot ' . $p['plot_number'] : ''))
 : ($p['plot_number'] ?: 'Plot #' . $p['id']);

 $owner = $p['visitor_name'] ? htmlspecialchars($p['visitor_name'] . ($p['visitor_email'] ? ' (' . $p['visitor_email'] . ')' : '')) : '—';
 ?>
 <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
 <td class="px-4 py-3"><?php echo $statusBadge; ?></td>
 <td class="px-4 py-3"><?php echo $typeBadge; ?></td>
 <td class="px-4 py-3 text-sm text-slate-900"><?php echo htmlspecialchars($name); ?></td>
 <td class="px-4 py-3 text-sm text-slate-600"><?php echo $owner; ?></td>
 <td class="px-4 py-3 text-sm text-slate-600" title="<?php echo $daysText; ?>"><?php echo $exp->format('M d, Y'); ?></td>
 <td class="px-4 py-3 text-sm text-slate-600"><?php echo (int)$p['renewal_count']; ?></td>
 <td class="px-4 py-3">
 <button type="button" onclick="openRenewModal('<?php echo $p['plot_type']; ?>', <?php echo $p['plot_type'] === 'burial' ? $p['id'] : 'null'; ?>, <?php echo $p['plot_type'] === 'available' ? $p['id'] : 'null'; ?>, '<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $p['expiration_date']; ?>')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-700 transition mr-1.5" title="Renew Plot"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
 <?php if ($p['plot_type'] === 'burial' && !empty($p['is_relocated'])): ?>
 <button type="button" onclick="relocateRecord(<?php echo (int)$p['id']; ?>, 'restore', '<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 transition mr-1.5" title="Restore to original plot"><i data-lucide="undo-2" class="w-4 h-4"></i></button>
 <?php elseif ($p['plot_type'] === 'burial' && $days < 0): ?>
 <button type="button" onclick="relocateRecord(<?php echo (int)$p['id']; ?>, 'relocate', '<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-600 transition mr-1.5" title="Move to relocation area"><i data-lucide="move" class="w-4 h-4"></i></button>
 <?php endif; ?>
 <?php if ($p['plot_type'] === 'burial'): ?>
 <a href="records.php?search=<?php echo urlencode($p['decedent_name']); ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 transition" title="View in Records"><i data-lucide="external-link" class="w-4 h-4"></i></a>
 <?php else: ?>
 <a href="available-plots.php" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 transition" title="View Available Plots"><i data-lucide="external-link" class="w-4 h-4"></i></a>
 <?php endif; ?>
 </td>
 </tr>
 <?php endforeach; ?>
 <?php endif; ?>
 </tbody>
 </table>
 </div>
 </div>
</div>

<!-- Renew Modal -->
<div id="renewModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
 <div class="bg-white rounded-xl border border-slate-200 shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto p-6">
 <div class="flex items-center gap-3 mb-5">
 <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center shrink-0">
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
 </div>
 <h2 class="text-lg font-bold text-slate-900">Renew Plot</h2>
 </div>
 <form id="renewForm" class="space-y-4">
 <input type="hidden" id="renewPlotType" name="plot_type">
 <input type="hidden" id="renewRecordId" name="record_id">
 <input type="hidden" id="renewPlotId" name="plot_id">

 <div id="renewInfo" class="bg-slate-50 rounded-xl p-4 text-sm text-slate-700 space-y-1"></div>

 <div>
 <label class="block text-sm font-semibold text-slate-700 mb-2">OR Number (Official Receipt) <span class="text-rose-500">*</span></label>
 <input type="text" name="or_number" required placeholder="e.g. OR-2026-00123" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
 <p class="text-xs text-slate-500 mt-1">Payment is made to the Treasurer. Enter the OR number here to record the renewal.</p>
 </div>

 <div>
 <label class="block text-sm font-semibold text-slate-700 mb-2">Renewal Period</label>
 <input type="text" value="5 years" disabled class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-500 bg-slate-50">
 </div>

 <div>
 <label class="block text-sm font-semibold text-slate-700 mb-2">Notes (optional)</label>
 <textarea name="notes" rows="2" placeholder="Reason for renewal..." class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 resize-y"></textarea>
 </div>

 <div class="flex gap-3 pt-2">
 <button type="button" onclick="closeRenewModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Cancel</button>
 <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold transition">Renew Now</button>
 </div>
 </form>
 </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
 // ── Relocation area mini-map ──
 const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
 let relocMarker = null;
 const relocMap = L.map('relocMap', { scrollWheelZoom: true }).setView(CEMETERY_CENTER, 17);
 L.tileLayer('https://mt{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
 subdomains: ['0','1','2','3'], maxZoom: 22, attribution: ''
 }).addTo(relocMap);

 const savedLat = <?php echo json_encode($relocLat); ?>;
 const savedLng = <?php echo json_encode($relocLng); ?>;
 if (savedLat !== null && savedLng !== null) {
 setRelocMarker([savedLat, savedLng]);
 relocMap.setView([savedLat, savedLng], 19);
 }

 function setRelocMarker(latlng) {
 if (relocMarker) relocMarker.setLatLng(latlng);
 else {
 relocMarker = L.marker(latlng, {
 draggable: true,
 icon: L.divIcon({
 className: 'reloc-pin',
 html: '<div style="width:16px;height:16px;background:#dc2626;border:3px solid #fff;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.4);"></div>',
 iconSize: [16, 16], iconAnchor: [8, 8]
 })
 }).addTo(relocMap);
 relocMarker.on('dragend', () => saveRelocPoint(relocMarker.getLatLng()));
 }
 document.getElementById('relocCoords').textContent =
 'Point: ' + latlng.lat.toFixed(8) + ', ' + latlng.lng.toFixed(8) + ' — saving…';
 saveRelocPoint(latlng);
 }

 relocMap.on('click', e => setRelocMarker(e.latlng));

 async function saveRelocPoint(latlng) {
 const fd = new FormData();
 fd.append('lat', latlng.lat);
 fd.append('lng', latlng.lng);
 try {
 const res = await fetch('../api/relocation_area.php', { method: 'POST', body: fd });
 const data = await res.json();
 const status = document.getElementById('relocStatus');
 const coordsEl = document.getElementById('relocCoords');
 if (data.success) {
 status.className = 'inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700';
 document.getElementById('relocStatusText').textContent = 'Set';
 coordsEl.textContent = 'Point: ' + data.lat.toFixed(8) + ', ' + data.lng.toFixed(8);
 } else {
 coordsEl.textContent = data.message || 'Failed to save';
 }
 } catch (e) {
 document.getElementById('relocCoords').textContent = 'Network error — could not save';
 }
 }

 // ── Relocate / restore actions ──
 async function relocateRecord(id, action, name) {
 const verb = action === 'relocate' ? 'move to the relocation area' : 'restore to its original plot';
 if (!confirm(`Really ${verb}?\n\n${name}`)) return;
 const fd = new FormData();
 fd.append('record_id', id);
 fd.append('action', action);
 try {
 const res = await fetch('../api/relocate_record.php', { method: 'POST', body: fd });
 const data = await res.json();
 if (data.success) {
 if (typeof themeUtils !== 'undefined' && themeUtils.showAlert) themeUtils.showAlert(data.message, 'success');
 else alert(data.message);
 setTimeout(() => location.reload(), 1000);
 } else {
 const msg = data.message || 'Operation failed';
 if (typeof themeUtils !== 'undefined' && themeUtils.showAlert) themeUtils.showAlert(msg, 'error');
 else alert(msg);
 }
 } catch (e) {
 alert('Network error');
 }
 }

 async function relocateAllExpired() {
 if (!confirm('Move ALL expired, unrenewed burial plots to the relocation area?\n\nTheir original locations are saved and can be restored individually.')) return;
 const btn = document.getElementById('relocateAllBtn');
 if (btn) { btn.disabled = true; btn.classList.add('opacity-60'); }
 const fd = new FormData();
 fd.append('action', 'relocate_all');
 try {
 const res = await fetch('../api/relocate_record.php', { method: 'POST', body: fd });
 const data = await res.json();
 const msg = data.message || (data.success ? 'Done' : 'Operation failed');
 if (typeof themeUtils !== 'undefined' && themeUtils.showAlert) themeUtils.showAlert(msg, data.success ? 'success' : 'error');
 else alert(msg);
 if (data.success) setTimeout(() => location.reload(), 1200);
 } catch (e) {
 alert('Network error');
 }
 if (btn) { btn.disabled = false; btn.classList.remove('opacity-60'); }
 }

 function openRenewModal(plotType, recordId, plotId, plotName, currentExp) {
 const modal = document.getElementById('renewModal');
 document.getElementById('renewPlotType').value = plotType;
 document.getElementById('renewRecordId').value = recordId || '';
 document.getElementById('renewPlotId').value = plotId || '';
 document.getElementById('renewInfo').innerHTML = `
 <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">${plotType === 'burial' ? 'Burial Record' : 'Available Plot'}</div>
 <div class="font-semibold text-slate-900">${plotName}</div>
 <div class="text-xs text-slate-500 mt-1">Current expiration: ${currentExp ? new Date(currentExp).toLocaleDateString() : 'No expiry'}</div>
 `;
 modal.classList.remove('hidden');
 modal.classList.add('flex');
 }

 function closeRenewModal() {
 const m = document.getElementById('renewModal');
 m.classList.add('hidden');
 m.classList.remove('flex');
 }

 document.getElementById('renewForm').addEventListener('submit', async function(e) {
 e.preventDefault();
 const fd = new FormData(this);
 try {
 const res = await fetch('../api/direct_renew.php', { method: 'POST', body: fd });
 const data = await res.json();
 if (data.success) {
 if (typeof themeUtils !== 'undefined' && themeUtils.showAlert) {
 themeUtils.showAlert(data.message, 'success');
 } else {
 alert(data.message);
 }
 closeRenewModal();
 this.reset();
 setTimeout(() => location.reload(), 1200);
 } else {
 if (typeof themeUtils !== 'undefined' && themeUtils.showAlert) {
 themeUtils.showAlert(data.message || 'Failed to renew plot', 'error');
 } else {
 alert(data.message || 'Failed to renew plot');
 }
 }
 } catch (err) {
 if (typeof themeUtils !== 'undefined' && themeUtils.showAlert) {
 themeUtils.showAlert('Network error', 'error');
 } else {
 alert('Network error');
 }
 }
 });

 document.getElementById('renewModal').addEventListener('click', function(e) {
 if (e.target === this) closeRenewModal();
 });
</script>
