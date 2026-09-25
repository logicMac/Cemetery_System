<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Filters
$search = trim($_GET['search'] ?? '');
$barangay_filter = trim($_GET['barangay'] ?? '');
$status_filter = trim($_GET['type'] ?? 'all');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query with filters
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(decedent_name LIKE ? OR family_name LIKE ? OR plot_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($barangay_filter)) {
    $where[] = "barangay = ?";
    $params[] = $barangay_filter;
}

if ($status_filter === 'premium') {
    $where[] = "is_fenced = 1";
} elseif ($status_filter === 'standard') {
    $where[] = "is_fenced = 0";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) FROM burial_records $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get records
$sql = "
    SELECT id, decedent_name, family_name, visitor_id, birth_date, death_date, plot_number,
           barangay, is_fenced, photo, date_added
    FROM burial_records
    $whereClause
    ORDER BY date_added DESC
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get visitors for the owner dropdown
$visitors = $pdo->query("SELECT id, full_name, email FROM visitors WHERE is_active = 1 ORDER BY full_name ASC")->fetchAll();

// Get stats
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn(),
    'premium' => $pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_fenced = 1")->fetchColumn(),
    'standard' => $pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_fenced = 0")->fetchColumn()
];

// Barangays of Polomolok, South Cotabato
$barangays = [
    'Bentung',
    'Cannery Site',
    'Crossing Palkan',
    'Glamang',
    'Kinilis',
    'Klinan 6',
    'Koronadal Proper',
    'Lam Caliaf',
    'Landan',
    'Lapu',
    'Lumakil',
    'Magsaysay',
    'Maligo',
    'Pagalungan',
    'Palkan',
    'Poblacion',
    'Polo',
    'Rubber',
    'Silway 7',
    'Silway 8',
    'Sulit',
    'Sumbakil',
    'Upper Klinan',
];
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
<div class="flex items-center justify-between flex-wrap gap-4 mb-6 animate-fade">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
            <i data-lucide="file-text" class="w-5 h-5"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-slate-900">Burial Records</h2>
            <p class="text-sm text-slate-500">Manage all cemetery burial records</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" onclick="openImportModal()" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold px-4 py-2.5 shadow-sm transition">
            <i data-lucide="upload" class="w-4 h-4"></i>
            Import CSV / Excel
        </button>
        <button type="button" onclick="openAddModal()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 shadow-sm transition">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add New Record
        </button>
    </div>
</div>

<!-- Statistics Overview -->
<div class="grid grid-cols-3 gap-4 mb-6 animate-fade">
    <button type="button" onclick="filterByType('all')"
        class="text-left bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-emerald-300 hover:shadow-md transition <?php echo $status_filter === 'all' ? 'ring-2 ring-emerald-200 bg-emerald-50/40' : ''; ?>">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?php echo $stats['total']; ?></div>
                <div class="text-xs text-slate-500">Total Records</div>
            </div>
        </div>
    </button>
    <button type="button" onclick="filterByType('premium')"
        class="text-left bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-emerald-300 hover:shadow-md transition <?php echo $status_filter === 'premium' ? 'ring-2 ring-emerald-200 bg-emerald-50/40' : ''; ?>">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                <i data-lucide="crown" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?php echo $stats['premium']; ?></div>
                <div class="text-xs text-slate-500">Premium Plots</div>
            </div>
        </div>
    </button>
    <button type="button" onclick="filterByType('standard')"
        class="text-left bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-emerald-300 hover:shadow-md transition <?php echo $status_filter === 'standard' ? 'ring-2 ring-emerald-200 bg-emerald-50/40' : ''; ?>">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                <i data-lucide="map-pin" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?php echo $stats['standard']; ?></div>
                <div class="text-xs text-slate-500">Standard Plots</div>
            </div>
        </div>
    </button>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6 animate-fade">
    <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr_1fr_auto_auto] gap-4 items-end">
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5 flex items-center gap-1.5">
                <i data-lucide="search" class="w-3.5 h-3.5"></i> Search
            </label>
            <input type="text" id="searchInput" placeholder="Name, family, or plot..." oninput="debouncedSearch()"
                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5 flex items-center gap-1.5">
                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> Barangay
            </label>
            <select id="barangayFilter" onchange="fetchRecords()"
                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                <option value="">All</option>
                <?php foreach ($barangays as $brgy): ?>
                    <option value="<?php echo htmlspecialchars($brgy); ?>"><?php echo htmlspecialchars($brgy); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5 flex items-center gap-1.5">
                <i data-lucide="tag" class="w-3.5 h-3.5"></i> Type
            </label>
            <select id="typeFilter" onchange="fetchRecords()"
                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                <option value="all">All</option>
                <option value="premium">Premium</option>
                <option value="standard">Standard</option>
            </select>
        </div>
        <!-- View Toggle -->
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5 flex items-center gap-1.5">
                <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i> View
            </label>
            <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-lg">
                <button type="button" id="viewGrid" onclick="switchView('grid')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i> Grid
                </button>
                <button type="button" id="viewList" onclick="switchView('list')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700">
                    <i data-lucide="list" class="w-4 h-4"></i> List
                </button>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="fetchRecords()"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                <i data-lucide="search" class="w-4 h-4"></i> Search
            </button>
            <button type="button" onclick="clearFilters()"
                class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
                <i data-lucide="x" class="w-4 h-4"></i> Clear
            </button>
        </div>
    </div>
</div>

<!-- Results Container -->
<div id="resultsContainer">
    <div class="text-center py-10 text-slate-400 text-sm">Loading records...</div>
</div>

<!-- Pagination Container -->
<div id="paginationContainer"></div>

</main>
</div>

