<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Get all available plots
try {
    $stmt = $pdo->query("
        SELECT id, plot_number, latitude, longitude, notes, photo, expiration_date, renewal_count,
               has_grid, grid_rows, grid_cols, compartment_count, date_added
        FROM available_plots 
        ORDER BY date_added DESC
    ");
    $plots = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Get plots error: " . $e->getMessage());
    $plots = [];
}
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
.admin-layout { background: #ffffff; }
.admin-layout::after { display: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade { animation: fadeUp 0.5s ease both; }
button svg, a svg, button i, a i { pointer-events: none; }
/* Override theme modals with Tailwind-style white modal */
.modal-overlay { background: rgba(15,23,42,0.5) !important; backdrop-filter: blur(4px) !important; }
.modal-content {
    background: #ffffff !important;
    backdrop-filter: none !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15) !important;
    padding: 24px !important;
    max-width: 1100px !important;
    width: 95vw !important;
    max-height: 92vh !important;
    overflow-y: auto !important;
}
.modal-content h3 {
    color: #0f172a !important;
    font-weight: 700 !important;
    font-size: 1.1rem !important;
    padding-bottom: 16px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
}
.modal-content h3::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
}
.modal-content label { color: #334155 !important; }
.modal-content .btn-primary {
    background: #10b981 !important;
    color: #fff !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 10px 20px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
}
.modal-content .btn-primary:hover { background: #059669 !important; }
.modal-content .btn-secondary {
    background: #fff !important;
    color: #334155 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    padding: 10px 20px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
}
.modal-content .btn-secondary:hover { background: #f8fafc !important; }
</style>

<!-- Page Header -->
<div class="flex items-center justify-between flex-wrap gap-4 mb-6 animate-fade">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
            <i data-lucide="map-pin" class="w-5 h-5"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-slate-900">Available Plots</h2>
            <p class="text-sm text-slate-500">Manage available burial plots and compartments</p>
        </div>
    </div>
    <button type="button" onclick="showAddPlotModal()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 shadow-sm transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add Available Plot
    </button>
</div>

<?php
function getPlotExpirationBadge($dateStr) {
    if (!$dateStr) {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-slate-100 text-slate-600 border border-slate-200">No expiry</span>';
    }
    $today = new DateTime();
    $exp = new DateTime($dateStr);
    $diff = $today->diff($exp);
    $days = $diff->invert ? -$diff->days : $diff->days;
    $dateText = $exp->format('M d, Y');
    if ($days < 0) {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-rose-100 text-rose-700 border border-rose-200" title="Expired ' . abs($days) . ' days ago">Expired · ' . $dateText . '</span>';
    }
    if ($days <= 30) {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-amber-100 text-amber-700 border border-amber-200" title="Expires in ' . $days . ' days">Expiring · ' . $dateText . '</span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-emerald-100 text-emerald-700 border border-emerald-200" title="Active, expires in ' . $days . ' days">Active · ' . $dateText . '</span>';
}
$today = new DateTime();
$expiring = count(array_filter($plots, function($p) use ($today) { return $p['expiration_date'] && (new DateTime($p['expiration_date'])) < (clone $today)->modify('+30 days'); }));
?>

<!-- Stats summary -->
<div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6 animate-fade">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo count($plots); ?></div><div class="text-xs text-slate-500">Total Plots</div></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i data-lucide="grid-3x3" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo count(array_filter($plots, fn($p) => $p['has_grid'])); ?></div><div class="text-xs text-slate-500">With Grid</div></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center"><i data-lucide="alert-triangle" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo $expiring; ?></div><div class="text-xs text-slate-500">Expiring in 30d</div></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 col-span-2 sm:col-span-1">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center"><i data-lucide="layout-grid" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo array_sum(array_map(fn($p) => (int)($p['compartment_count'] ?? 0), $plots)); ?></div><div class="text-xs text-slate-500">Compartments</div></div>
        </div>
    </div>
</div>

<!-- Plots Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php if (empty($plots)): ?>
        <div class="col-span-full text-center py-16 bg-white rounded-2xl border border-slate-200">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                <i data-lucide="map-pin" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-700 mb-1">No Available Plots</h3>
            <p class="text-sm text-slate-500">Click "Add Available Plot" to create your first plot</p>
        </div>
    <?php else: ?>
        <?php foreach ($plots as $plot): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col hover:border-emerald-300 hover:shadow-md transition animate-fade">
                <?php if ($plot['photo']): ?>
                    <img src="../uploads/plots/<?php echo htmlspecialchars($plot['photo'], ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-44 object-cover" alt="Plot photo">
                <?php else: ?>
                    <div class="w-full h-44 bg-slate-100 flex items-center justify-center text-slate-300">
                        <i data-lucide="map-pin" class="w-12 h-12"></i>
                    </div>
                <?php endif; ?>

                <div class="p-5 flex flex-col flex-1">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-bold text-slate-900"><?php echo htmlspecialchars($plot['plot_number'] ?: 'Plot #' . $plot['id'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-emerald-100 text-emerald-700 border border-emerald-200">Available</span>
                    </div>

                    <div class="text-sm text-slate-500 space-y-1.5 mb-4 flex-1">
                        <div class="flex items-center gap-2"><i data-lucide="navigation" class="w-3.5 h-3.5 text-slate-400"></i> <span class="text-xs"><?php echo $plot['latitude']; ?>, <?php echo $plot['longitude']; ?></span></div>
                        <div class="flex items-center gap-2"><?php echo getPlotExpirationBadge($plot['expiration_date']); ?></div>
                        <?php if ($plot['has_grid']): ?>
                            <div class="flex items-center gap-2"><i data-lucide="grid-3x3" class="w-3.5 h-3.5 text-slate-400"></i> <?php echo $plot['grid_rows']; ?> × <?php echo $plot['grid_cols']; ?> (<?php echo $plot['compartment_count']; ?> compartments)</div>
                        <?php endif; ?>
                        <?php if ($plot['notes']): ?>
                            <div class="flex items-start gap-2"><i data-lucide="sticky-note" class="w-3.5 h-3.5 text-slate-400 mt-0.5"></i> <span class="italic"><?php echo htmlspecialchars($plot['notes'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <?php endif; ?>
                        <div class="flex items-center gap-2"><i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i> <?php echo date('M d, Y', strtotime($plot['date_added'])); ?></div>
                    </div>

                    <div class="flex gap-2 pt-3 border-t border-slate-100">
                        <button onclick="viewPlotOnMap(<?php echo $plot['latitude']; ?>, <?php echo $plot['longitude']; ?>)" title="View on Map" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-medium transition">
                            <i data-lucide="map" class="w-3.5 h-3.5"></i> Map
                        </button>
                        <?php if ($plot['has_grid']): ?>
                            <button onclick="viewGrid(<?php echo $plot['id']; ?>)" title="View Grid" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-medium transition">
                                <i data-lucide="grid-3x3" class="w-3.5 h-3.5"></i> Grid
                            </button>
                        <?php endif; ?>
                        <button onclick="editPlot(<?php echo $plot['id']; ?>)" title="Edit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-medium transition">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                        </button>
                        <button onclick="directRenewAvailable(<?php echo $plot['id']; ?>, '<?php echo htmlspecialchars($plot['plot_number'] ?: 'Plot #' . $plot['id'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $plot['expiration_date']; ?>')" title="Renew" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-amber-50 hover:border-amber-200 hover:text-amber-700 text-slate-700 text-xs font-medium transition">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Renew
                        </button>
                        <button onclick="deletePlot(<?php echo $plot['id']; ?>, '<?php echo htmlspecialchars($plot['plot_number'] ?: 'Plot #' . $plot['id'], ENT_QUOTES, 'UTF-8'); ?>')" title="Delete" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 text-slate-700 text-xs font-medium transition">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        </main>
    </div>

<!-- Direct Renew Modal (Admin) -->
<div id="directRenewModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center shrink-0">
                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-bold text-slate-900">Renew Plot</h2>
        </div>
        <form id="directRenewForm" class="space-y-4">
            <input type="hidden" id="directRenewType" name="plot_type">
            <input type="hidden" id="directRenewRecordId" name="record_id">
            <input type="hidden" id="directRenewPlotId" name="plot_id">

            <div id="directRenewInfo" class="bg-slate-50 rounded-xl p-4 text-sm text-slate-700 space-y-1"></div>

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
                <textarea name="notes" rows="2" placeholder="Reason for direct renewal..." class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 resize-y"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeDirectRenewModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold transition">Renew Now</button>
            </div>
        </form>
    </div>
</div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        const plotsData = <?php echo json_encode($plots); ?>;
        window.plotsData = plotsData;
    </script>
    <script>
        // Ray-casting point-in-polygon test (polygon is [lat, lng])
        function isPointInPolygon(lat, lng, polygon) {
            let inside = false;
            for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
                const yi = polygon[i][0], xi = polygon[i][1];
                const yj = polygon[j][0], xj = polygon[j][1];
                const intersect = ((yi > lat) !== (yj > lat)) &&
                    (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi);
                if (intersect) inside = !inside;
            }
            return inside;
        }

        // Compute the grid cell polygons for a plot (same geometry as drawExistingPlotGrid)
        function getPlotGridCells(plot) {
            const rows = parseInt(plot.grid_rows) || 1;
            const cols = parseInt(plot.grid_cols) || 1;
            const centerLat = parseFloat(plot.latitude);
            const centerLng = parseFloat(plot.longitude);
            const cellSize = 2;
            const angleRad = 45 * Math.PI / 180;
            const metersPerLat = 111320;
            const metersPerLng = 111320 * Math.cos(centerLat * Math.PI / 180);
            const cells = [];

            for (let row = 0; row < rows; row++) {
                for (let col = 0; col < cols; col++) {
                    const offsetX = (col - cols / 2 + 0.5) * cellSize;
                    const offsetY = (row - rows / 2 + 0.5) * cellSize;
                    const rotatedX = offsetX * Math.cos(angleRad) - offsetY * Math.sin(angleRad);
                    const rotatedY = offsetX * Math.sin(angleRad) + offsetY * Math.cos(angleRad);
                    const cellLat = centerLat + (rotatedY / metersPerLat);
                    const cellLng = centerLng + (rotatedX / metersPerLng);

                    const halfCell = cellSize / 2;
                    const corners = [
                        {x: -halfCell, y: -halfCell},
                        {x: halfCell, y: -halfCell},
                        {x: halfCell, y: halfCell},
                        {x: -halfCell, y: halfCell}
                    ];
                    const poly = corners.map(c => {
                        const rx = c.x * Math.cos(angleRad) - c.y * Math.sin(angleRad);
                        const ry = c.x * Math.sin(angleRad) + c.y * Math.cos(angleRad);
                        return [cellLat + (ry / metersPerLat), cellLng + (rx / metersPerLng)];
                    });
                    const label = String.fromCharCode(65 + row) + (col + 1);
                    cells.push({ label, polygon: poly });
                }
            }
            return cells;
        }

        // Check if a lat/lng point conflicts with any existing plot
        // Returns the conflicting plot name or null
        function checkPlotConflict(lat, lng, excludeId) {
            if (!window.plotsData) return null;
            for (const p of window.plotsData) {
                if (excludeId && p.id == excludeId) continue;
                if (!p.latitude || !p.longitude) continue;

                // Check grid cells
                if (p.has_grid) {
                    const cells = getPlotGridCells(p);
                    for (const cell of cells) {
                        if (isPointInPolygon(lat, lng, cell.polygon)) {
                            return escapeHtml(p.plot_number || 'Plot #' + p.id) + ' (grid cell ' + cell.label + ')';
                        }
                    }
                } else {
                    // For non-grid plots, check a small radius (~2m) around the point
                    const pLat = parseFloat(p.latitude);
                    const pLng = parseFloat(p.longitude);
                    const dLat = lat - pLat;
                    const dLng = lng - pLng;
                    const distM = Math.sqrt(Math.pow(dLat * 111320, 2) + Math.pow(dLng * 111320 * Math.cos(lat * Math.PI / 180), 2));
                    if (distM < 2) {
                        return escapeHtml(p.plot_number || 'Plot #' + p.id);
                    }
                }

                // Check polygon boundary
                if (p.polygon) {
                    try {
                        const pts = JSON.parse(p.polygon);
                        if (Array.isArray(pts) && pts.length >= 3 && isPointInPolygon(lat, lng, pts)) {
                            return escapeHtml(p.plot_number || 'Plot #' + p.id);
                        }
                    } catch (e) { /* ignore */ }
                }
            }
            return null;
        }

        // Draw grid cells for an existing plot on a Leaflet map
        // Matches the visitor portal rendering (2m cells, 45° rotation)
        // Green = available, Red = reserved
        async function drawExistingPlotGrid(map, plot) {
            if (!plot.has_grid || !plot.latitude || !plot.longitude) return;
            const rows = parseInt(plot.grid_rows) || 1;
            const cols = parseInt(plot.grid_cols) || 1;
            const centerLat = parseFloat(plot.latitude);
            const centerLng = parseFloat(plot.longitude);
            const cellSize = 2; // 2 meters per cell (same as visitor portal)
            const rotationAngle = 45; // degrees (same as visitor portal)
            const angleRad = rotationAngle * Math.PI / 180;
            const metersPerLat = 111320;
            const metersPerLng = 111320 * Math.cos(centerLat * Math.PI / 180);

            // Fetch reserved compartments for this plot
            let reservedCompartments = [];
            try {
                const resp = await fetch(`../api/get_reserved_compartments.php?plot_id=${plot.id}`);
                const data = await resp.json();
                if (data.success) reservedCompartments = data.reserved || [];
            } catch (e) { /* ignore */ }

            const plotName = escapeHtml(plot.plot_number || 'Plot #' + plot.id);

            for (let row = 0; row < rows; row++) {
                for (let col = 0; col < cols; col++) {
                    // Offset in meters from center
                    const offsetX = (col - cols / 2 + 0.5) * cellSize;
                    const offsetY = (row - rows / 2 + 0.5) * cellSize;

                    // Apply rotation
                    const rotatedX = offsetX * Math.cos(angleRad) - offsetY * Math.sin(angleRad);
                    const rotatedY = offsetX * Math.sin(angleRad) + offsetY * Math.cos(angleRad);

                    // Cell center in lat/lng
                    const cellLat = centerLat + (rotatedY / metersPerLat);
                    const cellLng = centerLng + (rotatedX / metersPerLng);

                    // Compute rotated corner offsets
                    const halfCell = cellSize / 2;
                    const corners = [
                        {x: -halfCell, y: -halfCell},
                        {x: halfCell, y: -halfCell},
                        {x: halfCell, y: halfCell},
                        {x: -halfCell, y: halfCell}
                    ];

                    const latLngs = corners.map(corner => {
                        const rotX = corner.x * Math.cos(angleRad) - corner.y * Math.sin(angleRad);
                        const rotY = corner.x * Math.sin(angleRad) + corner.y * Math.cos(angleRad);
                        return [
                            cellLat + (rotY / metersPerLat),
                            cellLng + (rotX / metersPerLng)
                        ];
                    });

                    const label = String.fromCharCode(65 + row) + (col + 1);
                    const compartmentNum = row * cols + col + 1;
                    const isReserved = reservedCompartments.includes(compartmentNum);

                    const cellStyle = isReserved ? {
                        color: '#ef4444',
                        weight: 2,
                        opacity: 1,
                        fillColor: '#ef4444',
                        fillOpacity: 0.55,
                        dashArray: null
                    } : {
                        color: '#10b981',
                        weight: 1.5,
                        opacity: 0.9,
                        fillColor: '#10b981',
                        fillOpacity: 0.2,
                        dashArray: '4, 4'
                    };

                    const statusText = isReserved ? 'Reserved' : 'Available';
                    const statusColor = isReserved ? '#ef4444' : '#10b981';
                    const popupHtml = `<strong>${plotName}</strong><br>` +
                        `<span style="font-size:12px;">Compartment ${label} (#${compartmentNum})</span><br>` +
                        `<span style="color:${statusColor};font-size:11px;font-weight:600;">${statusText}</span>`;

                    const cell = L.polygon(latLngs, cellStyle).addTo(map);
                    cell.bindPopup(popupHtml);

                    L.marker([cellLat, cellLng], {
                        icon: L.divIcon({
                            className: 'grid-cell-label',
                            html: `<div style="font-size:9px;font-weight:700;color:${isReserved ? '#991b1b' : '#047857'};background:rgba(255,255,255,0.85);padding:0 3px;border-radius:2px;white-space:nowrap;">${label}</div>`,
                            iconSize: [30, 12],
                            iconAnchor: [15, 6]
                        }),
                        interactive: false
                    }).addTo(map);
                }
            }
        }

        function showAddPlotModal() {
            const content = `
                <form id="addPlotForm" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Number</label>
                        <input type="text" id="plot_number" name="plot_number" placeholder="e.g., A-101" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes / Description</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Additional information..." class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition resize-y"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Expiration Date</label>
                        <input type="date" id="expiration_date" name="expiration_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Photo</label>
                        <input type="file" id="plot_photo" name="photo" accept="image/jpeg,image/png,image/jpg" class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" id="has_grid" name="has_grid" onchange="toggleGridOptions()" class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-100">
                            <span class="text-sm font-medium text-slate-700">Enable Grid (Multiple Compartments)</span>
                        </label>
                    </div>
                    <div id="gridOptions" style="display:none;">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Rows (1-10)</label>
                                <input type="number" id="grid_rows" name="grid_rows" min="1" max="10" value="2" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Columns (1-10)</label>
                                <input type="number" id="grid_cols" name="grid_cols" min="1" max="10" value="2" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-2">Location (Click on map)</div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Latitude <span class="text-rose-500">*</span></label>
                                <input type="number" id="latitude" name="latitude" step="0.00000001" required readonly class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Longitude <span class="text-rose-500">*</span></label>
                                <input type="number" id="longitude" name="longitude" step="0.00000001" required readonly class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                            </div>
                        </div>
                        <div class="flex items-center gap-4 mb-2 text-xs text-slate-500 flex-wrap">
                            <span class="inline-flex items-center gap-1.5"><span style="width:10px;height:10px;background:#10b981;border-radius:50%;display:inline-block;"></span> New plot (click to set)</span>
                            <span class="inline-flex items-center gap-1.5"><span style="width:10px;height:10px;background:#f59e0b;border-radius:50%;display:inline-block;"></span> Existing plot</span>
                            <span class="inline-flex items-center gap-1.5"><span style="width:10px;height:10px;background:#10b981;border:1px dashed #10b981;border-radius:2px;display:inline-block;opacity:0.5;"></span> Available compartment</span>
                            <span class="inline-flex items-center gap-1.5"><span style="width:10px;height:10px;background:#ef4444;border-radius:2px;display:inline-block;opacity:0.7;"></span> Reserved compartment</span>
                        </div>
                        <div id="modalMap" style="height:350px;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;"></div>
                    </div>
                    <div class="flex gap-3 justify-end pt-2">
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-5 py-2.5 transition" onclick="themeUtils.closeModal()">Cancel</button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 shadow-sm transition"><i data-lucide="check" class="w-4 h-4"></i> Save Plot</button>
                    </div>
                </form>
            `;

            themeUtils.showModal(content, 'Add Available Plot');

            setTimeout(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                const map = adminUtils.initAdminMap('modalMap', { bearing: 315 });
                let marker = null;

                // Show existing plots on the map so admin can avoid duplicates
                if (window.plotsData) {
                    window.plotsData.forEach(function(p) {
                        if (p.latitude && p.longitude) {
                            if (p.has_grid) {
                                // Draw grid cells for plots with grids
                                drawExistingPlotGrid(map, p);
                            } else {
                                const m = L.marker([parseFloat(p.latitude), parseFloat(p.longitude)], {
                                    icon: L.divIcon({
                                        className: 'existing-plot-marker',
                                        html: '<div style="width:12px;height:12px;background:#f59e0b;border:2px solid #fff;border-radius:50%;box-shadow:0 0 6px rgba(0,0,0,0.5);"></div>',
                                        iconSize: [12, 12],
                                        iconAnchor: [6, 6]
                                    })
                                }).addTo(map);
                                m.bindPopup(`<strong>${escapeHtml(p.plot_number || 'Plot #' + p.id)}</strong><br><span style="color:#b45309;font-size:11px;">Already created</span>`);
                            }
                        }
                        if (p.polygon) {
                            try {
                                const pts = JSON.parse(p.polygon);
                                if (Array.isArray(pts) && pts.length >= 3) {
                                    L.polygon(pts, {
                                        color: '#f59e0b',
                                        weight: 1.5,
                                        fillColor: '#f59e0b',
                                        fillOpacity: 0.08,
                                        dashArray: '4, 4'
                                    }).addTo(map).bindPopup(`<strong>${escapeHtml(p.plot_number || 'Plot #' + p.id)}</strong><br><span style="color:#b45309;font-size:11px;">Already created</span>`);
                                }
                            } catch (e) { /* ignore */ }
                        }
                    });
                }

                map.on('click', function(e) {
                    // Check if clicked location is inside the cemetery boundary
                    if (map._cemeteryPolygon && !isPointInPolygon(e.latlng.lat, e.latlng.lng, map._cemeteryPolygon)) {
                        themeUtils.showAlert('Cannot place plot outside the cemetery boundary (red border).', 'error');
                        return;
                    }

                    // Check if clicked location conflicts with an existing plot
                    const conflict = checkPlotConflict(e.latlng.lat, e.latlng.lng);
                    if (conflict) {
                        themeUtils.showAlert(`Cannot place plot here — overlaps with existing plot: ${conflict}`, 'error');
                        return;
                    }

                    document.getElementById('latitude').value = e.latlng.lat;
                    document.getElementById('longitude').value = e.latlng.lng;

                    if (marker) {
                        marker.setLatLng(e.latlng);
                    } else {
                        marker = L.marker(e.latlng, {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: '<div style="background:#10b981;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 3px 8px rgba(16,185,129,0.5);"></div>',
                                iconSize: [16, 16],
                                iconAnchor: [8, 8]
                            })
                        }).addTo(map);
                    }
                });
            }, 200);

            document.getElementById('addPlotForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                try {
                    const response = await fetch('../api/add_available_plot.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.success) {
                        themeUtils.showAlert('Plot added successfully!', 'success');
                        themeUtils.closeModal();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        themeUtils.showAlert(data.error || 'Failed to add plot', 'error');
                    }
                } catch (error) {
                    themeUtils.showAlert('An error occurred', 'error');
                }
            });
        }

        function toggleGridOptions() {
            const checkbox = document.getElementById('has_grid');
            const options = document.getElementById('gridOptions');
            options.style.display = checkbox.checked ? 'block' : 'none';
        }

        function viewPlotOnMap(lat, lng) {
            window.open(`map-view.php?lat=${lat}&lng=${lng}&zoom=19`, '_blank');
        }

        function viewGrid(plotId) {
            window.location.href = `plot-grid.php?id=${plotId}`;
        }

        function editPlot(id) {
            const plot = plotsData.find(p => p.id == id);
            if (!plot) { themeUtils.showAlert('Plot not found', 'error'); return; }

            const content = `
                <form id="editPlotForm" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="id" value="${plot.id}">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Number</label>
                        <input type="text" name="plot_number" value="${escapeAttr(plot.plot_number || '')}" placeholder="e.g., A-101" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes / Description</label>
                        <textarea name="notes" rows="3" placeholder="Additional information..." class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition resize-y">${escapeHtml(plot.notes || '')}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Expiration Date</label>
                        <input type="date" name="expiration_date" value="${escapeAttr(plot.expiration_date || '')}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Photo</label>
                        ${plot.photo ? `<img src="../uploads/plots/${plot.photo}" class="max-h-32 rounded-lg border border-slate-200 object-cover mb-2"><p class="text-xs text-slate-400 mb-2">Current photo (upload new to replace)</p>` : ''}
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg" class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" name="has_grid" onchange="toggleEditGridOptions()" ${plot.has_grid == 1 ? 'checked' : ''} class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-100">
                            <span class="text-sm font-medium text-slate-700">Enable Grid (Multiple Compartments)</span>
                        </label>
                    </div>
                    <div id="editGridOptions" style="display:${plot.has_grid == 1 ? 'block' : 'none'};">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Rows (1-10)</label>
                                <input type="number" name="grid_rows" min="1" max="10" value="${plot.grid_rows || 2}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Columns (1-10)</label>
                                <input type="number" name="grid_cols" min="1" max="10" value="${plot.grid_cols || 2}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-2">Location (Click on map to update)</div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Latitude <span class="text-rose-500">*</span></label>
                                <input type="number" id="editLat" name="latitude" step="0.00000001" required readonly value="${plot.latitude}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Longitude <span class="text-rose-500">*</span></label>
                                <input type="number" id="editLng" name="longitude" step="0.00000001" required readonly value="${plot.longitude}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                            </div>
                        </div>
                        <div id="editModalMap" style="height:300px;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;"></div>
                    </div>
                    <div class="flex gap-3 justify-end pt-2">
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-5 py-2.5 transition" onclick="themeUtils.closeModal()">Cancel</button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 shadow-sm transition"><i data-lucide="save" class="w-4 h-4"></i> Update Plot</button>
                    </div>
                </form>
            `;

            themeUtils.showModal(content, 'Edit Available Plot');

            setTimeout(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                const editMap = adminUtils.initAdminMap('editModalMap', { bearing: 315 });
                editMap.setView([plot.latitude, plot.longitude], 19);

                // Show other existing plots on the map (exclude current)
                if (window.plotsData) {
                    window.plotsData.forEach(function(p) {
                        if (p.id == plot.id) return;
                        if (p.latitude && p.longitude) {
                            if (p.has_grid) {
                                drawExistingPlotGrid(editMap, p);
                            } else {
                                const m = L.marker([parseFloat(p.latitude), parseFloat(p.longitude)], {
                                    icon: L.divIcon({
                                        className: 'existing-plot-marker',
                                        html: '<div style="width:12px;height:12px;background:#f59e0b;border:2px solid #fff;border-radius:50%;box-shadow:0 0 6px rgba(0,0,0,0.5);"></div>',
                                        iconSize: [12, 12],
                                        iconAnchor: [6, 6]
                                    })
                                }).addTo(editMap);
                                m.bindPopup(`<strong>${escapeHtml(p.plot_number || 'Plot #' + p.id)}</strong><br><span style="color:#b45309;font-size:11px;">Existing plot</span>`);
                            }
                        }
                        if (p.polygon) {
                            try {
                                const pts = JSON.parse(p.polygon);
                                if (Array.isArray(pts) && pts.length >= 3) {
                                    L.polygon(pts, {
                                        color: '#f59e0b',
                                        weight: 1.5,
                                        fillColor: '#f59e0b',
                                        fillOpacity: 0.08,
                                        dashArray: '4, 4'
                                    }).addTo(editMap).bindPopup(`<strong>${escapeHtml(p.plot_number || 'Plot #' + p.id)}</strong><br><span style="color:#b45309;font-size:11px;">Existing plot</span>`);
                                }
                            } catch (e) { /* ignore */ }
                        }
                    });
                }

                let editMarker = L.marker([plot.latitude, plot.longitude], {
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background:#10b981;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 3px 8px rgba(16,185,129,0.5);"></div>',
                        iconSize: [16, 16], iconAnchor: [8, 8]
                    })
                }).addTo(editMap);

                editMap.on('click', function(e) {
                    // Check if clicked location is inside the cemetery boundary
                    if (editMap._cemeteryPolygon && !isPointInPolygon(e.latlng.lat, e.latlng.lng, editMap._cemeteryPolygon)) {
                        themeUtils.showAlert('Cannot place plot outside the cemetery boundary (red border).', 'error');
                        return;
                    }

                    // Check if clicked location conflicts with another existing plot
                    const conflict = checkPlotConflict(e.latlng.lat, e.latlng.lng, plot.id);
                    if (conflict) {
                        themeUtils.showAlert(`Cannot move plot here — overlaps with existing plot: ${conflict}`, 'error');
                        return;
                    }
                    document.getElementById('editLat').value = e.latlng.lat;
                    document.getElementById('editLng').value = e.latlng.lng;
                    editMarker.setLatLng(e.latlng);
                });
            }, 100);

            document.getElementById('editPlotForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                try {
                    themeUtils.showLoading(this);
                    const response = await fetch('../api/update_available_plot.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    themeUtils.hideLoading();
                    if (data.success) {
                        themeUtils.showAlert('Plot updated successfully!', 'success');
                        themeUtils.closeModal();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        themeUtils.showAlert(data.error || 'Failed to update plot', 'error');
                    }
                } catch (error) {
                    themeUtils.hideLoading();
                    themeUtils.showAlert('An error occurred', 'error');
                }
            });
        }

        function toggleEditGridOptions() {
            const checkbox = document.querySelector('#editPlotForm [name="has_grid"]');
            const options = document.getElementById('editGridOptions');
            options.style.display = checkbox.checked ? 'block' : 'none';
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function escapeAttr(str) {
            if (!str) return '';
            return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // ---- Direct Renew (admin) ----
        function directRenewAvailable(plotId, plotName, currentExp) {
            const modal = document.getElementById('directRenewModal');
            document.getElementById('directRenewType').value = 'available';
            document.getElementById('directRenewRecordId').value = '';
            document.getElementById('directRenewPlotId').value = plotId;
            document.getElementById('directRenewInfo').innerHTML = `
                <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">Available Plot</div>
                <div class="font-semibold text-slate-900">${escapeHtml(plotName)}</div>
                <div class="text-xs text-slate-500 mt-1">Current expiration: ${currentExp ? new Date(currentExp).toLocaleDateString() : 'No expiry set'}</div>
            `;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDirectRenewModal() {
            const m = document.getElementById('directRenewModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        document.getElementById('directRenewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            try {
                const res = await fetch('../api/direct_renew.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    themeUtils.showAlert(data.message, 'success');
                    closeDirectRenewModal();
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    themeUtils.showAlert(data.message || 'Failed to renew plot', 'error');
                }
            } catch (err) {
                themeUtils.showAlert('Network error', 'error');
            }
        });

        function deletePlot(id, name) {
            themeUtils.confirm(
                `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                async () => {
                    try {
                        const response = await fetch('../api/delete_available_plot.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        const data = await response.json();
                        if (data.success) {
                            themeUtils.showAlert('Plot deleted successfully', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            themeUtils.showAlert(data.error || 'Failed to delete plot', 'error');
                        }
                    } catch (error) {
                        themeUtils.showAlert('An error occurred', 'error');
                    }
                }
            );
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
