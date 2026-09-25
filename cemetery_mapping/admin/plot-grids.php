<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

try {
    $stmt = $pdo->query(
        "SELECT pg.*,
                (SELECT COUNT(*) FROM plot_grid_cells c WHERE c.grid_id = pg.id) AS total_cells,
                (SELECT COUNT(*) FROM plot_grid_cells c WHERE c.grid_id = pg.id AND c.record_id IS NOT NULL) AS occupied_cells
         FROM plot_grids pg
         WHERE pg.status = 'active'
         ORDER BY pg.date_created DESC"
    );
    $grids = $stmt->fetchAll();
} catch (PDOException $e) { $grids = []; }

$totalGrids = count($grids);
$totalCells = 0;
$totalOccupied = 0;
foreach ($grids as $g) { $totalCells += $g['total_cells']; $totalOccupied += $g['occupied_cells']; }
$totalAvailable = $totalCells - $totalOccupied;
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
.admin-layout { background: #ffffff; }
.admin-layout::after { display: none; }
button svg, a svg, button i, a i { pointer-events: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="max-w-7xl mx-auto space-y-6">
    <!-- Intro -->
    <div class="flex items-center justify-between animate-[fadeUp_0.5s_ease]">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600"><i data-lucide="grid" class="w-5 h-5"></i></div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Plot Grids</h2>
                <p class="text-sm text-slate-500">Draw and manage standalone cemetery grids. Drag, rotate, and resize on the satellite map to match the cemetery layout.</p>
            </div>
        </div>
        <button type="button" onclick="openGridEditor(0)" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 shadow-sm transition">
            <i data-lucide="plus" class="w-4 h-4"></i> Create New Grid
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 animate-[fadeUp_0.6s_ease]">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600"><i data-lucide="grid" class="w-5 h-5"></i></div><div><p class="text-2xl font-bold text-slate-900"><?php echo $totalGrids; ?></p><p class="text-xs text-slate-500">Total Grids</p></div></div></div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600"><i data-lucide="square" class="w-5 h-5"></i></div><div><p class="text-2xl font-bold text-slate-900"><?php echo $totalCells; ?></p><p class="text-xs text-slate-500">Total Cells</p></div></div></div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600"><i data-lucide="check-square" class="w-5 h-5"></i></div><div><p class="text-2xl font-bold text-slate-900"><?php echo $totalOccupied; ?></p><p class="text-xs text-slate-500">Occupied</p></div></div></div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600"><i data-lucide="grid-3x3" class="w-5 h-5"></i></div><div><p class="text-2xl font-bold text-slate-900"><?php echo $totalAvailable; ?></p><p class="text-xs text-slate-500">Available</p></div></div></div>
    </div>

    <!-- List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden animate-[fadeUp_0.7s_ease]">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">All Plot Grids</h3>
            <span class="text-xs text-slate-400"><?php echo $totalGrids; ?> grid(s)</span>
        </div>
        <?php if (empty($grids)): ?>
            <div class="p-12 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-slate-100 flex items-center justify-center text-slate-400"><i data-lucide="grid" class="w-7 h-7"></i></div>
                <p class="text-sm text-slate-500 mb-4">No grids yet. Create one to start.</p>
                <button type="button" onclick="openGridEditor(0)" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 transition"><i data-lucide="plus" class="w-4 h-4"></i> Create First Grid</button>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($grids as $g):
                    $avail = $g['total_cells'] - $g['occupied_cells'];
                    $pct = $g['total_cells'] > 0 ? round(($g['occupied_cells'] / $g['total_cells']) * 100) : 0;
                ?>
                <div class="flex items-center gap-4 p-4 hover:bg-slate-50/60 transition">
                    <div class="w-12 h-12 rounded-xl flex-shrink-0 bg-emerald-50 flex items-center justify-center text-emerald-600"><i data-lucide="grid" class="w-5 h-5"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($g['name'] ?: 'Grid #' . $g['id']); ?></p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wide">Standalone</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php echo (int)$g['rows_count']; ?> &times; <?php echo (int)$g['cols_count']; ?> grid
                            &middot; <?php echo $g['total_cells']; ?> cells
                            &middot; <?php echo $g['occupied_cells']; ?> occupied
                            &middot; <?php echo $avail; ?> available
                            <?php if ($g['center_lat']): ?> &middot; <?php echo number_format((float)$g['center_lat'], 6); ?>, <?php echo number_format((float)$g['center_lng'], 6); ?><?php endif; ?>
                        </p>
                        <div class="mt-1.5 w-full max-w-xs h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-emerald-500 rounded-full transition-all" style="width: <?php echo $pct; ?>%"></div></div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="button" onclick="openGridEditor(<?php echo (int)$g['id']; ?>)" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition"><i data-lucide="grid" class="w-4 h-4"></i> Open Grid</button>
                        <button type="button" onclick="deleteGrid(<?php echo (int)$g['id']; ?>, '<?php echo htmlspecialchars($g['name'] ?: 'Grid #' . $g['id'], ENT_QUOTES); ?>')" class="inline-flex items-center gap-2 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-sm font-semibold px-3 py-2.5 transition"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Grid Editor/Create Modal -->
<div id="gridEditorModal" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeGridEditor()"></div>
    <div class="absolute inset-4 md:inset-2 bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100 bg-slate-50/60">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="grid" class="w-5 h-5"></i></div>
                <div><h3 class="text-lg font-bold text-slate-900" id="editorModalTitle">Grid: <span id="editorGridNameDisplay" class="text-emerald-700">Loading...</span></h3><p class="text-xs text-slate-500">Drag the grid over the satellite imagery. Click a cell to log its coordinates.</p></div>
            </div>
            <button type="button" onclick="closeGridEditor()" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <div class="flex-1 flex flex-col md:flex-row overflow-hidden">
            <!-- Controls -->
            <div class="w-full md:w-80 border-b md:border-b-0 md:border-r border-slate-100 p-4 overflow-y-auto bg-slate-50/40 space-y-4">
                <!-- Grid definition -->
                <div id="gridDefinitionPanel" class="space-y-3">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700 flex items-center gap-1.5"><i data-lucide="settings" class="w-3.5 h-3.5"></i> Grid Settings</h4>
                    <div><label class="block text-xs font-semibold text-slate-600 mb-1">Grid Name</label><input type="text" id="gridName" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Rows</label><input type="number" id="gridRows" min="1" value="5" onchange="regenerateGrid()" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"></div>
                        <div><label class="block text-xs font-semibold text-slate-600 mb-1">Columns</label><input type="number" id="gridCols" min="1" value="5" onchange="regenerateGrid()" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"></div>
                    </div>
                    <button type="button" onclick="previewGridOnMap()" id="previewGridBtn" class="w-full hidden inline-flex items-center justify-center gap-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold px-3 py-2 shadow-sm transition"><i data-lucide="map" class="w-4 h-4"></i> Preview on Map</button>
                    <p class="text-[10px] text-slate-400">For new grids, click the satellite map inside the cemetery to set the grid center, then click <strong>Preview on Map</strong> to draw the grid. Drag, rotate, and resize until it matches the cemetery.</p>
                </div>

                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700 mb-3 flex items-center gap-1.5"><i data-lucide="move" class="w-3.5 h-3.5"></i> Map Transform</h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-xs text-slate-600 mb-1"><span>Map Rotation</span><span id="editorBearingValue">315&deg;</span></div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" onclick="editorRotate(-15)" class="flex-1 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition">L</button>
                                <button type="button" onclick="editorRotateReset()" class="flex-1 h-8 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition">315&deg;</button>
                                <button type="button" onclick="editorRotate(15)" class="flex-1 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition">R</button>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-slate-600 mb-1"><span>Resize Whole Grid</span><span id="editorScaleVal">100%</span></div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="editorShrink()" class="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold">&minus;</button>
                                <input type="range" id="editorGridScale" min="25" max="200" value="100" step="5" oninput="editorUpdateScale(this.value)" class="flex-1 accent-emerald-600">
                                <button type="button" onclick="editorEnlarge()" class="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold">+</button>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Drag the corner handle on the map to resize directly.</p>
                        </div>
                        <div class="grid grid-cols-3 gap-1.5 w-32 mx-auto">
                            <div></div>
                            <button onclick="editorNudge(0,-1)" class="h-7 rounded bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 text-xs font-bold">&#9650;</button>
                            <div></div>
                            <button onclick="editorNudge(-1,0)" class="h-7 rounded bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 text-xs font-bold">&#9664;</button>
                            <button onclick="editorResetTransform()" class="h-7 rounded bg-emerald-100 text-emerald-700 text-xs font-bold hover:bg-emerald-200" title="Reset">&#8634;</button>
                            <button onclick="editorNudge(1,0)" class="h-7 rounded bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 text-xs font-bold">&#9654;</button>
                            <div></div>
                            <button onclick="editorNudge(0,1)" class="h-7 rounded bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 text-xs font-bold">&#9660;</button>
                            <div></div>
                        </div>
                        <button type="button" id="editorDragBtn" onclick="editorToggleDrag()" class="w-full rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-3 py-2 transition"><i data-lucide="move" class="w-4 h-4 inline"></i> <span id="editorDragLabel">Drag Grid</span></button>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700 mb-2 flex items-center gap-1.5"><i data-lucide="image" class="w-3.5 h-3.5"></i> Background</h4>
                    <input type="file" id="gridImageUpload" accept="image/*" class="hidden" onchange="loadGridImage(this)">
                    <button type="button" onclick="document.getElementById('gridImageUpload').click()" class="w-full inline-flex items-center justify-center gap-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-3 py-2.5 transition"><i data-lucide="image" class="w-4 h-4"></i> Upload Plan Image</button>
                    <p class="text-[10px] text-slate-400 mt-1">Or use satellite map below for real-world coordinates.</p>
                </div>

                <div class="border-t border-slate-200 pt-4">
                    <p class="text-[10px] text-slate-400"><i data-lucide="info" class="w-3 h-3 inline"></i> The grid is always drawn on the satellite map. Click the map to place the grid.</p>
                </div>

                <div id="editorCellInfo" class="hidden border-t border-slate-200 pt-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700 mb-2 flex items-center gap-1.5"><i data-lucide="square" class="w-3.5 h-3.5"></i> Selected Cell</h4>
                    <div id="editorCellDetails" class="text-xs text-slate-600 bg-slate-50 rounded-lg p-3 space-y-1"></div>
                </div>

                <div id="editorAssignPanel" class="hidden border-t border-slate-200 pt-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700 mb-2">Assign Deceased</h4>
                    <input type="text" id="recordSearchInput" placeholder="Search deceased..." oninput="searchRecordsForCell()" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    <div id="recordSearchResults" class="mt-2 max-h-48 overflow-y-auto space-y-1"></div>
                    <button type="button" onclick="unassignCellRecord()" id="unassignBtn" class="hidden w-full mt-2 inline-flex items-center justify-center gap-1.5 rounded-lg bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 text-sm font-semibold px-3 py-2 transition"><i data-lucide="user-x" class="w-4 h-4"></i> Remove from Cell</button>
                </div>

                <div id="editorCoordsLog" class="hidden border-t border-slate-200 pt-4">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Logged Coordinates</h4>
                        <button onclick="clearCoordLog()" class="text-[10px] text-rose-600 hover:underline">Clear</button>
                    </div>
                    <div id="editorCoordsLogContent" class="text-xs font-mono text-slate-600 bg-slate-50 rounded-lg p-3 max-h-40 overflow-y-auto space-y-1"></div>
                </div>

                <div class="border-t border-slate-200 pt-4 space-y-2">
                    <button type="button" onclick="saveGridToDb()" id="saveGridBtn" class="w-full inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 shadow-sm transition"><i data-lucide="save" class="w-4 h-4"></i> <span id="saveGridBtnLabel">Save Grid to Database</span></button>
                    <button type="button" onclick="deleteGridFromDb()" id="deleteGridBtn" class="w-full hidden inline-flex items-center justify-center gap-1.5 rounded-lg bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 text-sm font-semibold px-4 py-2.5 transition"><i data-lucide="trash-2" class="w-4 h-4"></i> Delete Grid</button>
                </div>

                <div id="editorStatus" class="hidden text-xs font-semibold rounded-lg px-3 py-2"></div>

                <div class="text-[10px] text-slate-400 leading-relaxed">
                    <strong>Tip:</strong> Toggle satellite map, drag grid to match the cemetery, then click a cell to log its real lat/lng. Click Save to persist.
                </div>
            </div>

            <!-- Canvas + Map -->
            <div class="flex-1 relative bg-slate-100 overflow-hidden" id="editorCanvasWrap">
                <canvas id="gridCanvas" class="absolute inset-0 w-full h-full cursor-grab active:cursor-grabbing z-10"></canvas>
                <div id="editorMapEl" class="absolute inset-0 z-0 hidden" style="background:#0a0a0a;"></div>
                <div id="editorEmptyState" class="absolute inset-0 flex items-center justify-center text-slate-400 text-sm pointer-events-none">No grid drawn yet.</div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="fixed inset-0 z-[10000] hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeDeleteConfirm()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-sm w-full overflow-hidden">
            <div class="p-6 text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-rose-100 flex items-center justify-center text-rose-600">
                    <i data-lucide="trash-2" class="w-7 h-7"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Delete Grid</h3>
                <p class="text-sm text-slate-500" id="deleteConfirmMsg">Are you sure you want to delete this grid and all its cells? This cannot be undone.</p>
            </div>
            <div class="flex border-t border-slate-100">
                <button type="button" onclick="closeDeleteConfirm()" class="flex-1 px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                <button type="button" id="deleteConfirmBtn" class="flex-1 px-4 py-3 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 transition border-l border-slate-100">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
<script src="../assets/js/theme.js"></script>
<script>
    const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
    const CEMETERY_POLYGON = [
        [6.184703227248634, 125.08388389813996],
        [6.183142672429919, 125.08538436803683],
        [6.182440110293303, 125.08476491148839],
        [6.184002859166614, 125.08321729641679]
    ];

    function isPointInPolygon(lat, lng, poly) {
        let inside = false;
        for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
            const yi = poly[i][0], xi = poly[i][1];
            const yj = poly[j][0], xj = poly[j][1];
            if (((yi > lat) !== (yj > lat)) && (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi)) inside = !inside;
        }
        return inside;
    }

    // ===============================================================
    // GRID EDITOR / CREATE
    // ===============================================================
    let gridCanvas, gridCtx, gridImage = null;
    let gridZoom = 1, gridOffsetX = 0, gridOffsetY = 0;
    let isGridDragging = false, gridLastX = 0, gridLastY = 0;
    let gridData = null;
    let gridCells = [];
    let currentGrid = null;
    let editorMode = 'create'; // 'create' or 'edit'
    let editorGridId = 0;
    let editorMap = null;
    let editorMapLayers = [];
    let editorMapVisible = false;
    let editorMapOffset = { lat: 0, lng: 0 };
    let editorMapScale = 1;
    let editorBearing = 45;
    let editorDragMode = false;
    let editorIsDragging = false;
    let editorDragMoved = false;
    let editorDragStart = null;
    let editorDragStartOffset = null;
    let editorResizeHandle = null;
    let editorIsResizing = false;
    let loggedCoords = [];
    let editorSelectedCell = null;
    let editorMapClickSetCenter = true; // for create mode, click sets center
    let editorHasCenterPreview = false; // has user previewed the grid on the map?

    async function openGridEditor(gridId) {
        editorGridId = gridId;
        editorMode = gridId === 0 ? 'create' : 'edit';
        document.getElementById('gridEditorModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        const sidebar = document.getElementById('adminSidebar');
        const header = document.querySelector('.admin-header');
        const main = document.querySelector('.admin-main');
        if (sidebar) sidebar.classList.add('hidden');
        if (header) header.classList.add('hidden');
        if (main) main.style.marginLeft = '0';

        resetEditorState();

        // Set up form for create or edit
        document.getElementById('gridRows').disabled = false;
        document.getElementById('gridCols').disabled = false;
        document.getElementById('gridName').disabled = false;
        document.getElementById('deleteGridBtn').classList.add('hidden');
        document.getElementById('editorAssignPanel').classList.add('hidden');

        if (editorMode === 'create') {
            document.getElementById('editorModalTitle').innerHTML = 'Create New Grid';
            document.getElementById('gridName').value = '';
            document.getElementById('gridRows').value = '5';
            document.getElementById('gridCols').value = '5';
            document.getElementById('saveGridBtnLabel').textContent = 'Create Grid';
            document.getElementById('previewGridBtn').classList.remove('hidden');
            currentGrid = { center_lat: null, center_lng: null };
            editorMapClickSetCenter = true;
            editorHasCenterPreview = false;
            showEditorStatus('Enter a name and rows/cols. Satellite map will open — click inside the cemetery to set the center.');
        } else {
            document.getElementById('editorModalTitle').innerHTML = 'Grid: <span id="editorGridNameDisplay" class="text-emerald-700">Loading...</span>';
            try {
                const res = await fetch(`../api/grids.php?action=get&grid_id=${gridId}`);
                const data = await res.json();
                if (data.success && data.grid) {
                    currentGrid = data.grid;
                    gridCells = data.cells || [];
                    document.getElementById('editorGridNameDisplay').textContent = data.grid.name || 'Grid #' + gridId;
                    document.getElementById('gridName').value = data.grid.name || '';
                    document.getElementById('gridRows').value = data.grid.rows;
                    document.getElementById('gridCols').value = data.grid.cols;
                    document.getElementById('gridRows').disabled = true;
                    document.getElementById('gridCols').disabled = true;
                    document.getElementById('saveGridBtnLabel').textContent = 'Update Grid';
                    document.getElementById('deleteGridBtn').classList.remove('hidden');
                    showEditorStatus('Loaded grid. Toggle satellite map to position it.');
                } else {
                    themeUtils.showAlert('Grid not found', 'error');
                    closeGridEditor();
                    return;
                }
            } catch (e) {
                themeUtils.showAlert('Failed to load grid', 'error');
                closeGridEditor();
                return;
            }
        }

        regenerateGrid();

        setTimeout(() => {
            initGridCanvas();
            editorMapVisible = true;
            if (!editorMap) {
                const baseLat = currentGrid.center_lat ? parseFloat(currentGrid.center_lat) : CEMETERY_CENTER[0];
                const baseLng = currentGrid.center_lng ? parseFloat(currentGrid.center_lng) : CEMETERY_CENTER[1];
                editorMap = L.map('editorMapEl', {
                    center: [baseLat, baseLng], zoom: 20, minZoom: 10, maxZoom: 22,
                    rotate: true, touchRotate: true, bearing: 315
                });
                L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', { maxZoom: 22, subdomains: ['mt0','mt1','mt2','mt3'] }).addTo(editorMap);
                L.polygon(CEMETERY_POLYGON, { color: '#b55a5a', weight: 2, fillOpacity: 0, dashArray: '5, 10' }).addTo(editorMap);

                editorMap.on('mousedown', editorStartMapDrag);
                editorMap.on('mousemove', editorMoveMapDrag);
                editorMap.on('mouseup', editorEndMapDrag);
                editorMap.on('mouseleave', editorEndMapDrag);
                editorMap.on('click', editorMapClick);
            } else {
                const baseLat = currentGrid.center_lat ? parseFloat(currentGrid.center_lat) : CEMETERY_CENTER[0];
                const baseLng = currentGrid.center_lng ? parseFloat(currentGrid.center_lng) : CEMETERY_CENTER[1];
                editorMap.setView([baseLat, baseLng], 20);
            }
            document.getElementById('editorMapEl').classList.remove('hidden');
            gridCanvas.classList.add('hidden');
            editorMap.off('click').on('click', editorMapClick);
            renderCanvasGrid();
            drawGridOnMap();
            setTimeout(() => { if (editorMap) { editorMap.invalidateSize(); drawGridOnMap(); } }, 50);
        }, 100);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeGridEditor() {
        document.getElementById('gridEditorModal').classList.add('hidden');
        document.body.style.overflow = '';
        const sidebar = document.getElementById('adminSidebar');
        const header = document.querySelector('.admin-header');
        const main = document.querySelector('.admin-main');
        if (sidebar) sidebar.classList.remove('hidden');
        if (header) header.classList.remove('hidden');
        if (main) main.style.marginLeft = '';
    }

    function resetEditorState() {
        gridData = null; gridCells = []; currentGrid = null; gridImage = null;
        gridZoom = 1; gridOffsetX = 0; gridOffsetY = 0;
        editorMapOffset = { lat: 0, lng: 0 }; editorMapScale = 1; editorBearing = 45;
        editorMapVisible = true; editorDragMode = false;
        loggedCoords = [];
        editorSelectedCell = null; editorHasCenterPreview = false;
        document.getElementById('editorCellInfo').classList.add('hidden');
        document.getElementById('editorAssignPanel').classList.add('hidden');
        document.getElementById('editorCoordsLog').classList.add('hidden');
        document.getElementById('editorCoordsLogContent').innerHTML = '';
        document.getElementById('deleteGridBtn').classList.add('hidden');
        document.getElementById('saveGridBtnLabel').textContent = 'Save Grid to Database';
        document.getElementById('editorGridScale').value = 100;
        document.getElementById('editorScaleVal').textContent = '100%';
        document.getElementById('editorBearingValue').textContent = '45°';
        hideEditorStatus();
        if (gridCtx && gridCanvas) gridCtx.clearRect(0, 0, gridCanvas.width, gridCanvas.height);
        clearGridMap();
    }

    function regenerateGrid() {
        const rows = parseInt(document.getElementById('gridRows').value) || 1;
        const cols = parseInt(document.getElementById('gridCols').value) || 1;
        gridData = { startX: 0, startY: 0, endX: cols * 100, endY: rows * 100, rows, cols };
        document.getElementById('editorEmptyState').classList.add('hidden');
        renderCanvasGrid();
        if (editorMapVisible) drawGridOnMap();
    }

    function initGridCanvas() {
        if (gridCanvas) return;
        gridCanvas = document.getElementById('gridCanvas');
        gridCtx = gridCanvas.getContext('2d');
        resizeGridCanvas();
        window.addEventListener('resize', resizeGridCanvas);
        gridCanvas.addEventListener('wheel', onGridWheel, { passive: false });
        gridCanvas.addEventListener('mousedown', onGridMouseDown);
        gridCanvas.addEventListener('mousemove', onGridMouseMove);
        gridCanvas.addEventListener('mouseup', onGridMouseUp);
        gridCanvas.addEventListener('mouseleave', onGridMouseUp);
        gridCanvas.addEventListener('click', onGridClick);
    }

    function resizeGridCanvas() {
        if (!gridCanvas) return;
        const wrap = document.getElementById('editorCanvasWrap');
        gridCanvas.width = wrap.clientWidth;
        gridCanvas.height = wrap.clientHeight;
        renderCanvasGrid();
    }

    function loadGridImage(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            gridImage = new Image();
            gridImage.onload = function() { document.getElementById('editorEmptyState').classList.add('hidden'); renderCanvasGrid(); };
            gridImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // ---- Canvas rendering ----
    function renderCanvasGrid() {
        if (!gridCtx || !gridCanvas) return;
        gridCtx.clearRect(0, 0, gridCanvas.width, gridCanvas.height);
        gridCtx.save();
        gridCtx.translate(gridCanvas.width / 2 + gridOffsetX, gridCanvas.height / 2 + gridOffsetY);
        gridCtx.scale(gridZoom, gridZoom);
        gridCtx.translate(-gridCanvas.width / 2, -gridCanvas.height / 2);

        if (gridImage && gridImage.complete) {
            const scale = Math.min(gridCanvas.width / gridImage.width, gridCanvas.height / gridImage.height);
            const w = gridImage.width * scale, h = gridImage.height * scale;
            gridCtx.drawImage(gridImage, (gridCanvas.width - w) / 2, (gridCanvas.height - h) / 2, w, h);
        } else if (!editorMapVisible) {
            gridCtx.strokeStyle = '#e2e8f0'; gridCtx.lineWidth = 1;
            for (let i = 0; i <= 20; i++) {
                const t = i / 20;
                gridCtx.beginPath(); gridCtx.moveTo(t * gridCanvas.width, 0); gridCtx.lineTo(t * gridCanvas.width, gridCanvas.height); gridCtx.stroke();
                gridCtx.beginPath(); gridCtx.moveTo(0, t * gridCanvas.height); gridCtx.lineTo(gridCanvas.width, t * gridCanvas.height); gridCtx.stroke();
            }
        }

        if (gridData) {
            const sx = gridData.startX, sy = gridData.startY;
            const ex = gridData.endX, ey = gridData.endY;
            const width = ex - sx, height = ey - sy;
            const cellW = width / gridData.cols;
            const cellH = height / gridData.rows;

            gridCtx.strokeStyle = '#10b981'; gridCtx.lineWidth = 2 / gridZoom; gridCtx.strokeRect(sx, sy, width, height);
            gridCtx.lineWidth = 1 / gridZoom;
            for (let c = 1; c < gridData.cols; c++) { const x = sx + c * cellW; gridCtx.beginPath(); gridCtx.moveTo(x, sy); gridCtx.lineTo(x, ey); gridCtx.stroke(); }
            for (let r = 1; r < gridData.rows; r++) { const y = sy + r * cellH; gridCtx.beginPath(); gridCtx.moveTo(sx, y); gridCtx.lineTo(ex, y); gridCtx.stroke(); }

            gridCtx.font = `bold ${11 / gridZoom}px Poppins, sans-serif`;
            gridCtx.textAlign = 'center'; gridCtx.textBaseline = 'middle';
            for (let r = 0; r < gridData.rows; r++) {
                for (let c = 0; c < gridData.cols; c++) {
                    const cx = sx + c * cellW + cellW / 2;
                    const cy = sy + r * cellH + cellH / 2;
                    const label = `${r + 1}-${c + 1}`;
                    const txtW = gridCtx.measureText(label).width;
                    gridCtx.fillStyle = 'rgba(255,255,255,0.85)'; gridCtx.fillRect(cx - txtW / 2 - 2, cy - 5, txtW + 4, 10);
                    gridCtx.fillStyle = '#047857'; gridCtx.fillText(label, cx, cy + 1);
                }
            }
        }
        gridCtx.restore();
    }

    // ---- Canvas pan/zoom ----
    function onGridWheel(e) { e.preventDefault(); gridZoom = Math.max(0.2, Math.min(5, gridZoom * (e.deltaY > 0 ? 0.9 : 1.1))); renderCanvasGrid(); }
    function onGridMouseDown(e) { isGridDragging = true; gridLastX = e.clientX; gridLastY = e.clientY; gridCanvas.style.cursor = 'grabbing'; }
    function onGridMouseMove(e) {
        if (!isGridDragging) return;
        gridOffsetX += e.clientX - gridLastX; gridOffsetY += e.clientY - gridLastY;
        gridLastX = e.clientX; gridLastY = e.clientY; renderCanvasGrid();
    }
    function onGridMouseUp() { isGridDragging = false; if (gridCanvas) gridCanvas.style.cursor = 'grab'; }
    function onGridClick(e) {
        if (isGridDragging && (Math.abs(e.clientX - gridLastX) > 3 || Math.abs(e.clientY - gridLastY) > 3)) return;
        if (!gridData) return;
        const rect = gridCanvas.getBoundingClientRect();
        const canvasX = e.clientX - rect.left;
        const canvasY = e.clientY - rect.top;
        const worldX = (canvasX - gridCanvas.width / 2 - gridOffsetX) / gridZoom + gridCanvas.width / 2;
        const worldY = (canvasY - gridCanvas.height / 2 - gridOffsetY) / gridZoom + gridCanvas.height / 2;
        const sx = gridData.startX, sy = gridData.startY;
        const ex = gridData.endX, ey = gridData.endY;
        const cellW = (ex - sx) / gridData.cols, cellH = (ey - sy) / gridData.rows;
        if (worldX < sx || worldX > ex || worldY < sy || worldY > ey) return;
        const col = Math.floor((worldX - sx) / cellW);
        const row = Math.floor((worldY - sy) / cellH);
        if (col < 0 || col >= gridData.cols || row < 0 || row >= gridData.rows) return;

        const cellData = gridCells.find(c => c.row === (row + 1) && c.col === (col + 1));
        if (cellData) showCellInfo(cellData, row + 1, col + 1);
    }

    function showCellInfo(cell, row, col) {
        editorSelectedCell = cell;
        const info = document.getElementById('editorCellInfo');
        const details = document.getElementById('editorCellDetails');
        const assignPanel = document.getElementById('editorAssignPanel');
        const unassignBtn = document.getElementById('unassignBtn');
        info.classList.remove('hidden');
        assignPanel.classList.remove('hidden');

        if (cell.record_id) {
            details.innerHTML = `
                <div><strong>Cell:</strong> ${row}-${col}</div>
                <div><strong>Occupied by:</strong> ${escapeHtml(cell.decedent_name || 'Unknown')}</div>
                <div><strong>Plot #:</strong> ${escapeHtml(cell.record_plot_number || 'N/A')}</div>
            `;
            unassignBtn.classList.remove('hidden');
        } else {
            details.innerHTML = `
                <div><strong>Cell:</strong> ${row}-${col}</div>
                <div><strong>Status:</strong> Available</div>
                <div class="text-slate-400">Search for a deceased person below to assign.</div>
            `;
            unassignBtn.classList.add('hidden');
        }
        document.getElementById('recordSearchInput').value = '';
        document.getElementById('recordSearchResults').innerHTML = '';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ---- Satellite map ----
    function editorToggleSatellite() {
        const canvas = document.getElementById('gridCanvas');
        const mapEl = document.getElementById('editorMapEl');
        const empty = document.getElementById('editorEmptyState');

        // Always satellite map; this function now just refreshes the grid overlay
        canvas.classList.add('hidden');
        mapEl.classList.remove('hidden');
        if (empty) empty.classList.add('hidden');
        drawGridOnMap();
        setTimeout(() => { if (editorMap) editorMap.invalidateSize(); drawGridOnMap(); }, 250);
    }

    function editorMapClick(e) {
        if (editorMode !== 'create' || !editorMapClickSetCenter) return;
        const lat = e.latlng ? e.latlng.lat : null;
        const lng = e.latlng ? e.latlng.lng : null;
        if (!lat || !lng) return;
        currentGrid.center_lat = lat;
        currentGrid.center_lng = lng;
        editorHasCenterPreview = true;
        editorMap.setView([lat, lng], 20);
        drawGridOnMap();
        const inside = isPointInPolygon(lat, lng, CEMETERY_POLYGON);
        showEditorStatus(inside ? `Grid drawn at center ${lat.toFixed(6)}, ${lng.toFixed(6)}. Use controls or the corner handle to align it.` : `Center ${lat.toFixed(6)}, ${lng.toFixed(6)} is outside the cemetery. Drag the grid into the boundary or click inside.`, inside ? false : true);
    }

    function previewGridOnMap() {
        if (!currentGrid || !currentGrid.center_lat || !currentGrid.center_lng) {
            showEditorStatus('Click anywhere on the satellite map to draw the grid at that center.', false);
            return;
        }
        editorHasCenterPreview = true;
        editorMap.setView([currentGrid.center_lat, currentGrid.center_lng], 20);
        drawGridOnMap();
        showEditorStatus('Grid preview reloaded. Drag, rotate, and resize to align.');
    }

    const GRID_BEARING_RAD = 45 * Math.PI / 180; // rotate grid 45° to match cemetery orientation
    const GRID_CELL_METERS = 2.0; // match visitor portal cell size

    function getMapGridGeometry() {
        const baseLat = parseFloat(currentGrid.center_lat) || CEMETERY_CENTER[0];
        const baseLng = parseFloat(currentGrid.center_lng) || CEMETERY_CENTER[1];
        const cellMeters = GRID_CELL_METERS * editorMapScale;
        const metersPerLat = 111320;
        const metersPerLng = 111320 * Math.cos(baseLat * Math.PI / 180);
        const centerLat = baseLat + editorMapOffset.lat;
        const centerLng = baseLng + editorMapOffset.lng;
        return { cellMeters, metersPerLat, metersPerLng, centerLat, centerLng };
    }

    // Convert an (x, y) offset in meters (east, north) to [lat, lng], rotated by GRID_BEARING_RAD
    function metersToLatLng(dx, dy, centerLat, centerLng, metersPerLat, metersPerLng) {
        const cos = Math.cos(GRID_BEARING_RAD);
        const sin = Math.sin(GRID_BEARING_RAD);
        const rdx = dx * cos - dy * sin;
        const rdy = dx * sin + dy * cos;
        return [centerLat + rdy / metersPerLat, centerLng + rdx / metersPerLng];
    }

    function getCellCorners(r, c, cellMeters, centerLat, centerLng, metersPerLat, metersPerLng) {
        const half = cellMeters / 2;
        const rows = gridData.rows;
        const cols = gridData.cols;
        // x: east, y: north from grid center
        const cx = (c - (cols - 1) / 2) * cellMeters;
        const cy = ((rows - 1) / 2 - r) * cellMeters;
        const corners = [
            [cx + half, cy + half], // top right
            [cx - half, cy + half], // top left
            [cx - half, cy - half], // bottom left
            [cx + half, cy - half]  // bottom right
        ];
        return corners.map(pt => metersToLatLng(pt[0], pt[1], centerLat, centerLng, metersPerLat, metersPerLng));
    }

    function drawGridOnMap() {
        if (!editorMap || !gridData || !currentGrid || !currentGrid.center_lat) return;
        if (editorMode === 'create' && !editorHasCenterPreview) return;
        clearGridMap();

        const geo = getMapGridGeometry();
        const rows = gridData.rows; const cols = gridData.cols;

        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const corners = getCellCorners(r, c, geo.cellMeters, geo.centerLat, geo.centerLng, geo.metersPerLat, geo.metersPerLng);
                const cellCenter = [
                    (corners[0][0] + corners[2][0]) / 2,
                    (corners[0][1] + corners[2][1]) / 2
                ];
                const label = `${r + 1}-${c + 1}`;
                const cellData = gridCells.find(cc => cc.row === (r + 1) && cc.col === (c + 1));
                const isOccupied = cellData && cellData.record_id;

                const style = isOccupied
                    ? { color: '#ef4444', weight: 2.5, fillColor: '#ef4444', fillOpacity: 0.55 }
                    : { color: '#10b981', weight: 2.5, fillColor: '#10b981', fillOpacity: 0.35 };

                const poly = L.polygon(corners, style).addTo(editorMap);
                poly.on('click', function(e) {
                    L.DomEvent.stopPropagation(e);
                    if (editorDragMode && editorDragMoved) { editorDragMoved = false; return; }
                    logCoord(label, cellCenter[0], cellCenter[1]);
                    if (cellData) showCellInfo(cellData, r + 1, c + 1);
                });

                editorMapLayers.push(poly);
            }
        }

        if (editorResizeHandle) { editorMap.removeLayer(editorResizeHandle); editorResizeHandle = null; }
        if (!editorIsResizing) {
            // Place resize handle at the outer (screen) top-right of the grid
            const rows = gridData.rows; const cols = gridData.cols;
            const halfW = cols * geo.cellMeters / 2;
            const halfH = rows * geo.cellMeters / 2;
            const neCorner = metersToLatLng(halfW, halfH, geo.centerLat, geo.centerLng, geo.metersPerLat, geo.metersPerLng);

            editorResizeHandle = L.marker(neCorner, {
                icon: L.divIcon({
                    className: 'grid-resize-handle',
                    html: '<div style="width:16px;height:16px;background:#fff;border:2px solid #10b981;border-radius:4px;box-shadow:0 2px 6px rgba(0,0,0,0.4);cursor:nwse-resize;"></div>',
                    iconSize: [16, 16], iconAnchor: [8, 8]
                }),
                draggable: true
            }).addTo(editorMap);

            editorResizeHandle.on('dragstart', function() { editorIsResizing = true; });
            editorResizeHandle.on('drag', function(e) {
                const pos = e.target.getLatLng();
                const g = getMapGridGeometry();
                // Distance from center to dragged handle in meters
                const dLat = g.centerLat - pos.lat;
                const dLng = pos.lng - g.centerLng;
                const distMeters = Math.sqrt(Math.pow(dLat * g.metersPerLat, 2) + Math.pow(dLng * g.metersPerLng, 2));
                const rows = gridData.rows; const cols = gridData.cols;
                const baseHalfDiagonal = Math.sqrt(Math.pow(cols, 2) + Math.pow(rows, 2)) * GRID_CELL_METERS / 2;
                let newScale = distMeters / baseHalfDiagonal;
                newScale = Math.max(0.25, Math.min(2, newScale));
                editorMapScale = newScale;
                const pct = Math.round(newScale * 100);
                document.getElementById('editorGridScale').value = pct;
                document.getElementById('editorScaleVal').textContent = `${pct}%`;
                drawGridOnMap();
            });
            editorResizeHandle.on('dragend', function() { editorIsResizing = false; drawGridOnMap(); });

            editorMapLayers.push(editorResizeHandle);
        }

        // Center marker
        const centerMarker = L.marker([geo.centerLat, geo.centerLng], {
            icon: L.divIcon({
                className: 'grid-center-marker',
                html: '<div style="width:14px;height:14px;background:#0ea5e9;border:3px solid #fff;border-radius:50%;box-shadow:0 0 0 2px #0ea5e9,0 3px 8px rgba(0,0,0,0.5);"></div>',
                iconSize: [20, 20], iconAnchor: [10, 10]
            })
        }).addTo(editorMap);
        editorMapLayers.push(centerMarker);
    }

    function clearGridMap() {
        if (!editorMap) return;
        editorMapLayers.forEach(l => editorMap.removeLayer(l));
        editorMapLayers = [];
    }

    // ---- Map drag ----
    function editorToggleDrag() {
        editorDragMode = !editorDragMode;
        const btn = document.getElementById('editorDragBtn');
        const label = document.getElementById('editorDragLabel');
        if (editorDragMode) {
            label.textContent = 'Dragging Active';
            btn.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
            if (editorMap) editorMap.dragging.disable();
            document.getElementById('editorMapEl').style.cursor = 'move';
        } else {
            label.textContent = 'Drag Grid';
            btn.classList.remove('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
            if (editorMap) editorMap.dragging.enable();
            document.getElementById('editorMapEl').style.cursor = '';
            editorIsDragging = false;
        }
    }

    function editorStartMapDrag(e) {
        if (!editorDragMode || !editorMap) return;
        editorIsDragging = true;
        editorDragMoved = false;
        editorDragStart = e.latlng;
        editorDragStartOffset = { ...editorMapOffset };
    }
    function editorMoveMapDrag(e) {
        if (!editorIsDragging || !editorMap || !e.latlng) return;
        editorDragMoved = true;
        editorMapOffset.lat = editorDragStartOffset.lat + (e.latlng.lat - editorDragStart.lat);
        editorMapOffset.lng = editorDragStartOffset.lng + (e.latlng.lng - editorDragStart.lng);
        drawGridOnMap();
    }
    function editorEndMapDrag() { editorIsDragging = false; }

    // ---- Transform controls ----
    function editorRotate(deg) {
        if (editorMap && typeof editorMap.setBearing === 'function')
            editorMap.setBearing(editorMap.getBearing() + deg);
    }
    function editorRotateReset() {
        if (editorMap && typeof editorMap.setBearing === 'function')
            editorMap.setBearing(315);
    }
    function editorUpdateScale(val) {
        editorMapScale = parseFloat(val) / 100;
        document.getElementById('editorScaleVal').textContent = val + '%';
        drawGridOnMap();
    }
    function editorShrink() {
        const input = document.getElementById('editorGridScale');
        const newValue = Math.max(parseFloat(input.min), parseFloat(input.value) - 5);
        input.value = newValue; editorUpdateScale(newValue);
    }
    function editorEnlarge() {
        const input = document.getElementById('editorGridScale');
        const newValue = Math.min(parseFloat(input.max), parseFloat(input.value) + 5);
        input.value = newValue; editorUpdateScale(newValue);
    }
    function editorNudge(dx, dy) {
        const step = 0.000005;
        editorMapOffset.lng += dx * step;
        editorMapOffset.lat -= dy * step;
        drawGridOnMap();
    }
    function editorResetTransform() {
        editorMapOffset = { lat: 0, lng: 0 }; editorMapScale = 1;
        document.getElementById('editorGridScale').value = 100;
        document.getElementById('editorScaleVal').textContent = '100%';
        if (editorMap && typeof editorMap.setBearing === 'function') editorMap.setBearing(315);
        if (editorMapVisible) drawGridOnMap();
    }

    // ---- Coordinates log ----
    function logCoord(label, lat, lng) {
        const panel = document.getElementById('editorCoordsLog');
        const log = document.getElementById('editorCoordsLogContent');
        panel.classList.remove('hidden');
        const time = new Date().toLocaleTimeString();
        loggedCoords.push({ label, lat, lng, time });
        const entry = document.createElement('div');
        entry.innerHTML = `<span class="text-emerald-600">[${time}]</span> <strong>${label}</strong> &mdash; lat: ${lat.toFixed(8)}, lng: ${lng.toFixed(8)}`;
        log.appendChild(entry); log.scrollTop = log.scrollHeight;
    }

    function clearCoordLog() {
        loggedCoords = [];
        document.getElementById('editorCoordsLogContent').innerHTML = '';
        document.getElementById('editorCoordsLog').classList.add('hidden');
    }

    // ---- Database ----
    async function saveGridToDb() {
        const name = document.getElementById('gridName').value.trim();
        const rows = parseInt(document.getElementById('gridRows').value);
        const cols = parseInt(document.getElementById('gridCols').value);

        if (!name) { showEditorStatus('Please enter a grid name.', true); return; }
        if (!currentGrid || !currentGrid.center_lat || !currentGrid.center_lng) { showEditorStatus('Please set the grid center on the satellite map.', true); return; }

        const baseLat = parseFloat(currentGrid.center_lat);
        const baseLng = parseFloat(currentGrid.center_lng);
        const newCenterLat = baseLat + editorMapOffset.lat;
        const newCenterLng = baseLng + editorMapOffset.lng;

        const payload = {
            name: name,
            center_lat: newCenterLat, center_lng: newCenterLng,
            start_x: 0, start_y: 0,
            end_x: cols * 100, end_y: rows * 100,
            rows: rows, cols: cols
        };

        if (editorMode === 'edit') {
            payload.action = 'update';
            payload.grid_id = currentGrid.id;
        } else {
            payload.action = 'create';
        }

        try {
            const res = await fetch('../api/grids.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                showEditorStatus(editorMode === 'create' ? 'Grid created!' : 'Grid updated!');
                setTimeout(() => { closeGridEditor(); location.reload(); }, 800);
            } else { showEditorStatus(data.message || 'Failed to save grid.', true); }
        } catch (e) { showEditorStatus('Network error.', true); }
    }

    // ---- Delete confirmation modal ----
    let pendingDeleteAction = null;

    function showDeleteConfirm(message, onConfirm) {
        const modal = document.getElementById('deleteConfirmModal');
        document.getElementById('deleteConfirmMsg').textContent = message;
        const btn = document.getElementById('deleteConfirmBtn');
        // Clone to remove old listeners
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', async () => {
            newBtn.disabled = true;
            newBtn.textContent = 'Deleting...';
            await onConfirm();
            newBtn.disabled = false;
            newBtn.textContent = 'Delete';
            closeDeleteConfirm();
        });
        modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeDeleteConfirm() {
        document.getElementById('deleteConfirmModal').classList.add('hidden');
    }

    async function deleteGridFromDb() {
        if (!currentGrid || editorMode !== 'edit') return;
        showDeleteConfirm('Delete this grid, all its cells, AND all associated deceased records? This cannot be undone.', async () => {
            try {
                const res = await fetch('../api/grids.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', grid_id: currentGrid.id })
                });
                const data = await res.json();
                if (data.success) {
                    themeUtils.showAlert('Grid deleted.', 'success');
                    closeGridEditor();
                    setTimeout(() => location.reload(), 800);
                } else { themeUtils.showAlert(data.message || 'Failed to delete grid.', 'error'); }
            } catch (e) { themeUtils.showAlert('Network error', 'error'); }
        });
    }

    async function deleteGrid(gridId, gridName) {
        showDeleteConfirm(`Delete "${gridName}", all its cells, AND all associated deceased records? This cannot be undone.`, async () => {
            try {
                const res = await fetch('../api/grids.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', grid_id: gridId })
                });
                const data = await res.json();
                if (data.success) { themeUtils.showAlert('Grid deleted.', 'success'); setTimeout(() => location.reload(), 800); }
                else { themeUtils.showAlert(data.message || 'Failed to delete grid.', 'error'); }
            } catch (e) { themeUtils.showAlert('Network error', 'error'); }
        });
    }

    // ---- Record assignment ----
    let searchTimer = null;
    function searchRecordsForCell() {
        clearTimeout(searchTimer);
        const q = document.getElementById('recordSearchInput').value.trim();
        const results = document.getElementById('recordSearchResults');
        if (q.length < 2) { results.innerHTML = ''; return; }
        searchTimer = setTimeout(async () => {
            try {
                const res = await fetch(`../api/grids.php?action=search_records&q=${encodeURIComponent(q)}`);
                const data = await res.json();
                if (data.success && data.records.length > 0) {
                    results.innerHTML = data.records.map(r => `
                        <div onclick="assignRecordToCell(${r.id}, '${escapeAttr(r.decedent_name || '')}')" class="cursor-pointer rounded-lg border border-slate-200 hover:border-emerald-400 hover:bg-emerald-50 px-3 py-2 text-xs transition">
                            <strong>${escapeHtml(r.decedent_name)}</strong>
                            ${r.family_name ? ' &middot; ' + escapeHtml(r.family_name) : ''}
                            ${r.plot_number ? ' &middot; ' + escapeHtml(r.plot_number) : ''}
                        </div>
                    `).join('');
                } else { results.innerHTML = '<div class="text-xs text-slate-400 px-3 py-2">No records found.</div>'; }
            } catch (e) { results.innerHTML = '<div class="text-xs text-rose-500 px-3 py-2">Search failed.</div>'; }
        }, 300);
    }

    async function assignRecordToCell(recordId, recordName) {
        if (!editorSelectedCell) { themeUtils.showAlert('No cell selected.', 'error'); return; }
        const cellId = editorSelectedCell.id;
        try {
            const res = await fetch('../api/grids.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'assign_cell_record', cell_id: cellId, record_id: recordId })
            });
            const data = await res.json();
            if (data.success) {
                themeUtils.showAlert(`Assigned ${recordName} to cell.`, 'success');
                await reloadGrid();
            } else { themeUtils.showAlert(data.message || 'Failed to assign record.', 'error'); }
        } catch (e) { themeUtils.showAlert('Network error', 'error'); }
    }

    async function unassignCellRecord() {
        if (!editorSelectedCell || !editorSelectedCell.record_id) return;
        showDeleteConfirm('Remove the deceased record from this cell?', async () => {
            try {
                const res = await fetch('../api/grids.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'unassign_cell_record', cell_id: editorSelectedCell.id })
                });
                const data = await res.json();
                if (data.success) {
                    themeUtils.showAlert('Record removed from cell.', 'success');
                    await reloadGrid();
                } else { themeUtils.showAlert(data.message || 'Failed to remove record.', 'error'); }
            } catch (e) { themeUtils.showAlert('Network error', 'error'); }
        });
    }

    async function reloadGrid() {
        if (editorMode !== 'edit' || !currentGrid) return;
        try {
            const res = await fetch(`../api/grids.php?action=get&grid_id=${currentGrid.id}`);
            const data = await res.json();
            if (data.success && data.grid) {
                currentGrid = data.grid;
                gridCells = data.cells || [];
                editorMapOffset = { lat: 0, lng: 0 };
                if (editorMapVisible) drawGridOnMap();
                renderCanvasGrid();
            }
        } catch (e) {}
    }

    function showEditorStatus(msg, isError = false) {
        const box = document.getElementById('editorStatus');
        box.textContent = msg;
        box.className = 'text-xs font-semibold rounded-lg px-3 py-2 ' + (isError
            ? 'text-rose-700 bg-rose-50 border border-rose-200'
            : 'text-emerald-700 bg-emerald-50 border border-emerald-200');
        box.classList.remove('hidden');
    }
    function hideEditorStatus() { document.getElementById('editorStatus').classList.add('hidden'); }

    function escapeHtml(s) { return s ? String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])) : ''; }
    function escapeAttr(s) { return escapeHtml(s).replace(/'/g, '&#39;'); }

    document.addEventListener('DOMContentLoaded', function() { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
</body>
</html>
