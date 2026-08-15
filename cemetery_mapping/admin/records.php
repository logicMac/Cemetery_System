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
    SELECT id, decedent_name, family_name, birth_date, death_date, plot_number,
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

// Get stats
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn(),
    'premium' => $pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_fenced = 1")->fetchColumn(),
    'standard' => $pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_fenced = 0")->fetchColumn()
];

// Get unique barangays from database
$barangayStmt = $pdo->query("SELECT DISTINCT barangay FROM burial_records WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay");
$barangays = $barangayStmt->fetchAll(PDO::FETCH_COLUMN);
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
    <button type="button" onclick="openAddModal()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 shadow-sm transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add New Record
    </button>
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
    <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr_1fr_auto] gap-4 items-end">
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
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
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
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Birth Date</label>
                        <input type="date" name="birth_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Death Date</label>
                        <input type="date" name="death_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
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

            <!-- Location -->
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="navigation" class="w-4 h-4 text-emerald-600"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Location Coordinates</h4>
                </div>
                <p class="text-sm text-slate-500 mb-3">Click on the map to set the burial location</p>
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
                <div id="modalMapPicker" class="rounded-xl overflow-hidden border border-slate-200" style="height: 300px;"></div>
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
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
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
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Birth Date</label>
                        <input type="date" name="birth_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Death Date</label>
                        <input type="date" name="death_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
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
                <div id="editMapPicker" class="rounded-xl overflow-hidden border border-slate-200" style="height: 300px;"></div>
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

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../assets/js/theme.js"></script>
<script>
    let currentPage = 1;
    let searchTimer = null;
    let modalMap = null;
    let modalMarker = null;

    // ---- Add Record Modal ----
    function openAddModal() {
        const modal = document.getElementById('addRecordModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        // Initialize map after modal is visible
        setTimeout(() => {
            if (!modalMap) {
                initModalMap();
            } else {
                modalMap.invalidateSize();
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 100);
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
        const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
        const CEMETERY_BOUNDS = [
            [6.18244118743717, 125.08357146469357],
            [6.18444118743717, 125.08557146469357]
        ];

        modalMap = L.map('modalMapPicker').setView(CEMETERY_CENTER, 17);

        L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        }).addTo(modalMap);

        L.rectangle(CEMETERY_BOUNDS, {
            color: '#b55a5a',
            weight: 2,
            fillOpacity: 0,
            dashArray: '5, 10'
        }).addTo(modalMap);

        modalMap.on('click', function(e) {
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

        setTimeout(() => modalMap.invalidateSize(), 200);
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
                container.innerHTML = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">' + data.records.map(r => renderCard(r)).join('') + '</div>';
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

        return `
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col hover:border-emerald-300 hover:shadow-md transition">
                ${photo}
                <div class="text-base font-semibold text-slate-900 mb-2">${escapeHtml(r.decedent_name)}</div>
                <div class="text-sm text-slate-500 space-y-1.5 mb-3">
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg> ${escapeHtml(r.family_name || 'N/A')}</div>
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> ${birth} - ${death}</div>
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg> Plot: ${escapeHtml(r.plot_number || 'N/A')}</div>
                    <div class="flex items-center gap-2"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg> ${escapeHtml(r.barangay || 'N/A')}</div>
                </div>
                <div class="pt-3 border-t border-slate-100 mb-3">${badge}</div>
                <div class="flex gap-2">
                    <button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-medium transition" onclick="viewRecord(${r.id})">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg> View
                    </button>
                    <button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-medium transition" onclick="editRecord(${r.id})">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Edit
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
                form.querySelector('[name="birth_date"]').value = r.birth_date || '';
                form.querySelector('[name="death_date"]').value = r.death_date || '';
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
                    }
                    if (r.latitude && r.longitude) {
                        setEditMarker(r.latitude, r.longitude);
                    }
                }, 150);
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

    function initEditMap() {
        const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
        const CEMETERY_BOUNDS = [
            [6.18244118743717, 125.08357146469357],
            [6.18444118743717, 125.08557146469357]
        ];

        editMap = L.map('editMapPicker').setView(CEMETERY_CENTER, 17);

        L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        }).addTo(editMap);

        L.rectangle(CEMETERY_BOUNDS, {
            color: '#b55a5a', weight: 2, fillOpacity: 0, dashArray: '5, 10'
        }).addTo(editMap);

        editMap.on('click', function(e) {
            document.getElementById('editLat').value = e.latlng.lat;
            document.getElementById('editLng').value = e.latlng.lng;
            setEditMarker(e.latlng.lat, e.latlng.lng);
        });

        setTimeout(() => editMap.invalidateSize(), 200);
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
