<?php
session_start();
require_once 'includes/header.php';
?>
<?php require_once 'includes/sidebar.php'; ?>
<?php require_once 'includes/topbar.php'; ?>

<style>
.admin-layout { background: #ffffff; }
.admin-layout::after { display: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes scaleIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
.animate-fade { animation: fadeUp 0.5s ease both; }
.animate-fade-in { animation: fadeIn 0.4s ease both; }
.animate-scale { animation: scaleIn 0.4s ease both; }
.animate-stagger > * { opacity: 0; animation: fadeUp 0.5s ease forwards; }
.animate-stagger > *:nth-child(1) { animation-delay: 0.05s; }
.animate-stagger > *:nth-child(2) { animation-delay: 0.12s; }
.animate-stagger > *:nth-child(3) { animation-delay: 0.19s; }
.animate-stagger > *:nth-child(4) { animation-delay: 0.26s; }
.animate-stagger > *:nth-child(5) { animation-delay: 0.33s; }
.animate-stagger > *:nth-child(6) { animation-delay: 0.40s; }
.animate-stagger > *:nth-child(7) { animation-delay: 0.47s; }
.animate-stagger > *:nth-child(8) { animation-delay: 0.54s; }
.reservation-card { animation: fadeUp 0.5s ease both; }
button svg, a svg, button i, a i { pointer-events: none; }

/* View toggle */
.reservations-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
.reservations-list { display: block; }

@media (min-width: 1024px) {
    .reservations-grid { grid-template-columns: repeat(2, 1fr); gap: 24px; }
}
</style>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-2 animate-fade">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900">My Reservations</h1>
                <p class="text-xs text-slate-500">Track and manage your plot reservations</p>
            </div>
        </div>
        <div id="viewToggle" class="hidden flex items-center gap-1 p-1 bg-slate-100 rounded-lg">
            <button type="button" id="viewGrid" onclick="switchView('grid')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Grid
            </button>
            <button type="button" id="viewList" onclick="switchView('list')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                List
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div id="statsGrid" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-stagger">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <div>
                <div id="totalReservations" class="text-2xl font-bold text-slate-900">0</div>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Reservations</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <div id="pendingCount" class="text-2xl font-bold text-slate-900">0</div>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Approval</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <div id="approvedCount" class="text-2xl font-bold text-slate-900">0</div>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Approved</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="w-12 h-12 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <div id="totalPaid" class="text-2xl font-bold text-slate-900">₱0.00</div>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Paid</div>
            </div>
        </div>
    </div>

    <!-- Reservations Container -->
    <div id="reservationsContainer" class="reservations-grid">
        <div class="text-center py-16 animate-fade-in" style="grid-column: 1 / -1;">
            <div class="w-8 h-8 border-2 border-emerald-200 border-t-emerald-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-slate-500">Loading your reservations...</p>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-24 right-5 z-[1000] px-5 py-4 rounded-xl shadow-2xl flex items-center gap-3 transition-transform duration-300 translate-x-full" style="transform: translateX(120%);"></div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 animate-scale">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
            </div>
            <h2 class="text-xl font-bold text-slate-900">Submit Payment</h2>    
        </div>
        <form id="paymentForm" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" id="reservation_id" name="reservation_id">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Amount to Pay *</label>
                <input type="number" name="amount" step="0.01" required placeholder="0.00" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                <p id="balanceInfo" class="text-xs text-slate-500 mt-2"></p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Payment Method *</label>
                <select name="payment_method" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 bg-white">
                    <option value="">Select Payment Method</option>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="gcash">GCash</option>
                    <option value="paymaya">PayMaya</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Reference Number</label>
                <input type="text" name="reference_number" placeholder="Transaction/Reference Number" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                <p class="text-xs text-slate-500 mt-2">Enter the transaction or reference number if applicable</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Proof of Payment (Image/PDF)</label>
                <input type="file" name="proof_of_payment" accept="image/*,.pdf" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                <p class="text-xs text-slate-500 mt-2">Upload screenshot or receipt of payment</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Notes</label>
                <textarea name="notes" rows="3" placeholder="Additional notes or comments..." class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 resize-none"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition">Submit Payment</button>
                <button type="button" onclick="closePaymentModal()" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

        </main>
    </div>
    <script src="../assets/js/theme.js"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>

    <script>
        const statusStyles = {
            pending: 'bg-amber-50 text-amber-700 border border-amber-200',
            approved: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            rejected: 'bg-rose-50 text-rose-700 border border-rose-200'
        };

        const paymentStyles = {
            unpaid: 'bg-rose-50 text-rose-700 border border-rose-200',
            partial: 'bg-amber-50 text-amber-700 border border-amber-200',
            paid: 'bg-emerald-50 text-emerald-700 border border-emerald-200'
        };

        const statusIcon = {
            pending: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            approved: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            rejected: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        };

        let currentReservation = null;
        let currentView = 'grid';
        let lastReservations = [];

        function switchView(view) {
            currentView = view;
            const btnGrid = document.getElementById('viewGrid');
            const btnList = document.getElementById('viewList');
            const container = document.getElementById('reservationsContainer');
            if (view === 'grid') {
                btnGrid.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm';
                btnList.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700';
                container.className = 'reservations-grid';
            } else {
                btnList.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm';
                btnGrid.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700';
                container.className = 'reservations-list';
            }
            if (lastReservations.length > 0) {
                displayReservations(lastReservations);
            }
        }

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            const icon = type === 'success'
                ? '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                : '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            const typeClass = type === 'success'
                ? 'bg-emerald-50 border border-emerald-200 text-emerald-800'
                : 'bg-rose-50 border border-rose-200 text-rose-800';

            toast.innerHTML = icon + '<span class="font-medium">' + message + '</span>';
            toast.className = 'fixed top-24 right-5 z-[1000] px-5 py-4 rounded-xl shadow-2xl flex items-center gap-3 transition-transform duration-300 ' + typeClass;
            toast.style.transform = 'translateX(0)';
            setTimeout(() => {
                toast.style.transform = 'translateX(120%)';
            }, 3500);
        }

        async function loadReservations() {
            try {
                const response = await fetch('../api/get_my_reservations.php');
                const data = await response.json();

                if (data.success) {
                    displayReservations(data.reservations);
                } else {
                    document.getElementById('reservationsContainer').innerHTML =
                        '<div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500 animate-fade" style="grid-column: 1 / -1;">Error loading reservations</div>';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function displayReservations(reservations) {
            const container = document.getElementById('reservationsContainer');
            lastReservations = reservations;

            document.getElementById('viewToggle').classList.remove('hidden');
            document.getElementById('viewToggle').classList.add('flex');

            if (reservations.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm animate-scale" style="grid-column: 1 / -1;">
                        <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-6">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-9 h-9"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">No Reservations Yet</h3>
                        <p class="text-slate-500 mb-6 max-w-md mx-auto">You haven't made any plot reservations. Start by browsing available plots on the map.</p>
                        <a href="dashboard.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                            Browse Available Plots
                        </a>
                    </div>
                `;
                return;
            }

            const stats = {
                total: reservations.length,
                pending: reservations.filter(r => r.status === 'pending').length,
                approved: reservations.filter(r => r.status === 'approved').length,
                totalPaid: reservations.reduce((sum, r) => sum + parseFloat(r.amount_paid || 0), 0)
            };

            document.getElementById('statsGrid').classList.remove('hidden');
            document.getElementById('statsGrid').classList.add('grid');
            document.getElementById('totalReservations').textContent = stats.total;
            document.getElementById('pendingCount').textContent = stats.pending;
            document.getElementById('approvedCount').textContent = stats.approved;
            document.getElementById('totalPaid').textContent = '₱' + stats.totalPaid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            if (currentView === 'list') {
                container.innerHTML = renderListView(reservations);
                return;
            }

            container.innerHTML = reservations.map((res, idx) => {
                const total = parseFloat(res.total_amount);
                const paid = parseFloat(res.amount_paid);
                const balance = total - paid;
                const progressPercent = total > 0 ? Math.min(100, (paid / total) * 100) : 0;
                const isComplete = balance <= 0;
                const delay = Math.min(idx * 0.08, 0.6);

                return `
                <div class="reservation-card bg-white rounded-2xl border-2 border-emerald-100 p-6 shadow-sm hover:shadow-lg hover:border-emerald-300 hover:-translate-y-0.5 transition-all relative" style="animation-delay: ${delay}s;">

                    <div class="card-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 pb-5 border-b border-slate-100">
                        <div class="flex items-center gap-3 text-lg font-semibold text-slate-900">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                            </div>
                            Reservation #${res.id}
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide ${statusStyles[res.status] || 'bg-slate-50 text-slate-700 border border-slate-200'}">${statusIcon[res.status] || ''}${res.status}</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide ${paymentStyles[res.payment_status] || 'bg-slate-50 text-slate-700 border border-slate-200'}">${res.payment_status}</span>
                        </div>
                    </div>

                    <div class="card-progress mb-5">
                        <div class="flex items-center justify-between mb-2 text-sm">
                            <span class="text-slate-500 flex items-center gap-2">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Payment Progress
                            </span>
                            <span class="font-semibold ${isComplete ? 'text-emerald-600' : 'text-amber-600'}">
                                ₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} / ₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full ${isComplete ? 'bg-emerald-500' : 'bg-amber-400'}" style="width: ${progressPercent}%;"></div>
                        </div>
                    </div>

                    <div class="card-details grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Plot Number
                            </span>
                            <span class="text-slate-900 font-medium">${res.plot_number || 'N/A'}</span>
                        </div>
                        ${res.compartment_number ? `
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                Compartment
                            </span>
                            <span class="text-slate-900 font-medium">${res.compartment_number}</span>
                        </div>
                        ` : ''}
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                Type
                            </span>
                            <span class="text-slate-900 font-medium">${res.reservation_type.charAt(0).toUpperCase() + res.reservation_type.slice(1)}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Total Amount
                            </span>
                            <span class="text-emerald-600 font-bold">₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Amount Paid
                            </span>
                            <span class="text-emerald-600 font-medium">₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Balance
                            </span>
                            <span class="font-medium ${balance > 0 ? 'text-amber-600' : 'text-emerald-600'}">
                                ₱${balance.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Reservation Date
                            </span>
                            <span class="text-slate-900 font-medium">${new Date(res.reservation_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                        </div>
                    </div>

                    ${res.intended_for ? `
                        <div class="mt-4 flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Intended For
                            </span>
                            <span class="text-slate-900 font-medium">${res.intended_for}</span>
                        </div>
                    ` : ''}

                    ${res.purpose ? `
                        <div class="mt-4 flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Purpose
                            </span>
                            <span class="text-slate-900 font-medium">${res.purpose}</span>
                        </div>
                    ` : ''}

                    ${res.rejection_reason ? `
                        <div class="mt-4 bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-start gap-3 text-rose-800">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5 shrink-0 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <strong class="font-semibold">Rejection Reason:</strong>
                                <p class="mt-1">${res.rejection_reason}</p>
                            </div>
                        </div>
                    ` : ''}

                    ${res.status === 'approved' && res.payment_status !== 'paid' ? `
                        <div class="card-actions mt-5">
                            <button onclick="openPaymentModal(${res.id}, ${res.total_amount}, ${res.amount_paid})" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Submit Payment
                            </button>
                        </div>
                    ` : ''}

                    ${res.payments && res.payments.length > 0 ? `
                        <div class="card-payments mt-6 pt-5 border-t border-slate-100">
                            <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-emerald-600"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Payment History
                            </h3>
                            ${res.payments.map(payment => `
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-3 flex flex-col sm:flex-row sm:items-center gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-emerald-700 font-bold text-lg">₱${parseFloat(payment.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500 mt-1">
                                            <span class="flex items-center gap-1.5">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                ${new Date(payment.payment_date).toLocaleString('en-PH')}
                                            </span>
                                            <span class="font-medium uppercase text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">${payment.payment_method.replace('_', ' ').toUpperCase()}</span>
                                            ${payment.reference_number ? `
                                            <span class="flex items-center gap-1.5">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                                                Ref: ${payment.reference_number}
                                            </span>
                                            ` : ''}
                                            ${payment.proof_of_payment ? `
                                            <span class="flex items-center gap-1.5">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                <a href="../uploads/payments/${payment.proof_of_payment}" target="_blank" class="text-emerald-600 hover:underline font-medium">View Proof</a>
                                            </span>
                                            ` : ''}
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide ${paymentStyles[payment.verification_status] || 'bg-slate-50 text-slate-700 border border-slate-200'}">${payment.verification_status}</span>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
                `;
            }).join('');
        }

        function renderListView(reservations) {
            const rows = reservations.map((res, idx) => {
                const total = parseFloat(res.total_amount);
                const paid = parseFloat(res.amount_paid);
                const balance = total - paid;
                const delay = Math.min(idx * 0.05, 0.4);
                const statusBadge = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase ${statusStyles[res.status] || 'bg-slate-100 text-slate-600'}">${res.status}</span>`;
                const payBadge = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase ${paymentStyles[res.payment_status] || 'bg-slate-100 text-slate-600'}">${res.payment_status}</span>`;
                const payBtn = res.status === 'approved' && res.payment_status !== 'paid'
                    ? `<button onclick="openPaymentModal(${res.id}, ${res.total_amount}, ${res.amount_paid})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition">Pay</button>`
                    : '<span class="text-xs text-slate-400">—</span>';
                return `<tr class="border-b border-slate-100 hover:bg-slate-50 transition" style="animation: fadeUp 0.4s ease both; animation-delay: ${delay}s;">
                    <td class="px-4 py-3"><span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">#${res.id}</span></td>
                    <td class="px-4 py-3 text-sm font-medium text-slate-900">${res.plot_number || 'N/A'}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">${res.compartment_number || '—'}</td>
                    <td class="px-4 py-3 text-sm text-slate-600 capitalize">${res.reservation_type || '—'}</td>
                    <td class="px-4 py-3 text-sm font-semibold text-emerald-600">₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-sm font-medium ${balance > 0 ? 'text-amber-600' : 'text-emerald-600'}">₱${balance.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">${new Date(res.reservation_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                    <td class="px-4 py-3">${statusBadge}</td>
                    <td class="px-4 py-3">${payBadge}</td>
                    <td class="px-4 py-3">${payBtn}</td>
                </tr>`;
            }).join('');

            return `<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden animate-fade">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-left">
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Plot</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Comp</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Paid</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Balance</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Payment</th>
                                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>`;
        }

        function openPaymentModal(reservationId, totalAmount, amountPaid) {
            currentReservation = { id: reservationId, totalAmount, amountPaid };
            const balance = totalAmount - amountPaid;

            document.getElementById('reservation_id').value = reservationId;
            document.getElementById('balanceInfo').textContent = `Remaining balance: ₱${balance.toFixed(2)}`;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
            document.getElementById('paymentForm').reset();
        }

        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const response = await fetch('../api/submit_payment.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message || 'Payment submitted successfully', 'success');
                    closePaymentModal();
                    loadReservations();
                } else {
                    showToast(data.message || 'Error submitting payment', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error submitting payment', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Payment';
            }
        });

        // Close modal on outside click
        document.getElementById('paymentModal').addEventListener('click', (e) => {
            if (e.target.id === 'paymentModal') closePaymentModal();
        });

        loadReservations();
    </script>
</body>
</html>
