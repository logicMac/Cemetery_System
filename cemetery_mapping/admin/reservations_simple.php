<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT 
        pr.*,
        v.full_name as visitor_name,
        v.email as visitor_email,
        v.phone as visitor_phone,
        ap.plot_number
    FROM plot_reservations pr
    JOIN visitors v ON pr.visitor_id = v.id
    LEFT JOIN available_plots ap ON pr.plot_id = ap.id
    WHERE 1=1
";

if ($status_filter !== 'all') {
    $query .= " AND pr.status = :status";
}

if (!empty($search)) {
    $query .= " AND (v.full_name LIKE :search OR v.email LIKE :search OR ap.plot_number LIKE :search)";
}

$query .= " ORDER BY pr.id DESC";

$stmt = $pdo->prepare($query);

if ($status_filter !== 'all') {
    $stmt->bindValue(':status', $status_filter);
}

if (!empty($search)) {
    $stmt->bindValue(':search', '%' . $search . '%');
}

$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM plot_reservations")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM plot_reservations WHERE status = 'pending'")->fetchColumn(),
    'approved' => $pdo->query("SELECT COUNT(*) FROM plot_reservations WHERE status = 'approved'")->fetchColumn(),
    'rejected' => $pdo->query("SELECT COUNT(*) FROM plot_reservations WHERE status = 'rejected'")->fetchColumn(),
];

// Get payments for each reservation
foreach ($reservations as &$res) {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE reservation_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$res['id']]);
    $res['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate payment summary
    $res['total_paid'] = 0;
    foreach ($res['payments'] as $payment) {
        if ($payment['verification_status'] === 'verified') {
            $res['total_paid'] += $payment['amount'];
        }
    }
    $res['balance'] = $res['total_amount'] - $res['total_paid'];
}
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
.admin-layout { background: #ffffff; }
.admin-layout::after { display: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade { animation: fadeUp 0.5s ease both; }
button svg, a svg, button i, a i { pointer-events: none; }
</style>

<!-- Page Header -->
<div class="flex items-center gap-3 mb-6 animate-fade">
    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
        <i data-lucide="calendar-check" class="w-5 h-5"></i>
    </div>
    <div>
        <h2 class="text-xl font-bold text-slate-900">Reservations</h2>
        <p class="text-sm text-slate-500">Manage plot reservations and payments</p>
    </div>
</div>

<!-- Statistics Overview -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 animate-fade">
    <a href="?status=all" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-emerald-300 hover:shadow-md transition <?php echo $status_filter === 'all' ? 'ring-2 ring-emerald-200 bg-emerald-50/40' : ''; ?>">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="layers" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo $stats['total']; ?></div><div class="text-xs text-slate-500">Total</div></div>
        </div>
    </a>
    <a href="?status=pending" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-emerald-300 hover:shadow-md transition <?php echo $status_filter === 'pending' ? 'ring-2 ring-emerald-200 bg-emerald-50/40' : ''; ?>">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center"><i data-lucide="clock" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo $stats['pending']; ?></div><div class="text-xs text-slate-500">Pending</div></div>
        </div>
    </a>
    <a href="?status=approved" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-emerald-300 hover:shadow-md transition <?php echo $status_filter === 'approved' ? 'ring-2 ring-emerald-200 bg-emerald-50/40' : ''; ?>">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo $stats['approved']; ?></div><div class="text-xs text-slate-500">Approved</div></div>
        </div>
    </a>
    <a href="?status=rejected" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-emerald-300 hover:shadow-md transition <?php echo $status_filter === 'rejected' ? 'ring-2 ring-emerald-200 bg-emerald-50/40' : ''; ?>">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center"><i data-lucide="x-circle" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo $stats['rejected']; ?></div><div class="text-xs text-slate-500">Rejected</div></div>
        </div>
    </a>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6 animate-fade">
    <div class="flex gap-3 flex-wrap items-center">
        <div class="flex-1 min-w-[250px] relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" id="searchInput" placeholder="Search by name, email, or plot number..." value="<?php echo htmlspecialchars($search); ?>" class="w-full rounded-lg border border-slate-300 pl-9 pr-9 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
            <div id="searchSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                <div class="w-4 h-4 border-2 border-emerald-200 border-t-emerald-600 rounded-full animate-spin"></div>
            </div>
        </div>
        <!-- View Toggle -->
        <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-lg">
            <button type="button" id="viewCard" onclick="switchView('card')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700">
                <i data-lucide="layout-grid" class="w-4 h-4"></i> Card
            </button>
            <button type="button" id="viewList" onclick="switchView('list')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm">
                <i data-lucide="list" class="w-4 h-4"></i> List
            </button>
        </div>
        <?php if (!empty($search) || $status_filter !== 'all'): ?>
        <button onclick="clearFilters()" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
            <i data-lucide="x" class="w-4 h-4"></i> Clear
        </button>
        <?php endif; ?>
    </div>
    <div id="resultCount" class="text-xs text-slate-400 mt-3"></div>
</div>

<script>
let currentView = 'list';
let searchTimer = null;

function switchView(view) {
    currentView = view;
    const btnCard = document.getElementById('viewCard');
    const btnList = document.getElementById('viewList');
    if (view === 'card') {
        btnCard.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm';
        btnList.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700';
    } else {
        btnList.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm';
        btnCard.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700';
    }
    renderReservations(window._lastReservations || []);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    window.location.href = 'reservations_simple.php';
}

function ajaxSearch() {
    const search = document.getElementById('searchInput').value.trim();
    const status = '<?php echo $status_filter; ?>';
    const spinner = document.getElementById('searchSpinner');
    const container = document.getElementById('reservations');

    spinner.classList.remove('hidden');

    fetch(`../api/search_reservations.php?status=${encodeURIComponent(status)}&search=${encodeURIComponent(search)}`)
        .then(r => r.json())
        .then(data => {
            spinner.classList.add('hidden');
            if (data.success) {
                window._lastReservations = data.reservations;
                renderReservations(data.reservations);
                const countEl = document.getElementById('resultCount');
                countEl.textContent = data.count + ' reservation' + (data.count !== 1 ? 's' : '') + ' found' + (search ? ' for "' + search + '"' : '');
            } else {
                container.innerHTML = '<div class="text-center py-16 bg-white rounded-2xl border border-slate-200"><p class="text-sm text-rose-500">Error loading reservations.</p></div>';
            }
        })
        .catch(() => {
            spinner.classList.add('hidden');
            container.innerHTML = '<div class="text-center py-16 bg-white rounded-2xl border border-slate-200"><p class="text-sm text-rose-500">Network error.</p></div>';
        });
}

document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(ajaxSearch, 300);
});
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { clearTimeout(searchTimer); ajaxSearch(); }
});

