<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

$plot_id = $_GET['id'] ?? 0;

// Get plot details
try {
    $stmt = $pdo->prepare("SELECT * FROM available_plots WHERE id = ? AND has_grid = 1");
    $stmt->execute([$plot_id]);
    $plot = $stmt->fetch();
    
    if (!$plot) {
        header('Location: available-plots.php');
        exit;
    }
    
    // Get reserved compartments with details
    $stmt = $pdo->prepare("
        SELECT 
            pr.compartment_id,
            pr.status,
            pr.reservation_type,
            pr.payment_status,
            pr.intended_for,
            v.name as visitor_name,
            v.email as visitor_email
        FROM plot_reservations pr
        JOIN visitors v ON pr.visitor_id = v.id
        WHERE pr.plot_id = ?
        AND pr.compartment_id IS NOT NULL
        AND pr.status IN ('pending', 'approved')
        ORDER BY pr.compartment_id
    ");
    $stmt->execute([$plot_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create lookup array for quick access
    $reservedCompartments = [];
    foreach ($reservations as $res) {
        $reservedCompartments[$res['compartment_id']] = $res;
    }
    
} catch (PDOException $e) {
    error_log("Get plot error: " . $e->getMessage());
    header('Location: available-plots.php');
    exit;
}

// Calculate statistics
$totalCompartments = $plot['compartment_count'];
$reservedCount = count($reservedCompartments);
$availableCount = $totalCompartments - $reservedCount;
$occupancyRate = $totalCompartments > 0 ? ($reservedCount / $totalCompartments) * 100 : 0;
?>

<?php require_once 'includes/sidebar.php'; ?>

<div style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
        <a href="available-plots.php" class="btn-secondary" style="padding: 8px 16px;">
            <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Plots
        </a>
        <h2 style="margin: 0;">Plot Grid: <?php echo htmlspecialchars($plot['plot_number'], ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>
    
    <div class="glass-card" style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <p style="color: var(--zinc-400); margin: 0; font-size: 0.85rem;">GRID SIZE</p>
                <p style="margin: 5px 0 0 0; font-size: 1.3rem; font-weight: 600;">
                    <?php echo $plot['grid_rows']; ?> × <?php echo $plot['grid_cols']; ?>
                </p>
            </div>
            <div>
                <p style="color: var(--zinc-400); margin: 0; font-size: 0.85rem;">TOTAL COMPARTMENTS</p>
                <p style="margin: 5px 0 0 0; font-size: 1.3rem; font-weight: 600;">
                    <?php echo $totalCompartments; ?>
                </p>
            </div>
            <div>
                <p style="color: var(--zinc-400); margin: 0; font-size: 0.85rem;">RESERVED</p>
                <p style="margin: 5px 0 0 0; font-size: 1.3rem; font-weight: 600; color: #f59e0b;">
                    <?php echo $reservedCount; ?>
                </p>
            </div>
            <div>
                <p style="color: var(--zinc-400); margin: 0; font-size: 0.85rem;">AVAILABLE</p>
                <p style="margin: 5px 0 0 0; font-size: 1.3rem; font-weight: 600; color: #22c55e;">
                    <?php echo $availableCount; ?>
                </p>
            </div>
            <div>
                <p style="color: var(--zinc-400); margin: 0; font-size: 0.85rem;">OCCUPANCY</p>
                <p style="margin: 5px 0 0 0; font-size: 1.3rem; font-weight: 600; color: #667eea;">
                    <?php echo number_format($occupancyRate, 1); ?>%
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Grid Visualization -->
<div class="glass-card" style="padding: 30px;">
    <h3 style="margin: 0 0 20px 0;">Compartment Grid</h3>
    
    <div id="gridContainer" style="display: inline-block; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px;">
        <?php
        $rows = (int)$plot['grid_rows'];
        $cols = (int)$plot['grid_cols'];
        $compartmentNum = 1;
        
        for ($row = 0; $row < $rows; $row++) {
            echo '<div style="display: flex; gap: 10px; margin-bottom: 10px;">';
            
            for ($col = 0; $col < $cols; $col++) {
                $label = chr(65 + $row) . ($col + 1); // A1, A2, B1, B2, etc.
                $isReserved = isset($reservedCompartments[$compartmentNum]);
                
                if ($isReserved) {
                    $reservation = $reservedCompartments[$compartmentNum];
                    $statusColor = $reservation['status'] === 'approved' ? '#22c55e' : '#f59e0b';
                    $bgGradient = $reservation['status'] === 'approved' 
                        ? 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)'
                        : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                    
                    $tooltipData = htmlspecialchars(json_encode($reservation), ENT_QUOTES, 'UTF-8');
                    
                    echo '<div class="grid-cell reserved-cell" 
                        data-reservation=\'' . $tooltipData . '\'
                        style="
                            width: 80px; 
                            height: 80px; 
                            background: ' . $bgGradient . ';
                            border: 2px solid ' . $statusColor . ';
                            border-radius: 8px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            font-weight: 600;
                            font-size: 1.1rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            position: relative;
                        " 
                        onclick="showReservationDetails(' . $compartmentNum . ')"
                        onmouseover="this.style.transform=\'scale(1.05)\'; this.style.boxShadow=\'0 8px 20px rgba(0,0,0,0.4)\';" 
                        onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'none\';">';
                    echo '<div>' . $label . '</div>';
                    echo '<div style="font-size: 0.7rem; margin-top: 2px; opacity: 0.8;">#' . $compartmentNum . '</div>';
                    echo '<div style="position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>';
                    echo '</div>';
                } else {
                    echo '<div class="grid-cell available-cell" style="
                        width: 80px; 
                        height: 80px; 
                        background: rgba(255,255,255,0.05);
                        border: 2px solid rgba(255,255,255,0.1);
                        border-radius: 8px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        font-weight: 600;
                        font-size: 1.1rem;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        color: rgba(255,255,255,0.5);
                    " 
                    onmouseover="this.style.transform=\'scale(1.05)\'; this.style.borderColor=\'rgba(102, 126, 234, 0.5)\';" 
                    onmouseout="this.style.transform=\'scale(1)\'; this.style.borderColor=\'rgba(255,255,255,0.1)\';"
                    onclick="selectCompartment(\'' . $label . '\', ' . $compartmentNum . ')">';
                    echo '<div>' . $label . '</div>';
                    echo '<div style="font-size: 0.7rem; margin-top: 2px; opacity: 0.5;">#' . $compartmentNum . '</div>';
                    echo '</div>';
                }
                
                $compartmentNum++;
            }
            
            echo '</div>';
        }
        ?>
    </div>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--glass-border);">
        <h4 style="margin: 0 0 15px 0;">Legend</h4>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 30px; height: 30px; background: rgba(255,255,255,0.05); border: 2px solid rgba(255,255,255,0.1); border-radius: 6px;"></div>
                <span style="color: var(--zinc-400);">Available</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 30px; height: 30px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 6px;"></div>
                <span style="color: var(--zinc-400);">Reserved (Pending Approval)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 30px; height: 30px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 6px;"></div>
                <span style="color: var(--zinc-400);">Reserved (Approved)</span>
            </div>
        </div>
        <p style="margin: 15px 0 0 0; font-size: 0.9rem; color: var(--zinc-500);">
            💡 Click on reserved compartments to view reservation details
        </p>
    </div>
</div>

<!-- Reservation Details Modal -->
<div id="reservationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 20px; padding: 40px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.5rem;">Compartment Details</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='none'">×</button>
        </div>
        <div id="modalContent"></div>
    </div>
</div>

<!-- Action Panel -->
<div class="glass-card" style="padding: 20px; margin-top: 20px;">
    <h4 style="margin: 0 0 15px 0;">Actions</h4>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="viewOnMap()" class="btn-primary">
            <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
            </svg>
            View on Map
        </button>
        <button onclick="printGrid()" class="btn-secondary">
            <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print Grid
        </button>
    </div>
</div>

        </main>
    </div>
    
    <script src="../assets/js/theme.js"></script>
    <script>
        // Store reservation data
        const reservations = <?php echo json_encode($reservedCompartments); ?>;
        
        function selectCompartment(label, num) {
            themeUtils.showAlert(`Compartment ${label} (#${num}) is available`, 'info');
        }
        
        function showReservationDetails(compartmentNum) {
            const reservation = reservations[compartmentNum];
            if (!reservation) return;
            
            const statusBadge = reservation.status === 'approved' 
                ? '<span style="padding: 4px 12px; background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; border-radius: 12px; color: #22c55e; font-size: 0.85rem; font-weight: 600;">APPROVED</span>'
                : '<span style="padding: 4px 12px; background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; border-radius: 12px; color: #f59e0b; font-size: 0.85rem; font-weight: 600;">PENDING</span>';
            
            const paymentBadge = reservation.payment_status === 'paid'
                ? '<span style="padding: 4px 12px; background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; border-radius: 12px; color: #22c55e; font-size: 0.85rem; font-weight: 600;">PAID</span>'
                : '<span style="padding: 4px 12px; background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; border-radius: 12px; color: #ef4444; font-size: 0.85rem; font-weight: 600;">UNPAID</span>';
            
            const rows = <?php echo $plot['grid_rows']; ?>;
            const cols = <?php echo $plot['grid_cols']; ?>;
            const row = Math.floor((compartmentNum - 1) / cols);
            const col = (compartmentNum - 1) % cols;
            const label = String.fromCharCode(65 + row) + (col + 1);
            
            const content = `
                <div style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h4 style="margin: 0; font-size: 1.3rem; color: #667eea;">Compartment ${label}</h4>
                        <span style="font-size: 0.9rem; color: rgba(255,255,255,0.5);">#${compartmentNum}</span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        ${statusBadge}
                        ${paymentBadge}
                    </div>
                </div>
                
                <div style="background: rgba(0,0,0,0.3); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                    <div style="margin-bottom: 16px;">
                        <p style="margin: 0 0 6px 0; font-size: 0.85rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.5px;">Reserved By</p>
                        <p style="margin: 0; font-size: 1.1rem; font-weight: 600;">${reservation.visitor_name}</p>
                        <p style="margin: 4px 0 0 0; font-size: 0.9rem; color: rgba(255,255,255,0.6);">${reservation.visitor_email}</p>
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <p style="margin: 0 0 6px 0; font-size: 0.85rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.5px;">Intended For</p>
                        <p style="margin: 0; font-size: 1.1rem; font-weight: 600;">${reservation.intended_for || 'Not specified'}</p>
                    </div>
                    
                    <div>
                        <p style="margin: 0 0 6px 0; font-size: 0.85rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.5px;">Reservation Type</p>
                        <p style="margin: 0; font-size: 1.1rem; font-weight: 600; text-transform: capitalize;">${reservation.reservation_type}</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <a href="reservations.php" class="btn-primary" style="flex: 1; text-align: center; padding: 12px; text-decoration: none;">
                        View All Reservations
                    </a>
                    <button onclick="closeModal()" class="btn-secondary" style="padding: 12px 24px;">
                        Close
                    </button>
                </div>
            `;
            
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('reservationModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('reservationModal').style.display = 'none';
        }
        
        function viewOnMap() {
            window.open(`map-view.php?lat=<?php echo $plot['latitude']; ?>&lng=<?php echo $plot['longitude']; ?>&zoom=20`, '_blank');
        }
        
        function printGrid() {
            window.print();
        }
        
        // Close modal when clicking outside
        document.getElementById('reservationModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
    
    <style>
        @media print {
            .sidebar, .btn-secondary, .btn-primary {
                display: none !important;
            }
            
            .glass-card {
                border: 1px solid #000 !important;
                background: white !important;
                color: #000 !important;
            }
            
            .grid-cell {
                border: 2px solid #000 !important;
                background: white !important;
                color: #000 !important;
            }
        }
    </style>
</body>
</html>
