<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
 header('Location: ../login.php');
 exit;
}

$current_page = 'renewals';
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
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
 </div>
 <div>
 <h1 class="text-xl font-bold text-slate-900">Renewal History</h1>
 <p class="text-xs text-slate-500">Log of plot renewals recorded via Official Receipt (OR)</p>
 </div>
 </div>
 </div>

 <!-- Stats -->
 <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 animate-fade">
 <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4 ">
 <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
 </div>
 <div><div id="totalCount" class="text-2xl font-bold text-slate-900">0</div><div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Renewals</div></div>
 </div>
 <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4 ">
 <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
 </div>
 <div><div id="monthCount" class="text-2xl font-bold text-slate-900">0</div><div class="text-xs font-medium text-slate-500 uppercase tracking-wide">This Month</div></div>
 </div>
 <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4 ">
 <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
 </div>
 <div><div id="weekCount" class="text-2xl font-bold text-slate-900">0</div><div class="text-xs font-medium text-slate-500 uppercase tracking-wide">This Week</div></div>
 </div>
 </div>

 <!-- Renewal Log Table -->
 <div class="bg-white rounded-xl border border-slate-200 animate-fade overflow-hidden">
 <div class="overflow-x-auto">
 <table class="w-full">
 <thead>
 <tr class="bg-slate-50 border-b border-slate-200 text-left">
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">ID</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Plot</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">OR Number</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Payment</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Previous Expiry</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">New Expiry</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Processed By</th>
 <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Date</th>
 </tr>
 </thead>
 <tbody id="requestsBody">
 <tr><td colspan="8" class="text-center py-10 text-slate-400 text-sm">
 <div class="w-8 h-8 border-2 border-amber-200 border-t-amber-600 rounded-full animate-spin mx-auto mb-3"></div>
 Loading renewal history...
 </td></tr>
 </tbody>
 </table>
 </div>
 </div>
</div>

<script>
function escapeHtml(s) {
 if (!s) return '';
 return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

async function loadRequests() {
 try {
 const res = await fetch('../api/renew_plot.php?action=admin_list&status=approved');
 const data = await res.json();
 const body = document.getElementById('requestsBody');
 const all = data.requests || [];

 const now = new Date();
 const startOfWeek = new Date(now); startOfWeek.setDate(now.getDate() - now.getDay()); startOfWeek.setHours(0,0,0,0);
 document.getElementById('totalCount').textContent = all.length;
 document.getElementById('monthCount').textContent = all.filter(r => {
 const d = new Date(r.reviewed_at || r.created_at);
 return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
 }).length;
 document.getElementById('weekCount').textContent = all.filter(r => new Date(r.reviewed_at || r.created_at) >= startOfWeek).length;

 if (!data.success || all.length === 0) {
 body.innerHTML = '<tr><td colspan="8" class="text-center py-10 text-slate-400 text-sm">No renewals recorded yet.</td></tr>';
 return;
 }

 body.innerHTML = all.map(r => {
 const plotName = r.plot_type === 'burial' ? (r.decedent_name || 'Unknown') : (r.available_plot || 'Plot #' + r.plot_id);
 const plotNumber = r.plot_type === 'burial' ? (r.burial_plot || '') : '';
 const plotTypeBadge = r.plot_type === 'burial'
 ? '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">Burial</span>'
 : '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">Available</span>';
 const expDate = r.current_expiration_date ? new Date(r.current_expiration_date).toLocaleDateString() : '—';
 const processed = new Date(r.reviewed_at || r.created_at).toLocaleDateString();
 const orNum = r.or_number ? `<span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">${escapeHtml(r.or_number)}</span>` : '<span class="text-xs text-slate-400">—</span>';
 const payBadge = r.or_number
 ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold border border-emerald-200">PAID</span>'
 : '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold border border-rose-200">UNVERIFIED</span>';
 // New expiry = previous expiry (if still future at renewal time) or renewal date + period
 const years = r.requested_years || 5;
 let base = new Date(r.reviewed_at || r.created_at);
 if (r.current_expiration_date) {
 const prev = new Date(r.current_expiration_date);
 if (prev > base) base = prev;
 }
 base.setFullYear(base.getFullYear() + years);
 const newExpDate = base.toLocaleDateString();
 return `<tr class="border-b border-slate-100 hover:bg-slate-50 transition">
 <td class="px-4 py-3"><span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">#${r.id}</span></td>
 <td class="px-4 py-3"><div class="text-sm font-semibold text-slate-900">${escapeHtml(plotName)}</div>${plotNumber ? `<div class="text-xs text-slate-500">${escapeHtml(plotNumber)}</div>` : ''}<div class="mt-0.5">${plotTypeBadge}</div></td>
 <td class="px-4 py-3">${orNum}</td>
 <td class="px-4 py-3">${payBadge}</td>
 <td class="px-4 py-3 text-sm text-slate-600">${expDate}</td>
 <td class="px-4 py-3 text-sm font-semibold text-emerald-700">${newExpDate}</td>
 <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(r.reviewer_name || 'Admin')}</td>
 <td class="px-4 py-3 text-sm text-slate-600">${processed}</td>
 </tr>`;
 }).join('');
 } catch (e) {
 document.getElementById('requestsBody').innerHTML = '<tr><td colspan="8" class="text-center py-10 text-rose-500 text-sm">Failed to load renewal history.</td></tr>';
 }
}

loadRequests();
</script>
