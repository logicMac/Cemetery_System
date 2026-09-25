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
                <p style="margin: 5px 0 0 0; font-size: 1.3rem; font-weight: 600; color: #a68b52;">
                    <?php echo $reservedCount; ?>
                </p>
            </div>
            <div>
                <p style="color: var(--zinc-400); margin: 0; font-size: 0.85rem;">AVAILABLE</p>
                <p style="margin: 5px 0 0 0; font-size: 1.3rem; font-weight: 600; color: #5a9b6f;">
                    <?php echo $availableCount; ?>
                </p>
            </div>
            <div>
                <p style="color: var(--zinc-400); margin: 0; font-size: 0.85rem;">OCCUPANCY</p>
                <p style="margin: 5px 0 0 0; font-size: 1.3rem; font-weight: 600; color: #22c55e;">
                    <?php echo number_format($occupancyRate, 1); ?>%
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Grid Visualization -->
<div class="glass-card" style="padding: 30px;">
    <h3 style="margin: 0 0 20px 0;">Compartment Grid</h3>
    <p style="margin: 0 0 15px 0; font-size: 0.9rem; color: var(--zinc-500);">
        Satellite map is shown behind the grid. Click any cell to log its map coordinates.
    </p>

    <?php
    $rows = (int)$plot['grid_rows'];
    $cols = (int)$plot['grid_cols'];
    // Compute pixel dimensions of the grid block (cell 80 + gap 10) + padding 20 each side
    $gridWidthPx  = $cols * 80 + ($cols - 1) * 10 + 40;
    $gridHeightPx = $rows * 80 + ($rows - 1) * 10 + 40;
    ?>
    <div id="gridMapWrapper" style="position: relative; width: <?php echo $gridWidthPx; ?>px; height: <?php echo $gridHeightPx; ?>px; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
        <!-- Satellite map background -->
        <div id="gridSatMap" style="position: absolute; inset: 0; width: 100%; height: 100%; background: #0a0a0a; z-index: 100;"></div>
        <!-- Grid overlay -->
        <div id="gridContainer" style="position: absolute; inset: 0; display: flex; flex-direction: column; padding: 20px; z-index: 400; pointer-events: none;">
        <?php
        $compartmentNum = 1;
        
        for ($row = 0; $row < $rows; $row++) {
            echo '<div style="display: flex; gap: 10px; margin-bottom: 10px;">';
            
            for ($col = 0; $col < $cols; $col++) {
                $label = chr(65 + $row) . ($col + 1); // A1, A2, B1, B2, etc.
                $isReserved = isset($reservedCompartments[$compartmentNum]);
                
                if ($isReserved) {
                    $reservation = $reservedCompartments[$compartmentNum];
                    $statusColor = $reservation['status'] === 'approved' ? '#5a9b6f' : '#a68b52';
                    $bgGradient = $reservation['status'] === 'approved' 
                        ? 'linear-gradient(135deg, rgba(90,155,111,0.85) 0%, rgba(5,150,105,0.85) 100%)'
                        : 'linear-gradient(135deg, rgba(166,139,82,0.85) 0%, rgba(138,115,64,0.85) 100%)';
                    
                    $tooltipData = htmlspecialchars(json_encode($reservation), ENT_QUOTES, 'UTF-8');
                    
                    echo '<div class="grid-cell reserved-cell" 
                        data-reservation=\'' . $tooltipData . '\'
                        data-row="' . $row . '" data-col="' . $col . '"
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
                            pointer-events: auto;
                        " 
                        onclick="showReservationDetails(' . $compartmentNum . ')"
                        onmouseover="this.style.transform=\'scale(1.05)\'; this.style.boxShadow=\'0 8px 20px rgba(0,0,0,0.4)\';" 
                        onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'none\';">';
                    echo '<div>' . $label . '</div>';
                    echo '<div style="font-size: 0.7rem; margin-top: 2px; opacity: 0.8;">#' . $compartmentNum . '</div>';
                    echo '<div style="position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>';
                    echo '</div>';
                } else {
                    echo '<div class="grid-cell available-cell" data-row="' . $row . '" data-col="' . $col . '" style="
                        width: 80px; 
                        height: 80px; 
                        background: rgba(255,255,255,0.08);
                        border: 2px solid rgba(255,255,255,0.25);
                        border-radius: 8px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        font-weight: 600;
                        font-size: 1.1rem;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        color: rgba(255,255,255,0.85);
                        pointer-events: auto;
                    " 
                    onmouseover="this.style.transform=\'scale(1.05)\'; this.style.borderColor=\'rgba(74, 222, 128, 0.8)\';" 
                    onmouseout="this.style.transform=\'scale(1)\'; this.style.borderColor=\'rgba(255,255,255,0.25)\';"
                    onclick="selectCompartment(\'' . $label . '\', ' . $compartmentNum . ', this)">';
                    echo '<div>' . $label . '</div>';
                    echo '<div style="font-size: 0.7rem; margin-top: 2px; opacity: 0.8;">#' . $compartmentNum . '</div>';
                    echo '</div>';
                }
                
                $compartmentNum++;
            }
            
            echo '</div>';
        }
        ?>
        </div>
    </div>

    <!-- Coordinates log -->
    <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h4 style="margin: 0; font-size: 0.95rem;">Logged Coordinates</h4>
            <button onclick="clearLoggedCoords()" class="btn-secondary" style="padding: 4px 12px; font-size: 0.8rem;">Clear</button>
        </div>
        <div id="coordsLog" style="font-family: monospace; font-size: 0.85rem; color: #a3e635; max-height: 150px; overflow-y: auto;">
            <div style="color: rgba(255,255,255,0.4);">Click a grid cell to log its map coordinates...</div>
        </div>
    </div>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--glass-border);">
        <h4 style="margin: 0 0 15px 0;">Legend</h4>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 30px; height: 30px; background: rgba(255,255,255,0.05); border: 2px solid rgba(255,255,255,0.1); border-radius: 6px;"></div>
                <span style="color: var(--zinc-400);">Available</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 30px; height: 30px; background: linear-gradient(135deg, #a68b52 0%, #8a7340 100%); border-radius: 6px;"></div>
                <span style="color: var(--zinc-400);">Reserved (Pending Approval)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 30px; height: 30px; background: linear-gradient(135deg, #5a9b6f 0%, #059669 100%); border-radius: 6px;"></div>
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
    <div style="background: linear-gradient(135deg, #0a0a0a 0%, #050505 100%); border: 1px solid rgba(74, 222, 128, 0.3); border-radius: 20px; padding: 40px; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
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
    <!-- Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.fullscreen@2.4.0/Control.FullScreen.js"></script>
    <script>
        // Store reservation data
        const reservations = <?php echo json_encode($reservedCompartments); ?>;
        const PLOT_LAT = <?php echo (float)$plot['latitude']; ?>;
        const PLOT_LNG = <?php echo (float)$plot['longitude']; ?>;
        const PLOT_ROWS = <?php echo (int)$plot['grid_rows']; ?>;
        const PLOT_COLS = <?php echo (int)$plot['grid_cols']; ?>;
        let gridMap = null;
        const loggedCoords = [];

        // Initialize satellite map behind the grid
        function initGridSatMap() {
            gridMap = L.map('gridSatMap', {
                zoomControl: true,
                attributionControl: false,
                dragging: true,
                scrollWheelZoom: true,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false
            }).setView([PLOT_LAT, PLOT_LNG], 20);

            L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
                maxZoom: 22,
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
            }).addTo(gridMap);

            if (typeof L.Control.Fullscreen !== 'undefined') {
                gridMap.addControl(new L.Control.Fullscreen());
            }

            // Center marker for the plot
            L.marker([PLOT_LAT, PLOT_LNG], {
                icon: L.divIcon({
                    className: 'custom-marker',
                    html: '<div style="width:14px;height:14px;background:#22c55e;border:2px solid #fff;border-radius:50%;box-shadow:0 0 8px rgba(0,0,0,0.6);"></div>',
                    iconSize: [14, 14],
                    iconAnchor: [7, 7]
                })
            }).addTo(gridMap);

            // Show polygon if defined
            const polygonRaw = <?php echo json_encode($plot['polygon'] ?? null); ?>;
            if (polygonRaw) {
                try {
                    const pts = (typeof polygonRaw === 'string') ? JSON.parse(polygonRaw) : polygonRaw;
                    if (Array.isArray(pts) && pts.length >= 3) {
                        L.polygon(pts, {
                            color: '#22c55e',
                            weight: 2,
                            fillColor: '#22c55e',
                            fillOpacity: 0.15
                        }).addTo(gridMap);
                    }
                } catch (e) { /* ignore bad polygon */ }
            }
        }

        // Compute the lat/lng of a grid cell based on its row/col index.
        // We treat the plot lat/lng as the center of the grid and spread cells
        // evenly across a small area proportional to the grid size.
        function getCellLatLng(row, col) {
            // Estimate the plot's geographic span. ~0.0001 deg ~= 11m.
            // We spread the grid over a span that scales with the number of cells.
            const spanLat = 0.00010 * PLOT_ROWS + 0.00002 * (PLOT_ROWS - 1);
            const spanLng = 0.00010 * PLOT_COLS + 0.00002 * (PLOT_COLS - 1);
            const startLat = PLOT_LAT + spanLat / 2;
            const startLng = PLOT_LNG - spanLng / 2;
            const stepLat = spanLat / Math.max(PLOT_ROWS, 1);
            const stepLng = spanLng / Math.max(PLOT_COLS, 1);
            // Center of the cell
            const lat = startLat - (row + 0.5) * stepLat;
            const lng = startLng + (col + 0.5) * stepLng;
            return [lat, lng];
        }

        function logCoordinates(label, num, lat, lng) {
            const log = document.getElementById('coordsLog');
            // Remove placeholder
            if (loggedCoords.length === 0) log.innerHTML = '';
            const entry = { label, num, lat, lng, time: new Date().toLocaleTimeString() };
            loggedCoords.push(entry);
            const div = document.createElement('div');
            div.style.padding = '4px 0';
            div.style.borderBottom = '1px dashed rgba(255,255,255,0.08)';
            div.innerHTML = `<span style="color:#22c55e;">[${entry.time}]</span> ` +
                `<strong>${label}</strong> (#${num}) &mdash; ` +
                `lat: <span style="color:#fff;">${lat.toFixed(8)}</span>, ` +
                `lng: <span style="color:#fff;">${lng.toFixed(8)}</span>`;
            log.appendChild(div);
            log.scrollTop = log.scrollHeight;
        }

        function clearLoggedCoords() {
            loggedCoords.length = 0;
            document.getElementById('coordsLog').innerHTML =
                '<div style="color: rgba(255,255,255,0.4);">Click a grid cell to log its map coordinates...</div>';
        }
        
        function selectCompartment(label, num, el) {
            const row = parseInt(el.dataset.row, 10);
            const col = parseInt(el.dataset.col, 10);
            const [lat, lng] = getCellLatLng(row, col);
            logCoordinates(label, num, lat, lng);
            // Drop a temporary marker on the map
            if (gridMap) {
                L.popup({ className: 'grid-coord-popup' })
                    .setLatLng([lat, lng])
                    .setContent(`<strong>${label}</strong> (#${num})<br>lat: ${lat.toFixed(8)}<br>lng: ${lng.toFixed(8)}`)
                    .openOn(gridMap);
            }
            themeUtils.showAlert(`Compartment ${label} (#${num}) is available. Coordinates logged.`, 'info');
        }
        
        function showReservationDetails(compartmentNum) {
            const reservation = reservations[compartmentNum];
            if (!reservation) return;
            
            const statusBadge = reservation.status === 'approved' 
                ? '<span style="padding: 4px 12px; background: rgba(34, 197, 94, 0.2); border: 1px solid #5a9b6f; border-radius: 12px; color: #5a9b6f; font-size: 0.85rem; font-weight: 600;">APPROVED</span>'
                : '<span style="padding: 4px 12px; background: rgba(166, 139, 82, 0.2); border: 1px solid #a68b52; border-radius: 12px; color: #a68b52; font-size: 0.85rem; font-weight: 600;">PENDING</span>';
            
            const paymentBadge = reservation.payment_status === 'paid'
                ? '<span style="padding: 4px 12px; background: rgba(34, 197, 94, 0.2); border: 1px solid #5a9b6f; border-radius: 12px; color: #5a9b6f; font-size: 0.85rem; font-weight: 600;">PAID</span>'
                : '<span style="padding: 4px 12px; background: rgba(181, 90, 90, 0.2); border: 1px solid #b55a5a; border-radius: 12px; color: #b55a5a; font-size: 0.85rem; font-weight: 600;">UNPAID</span>';
            
            const rows = <?php echo $plot['grid_rows']; ?>;
            const cols = <?php echo $plot['grid_cols']; ?>;
            const row = Math.floor((compartmentNum - 1) / cols);
            const col = (compartmentNum - 1) % cols;
            const label = String.fromCharCode(65 + row) + (col + 1);
            
            const content = `
                <div style="background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h4 style="margin: 0; font-size: 1.3rem; color: #22c55e;">Compartment ${label}</h4>
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

        // Initialize the satellite map behind the grid
        document.addEventListener('DOMContentLoaded', function() {
            try {
                initGridSatMap();
            } catch (e) {
                console.error('Failed to init grid satellite map:', e);
            }
        });
    </script>
    
    <style>
        #gridSatMap .leaflet-control-zoom,
        #gridSatMap .leaflet-control-fullscreen-button {
            margin: 10px;
        }
        #gridSatMap .leaflet-popup-content-wrapper {
            background: #0a0a0a;
            color: #fff;
            border: 1px solid rgba(74, 222, 128, 0.4);
        }
        #gridSatMap .leaflet-popup-tip { background: #0a0a0a; }
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