<!-- Add Record Modal -->
<div id="addRecordModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeAddModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl max-h-[94vh] overflow-y-auto">
        <!-- Modal header -->
        <div class="sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Add New Burial Record</h3>
                    <p class="text-xs text-slate-500">Fill in the details below</p>
                </div>
            </div>
            <button type="button" onclick="closeAddModal()" class="w-9 h-9 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal body -->
        <form id="addRecordForm" enctype="multipart/form-data" class="p-6 space-y-5">
            <!-- Decedent details -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="user" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Decedent Details</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Decedent Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="decedent_name" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Family Name</label>
                        <input type="text" name="family_name" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Owner (Visitor) <span class="text-xs text-slate-400">— for renewals</span></label>
                        <select name="visitor_id" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            <option value="">No linked visitor</option>
                            <?php foreach ($visitors as $v): ?>
                                <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['full_name'] . ' (' . $v['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Birth Date</label>
                        <input type="date" name="birth_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Death Date</label>
                        <input type="date" name="death_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Burial Date <span class="text-xs text-slate-400">— schedule</span></label>
                        <input type="date" name="burial_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Burial Time</label>
                        <input type="time" name="burial_time" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Burial Status</label>
                        <select name="is_buried" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            <option value="1">Buried / Done</option>
                            <option value="0">Scheduled (not yet buried)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Plot info -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Plot Information</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Number</label>
                        <input type="text" name="plot_number" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Expiration Date</label>
                        <input type="date" name="expiration_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Barangay</label>
                        <select name="barangay" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            <option value="">Select Barangay</option>
                            <?php foreach ($barangays as $brgy): ?>
                                <option value="<?php echo htmlspecialchars($brgy); ?>"><?php echo htmlspecialchars($brgy); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" name="is_fenced" value="1" class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-100">
                            <span class="text-sm font-medium text-slate-700">Premium / Fenced Plot</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Memory & photo -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="book-open" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Memory & Photo</h4>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Memory / Biography</label>
                        <textarea name="memory_space" rows="3" placeholder="Share memories or biographical information..." class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition resize-y"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Photo</label>
                        <div onclick="document.getElementById('modalPhoto').click()" class="cursor-pointer rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-400 hover:bg-emerald-50/40 transition p-6 text-center">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                <i data-lucide="image" class="w-5 h-5"></i>
                            </div>
                            <p class="text-sm text-slate-500">Click to upload photo <span class="text-slate-400">(max 5MB)</span></p>
                            <input type="file" id="modalPhoto" name="photo" accept="image/jpeg,image/png,image/jpg" class="hidden" onchange="previewModalPhoto(this)">
                        </div>
                        <div id="modalPhotoPreview" class="mt-3 hidden">
                            <img src="" alt="Preview" class="max-h-40 rounded-lg border border-slate-200 object-cover">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid / Cell Assignment -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="layout-grid" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Grid Assignment</h4>
                </div>
                <p class="text-sm text-slate-500 mb-3">Optional: assign the deceased to an available grid cell. Occupied cells show the current decedent.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Grid</label>
                        <select id="modalGrid" name="grid_id" onchange="loadGridCellsForRecord(this.value)" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            <option value="">No grid</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Grid Cell</label>
                        <select id="modalCell" name="cell" disabled class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            <option value="">Select grid first</option>
                        </select>
                    </div>
                </div>
                <div id="modalGridPreview" class="mt-3 hidden max-h-48 overflow-y-auto rounded-lg border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase sticky top-0">
                            <tr><th class="px-3 py-2 text-left">Cell</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Decedent</th></tr>
                        </thead>
                        <tbody id="modalGridPreviewBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>

            <!-- Location -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="navigation" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Location Coordinates</h4>
                </div>
                <p class="text-sm text-slate-500 mb-3">Click on the map to set the burial location, or click a colored grid cell to assign a plot grid cell directly (green = available, red = occupied by a decedent).</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Latitude <span class="text-rose-500">*</span></label>
                        <input type="number" id="modalLat" name="latitude" step="0.00000001" required readonly class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Longitude <span class="text-rose-500">*</span></label>
                        <input type="number" id="modalLng" name="longitude" step="0.00000001" required readonly class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                    </div>
                </div>
                <div id="modalMapPicker" class="rounded-xl overflow-hidden border border-slate-200 w-full" style="height: 400px;"></div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 shadow-sm transition">
                    <i data-lucide="check" class="w-4 h-4"></i> Save Record
                </button>
                <button type="button" onclick="closeAddModal()" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-5 py-2.5 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Record Modal -->
<div id="viewRecordModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeViewModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Record Details</h3>
                    <p class="text-xs text-slate-500">View burial record information</p>
                </div>
            </div>
            <button type="button" onclick="closeViewModal()" class="w-9 h-9 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="viewModalBody" class="p-6"></div>
    </div>
</div>

<!-- Edit Record Modal -->
<div id="editRecordModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl max-h-[94vh] overflow-y-auto">
        <div class="sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <i data-lucide="pencil" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Burial Record</h3>
                    <p class="text-xs text-slate-500">Update record details</p>
                </div>
            </div>
            <button type="button" onclick="closeEditModal()" class="w-9 h-9 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="editRecordForm" enctype="multipart/form-data" class="p-6 space-y-5">
            <input type="hidden" name="id">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="user" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Decedent Details</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Decedent Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="decedent_name" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Family Name</label>
                        <input type="text" name="family_name" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Owner (Visitor) <span class="text-xs text-slate-400">— for renewals</span></label>
                        <select name="visitor_id" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            <option value="">No linked visitor</option>
                            <?php foreach ($visitors as $v): ?>
                                <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['full_name'] . ' (' . $v['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Birth Date</label>
                        <input type="date" name="birth_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Death Date</label>
                        <input type="date" name="death_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Burial Date <span class="text-xs text-slate-400">— schedule</span></label>
                        <input type="date" name="burial_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Burial Time</label>
                        <input type="time" name="burial_time" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Burial Status</label>
                        <select name="is_buried" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            <option value="1">Buried / Done</option>
                            <option value="0">Scheduled (not yet buried)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Plot Information</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Number</label>
                        <input type="text" name="plot_number" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot Expiration Date</label>
                        <input type="date" name="expiration_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Barangay</label>
                        <select name="barangay" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                            <option value="">Select Barangay</option>
                            <?php foreach ($barangays as $brgy): ?>
                                <option value="<?php echo htmlspecialchars($brgy); ?>"><?php echo htmlspecialchars($brgy); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" name="is_fenced" value="1" class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-100">
                            <span class="text-sm font-medium text-slate-700">Premium / Fenced Plot</span>
                        </label>
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="book-open" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Memory & Photo</h4>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Memory / Biography</label>
                        <textarea name="memory_space" rows="3" placeholder="Share memories or biographical information..." class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition resize-y"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Photo</label>
                        <div onclick="document.getElementById('editPhoto').click()" class="cursor-pointer rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-400 hover:bg-emerald-50/40 transition p-6 text-center">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                <i data-lucide="image" class="w-5 h-5"></i>
                            </div>
                            <p class="text-sm text-slate-500">Click to upload new photo <span class="text-slate-400">(max 5MB)</span></p>
                            <input type="file" id="editPhoto" name="photo" accept="image/jpeg,image/png,image/jpg" class="hidden" onchange="previewEditPhoto(this)">
                        </div>
                        <div id="editPhotoPreview" class="mt-3 hidden">
                            <img src="" alt="Preview" class="max-h-40 rounded-lg border border-slate-200 object-cover">
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="navigation" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Location Coordinates</h4>
                </div>
                <p class="text-sm text-slate-500 mb-3">Click on the map to update the burial location</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Latitude <span class="text-rose-500">*</span></label>
                        <input type="number" id="editLat" name="latitude" step="0.00000001" required readonly class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Longitude <span class="text-rose-500">*</span></label>
                        <input type="number" id="editLng" name="longitude" step="0.00000001" required readonly class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                    </div>
                </div>
                <div id="editMapPicker" class="rounded-xl overflow-hidden border border-slate-200 w-full" style="height: 400px;"></div>
            </div>
            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 shadow-sm transition">
                    <i data-lucide="save" class="w-4 h-4"></i> Update Record
                </button>
                <button type="button" onclick="closeEditModal()" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-5 py-2.5 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Direct Renew Modal (Admin) -->
<!-- Import CSV Modal -->
<div id="importModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0">
                <i data-lucide="upload" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-bold text-slate-900">Import Records from CSV / Excel</h2>
        </div>
        <form id="importForm" enctype="multipart/form-data" class="space-y-4">
            <div class="bg-slate-50 rounded-xl p-4 text-sm text-slate-600 space-y-2">
                <p class="font-semibold text-slate-700">How to use:</p>
                <ol class="list-decimal list-inside space-y-1 text-xs">
                    <li>In Excel, go to <strong>File &rarr; Save As</strong> and choose <strong>CSV (Comma delimited)</strong>.</li>
                    <li>Upload the .csv file below.</li>
                </ol>
                <p class="text-xs pt-1"><strong>Required columns</strong> (first row = headers):<br>
                <code class="text-[11px] bg-white border border-slate-200 rounded px-1.5 py-0.5 inline-block mt-1">decedent_name, family_name, birth_date, death_date, burial_date, burial_time, expiration_date, plot_number, barangay, memory_space, is_fenced, latitude, longitude</code></p>
                <p class="text-xs text-slate-500">Only <strong>decedent_name</strong> is required. Dates use YYYY-MM-DD, time uses HH:MM. is_fenced: 1 or 0. Rows missing latitude/longitude are imported without a map pin.</p>
                <a href="../api/download_template.php" class="inline-flex items-center gap-1.5 mt-2 text-xs font-semibold text-sky-700 hover:text-sky-800 underline">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Download CSV template
                </a>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">CSV File <span class="text-rose-500">*</span></label>
                <input type="file" name="csv_file" accept=".csv,text/csv" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 file:mr-4 file:rounded-lg file:border-0 file:bg-sky-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700 hover:file:bg-sky-100">
            </div>
            <div id="importResult" class="hidden rounded-xl p-4 text-sm"></div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeImportModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold transition">Import</button>
            </div>
        </form>
    </div>
</div>

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
<script>
    const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
    // Actual cemetery boundary polygon (4 corners, expanded ~15% to include all graves)
    const CEMETERY_POLYGON = [
        [6.184703227248634, 125.08388389813996],
        [6.183142672429919, 125.08538436803683],
        [6.182440110293303, 125.08476491148839],
        [6.184002859166614, 125.08321729641679]
    ];
    // Bounding box for fitBounds (derived from polygon)
    const CEMETERY_BOUNDS = [
        [6.182440110293303, 125.08321729641679],
        [6.184703227248634, 125.08538436803683]
    ];

    let currentPage = 1;
    let searchTimer = null;
    let modalMap = null;
    let modalMarker = null;
    let currentView = 'grid';
    let lastRecords = [];
    let modalGridLayers = [];
    let modalRecordLayers = [];
    let modalSelectedGridId = null;
    let modalSelectedCell = null;

    // Check if a lat/lng point is inside the cemetery polygon (ray-casting)
    function isInsideCemetery(lat, lng) {
        const poly = CEMETERY_POLYGON;
        let inside = false;
        for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
            const xi = poly[i][1], yi = poly[i][0];
            const xj = poly[j][1], yj = poly[j][0];
            const intersect = ((yi > lat) !== (yj > lat)) &&
                (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi);
            if (intersect) inside = !inside;
        }
        return inside;
    }

    function showBoundsError() {
        if (typeof themeUtils !== 'undefined' && themeUtils.showAlert) {
            themeUtils.showAlert('Please click inside the cemetery boundary (red box).', 'error');
        } else {
            alert('Please click inside the cemetery boundary (red box).');
        }
    }

    function switchView(view) {
        currentView = view;
        const btnGrid = document.getElementById('viewGrid');
        const btnList = document.getElementById('viewList');
        if (view === 'grid') {
            btnGrid.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm';
            btnList.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700';
        } else {
            btnList.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition bg-white text-emerald-700 shadow-sm';
            btnGrid.className = 'inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-semibold transition text-slate-500 hover:text-slate-700';
        }
        if (lastRecords.length > 0) {
            renderResults(lastRecords);
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function renderResults(records) {
        const container = document.getElementById('resultsContainer');
        if (currentView === 'grid') {
            container.innerHTML = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">' + records.map(r => renderCard(r)).join('') + '</div>';
        } else {
            container.innerHTML = renderListView(records);
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function formatExpiration(dateStr) {
        if (!dateStr) return { text: 'No expiry', badge: '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold uppercase bg-slate-100 text-slate-500">No expiry</span>' };
        const today = new Date();
        today.setHours(0,0,0,0);
        const exp = new Date(dateStr);
        exp.setHours(0,0,0,0);
        const diff = Math.floor((exp - today) / (1000 * 60 * 60 * 24));
        const dateText = exp.toLocaleDateString();
        if (diff < 0) {
            return { text: dateText, badge: `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold uppercase bg-rose-100 text-rose-700" title="Expired ${Math.abs(diff)} days ago">Expired · ${dateText}</span>` };
        }
        if (diff <= 30) {
            return { text: dateText, badge: `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold uppercase bg-amber-100 text-amber-700" title="Expires in ${diff} days">Expiring · ${dateText}</span>` };
        }
        return { text: dateText, badge: `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold uppercase bg-emerald-100 text-emerald-700" title="Active, expires in ${diff} days">Active · ${dateText}</span>` };
    }

    function renderListView(records) {
        const rows = records.map(r => {
            const birth = r.birth_date ? new Date(r.birth_date).getFullYear() : '?';
            const death = r.death_date ? new Date(r.death_date).getFullYear() : '?';
            const badge = r.is_fenced == 1
                ? '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase bg-amber-100 text-amber-700">Premium</span>'
                : '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase bg-emerald-100 text-emerald-700">Standard</span>';
            const gridCell = (r.grid_name && r.cell_row)
                ? `<span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-sky-100 text-sky-700" title="Grid: ${escapeHtml(r.grid_name)}">${escapeHtml(r.grid_name)} · R${r.cell_row}-C${r.cell_col}</span>`
                : '<span class="text-xs text-slate-400">—</span>';
            const exp = formatExpiration(r.expiration_date);
            const safeName = escapeHtml(r.decedent_name).replace(/'/g, "\\'");
            const scheduledBadge = (r.is_buried == 0 && r.burial_date)
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-amber-100 text-amber-700" title="Burial scheduled ${r.burial_date}">Scheduled</span>`
                : '';
            return `<tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                <td class="px-4 py-3"><span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">#${r.id}</span></td>
                <td class="px-4 py-3"><div class="flex items-center gap-2.5">
                    ${r.photo ? `<img src="../uploads/photos/${escapeHtml(r.photo)}" class="w-9 h-9 rounded-lg object-cover flex-shrink-0" alt="">` : `<div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-300 flex-shrink-0"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div>`}
                    <div><div class="text-sm font-semibold text-slate-900">${escapeHtml(r.decedent_name)}</div>${scheduledBadge}</div>
                </div></td>
                <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(r.family_name || 'N/A')}</td>
                <td class="px-4 py-3 text-sm text-slate-600">${birth} - ${death}</td>
                <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(r.plot_number || 'N/A')}</td>
                <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(r.barangay || 'N/A')}</td>
                <td class="px-4 py-3">${gridCell}</td>
                <td class="px-4 py-3">${exp.badge}</td>
                <td class="px-4 py-3">${badge}</td>
                <td class="px-4 py-3"><div class="flex gap-1.5">
                    <button type="button" onclick="viewRecord(${r.id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 transition" title="View"><i data-lucide="eye" class="w-4 h-4"></i></button>
                    <button type="button" onclick="editRecord(${r.id})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-700 transition" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                    <button type="button" onclick="directRenew('burial', ${r.id}, '${escapeHtml(r.decedent_name)}', '${r.expiration_date || ''}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-amber-100 text-slate-600 hover:text-amber-700 transition" title="Renew Plot"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
                    <button type="button" onclick="deleteRecord(${r.id}, '${safeName}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-600 transition" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </div></td>
            </tr>`;
        }).join('');

        return `<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-left">
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Decedent</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Family</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Years</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Plot</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Barangay</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Grid / Cell</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Expiration</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        </div>`;
    }

    // ---- Add Record Modal ----
    async function loadGridsForRecord() {
        try {
            const res = await fetch('../api/grids.php?action=list');
            const data = await res.json();
            const select = document.getElementById('modalGrid');
            select.innerHTML = '<option value="">No grid</option>';
            if (data.success && data.grids) {
                data.grids.forEach(g => {
                    select.insertAdjacentHTML('beforeend', `<option value="${g.id}">${escapeHtml(g.name || 'Unnamed Grid')} (${g.rows}x${g.cols})</option>`);
                });
            }
        } catch (e) {
            console.error('Failed to load grids', e);
        }
    }

    async function loadGridCellsForRecord(gridId) {
        const cellSelect = document.getElementById('modalCell');
        const preview = document.getElementById('modalGridPreview');
        const previewBody = document.getElementById('modalGridPreviewBody');
        cellSelect.disabled = true;
        cellSelect.innerHTML = '<option value="">Select grid first</option>';
        preview.classList.add('hidden');
        previewBody.innerHTML = '';
        if (!gridId) return;
        try {
            const res = await fetch(`../api/grids.php?action=get&grid_id=${gridId}`);
            const data = await res.json();
            cellSelect.innerHTML = '<option value="">Select a cell</option>';
            if (data.success && data.cells) {
                data.cells.forEach(c => {
                    const occupied = c.record_id ? ` · ${escapeHtml(c.decedent_name || 'Occupied')}` : ' · Available';
                    const disabled = c.record_id ? 'disabled' : '';
                    cellSelect.insertAdjacentHTML('beforeend', `<option value="${c.row},${c.col}" ${disabled}>R${c.row}-C${c.col}${occupied}</option>`);
                    const rowClass = c.record_id ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700';
                    const status = c.record_id ? 'Occupied' : 'Available';
                    const name = c.record_id ? escapeHtml(c.decedent_name || '—') : '—';
                    previewBody.insertAdjacentHTML('beforeend', `<tr class="${rowClass}"><td class="px-3 py-2 font-medium">R${c.row}-C${c.col}</td><td class="px-3 py-2">${status}</td><td class="px-3 py-2">${name}</td></tr>`);
                });
                preview.classList.remove('hidden');
            }
            cellSelect.disabled = false;
        } catch (e) {
            console.error('Failed to load grid cells', e);
        }
    }

    function openImportModal() {
        const m = document.getElementById('importModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.getElementById('importResult').classList.add('hidden');
        document.getElementById('importResult').innerHTML = '';
    }

    function closeImportModal() {
        const m = document.getElementById('importModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    document.getElementById('importForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const result = document.getElementById('importResult');
        btn.disabled = true;
        btn.textContent = 'Importing...';
        try {
            const res = await fetch('../api/import_records.php', { method: 'POST', body: new FormData(this) });
            const data = await res.json();
            result.classList.remove('hidden');
            if (data.success) {
                result.className = 'rounded-xl p-4 text-sm bg-emerald-50 border border-emerald-200 text-emerald-800';
                let html = `<strong>Imported ${data.imported} record(s).</strong>`;
                if (data.skipped) html += `<br>Skipped ${data.skipped} row(s).`;
                if (data.errors && data.errors.length) {
                    html += '<ul class="mt-2 list-disc list-inside text-xs text-rose-600">' + data.errors.slice(0, 10).map(e2 => `<li>${e2}</li>`).join('') + '</ul>';
                }
                result.innerHTML = html;
                this.reset();
                if (typeof loadRecords === 'function') loadRecords();
                else location.reload();
            } else {
                result.className = 'rounded-xl p-4 text-sm bg-rose-50 border border-rose-200 text-rose-700';
                result.innerHTML = data.message || 'Import failed';
            }
        } catch (err) {
            result.classList.remove('hidden');
            result.className = 'rounded-xl p-4 text-sm bg-rose-50 border border-rose-200 text-rose-700';
            result.innerHTML = 'Network error during import.';
        }
        btn.disabled = false;
        btn.textContent = 'Import';
    });

    function openAddModal() {
        const modal = document.getElementById('addRecordModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        loadGridsForRecord();

        // Initialize map after modal is visible
        setTimeout(() => {
            if (!modalMap) {
                initModalMap();
            } else {
                modalMap.invalidateSize();
                modalMap.fitBounds(CEMETERY_BOUNDS, { padding: [30, 30], maxZoom: 19, animate: false });
                if (typeof modalMap.setBearing === 'function') modalMap.setBearing(315);
                loadGridsOnModalMap();
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 200);
    }

    function closeAddModal() {
        const modal = document.getElementById('addRecordModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        // Reset form
        document.getElementById('addRecordForm').reset();
        document.getElementById('modalPhotoPreview').classList.add('hidden');
        if (modalMarker) {
            modalMarker.remove();
            modalMarker = null;
        }
    }

    function initModalMap() {
        modalMap = L.map('modalMapPicker', { rotate: true, touchRotate: true, bearing: 315 }).setView(CEMETERY_CENTER, 17);

        L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        }).addTo(modalMap);

        L.polygon(CEMETERY_POLYGON, {
            color: '#b55a5a',
            weight: 2,
            fillOpacity: 0,
            dashArray: '5, 10'
        }).addTo(modalMap);

        modalMap.on('click', function(e) {
            if (!isInsideCemetery(e.latlng.lat, e.latlng.lng)) {
                showBoundsError();
                return;
            }
            document.getElementById('modalLat').value = e.latlng.lat;
            document.getElementById('modalLng').value = e.latlng.lng;

            if (modalMarker) {
                modalMarker.setLatLng(e.latlng);
            } else {
                modalMarker = L.marker(e.latlng, {
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background: #10b981; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 8px rgba(16,185,129,0.5);"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(modalMap);
            }
        });

        setTimeout(() => { modalMap.invalidateSize(); }, 200);
        setTimeout(() => {
            modalMap.invalidateSize();
            modalMap.fitBounds(CEMETERY_BOUNDS, { padding: [30, 30], maxZoom: 19, animate: false });
            if (typeof modalMap.setBearing === 'function') modalMap.setBearing(315);
            loadGridsOnModalMap();
        }, 400);
    }

    // ---- Show grids + deceased on the Add Record map (same as visitor side) ----
    async function loadGridsOnModalMap() {
        if (!modalMap) return;
        modalGridLayers.forEach(l => modalMap.removeLayer(l));
        modalGridLayers = [];
        try {
            const response = await fetch('../api/grids.php?action=list');
            const data = await response.json();
            if (data.success && data.grids) {
                for (const grid of data.grids) {
                    if (!grid.center_lat || !grid.center_lng) continue;
                    await drawModalGrid(grid);
                }
            }
        } catch (error) {
            console.error('Error loading grids on record map:', error);
        }
        // Also load existing burial records (deceased markers)
        loadExistingRecordsOnModalMap();
    }

    // ---- Show existing deceased/burial records as markers on the Add Record map ----
    async function loadExistingRecordsOnModalMap() {
        if (!modalMap) return;
        modalRecordLayers.forEach(l => modalMap.removeLayer(l));
        modalRecordLayers = [];
        try {
            const res = await fetch('../api/get_all_records.php');
            const data = await res.json();
            console.log('Existing records loaded:', data);
            if (!data.success || !data.records) return;
            data.records.forEach(r => {
                if (!r.latitude || !r.longitude) return;
                const lat = parseFloat(r.latitude);
                const lng = parseFloat(r.longitude);
                const marker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'existing-record-marker',
                        html: '<div style="width:18px;height:18px;background:#f59e0b;border:3px solid #fff;border-radius:50%;box-shadow:0 0 10px rgba(245,158,11,0.8);"></div>',
                        iconSize: [18, 18],
                        iconAnchor: [9, 9]
                    }),
                    zIndexOffset: 1000
                });
                const photoHtml = r.photo
                    ? `<img src="../uploads/photos/${escapeHtml(r.photo)}" style="width:60px;height:60px;border-radius:8px;object-fit:cover;margin-top:6px;">`
                    : '';
                marker.bindPopup(`
                    <div style="min-width:180px;">
                        <strong style="font-size:0.95rem;color:#f59e0b;">${escapeHtml(r.decedent_name || 'Unknown')}</strong><br>
                        <span style="font-size:0.8rem;color:#666;">${escapeHtml(r.plot_number || 'No plot #')}</span><br>
                        <span style="font-size:0.75rem;color:#999;">${escapeHtml(r.barangay || '')}</span>
                        ${photoHtml}
                    </div>
                `);
                marker.addTo(modalMap);
                modalRecordLayers.push(marker);
            });
            console.log('Placed', modalRecordLayers.length, 'record markers on modal map');
        } catch (e) {
            console.error('Error loading existing records on modal map:', e);
        }
    }

    async function drawModalGrid(grid) {
        const rows = parseInt(grid.rows);
        const cols = parseInt(grid.cols);
        const centerLat = parseFloat(grid.center_lat);
        const centerLng = parseFloat(grid.center_lng);
        const cellSize = 2; // 2 meters per cell (same as visitor map)
        const rotationAngle = 45; // degrees (same as admin/visitor grid rendering)
        const angleRad = rotationAngle * Math.PI / 180;
        const metersPerLat = 111320;
        const metersPerLng = 111320 * Math.cos(centerLat * Math.PI / 180);

        let cellsData = [];
        try {
            const resp = await fetch(`../api/grids.php?action=get&grid_id=${grid.id}`);
            const data = await resp.json();
            if (data.success && data.cells) cellsData = data.cells;
        } catch (e) { /* ignore */ }

        const gridName = grid.name || 'Grid #' + grid.id;

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
                const latLngs = corners.map(c => {
                    const rx = c.x * Math.cos(angleRad) - c.y * Math.sin(angleRad);
                    const ry = c.x * Math.sin(angleRad) + c.y * Math.cos(angleRad);
                    return [cellLat + (ry / metersPerLat), cellLng + (rx / metersPerLng)];
                });

                const rowNum = row + 1, colNum = col + 1;
                const label = `R${rowNum}-C${colNum}`;
                const cellData = cellsData.find(c => c.row === rowNum && c.col === colNum);
                const isOccupied = cellData && cellData.record_id;

                const cellStyle = isOccupied ? {
                    color: '#ef4444', weight: 2, opacity: 1,
                    fillColor: '#ef4444', fillOpacity: 0.5
                } : {
                    color: '#10b981', weight: 1.5, opacity: 0.9,
                    fillColor: '#10b981', fillOpacity: 0.2
                };

                const statusText = isOccupied ? 'Occupied' : 'Available (click to select)';
                const decedentInfo = isOccupied && cellData.decedent_name
                    ? `<br><span style="font-size:0.85rem;">${escapeHtml(cellData.decedent_name)}</span>`
                    : '';

                const cellLayer = L.polygon(latLngs, cellStyle).bindPopup(`
                    <div style="text-align:center;padding:4px;">
                        <strong style="font-size:1rem;color:${isOccupied ? '#ef4444' : '#10b981'};display:block;margin-bottom:4px;">${escapeHtml(gridName)}</strong>
                        <span style="font-size:0.9rem;">Cell ${label}</span>
                        ${decedentInfo}
                        <div style="margin-top:6px;padding:3px 10px;background:${isOccupied ? 'rgba(239,68,68,0.15)' : 'rgba(16,185,129,0.15)'};border-radius:12px;display:inline-block;font-size:0.8rem;font-weight:600;color:${isOccupied ? '#ef4444' : '#10b981'};border:1px solid ${isOccupied ? 'rgba(239,68,68,0.3)' : 'rgba(16,185,129,0.3)'};">
                            ${statusText}
                        </div>
                    </div>
                `);

                cellLayer.on('click', function(e) {
                    L.DomEvent.stopPropagation(e);
                    if (isOccupied) return;
                    selectModalGridCell(grid.id, gridName, rowNum, colNum, cellLat, cellLng);
                });

                cellLayer.addTo(modalMap);
                modalGridLayers.push(cellLayer);
            }
        }
    }

    // Select a grid cell clicked on the map: syncs the dropdowns + lat/lng
    async function selectModalGridCell(gridId, gridName, row, col, lat, lng) {
        modalSelectedGridId = gridId;
        modalSelectedCell = `${row},${col}`;

        // Sync dropdowns (populate them if needed)
        const gridSelect = document.getElementById('modalGrid');
        if (![...gridSelect.options].some(o => o.value == gridId)) {
            await loadGridsForRecord();
        }
        gridSelect.value = gridId;
        await loadGridCellsForRecord(gridId);
        const cellSelect = document.getElementById('modalCell');
        cellSelect.value = `${row},${col}`;

        // Set marker + coordinates to the cell center
        document.getElementById('modalLat').value = lat;
        document.getElementById('modalLng').value = lng;
        if (modalMarker) {
            modalMarker.setLatLng([lat, lng]);
        } else {
            modalMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'custom-marker',
                    html: '<div style="background: #10b981; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 8px rgba(16,185,129,0.5);"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                })
            }).addTo(modalMap);
        }

        themeUtils.showAlert(`Selected ${gridName} · R${row}-C${col}`, 'success');
    }

    function previewModalPhoto(input) {
        const preview = document.getElementById('modalPhotoPreview');
        const img = preview.querySelector('img');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Modal form submission
    document.getElementById('addRecordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        try {
            themeUtils.showLoading(this);
            const response = await fetch('../api/add_record.php', { method: 'POST', body: formData });
            const data = await response.json();
            themeUtils.hideLoading();
            if (data.success) {
                closeAddModal();
                themeUtils.showAlert('Record added successfully!', 'success');
                fetchRecords();
            } else {
                themeUtils.showAlert(data.error || 'Failed to add record', 'error');
            }
        } catch (error) {
            themeUtils.hideLoading();
            themeUtils.showAlert('An error occurred', 'error');
        }
    });

    // Edit form submission
    document.getElementById('editRecordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        try {
            themeUtils.showLoading(this);
            const response = await fetch('../api/update_record.php', { method: 'POST', body: formData });
            const data = await response.json();
            themeUtils.hideLoading();
            if (data.success) {
                closeEditModal();
                themeUtils.showAlert('Record updated successfully!', 'success');
                fetchRecords();
            } else {
                themeUtils.showAlert(data.error || 'Failed to update record', 'error');
            }
        } catch (error) {
            themeUtils.hideLoading();
            themeUtils.showAlert('An error occurred', 'error');
        }
    });

    // Close modals on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAddModal();
            closeViewModal();
            closeEditModal();
        }
    });

    function getFilterParams(page) {
        return {
            search: document.getElementById('searchInput').value.trim(),
            barangay: document.getElementById('barangayFilter').value,
            type: document.getElementById('typeFilter').value,
            page: page || 1
        };
    }

    function buildQueryString(params) {
        const parts = [];
        for (const [key, val] of Object.entries(params)) {
            if (val) parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(val));
        }
        return parts.length ? '?' + parts.join('&') : '';
    }

    function debouncedSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            currentPage = 1;
            fetchRecords();
        }, 350);
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('barangayFilter').value = '';
        document.getElementById('typeFilter').value = 'all';
        currentPage = 1;
        fetchRecords();
    }

    function filterByType(type) {
        document.getElementById('typeFilter').value = type;
        currentPage = 1;
        fetchRecords();
    }

    async function fetchRecords() {
        const params = getFilterParams(currentPage);
        const qs = buildQueryString(params);
        const container = document.getElementById('resultsContainer');
        const paginationContainer = document.getElementById('paginationContainer');

        container.innerHTML = '<div class="text-center py-10 text-slate-400 text-sm">Loading...</div>';
        paginationContainer.innerHTML = '';

        try {
            const response = await fetch('../api/filter_records.php' + qs);
            const data = await response.json();

            if (data.success && data.records.length > 0) {
                lastRecords = data.records;
                renderResults(data.records);
                renderPagination(data);
            } else {
                container.innerHTML = renderEmptyState(params);
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (error) {
            container.innerHTML = '<div class="text-center py-10 text-slate-400 text-sm">Failed to load records. Please try again.</div>';
        }
    }

    function renderCard(r) {
        const photo = r.photo
            ? `<img src="../uploads/photos/${escapeHtml(r.photo)}" class="w-full h-48 rounded-xl object-cover mb-4" alt="${escapeHtml(r.decedent_name)}">`
            : `<div class="w-full h-48 rounded-xl bg-slate-100 flex items-center justify-center mb-4 text-slate-300"><svg width="56" height="56" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div>`;

        const birth = r.birth_date ? new Date(r.birth_date).getFullYear() : '?';
        const death = r.death_date ? new Date(r.death_date).getFullYear() : '?';
        const badge = r.is_fenced == 1
            ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-amber-100 text-amber-700 border border-amber-200">Premium</span>'
            : '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide bg-emerald-100 text-emerald-700 border border-emerald-200">Standard</span>';
        const gridCell = (r.grid_name && r.cell_row)
            ? `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold bg-sky-100 text-sky-700" title="Grid: ${escapeHtml(r.grid_name)}">${escapeHtml(r.grid_name)} · R${r.cell_row}-C${r.cell_col}</span>`
            : '<span class="text-xs text-slate-400">No grid assigned</span>';
        const exp = formatExpiration(r.expiration_date);
        const scheduledBadge = (r.is_buried == 0 && r.burial_date)
            ? `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-amber-100 text-amber-700 ml-1" title="Burial scheduled ${r.burial_date}">Scheduled ${r.burial_date}</span>`
            : '';

        return `
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col hover:border-emerald-300 hover:shadow-md transition">
                ${photo}
                <div class="text-base font-semibold text-slate-900 mb-2">${escapeHtml(r.decedent_name)}${scheduledBadge}</div>
                <div class="text-sm text-slate-500 space-y-1.5 mb-3">
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg> ${escapeHtml(r.family_name || 'N/A')}</div>
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> ${birth} - ${death}</div>
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg> Plot: ${escapeHtml(r.plot_number || 'N/A')}</div>
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg> ${escapeHtml(r.barangay || 'N/A')}</div>
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg> ${gridCell}</div>
                    <div class="flex items-center gap-2">${exp.badge}</div>
                </div>
                <div class="pt-3 border-t border-slate-100 mb-3">${badge}</div>
                <div class="flex gap-2">
                    <button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-medium transition" onclick="viewRecord(${r.id})">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg> View
                    </button>
                    <button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-medium transition" onclick="editRecord(${r.id})">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Edit
                    </button>
                    <button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-amber-50 hover:border-amber-200 hover:text-amber-700 text-slate-700 text-xs font-medium transition" onclick="directRenew('burial', ${r.id}, '${escapeHtml(r.decedent_name).replace(/'/g, "\\'")}', '${r.expiration_date || ''}')">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Renew
                    </button>
                    <button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 text-slate-700 text-xs font-medium transition" onclick="deleteRecord(${r.id}, '${escapeHtml(r.decedent_name).replace(/'/g, "\\'")}')">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Delete
                    </button>
                </div>
            </div>
        `;
    }

    function renderEmptyState(params) {
        const hasFilters = params.search || params.barangay || (params.type && params.type !== 'all');
        return `
            <div class="text-center py-16 bg-white rounded-2xl border border-slate-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                    <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-base font-semibold text-slate-700 mb-1">No Records Found</h3>
                <p class="text-sm text-slate-500">${hasFilters ? 'No records match your search criteria. Try adjusting your filters.' : 'There are no burial records yet. Click "Add New Record" to create one.'}</p>
            </div>
        `;
    }

    function renderPagination(data) {
        const container = document.getElementById('paginationContainer');
        if (data.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        const page = data.current_page;
        const total = data.total_pages;
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(total, page + 2);

        let html = '<div class="flex justify-center gap-2 flex-wrap mt-6">';

        if (page > 1) {
            html += `<a href="javascript:void(0)" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium transition" onclick="goToPage(${page - 1})"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg> Previous</a>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<a href="javascript:void(0)" class="${i === page ? 'inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold' : 'inline-flex items-center justify-center px-4 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium transition'}" onclick="goToPage(${i})">${i}</a>`;
        }

        if (page < total) {
            html += `<a href="javascript:void(0)" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium transition" onclick="goToPage(${page + 1})">Next <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    function goToPage(page) {
        currentPage = page;
        fetchRecords();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    async function viewRecord(id) {
        try {
            const response = await fetch(`../api/get_record.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                const r = data.record;
                const photoHtml = r.photo
                    ? `<img src="../uploads/photos/${r.photo}" class="w-full max-h-64 object-cover rounded-xl mb-4">`
                    : `<div class="w-full h-40 rounded-xl bg-slate-100 flex items-center justify-center text-slate-300 mb-4"><svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div>`;

                const badge = r.is_fenced == 1
                    ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase bg-amber-100 text-amber-700 border border-amber-200">Premium</span>'
                    : '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold uppercase bg-emerald-100 text-emerald-700 border border-emerald-200">Standard</span>';

                document.getElementById('viewModalBody').innerHTML = `
                    ${photoHtml}
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">${escapeHtml(r.decedent_name)}</h3>
                        ${badge}
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Family</div>
                            <div class="text-slate-800">${escapeHtml(r.family_name || 'N/A')}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Birth Date</div>
                            <div class="text-slate-800">${r.birth_date || 'N/A'}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Death Date</div>
                            <div class="text-slate-800">${r.death_date || 'N/A'}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Plot Number</div>
                            <div class="text-slate-800">${escapeHtml(r.plot_number || 'N/A')}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Barangay</div>
                            <div class="text-slate-800">${escapeHtml(r.barangay || 'N/A')}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="text-xs text-slate-400 uppercase font-semibold mb-1">Coordinates</div>
                            <div class="text-slate-800 text-xs">${r.latitude}, ${r.longitude}</div>
                        </div>
                    </div>
                    ${r.memory_space ? `<div class="mt-4"><div class="text-xs text-slate-400 uppercase font-semibold mb-1">Memory / Biography</div><div class="text-sm text-slate-600 italic bg-slate-50 rounded-lg p-3">${escapeHtml(r.memory_space)}</div></div>` : ''}
                    <div class="flex gap-3 mt-5 pt-4 border-t border-slate-100">
                        <button type="button" onclick="closeViewModal(); editRecord(${r.id});" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                            <i data-lucide="pencil" class="w-4 h-4"></i> Edit Record
                        </button>
                        <button type="button" onclick="closeViewModal()" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
                            Close
                        </button>
                    </div>
                `;
                openViewModal();
            }
        } catch (error) {
            themeUtils.showAlert('Failed to load record', 'error');
        }
    }

    function openViewModal() {
        const modal = document.getElementById('viewRecordModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeViewModal() {
        const modal = document.getElementById('viewRecordModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    async function editRecord(id) {
        try {
            const response = await fetch(`../api/get_record.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                const r = data.record;
                const form = document.getElementById('editRecordForm');

                form.querySelector('[name="id"]').value = r.id;
                form.querySelector('[name="decedent_name"]').value = r.decedent_name || '';
                form.querySelector('[name="family_name"]').value = r.family_name || '';
                form.querySelector('[name="visitor_id"]').value = r.visitor_id || '';
                form.querySelector('[name="birth_date"]').value = r.birth_date || '';
                form.querySelector('[name="death_date"]').value = r.death_date || '';
                form.querySelector('[name="burial_date"]').value = r.burial_date || '';
                form.querySelector('[name="burial_time"]').value = r.burial_time ? r.burial_time.substring(0, 5) : '';
                form.querySelector('[name="is_buried"]').value = r.is_buried == 0 ? '0' : '1';
                form.querySelector('[name="expiration_date"]').value = r.expiration_date || '';
                form.querySelector('[name="plot_number"]').value = r.plot_number || '';
                form.querySelector('[name="barangay"]').value = r.barangay || '';
                form.querySelector('[name="is_fenced"]').checked = r.is_fenced == 1;
                form.querySelector('[name="memory_space"]').value = r.memory_space || '';
                document.getElementById('editLat').value = r.latitude || '';
                document.getElementById('editLng').value = r.longitude || '';

                // Show existing photo
                if (r.photo) {
                    const img = document.querySelector('#editPhotoPreview img');
                    img.src = '../uploads/photos/' + r.photo;
                    document.getElementById('editPhotoPreview').classList.remove('hidden');
                } else {
                    document.getElementById('editPhotoPreview').classList.add('hidden');
                }

                openEditModal();

                // Set marker on map
                setTimeout(() => {
                    if (!editMap) {
                        initEditMap();
                    } else {
                        editMap.invalidateSize();
                        editMap.fitBounds(CEMETERY_BOUNDS, { padding: [30, 30], maxZoom: 19, animate: false });
                        if (typeof editMap.setBearing === 'function') editMap.setBearing(315);
                        loadGridsOnEditMap();
                        loadExistingRecordsOnEditMap();
                    }
                    if (r.latitude && r.longitude) {
                        setEditMarker(r.latitude, r.longitude);
                    }
                }, 200);
            }
        } catch (error) {
            themeUtils.showAlert('Failed to load record for editing', 'error');
        }
    }

    function openEditModal() {
        const modal = document.getElementById('editRecordModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeEditModal() {
        const modal = document.getElementById('editRecordModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        if (editMarker) { editMarker.remove(); editMarker = null; }
    }

    let editMap = null;
    let editMarker = null;
    let editGridLayers = [];
    let editRecordLayers = [];

    function initEditMap() {
        editMap = L.map('editMapPicker', { rotate: true, touchRotate: true, bearing: 315 }).setView(CEMETERY_CENTER, 17);

        L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        }).addTo(editMap);

        L.polygon(CEMETERY_POLYGON, {
            color: '#b55a5a', weight: 2, fillOpacity: 0, dashArray: '5, 10'
        }).addTo(editMap);

        editMap.on('click', function(e) {
            if (!isInsideCemetery(e.latlng.lat, e.latlng.lng)) {
                showBoundsError();
                return;
            }
            document.getElementById('editLat').value = e.latlng.lat;
            document.getElementById('editLng').value = e.latlng.lng;
            setEditMarker(e.latlng.lat, e.latlng.lng);
        });

        setTimeout(() => { editMap.invalidateSize(); }, 200);
        setTimeout(() => {
            editMap.invalidateSize();
            editMap.fitBounds(CEMETERY_BOUNDS, { padding: [30, 30], maxZoom: 19, animate: false });
            if (typeof editMap.setBearing === 'function') editMap.setBearing(315);
            loadGridsOnEditMap();
            loadExistingRecordsOnEditMap();
        }, 400);
    }

    // ---- Load grids on the Edit Record map ----
    async function loadGridsOnEditMap() {
        if (!editMap) return;
        editGridLayers.forEach(l => editMap.removeLayer(l));
        editGridLayers = [];
        try {
            const response = await fetch('../api/grids.php?action=list');
            const data = await response.json();
            if (data.success && data.grids) {
                for (const grid of data.grids) {
                    if (!grid.center_lat || !grid.center_lng) continue;
                    await drawEditGrid(grid);
                }
            }
        } catch (error) {
            console.error('Error loading grids on edit map:', error);
        }
    }

    async function drawEditGrid(grid) {
        const rows = parseInt(grid.rows);
        const cols = parseInt(grid.cols);
        const centerLat = parseFloat(grid.center_lat);
        const centerLng = parseFloat(grid.center_lng);
        const cellSize = 2;
        const angleRad = 45 * Math.PI / 180;
        const metersPerLat = 111320;
        const metersPerLng = 111320 * Math.cos(centerLat * Math.PI / 180);

        let cellsData = [];
        try {
            const resp = await fetch(`../api/grids.php?action=get&grid_id=${grid.id}`);
            const data = await resp.json();
            if (data.success && data.cells) cellsData = data.cells;
        } catch (e) { /* ignore */ }

        const gridName = grid.name || 'Grid #' + grid.id;

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
                const latLngs = corners.map(c => {
                    const rx = c.x * Math.cos(angleRad) - c.y * Math.sin(angleRad);
                    const ry = c.x * Math.sin(angleRad) + c.y * Math.cos(angleRad);
                    return [cellLat + (ry / metersPerLat), cellLng + (rx / metersPerLng)];
                });

                const rowNum = row + 1, colNum = col + 1;
                const cellData = cellsData.find(c => c.row === rowNum && c.col === colNum);
                const isOccupied = cellData && cellData.record_id;

                const cellStyle = isOccupied ? {
                    color: '#ef4444', weight: 2, opacity: 1,
                    fillColor: '#ef4444', fillOpacity: 0.5
                } : {
                    color: '#10b981', weight: 1.5, opacity: 0.9,
                    fillColor: '#10b981', fillOpacity: 0.2
                };

                const cellLayer = L.polygon(latLngs, cellStyle).bindPopup(`
                    <div style="text-align:center;padding:4px;">
                        <strong style="font-size:1rem;color:${isOccupied ? '#ef4444' : '#10b981'};">${escapeHtml(gridName)}</strong><br>
                        <span style="font-size:0.9rem;">R${rowNum}-C${colNum}</span><br>
                        <span style="font-size:0.8rem;color:${isOccupied ? '#ef4444' : '#10b981'};">${isOccupied ? 'Occupied' : 'Available'}</span>
                    </div>
                `);
                cellLayer.addTo(editMap);
                editGridLayers.push(cellLayer);
            }
        }
    }

    // ---- Load existing deceased records on the Edit Record map ----
    async function loadExistingRecordsOnEditMap() {
        if (!editMap) return;
        editRecordLayers.forEach(l => editMap.removeLayer(l));
        editRecordLayers = [];
        try {
            const res = await fetch('../api/get_all_records.php');
            const data = await res.json();
            if (!data.success || !data.records) return;
            data.records.forEach(r => {
                if (!r.latitude || !r.longitude) return;
                const marker = L.marker([parseFloat(r.latitude), parseFloat(r.longitude)], {
                    icon: L.divIcon({
                        className: 'existing-record-marker',
                        html: '<div style="width:18px;height:18px;background:#f59e0b;border:3px solid #fff;border-radius:50%;box-shadow:0 0 10px rgba(245,158,11,0.8);"></div>',
                        iconSize: [18, 18],
                        iconAnchor: [9, 9]
                    }),
                    zIndexOffset: 1000
                });
                const photoHtml = r.photo
                    ? `<img src="../uploads/photos/${escapeHtml(r.photo)}" style="width:60px;height:60px;border-radius:8px;object-fit:cover;margin-top:6px;">`
                    : '';
                marker.bindPopup(`
                    <div style="min-width:180px;">
                        <strong style="font-size:0.95rem;color:#f59e0b;">${escapeHtml(r.decedent_name || 'Unknown')}</strong><br>
                        <span style="font-size:0.8rem;color:#666;">${escapeHtml(r.plot_number || 'No plot #')}</span><br>
                        <span style="font-size:0.75rem;color:#999;">${escapeHtml(r.barangay || '')}</span>
                        ${photoHtml}
                    </div>
                `);
                marker.addTo(editMap);
                editRecordLayers.push(marker);
            });
        } catch (e) {
            console.error('Error loading existing records on edit map:', e);
        }
    }

    function setEditMarker(lat, lng) {
        if (editMarker) {
            editMarker.setLatLng([lat, lng]);
        } else {
            editMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'custom-marker',
                    html: '<div style="background: #10b981; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 8px rgba(16,185,129,0.5);"></div>',
                    iconSize: [16, 16], iconAnchor: [8, 8]
                })
            }).addTo(editMap);
        }
    }

    function previewEditPhoto(input) {
        const preview = document.getElementById('editPhotoPreview');
        const img = preview.querySelector('img');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) { img.src = e.target.result; preview.classList.remove('hidden'); };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // ---- Direct Renew (admin) ----
    function directRenew(plotType, recordId, plotName, currentExp) {
        const modal = document.getElementById('directRenewModal');
        document.getElementById('directRenewType').value = plotType;
        document.getElementById('directRenewRecordId').value = recordId || '';
        document.getElementById('directRenewPlotId').value = '';
        document.getElementById('directRenewInfo').innerHTML = `
            <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">${plotType === 'burial' ? 'Burial Record' : 'Available Plot'}</div>
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
                loadResults();
            } else {
                themeUtils.showAlert(data.message || 'Failed to renew plot', 'error');
            }
        } catch (err) {
            themeUtils.showAlert('Network error', 'error');
        }
    });

    function deleteRecord(id, name) {
        themeUtils.confirm(
            `Are you sure you want to delete the record for "${name}"? This action cannot be undone.`,
            async () => {
                try {
                    const response = await fetch('../api/delete_record.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });

                    const data = await response.json();

                    if (data.success) {
                        themeUtils.showAlert('Record deleted successfully', 'success');
                        fetchRecords();
                    } else {
                        themeUtils.showAlert(data.error || 'Failed to delete record', 'error');
                    }
                } catch (error) {
                    themeUtils.showAlert('An error occurred', 'error');
                }
            }
        );
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
        fetchRecords();
    });
</script>
</body>
</html>