function renderReservations(reservations) {
    const container = document.getElementById('reservations');
    if (!reservations || reservations.length === 0) {
        container.innerHTML = `
            <div class="text-center py-16 bg-white rounded-2xl border border-slate-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                    <i data-lucide="calendar-x" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-semibold text-slate-700 mb-1">No Reservations Found</h3>
                <p class="text-sm text-slate-500">Try a different search or clear filters.</p>
            </div>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    if (currentView === 'card') {
        container.className = 'space-y-5';
        container.innerHTML = reservations.map(r => renderCard(r)).join('');
    } else {
        container.className = '';
        container.innerHTML = renderListView(reservations);
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function statusBadge(status) {
    const styles = {
        pending: 'bg-amber-100 text-amber-700 border-amber-200',
        approved: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        rejected: 'bg-rose-100 text-rose-700 border-rose-200',
    };
    const icons = { pending: 'clock', approved: 'check', rejected: 'x' };
    const style = styles[status] || 'bg-slate-100 text-slate-700 border-slate-200';
    const icon = icons[status] || 'circle';
    return `<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wide border ${style}"><i data-lucide="${icon}" class="w-3.5 h-3.5"></i> ${status}</span>`;
}

function esc(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }

function fmtMoney(v) { return '₱' + Number(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

function fmtDate(s) {
    if (!s) return '';
    const d = new Date(s);
    return d.toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) + ' ' + d.toLocaleTimeString('en-US', {hour:'numeric', minute:'2-digit', hour12:true});
}

function renderCard(r) {
    const paid = r.total_paid || 0;
    const balance = r.balance !== undefined ? r.balance : (parseFloat(r.total_amount) - paid);
    let paymentsHtml = '';
    if (r.payments && r.payments.length > 0) {
        paymentsHtml = '<div class="mt-5 pt-5 border-t border-slate-100"><h4 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2"><i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i> Payment History</h4>';
        r.payments.forEach(p => {
            const payStyles = {pending:'bg-amber-100 text-amber-700 border-amber-200', verified:'bg-emerald-100 text-emerald-700 border-emerald-200', rejected:'bg-rose-100 text-rose-700 border-rose-200'};
            const payStyle = payStyles[p.verification_status] || 'bg-slate-100 text-slate-700 border-slate-200';
            let verifyBtns = '';
            if (p.verification_status === 'pending') {
                verifyBtns = `<div class="flex gap-2 mt-3">
                    <form method="POST" action="process_payment.php" class="inline"><input type="hidden" name="payment_id" value="${p.id}"><input type="hidden" name="action" value="verify"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-2 transition"><i data-lucide="check" class="w-3.5 h-3.5"></i> Verify</button></form>
                    <form method="POST" action="process_payment.php" class="inline"><input type="hidden" name="payment_id" value="${p.id}"><input type="hidden" name="action" value="reject"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-semibold px-3 py-2 transition"><i data-lucide="x" class="w-3.5 h-3.5"></i> Reject</button></form>
                </div>`;
            }
            let proofLink = '';
            if (p.proof_of_payment) {
                proofLink = `<a href="../uploads/payments/${esc(p.proof_of_payment)}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-emerald-600 hover:text-emerald-700 font-medium"><i data-lucide="paperclip" class="w-3.5 h-3.5"></i> View Proof of Payment</a>`;
            }
            paymentsHtml += `<div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-3">
                <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
                    <div class="flex items-center gap-3 flex-wrap"><strong class="text-base text-slate-900">${fmtMoney(p.amount)}</strong><span class="text-sm text-slate-500">${esc(p.payment_method ? p.payment_method.charAt(0).toUpperCase()+p.payment_method.slice(1) : '')}</span>${p.reference_number ? `<span class="text-xs text-slate-400">Ref: ${esc(p.reference_number)}</span>` : ''}</div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase border ${payStyle}">${p.verification_status}</span>
                </div>
                <div class="text-xs text-slate-400 mb-2">Submitted: ${fmtDate(p.payment_date)}</div>
                ${proofLink}
                ${verifyBtns}
            </div>`;
        });
        paymentsHtml += '</div>';
    }

    let actionBtns = '';
    if (r.status === 'pending') {
        actionBtns = `<div class="flex gap-3 flex-wrap mt-4">
            <form method="POST" action="process_reservation.php" class="inline"><input type="hidden" name="reservation_id" value="${r.id}"><input type="hidden" name="action" value="approve"><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition"><i data-lucide="check" class="w-4 h-4"></i> Approve</button></form>
            <form method="POST" action="process_reservation.php" class="inline"><input type="hidden" name="reservation_id" value="${r.id}"><input type="hidden" name="action" value="reject"><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-sm font-semibold px-4 py-2.5 transition"><i data-lucide="x" class="w-4 h-4"></i> Reject</button></form>
        </div>`;
    }

    let rejectReason = '';
    if (r.rejection_reason) {
        rejectReason = `<div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200"><div class="text-xs font-semibold text-rose-700 uppercase mb-1">Rejection Reason</div><p class="text-sm text-rose-800">${esc(r.rejection_reason)}</p></div>`;
    }

    let intendedFor = r.intended_for ? `<div class="bg-slate-50 rounded-lg p-3 mb-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Intended For</div><div class="text-sm font-medium text-slate-800">${esc(r.intended_for)}</div></div>` : '';
    let purpose = r.purpose ? `<div class="bg-slate-50 rounded-lg p-3 mb-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Purpose</div><div class="text-sm text-slate-700">${esc(r.purpose)}</div></div>` : '';
    let compartment = r.compartment_id ? `<div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Compartment</div><div class="text-sm font-semibold text-slate-800">#${r.compartment_id}</div></div>` : '';

    return `<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-emerald-300 hover:shadow-md transition animate-fade">
        <div class="flex justify-between items-start mb-5 pb-4 border-b border-slate-100 flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-bold">#${r.id}</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">${esc(r.visitor_name)}</h3>
                    <p class="text-sm text-slate-500 flex items-center gap-3 flex-wrap mt-0.5">
                        <span class="flex items-center gap-1.5"><i data-lucide="mail" class="w-3.5 h-3.5"></i> ${esc(r.visitor_email)}</span>
                        ${r.visitor_phone ? `<span class="flex items-center gap-1.5"><i data-lucide="phone" class="w-3.5 h-3.5"></i> ${esc(r.visitor_phone)}</span>` : ''}
                    </p>
                </div>
            </div>
            ${statusBadge(r.status)}
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Plot</div><div class="text-sm font-semibold text-slate-800">${r.plot_number || 'N/A'}</div></div>
            ${compartment}
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Type</div><div class="text-sm font-semibold text-slate-800">${esc(r.reservation_type ? r.reservation_type.charAt(0).toUpperCase()+r.reservation_type.slice(1) : '')}</div></div>
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Total</div><div class="text-sm font-semibold text-slate-800">${fmtMoney(r.total_amount)}</div></div>
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Paid</div><div class="text-sm font-semibold text-emerald-600">${fmtMoney(paid)}</div></div>
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Balance</div><div class="text-sm font-semibold ${balance > 0 ? 'text-amber-600' : 'text-emerald-600'}">${fmtMoney(balance)}</div></div>
        </div>
        ${intendedFor}
        ${purpose}
        ${actionBtns}
        ${rejectReason}
        ${paymentsHtml}
        <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400 flex items-center gap-2 flex-wrap">
            <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Reservation Date: ${fmtDate(r.reservation_date)}
            ${r.approved_date ? `<span class="text-slate-300">•</span><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Processed: ${fmtDate(r.approved_date)}` : ''}
        </div>
    </div>`;
}

function renderListView(reservations) {
    let rows = reservations.map(r => {
        const paid = r.total_paid || 0;
        const balance = r.balance !== undefined ? r.balance : (parseFloat(r.total_amount) - paid);

        // Build action buttons
        let actions = `<div class="flex gap-1.5 items-center">`;
        // View button (always)
        actions += `<button type="button" onclick="showDetailModal(${r.id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="View Details"><i data-lucide="eye" class="w-4 h-4"></i></button>`;
        // Approve/Reject for pending reservations
        if (r.status === 'pending') {
            actions += `<form method="POST" action="process_reservation.php" class="inline"><input type="hidden" name="reservation_id" value="${r.id}"><input type="hidden" name="action" value="approve"><button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition" title="Approve"><i data-lucide="check" class="w-4 h-4"></i></button></form>`;
            actions += `<form method="POST" action="process_reservation.php" class="inline"><input type="hidden" name="reservation_id" value="${r.id}"><input type="hidden" name="action" value="reject"><button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 transition" title="Reject"><i data-lucide="x" class="w-4 h-4"></i></button></form>`;
        }
        // Payment indicator badge if there are pending payments
        if (r.payments && r.payments.length > 0) {
            const pendingPayments = r.payments.filter(p => p.verification_status === 'pending');
            if (pendingPayments.length > 0) {
                actions += `<button type="button" onclick="showDetailModal(${r.id})" class="inline-flex items-center justify-center px-2 h-8 rounded-lg bg-amber-100 text-amber-700 text-xs font-bold transition hover:bg-amber-200" title="${pendingPayments.length} pending payment(s)"><i data-lucide="credit-card" class="w-3.5 h-3.5"></i> ${pendingPayments.length}</button>`;
            }
            // Receipt button — opens proof of payment in a modal
            const paymentsWithProof = r.payments.filter(p => p.proof_of_payment);
            if (paymentsWithProof.length > 0) {
                if (paymentsWithProof.length === 1) {
                    actions += `<button type="button" onclick="showReceiptModal('${esc(paymentsWithProof[0].proof_of_payment)}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="View Payment Receipt"><i data-lucide="file-text" class="w-4 h-4"></i></button>`;
                } else {
                    actions += `<button type="button" onclick="showDetailModal(${r.id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="${paymentsWithProof.length} payment receipts"><i data-lucide="file-text" class="w-4 h-4"></i></button>`;
                }
            }
            console.log('Reservation', r.id, 'payments:', r.payments, 'with proof:', paymentsWithProof);
        }
        actions += `</div>`;

        const statusStyles = {pending:'bg-amber-100 text-amber-700', approved:'bg-emerald-100 text-emerald-700', rejected:'bg-rose-100 text-rose-700'};
        const sStyle = statusStyles[r.status] || 'bg-slate-100 text-slate-700';
        return `<tr class="border-b border-slate-100 hover:bg-slate-50 transition">
            <td class="px-4 py-3"><span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full bg-emerald-600 text-white text-xs font-bold">#${r.id}</span></td>
            <td class="px-4 py-3"><div class="text-sm font-semibold text-slate-900">${esc(r.visitor_name)}</div><div class="text-xs text-slate-400">${esc(r.visitor_email)}</div></td>
            <td class="px-4 py-3 text-sm text-slate-700">${r.plot_number || 'N/A'}</td>
            <td class="px-4 py-3 text-sm text-slate-700">${esc(r.reservation_type ? r.reservation_type.charAt(0).toUpperCase()+r.reservation_type.slice(1) : '')}</td>
            <td class="px-4 py-3 text-sm font-semibold text-slate-800">${fmtMoney(r.total_amount)}</td>
            <td class="px-4 py-3 text-sm font-semibold text-emerald-600">${fmtMoney(paid)}</td>
            <td class="px-4 py-3 text-sm font-semibold ${balance > 0 ? 'text-amber-600' : 'text-emerald-600'}">${fmtMoney(balance)}</td>
            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase ${sStyle}">${r.status}</span></td>
            <td class="px-4 py-3 text-xs text-slate-400">${fmtDate(r.reservation_date)}</td>
            <td class="px-4 py-3">${actions}</td>
        </tr>`;
    }).join('');

    return `<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-left">
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Visitor</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Plot</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Paid</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Balance</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    </div>`;
}

// Detail modal
function showDetailModal(id) {
    const reservations = window._lastReservations || [];
    const r = reservations.find(x => x.id == id);
    if (!r) return;

    const paid = r.total_paid || 0;
    const balance = r.balance !== undefined ? r.balance : (parseFloat(r.total_amount) - paid);

    let paymentsHtml = '';
    if (r.payments && r.payments.length > 0) {
        paymentsHtml = '<div class="mt-4 pt-4 border-t border-slate-100"><h4 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2"><i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i> Payment History</h4>';
        r.payments.forEach(p => {
            const payStyles = {pending:'bg-amber-100 text-amber-700 border-amber-200', verified:'bg-emerald-100 text-emerald-700 border-emerald-200', rejected:'bg-rose-100 text-rose-700 border-rose-200'};
            const payStyle = payStyles[p.verification_status] || 'bg-slate-100 text-slate-700 border-slate-200';
            let verifyBtns = '';
            if (p.verification_status === 'pending') {
                verifyBtns = `<div class="flex gap-2 mt-3">
                    <form method="POST" action="process_payment.php" class="inline"><input type="hidden" name="payment_id" value="${p.id}"><input type="hidden" name="action" value="verify"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-2 transition"><i data-lucide="check" class="w-3.5 h-3.5"></i> Verify</button></form>
                    <form method="POST" action="process_payment.php" class="inline"><input type="hidden" name="payment_id" value="${p.id}"><input type="hidden" name="action" value="reject"><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-semibold px-3 py-2 transition"><i data-lucide="x" class="w-3.5 h-3.5"></i> Reject</button></form>
                </div>`;
            }
            let proofLink = '';
            if (p.proof_of_payment) {
                proofLink = `<a href="../uploads/payments/${esc(p.proof_of_payment)}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-emerald-600 hover:text-emerald-700 font-medium"><i data-lucide="paperclip" class="w-3.5 h-3.5"></i> View Proof of Payment</a>`;
            }
            paymentsHtml += `<div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-3">
                <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
                    <div class="flex items-center gap-3 flex-wrap"><strong class="text-base text-slate-900">${fmtMoney(p.amount)}</strong><span class="text-sm text-slate-500">${esc(p.payment_method ? p.payment_method.charAt(0).toUpperCase()+p.payment_method.slice(1) : '')}</span>${p.reference_number ? `<span class="text-xs text-slate-400">Ref: ${esc(p.reference_number)}</span>` : ''}</div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase border ${payStyle}">${p.verification_status}</span>
                </div>
                <div class="text-xs text-slate-400 mb-2">Submitted: ${fmtDate(p.payment_date)}</div>
                ${proofLink}
                ${verifyBtns}
            </div>`;
        });
        paymentsHtml += '</div>';
    }

    let actionBtns = '';
    if (r.status === 'pending') {
        actionBtns = `<div class="flex gap-3 flex-wrap mt-4">
            <form method="POST" action="process_reservation.php" class="inline"><input type="hidden" name="reservation_id" value="${r.id}"><input type="hidden" name="action" value="approve"><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition"><i data-lucide="check" class="w-4 h-4"></i> Approve</button></form>
            <form method="POST" action="process_reservation.php" class="inline"><input type="hidden" name="reservation_id" value="${r.id}"><input type="hidden" name="action" value="reject"><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-sm font-semibold px-4 py-2.5 transition"><i data-lucide="x" class="w-4 h-4"></i> Reject</button></form>
        </div>`;
    }

    let rejectReason = '';
    if (r.rejection_reason) {
        rejectReason = `<div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200"><div class="text-xs font-semibold text-rose-700 uppercase mb-1">Rejection Reason</div><p class="text-sm text-rose-800">${esc(r.rejection_reason)}</p></div>`;
    }

    const statusStyles = {pending:'bg-amber-100 text-amber-700 border-amber-200', approved:'bg-emerald-100 text-emerald-700 border-emerald-200', rejected:'bg-rose-100 text-rose-700 border-rose-200'};
    const statusIcons = {pending: 'clock', approved: 'check', rejected: 'x'};
    const sStyle = statusStyles[r.status] || 'bg-slate-100 text-slate-700 border-slate-200';
    const sIcon = statusIcons[r.status] || 'circle';

    const modalHtml = `
        <div class="flex justify-between items-start mb-5 pb-4 border-b border-slate-100 flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-bold">#${r.id}</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">${esc(r.visitor_name)}</h3>
                    <p class="text-sm text-slate-500 flex items-center gap-3 flex-wrap mt-0.5">
                        <span class="flex items-center gap-1.5"><i data-lucide="mail" class="w-3.5 h-3.5"></i> ${esc(r.visitor_email)}</span>
                        ${r.visitor_phone ? `<span class="flex items-center gap-1.5"><i data-lucide="phone" class="w-3.5 h-3.5"></i> ${esc(r.visitor_phone)}</span>` : ''}
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wide border ${sStyle}"><i data-lucide="${sIcon}" class="w-3.5 h-3.5"></i> ${r.status}</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Plot</div><div class="text-sm font-semibold text-slate-800">${r.plot_number || 'N/A'}</div></div>
            ${r.compartment_id ? `<div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Compartment</div><div class="text-sm font-semibold text-slate-800">#${r.compartment_id}</div></div>` : ''}
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Type</div><div class="text-sm font-semibold text-slate-800">${esc(r.reservation_type ? r.reservation_type.charAt(0).toUpperCase()+r.reservation_type.slice(1) : '')}</div></div>
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Total</div><div class="text-sm font-semibold text-slate-800">${fmtMoney(r.total_amount)}</div></div>
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Paid</div><div class="text-sm font-semibold text-emerald-600">${fmtMoney(paid)}</div></div>
            <div class="bg-slate-50 rounded-lg p-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Balance</div><div class="text-sm font-semibold ${balance > 0 ? 'text-amber-600' : 'text-emerald-600'}">${fmtMoney(balance)}</div></div>
        </div>
        ${r.intended_for ? `<div class="bg-slate-50 rounded-lg p-3 mb-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Intended For</div><div class="text-sm font-medium text-slate-800">${esc(r.intended_for)}</div></div>` : ''}
        ${r.purpose ? `<div class="bg-slate-50 rounded-lg p-3 mb-3"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Purpose</div><div class="text-sm text-slate-700">${esc(r.purpose)}</div></div>` : ''}
        ${actionBtns}
        ${rejectReason}
        ${paymentsHtml}
        <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400 flex items-center gap-2 flex-wrap">
            <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Reservation Date: ${fmtDate(r.reservation_date)}
            ${r.approved_date ? `<span class="text-slate-300">•</span><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Processed: ${fmtDate(r.approved_date)}` : ''}
        </div>
    `;

    // Build modal
    const overlay = document.createElement('div');
    overlay.id = 'detailModalOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;transition:opacity 0.2s ease;';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:20px;max-width:640px;width:100%;max-height:85vh;overflow-y:auto;padding:32px;box-shadow:0 24px 60px rgba(0,0,0,0.2);transform:scale(0.95);transition:transform 0.2s ease;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3 style="font-size:1.1rem;font-weight:700;color:#0f172a;">Reservation Details</h3>
                <button type="button" onclick="closeDetailModal()" style="width:32px;height:32px;border-radius:8px;background:#f1f5f9;color:#64748b;display:flex;align-items:center;justify-content:center;transition:background 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            ${modalHtml}
        </div>
    `;
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => { overlay.style.opacity = '1'; overlay.querySelector('div').style.transform = 'scale(1)'; });
    if (typeof lucide !== 'undefined') lucide.createIcons();

    overlay.addEventListener('click', e => { if (e.target === overlay) closeDetailModal(); });
}

function closeDetailModal() {
    const overlay = document.getElementById('detailModalOverlay');
    if (overlay) {
        overlay.style.opacity = '0';
        overlay.querySelector('div').style.transform = 'scale(0.95)';
        setTimeout(() => overlay.remove(), 200);
        document.body.style.overflow = '';
    }
}

function showReceiptModal(filename) {
    const overlay = document.createElement('div');
    overlay.id = 'receiptModalOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;transition:opacity 0.2s ease;';
    const receiptUrl = '../uploads/payments/' + esc(filename);
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:20px;max-width:720px;width:100%;max-height:90vh;overflow-y:auto;padding:24px;box-shadow:0 24px 60px rgba(0,0,0,0.3);transform:scale(0.95);transition:transform 0.2s ease;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="font-size:1.1rem;font-weight:700;color:#0f172a;">Payment Receipt</h3>
                <button type="button" onclick="closeReceiptModal()" style="width:32px;height:32px;border-radius:8px;background:#f1f5f9;color:#64748b;display:flex;align-items:center;justify-content:center;transition:background 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div id="receiptImageContainer" style="background:#f8fafc;border-radius:12px;padding:12px;border:1px solid #e2e8f0;">
                <img id="receiptImage" src="${receiptUrl}" alt="Payment Receipt" onerror="document.getElementById('receiptImage').style.display='none'; document.getElementById('receiptError').style.display='block';" style="max-width:100%;height:auto;border-radius:8px;display:block;margin:0 auto;max-height:70vh;object-fit:contain;">
                <div id="receiptError" style="display:none;text-align:center;padding:40px 20px;color:#ef4444;">
                    <p style="font-weight:600;margin-bottom:8px;">Receipt image not found</p>
                    <p style="font-size:0.85rem;color:#64748b;">Path: <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">${receiptUrl}</code></p>
                    <p style="font-size:0.85rem;color:#64748b;margin-top:8px;">Make sure the file exists in the uploads/payments folder on your server.</p>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
                <a href="${receiptUrl}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">Open in New Tab</a>
                <button type="button" onclick="closeReceiptModal()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => { overlay.style.opacity = '1'; overlay.querySelector('div').style.transform = 'scale(1)'; });
    if (typeof lucide !== 'undefined') lucide.createIcons();
    overlay.addEventListener('click', e => { if (e.target === overlay) closeReceiptModal(); });
}

function closeReceiptModal() {
    const overlay = document.getElementById('receiptModalOverlay');
    if (overlay) {
        overlay.style.opacity = '0';
        overlay.querySelector('div').style.transform = 'scale(0.95)';
        setTimeout(() => overlay.remove(), 200);
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDetailModal(); closeReceiptModal(); } });

// Store initial PHP-rendered reservations for view switching
window._lastReservations = <?php echo json_encode(array_map(function($r) { return $r; }, $reservations)); ?>;
</script>

<?php if (isset($_GET['success'])): ?>
<div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2 animate-fade">
    <i data-lucide="check-circle" class="w-5 h-5"></i> <?php echo htmlspecialchars($_GET['success']); ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2 animate-fade">
    <i data-lucide="x-circle" class="w-5 h-5"></i> <?php echo htmlspecialchars($_GET['error']); ?>
</div>
<?php endif; ?>

<div id="reservations" class="space-y-5">
    <div class="text-center py-10 text-slate-400 text-sm">Loading reservations...</div>
</div>

        </main>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
            // Render in default list view using cached data
            if (window._lastReservations && window._lastReservations.length > 0) {
                renderReservations(window._lastReservations);
            }
            // Show initial count
            var initialCount = window._lastReservations ? window._lastReservations.length : 0;
            var countEl = document.getElementById('resultCount');
            if (countEl && initialCount > 0) {
                countEl.textContent = initialCount + ' reservation' + (initialCount !== 1 ? 's' : '') + ' total';
            }
        });
    </script>
</body>
</html>
