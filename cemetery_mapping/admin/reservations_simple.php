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
    /* Enhanced Reservation Page Styles */
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-box {
        background: rgba(10, 10, 20, 0.75);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .stat-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
        border-color: rgba(102, 126, 234, 0.3);
    }
    
    .stat-box.active {
        border-color: rgba(102, 126, 234, 0.5);
        background: rgba(102, 126, 234, 0.1);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 8px;
    }
    
    .stat-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Filter Bar */
    .filter-bar {
        background: rgba(10, 10, 20, 0.75);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .search-box-enhanced {
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    
    .search-box-enhanced input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        color: white;
        font-size: 0.95rem;
    }
    
    .search-box-enhanced svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: rgba(255, 255, 255, 0.4);
    }
    
    /* Reservation Card Enhanced */
    .reservation-card {
        background: rgba(10, 10, 20, 0.75);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }
    
    .reservation-card:hover {
        border-color: rgba(102, 126, 234, 0.3);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        transform: translateY(-2px);
    }
    
    .reservation-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .reservation-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .reservation-id-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .status-pending { 
        background: rgba(251, 191, 36, 0.15); 
        color: #fbbf24; 
        border: 1px solid rgba(251, 191, 36, 0.3);
    }
    
    .status-approved { 
        background: rgba(34, 197, 94, 0.15); 
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    
    .status-rejected { 
        background: rgba(239, 68, 68, 0.15); 
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    .status-verified { 
        background: rgba(34, 197, 94, 0.15); 
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .info-item {
        background: rgba(255, 255, 255, 0.03);
        padding: 12px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    
    .info-label {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    
    .info-value {
        font-weight: 600;
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.95);
    }
    
    .payment-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .payment-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }
    
    .payment-card:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(102, 126, 234, 0.3);
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 16px;
    }
    
    .btn-approve {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-approve:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
    }
    
    .btn-reject {
        background: rgba(239, 68, 68, 0.2);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-reject:hover {
        background: rgba(239, 68, 68, 0.3);
        transform: translateY(-2px);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: rgba(10, 10, 20, 0.75);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    
    .empty-state svg {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        opacity: 0.5;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .stats-overview {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .stat-box {
            padding: 16px;
        }
        
        .stat-number {
            font-size: 2rem;
        }
        
        .filter-bar {
            flex-direction: column;
            padding: 16px;
        }
        
        .search-box-enhanced {
            width: 100%;
        }
        
        .reservation-header {
            flex-direction: column;
            gap: 12px;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .action-buttons button,
        .action-buttons form {
            width: 100%;
        }
        
        .action-buttons button {
            justify-content: center;
        }
    }
</style>


<!-- Statistics Overview -->
<div class="stats-overview">
    <div class="stat-box <?php echo $status_filter === 'all' ? 'active' : ''; ?>" onclick="window.location.href='?status=all'">
        <div class="stat-number"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total</div>
    </div>
    <div class="stat-box <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" onclick="window.location.href='?status=pending'">
        <div class="stat-number"><?php echo $stats['pending']; ?></div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-box <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" onclick="window.location.href='?status=approved'">
        <div class="stat-number"><?php echo $stats['approved']; ?></div>
        <div class="stat-label">Approved</div>
    </div>
    <div class="stat-box <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" onclick="window.location.href='?status=rejected'">
        <div class="stat-number"><?php echo $stats['rejected']; ?></div>
        <div class="stat-label">Rejected</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="search-box-enhanced">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <input type="text" id="searchInput" placeholder="Search by name, email, or plot number..." value="<?php echo htmlspecialchars($search); ?>" onkeypress="if(event.key === 'Enter') performSearch()">
    </div>
    <button class="btn-primary" onclick="performSearch()">
        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        Search
    </button>
    <?php if (!empty($search) || $status_filter !== 'all'): ?>
    <button class="btn-secondary" onclick="window.location.href='reservations_simple.php'">
        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        Clear Filters
    </button>
    <?php endif; ?>
</div>

<script>
function performSearch() {
    const search = document.getElementById('searchInput').value;
    const status = '<?php echo $status_filter; ?>';
    window.location.href = `?status=${status}&search=${encodeURIComponent(search)}`;
}
</script>

<?php if (isset($_GET['success'])): ?>
<div class="glass-card" style="margin-bottom: 20px; padding: 15px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3);">
    <strong>✓ Success:</strong> <?php echo htmlspecialchars($_GET['success']); ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="glass-card" style="margin-bottom: 20px; padding: 15px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);">
    <strong>✗ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
</div>
<?php endif; ?>

<div id="reservations">
    <?php if (empty($reservations)): ?>
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 style="color: rgba(255, 255, 255, 0.8); margin-bottom: 12px;">No Reservations Found</h3>
            <p style="color: rgba(255, 255, 255, 0.5);">
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
            <div class="reservation-card">
                <div class="reservation-header">
                    <div>
                        <div class="reservation-title">
                            <span class="reservation-id-badge">#<?php echo $res['id']; ?></span>
                            <div>
                                <h3 style="margin: 0 0 4px 0; font-size: 1.2rem;"><?php echo htmlspecialchars($res['visitor_name']); ?></h3>
                                <p style="margin: 0; color: rgba(255, 255, 255, 0.6); font-size: 0.9rem;">
                                    <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <?php echo htmlspecialchars($res['visitor_email']); ?>
                                    <?php if ($res['visitor_phone']): ?>
                                        <span style="margin-left: 12px;">
                                            <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            <?php echo htmlspecialchars($res['visitor_phone']); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <span class="status-badge status-<?php echo $res['status']; ?>">
                        <?php if ($res['status'] === 'pending'): ?>⏳<?php endif; ?>
                        <?php if ($res['status'] === 'approved'): ?>✓<?php endif; ?>
                        <?php if ($res['status'] === 'rejected'): ?>✗<?php endif; ?>
                        <?php echo strtoupper($res['status']); ?>
                    </span>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Plot Number</div>
                        <div class="info-value"><?php echo $res['plot_number'] ?? 'N/A'; ?></div>
                    </div>
                    <?php if ($res['compartment_id']): ?>
                    <div class="info-item">
                        <div class="info-label">Compartment</div>
                        <div class="info-value">#<?php echo $res['compartment_id']; ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="info-label">Type</div>
                        <div class="info-value"><?php echo ucfirst($res['reservation_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value">₱<?php echo number_format($res['total_amount'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Paid</div>
                        <div class="info-value" style="color: #22c55e;">₱<?php echo number_format($res['total_paid'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Balance</div>
                        <div class="info-value" style="color: <?php echo $res['balance'] > 0 ? '#fbbf24' : '#22c55e'; ?>;">
                            ₱<?php echo number_format($res['balance'], 2); ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($res['intended_for']): ?>
                <div style="background: rgba(255, 255, 255, 0.03); padding: 14px; border-radius: 10px; margin-bottom: 12px;">
                    <div class="info-label" style="margin-bottom: 6px;">Intended For</div>
                    <div style="font-weight: 500; font-size: 1.05rem;"><?php echo htmlspecialchars($res['intended_for']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($res['purpose']): ?>
                <div style="background: rgba(255, 255, 255, 0.03); padding: 14px; border-radius: 10px; margin-bottom: 12px;">
                    <div class="info-label" style="margin-bottom: 6px;">Purpose</div>
                    <div><?php echo htmlspecialchars($res['purpose']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($res['status'] === 'pending'): ?>
                <div class="action-buttons">
                    <form method="POST" action="process_reservation.php" style="display: inline;">
                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-approve">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Approve Reservation
                        </button>
                    </form>
                    <form method="POST" action="process_reservation.php" style="display: inline;">
                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn-reject">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Reject Reservation
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if ($res['rejection_reason']): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 12px; margin-top: 15px;">
                    <strong style="color: #ef4444;">Rejection Reason:</strong>
                    <p style="margin: 5px 0 0 0;"><?php echo htmlspecialchars($res['rejection_reason']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($res['payments'])): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                    <h4 style="margin: 0 0 15px 0;">Payment History</h4>
                    <?php foreach ($res['payments'] as $payment): ?>
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 15px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div>
                                <strong style="font-size: 1.1rem;">₱<?php echo number_format($payment['amount'], 2); ?></strong>
                                <span style="margin-left: 10px; color: var(--zinc-400);"><?php echo ucfirst($payment['payment_method']); ?></span>
                                <?php if ($payment['reference_number']): ?>
                                <span style="margin-left: 10px; font-size: 0.85rem; color: var(--zinc-500);">Ref: <?php echo htmlspecialchars($payment['reference_number']); ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge status-<?php echo $payment['verification_status']; ?>">
                                <?php echo strtoupper($payment['verification_status']); ?>
                            </span>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--zinc-400); margin-bottom: 10px;">
                            Submitted: <?php echo date('M d, Y g:i A', strtotime($payment['payment_date'])); ?>
                        </div>
                        <?php if ($payment['proof_of_payment']): ?>
                        <a href="../uploads/payments/<?php echo $payment['proof_of_payment']; ?>" target="_blank" style="color: #667eea; font-size: 0.9rem; text-decoration: underline;">
                            📎 View Proof of Payment
                        </a>
                        <?php endif; ?>
                        <?php if ($payment['verification_status'] === 'pending'): ?>
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <form method="POST" action="process_payment.php" style="display: inline;">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="action" value="verify">
                                <button type="submit" class="btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                    ✓ Verify Payment
                                </button>
                            </form>
                            <form method="POST" action="process_payment.php" style="display: inline;">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-secondary" style="padding: 6px 12px; font-size: 0.85rem; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444;">
                                    ✗ Reject
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.85rem; color: var(--zinc-500);">
                    Reservation Date: <?php echo date('M d, Y g:i A', strtotime($res['reservation_date'])); ?>
                    <?php if ($res['approved_date']): ?>
                        • Processed: <?php echo date('M d, Y g:i A', strtotime($res['approved_date'])); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        </main>
    </div>
    
    <script src="../assets/js/theme.js"></script>
</body>
</html>
