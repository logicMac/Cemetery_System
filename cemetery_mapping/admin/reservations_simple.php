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
            <input type="text" id="searchInput" placeholder="Search by name, email, or plot number..." value="<?php echo htmlspecialchars($search); ?>" onkeypress="if(event.key === 'Enter') performSearch()" class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
        </div>
        <button onclick="performSearch()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition">
            <i data-lucide="search" class="w-4 h-4"></i> Search
        </button>
        <?php if (!empty($search) || $status_filter !== 'all'): ?>
        <button onclick="window.location.href='reservations_simple.php'" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
            <i data-lucide="x" class="w-4 h-4"></i> Clear Filters
        </button>
        <?php endif; ?>
    </div>
</div>

<script>
function performSearch() {
    const search = document.getElementById('searchInput').value;
    const status = '<?php echo $status_filter; ?>';
    window.location.href = `?status=${status}&search=${encodeURIComponent(search)}`;
}
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
    <?php if (empty($reservations)): ?>
        <div class="text-center py-16 bg-white rounded-2xl border border-slate-200">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                <i data-lucide="calendar-x" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-700 mb-1">No Reservations Found</h3>
            <p class="text-sm text-slate-500">
                <?php if (!empty($search)): ?>
                    No reservations match your search criteria.
                <?php elseif ($status_filter !== 'all'): ?>
                    No <?php echo $status_filter; ?> reservations at the moment.
                <?php else: ?>
                    There are no reservations yet.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <?php foreach ($reservations as $res): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-emerald-300 hover:shadow-md transition animate-fade">
                <!-- Header -->
                <div class="flex justify-between items-start mb-5 pb-4 border-b border-slate-100 flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-bold">#<?php echo $res['id']; ?></span>
                        <div>
                            <h3 class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($res['visitor_name']); ?></h3>
                            <p class="text-sm text-slate-500 flex items-center gap-3 flex-wrap mt-0.5">
                                <span class="flex items-center gap-1.5"><i data-lucide="mail" class="w-3.5 h-3.5"></i> <?php echo htmlspecialchars($res['visitor_email']); ?></span>
                                <?php if ($res['visitor_phone']): ?>
                                    <span class="flex items-center gap-1.5"><i data-lucide="phone" class="w-3.5 h-3.5"></i> <?php echo htmlspecialchars($res['visitor_phone']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php
                    $statusStyles = [
                        'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                        'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                        'rejected' => 'bg-rose-100 text-rose-700 border-rose-200',
                    ];
                    $statusIcons = ['pending' => 'clock', 'approved' => 'check', 'rejected' => 'x'];
                    $style = $statusStyles[$res['status']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                    $icon = $statusIcons[$res['status']] ?? 'circle';
                    ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wide border <?php echo $style; ?>">
                        <i data-lucide="<?php echo $icon; ?>" class="w-3.5 h-3.5"></i> <?php echo $res['status']; ?>
                    </span>
                </div>

                <!-- Info grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Plot</div>
                        <div class="text-sm font-semibold text-slate-800"><?php echo $res['plot_number'] ?? 'N/A'; ?></div>
                    </div>
                    <?php if ($res['compartment_id']): ?>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Compartment</div>
                        <div class="text-sm font-semibold text-slate-800">#<?php echo $res['compartment_id']; ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Type</div>
                        <div class="text-sm font-semibold text-slate-800"><?php echo ucfirst($res['reservation_type']); ?></div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Total</div>
                        <div class="text-sm font-semibold text-slate-800">₱<?php echo number_format($res['total_amount'], 2); ?></div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Paid</div>
                        <div class="text-sm font-semibold text-emerald-600">₱<?php echo number_format($res['total_paid'], 2); ?></div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Balance</div>
                        <div class="text-sm font-semibold <?php echo $res['balance'] > 0 ? 'text-amber-600' : 'text-emerald-600'; ?>">₱<?php echo number_format($res['balance'], 2); ?></div>
                    </div>
                </div>

                <?php if ($res['intended_for']): ?>
                <div class="bg-slate-50 rounded-lg p-3 mb-3">
                    <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Intended For</div>
                    <div class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($res['intended_for']); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($res['purpose']): ?>
                <div class="bg-slate-50 rounded-lg p-3 mb-3">
                    <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Purpose</div>
                    <div class="text-sm text-slate-700"><?php echo htmlspecialchars($res['purpose']); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($res['status'] === 'pending'): ?>
                <div class="flex gap-3 flex-wrap mt-4">
                    <form method="POST" action="process_reservation.php" class="inline">
                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                            <i data-lucide="check" class="w-4 h-4"></i> Approve
                        </button>
                    </form>
                    <form method="POST" action="process_reservation.php" class="inline">
                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-sm font-semibold px-4 py-2.5 transition">
                            <i data-lucide="x" class="w-4 h-4"></i> Reject
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($res['rejection_reason']): ?>
                <div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200">
                    <div class="text-xs font-semibold text-rose-700 uppercase mb-1">Rejection Reason</div>
                    <p class="text-sm text-rose-800"><?php echo htmlspecialchars($res['rejection_reason']); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($res['payments'])): ?>
                <div class="mt-5 pt-5 border-t border-slate-100">
                    <h4 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2"><i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i> Payment History</h4>
                    <?php foreach ($res['payments'] as $payment): ?>
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-3">
                        <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
                            <div class="flex items-center gap-3 flex-wrap">
                                <strong class="text-base text-slate-900">₱<?php echo number_format($payment['amount'], 2); ?></strong>
                                <span class="text-sm text-slate-500"><?php echo ucfirst($payment['payment_method']); ?></span>
                                <?php if ($payment['reference_number']): ?>
                                <span class="text-xs text-slate-400">Ref: <?php echo htmlspecialchars($payment['reference_number']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $payStyles = [
                                'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                'verified' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'rejected' => 'bg-rose-100 text-rose-700 border-rose-200',
                            ];
                            $payStyle = $payStyles[$payment['verification_status']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase border <?php echo $payStyle; ?>"><?php echo $payment['verification_status']; ?></span>
                        </div>
                        <div class="text-xs text-slate-400 mb-2">Submitted: <?php echo date('M d, Y g:i A', strtotime($payment['payment_date'])); ?></div>
                        <?php if ($payment['proof_of_payment']): ?>
                        <a href="../uploads/payments/<?php echo $payment['proof_of_payment']; ?>" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                            <i data-lucide="paperclip" class="w-3.5 h-3.5"></i> View Proof of Payment
                        </a>
                        <?php endif; ?>
                        <?php if ($payment['verification_status'] === 'pending'): ?>
                        <div class="flex gap-2 mt-3">
                            <form method="POST" action="process_payment.php" class="inline">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="action" value="verify">
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-2 transition">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i> Verify
                                </button>
                            </form>
                            <form method="POST" action="process_payment.php" class="inline">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-semibold px-3 py-2 transition">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i> Reject
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400 flex items-center gap-2 flex-wrap">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    Reservation Date: <?php echo date('M d, Y g:i A', strtotime($res['reservation_date'])); ?>
                    <?php if ($res['approved_date']): ?>
                        <span class="text-slate-300">•</span>
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Processed: <?php echo date('M d, Y g:i A', strtotime($res['approved_date'])); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        </main>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
