<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

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
button svg, a svg, button i, a i { pointer-events: none; }
</style>

<div class="max-w-5xl mx-auto space-y-6">
    <!-- Page intro -->
    <div class="flex items-center gap-3 animate-[fadeUp_0.5s_ease]">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
            <i data-lucide="user-plus" class="w-5 h-5"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-slate-900">Add New Burial Record</h2>
            <p class="text-sm text-slate-500">Fill in the decedent details, plot info, and location</p>
        </div>
    </div>

    <form id="addRecordForm" enctype="multipart/form-data" class="space-y-6">
        <!-- Decedent details card -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 animate-[fadeUp_0.6s_ease]">
            <div class="flex items-center gap-2 mb-5">
                <i data-lucide="user" class="w-4 h-4 text-emerald-600"></i>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Decedent Details</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="decedent_name" class="block text-sm font-medium text-slate-700 mb-1.5">Decedent Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="decedent_name" name="decedent_name" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                </div>
                <div>
                    <label for="family_name" class="block text-sm font-medium text-slate-700 mb-1.5">Family Name</label>
                    <input type="text" id="family_name" name="family_name"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                </div>
                <div>
                    <label for="birth_date" class="block text-sm font-medium text-slate-700 mb-1.5">Birth Date</label>
                    <input type="date" id="birth_date" name="birth_date"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                </div>
                <div>
                    <label for="death_date" class="block text-sm font-medium text-slate-700 mb-1.5">Death Date</label>
                    <input type="date" id="death_date" name="death_date"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                </div>
                <div>
                    <label for="burial_date" class="block text-sm font-medium text-slate-700 mb-1.5">Burial Date <span class="text-xs text-slate-400">— schedule</span></label>
                    <input type="date" id="burial_date" name="burial_date"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                </div>
                <div>
                    <label for="burial_time" class="block text-sm font-medium text-slate-700 mb-1.5">Burial Time</label>
                    <input type="time" id="burial_time" name="burial_time"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                </div>
                <div>
                    <label for="is_buried" class="block text-sm font-medium text-slate-700 mb-1.5">Burial Status</label>
                    <select id="is_buried" name="is_buried"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition bg-white">
                        <option value="1">Buried / Done</option>
                        <option value="0">Scheduled (not yet buried)</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Plot info card -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 animate-[fadeUp_0.7s_ease]">
            <div class="flex items-center gap-2 mb-5">
                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Plot Information</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="plot_number" class="block text-sm font-medium text-slate-700 mb-1.5">Plot Number</label>
                    <input type="text" id="plot_number" name="plot_number"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                </div>
                <div>
                    <label for="barangay" class="block text-sm font-medium text-slate-700 mb-1.5">Barangay</label>
                    <select id="barangay" name="barangay"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition bg-white">
                        <option value="">Select Barangay</option>
                        <?php foreach ($barangays as $barangay): ?>
                            <option value="<?php echo $barangay; ?>"><?php echo $barangay; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox" id="is_fenced" name="is_fenced" value="1"
                            class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-100">
                        <span class="text-sm font-medium text-slate-700">Premium / Fenced Plot</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Memory & photo card -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 animate-[fadeUp_0.8s_ease]">
            <div class="flex items-center gap-2 mb-5">
                <i data-lucide="book-open" class="w-4 h-4 text-emerald-600"></i>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Memory & Photo</h3>
            </div>
            <div class="space-y-5">
                <div>
                    <label for="memory_space" class="block text-sm font-medium text-slate-700 mb-1.5">Memory / Biography</label>
                    <textarea id="memory_space" name="memory_space" rows="4" placeholder="Share memories or biographical information..."
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition resize-y"></textarea>
                </div>
                <div>
                    <label for="photo" class="block text-sm font-medium text-slate-700 mb-1.5">Photo</label>
                    <div onclick="document.getElementById('photo').click()"
                        class="cursor-pointer rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-400 hover:bg-emerald-50/40 transition p-8 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                            <i data-lucide="image" class="w-6 h-6"></i>
                        </div>
                        <p class="text-sm text-slate-500">Click to upload photo <span class="text-slate-400">(max 5MB)</span></p>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg" class="hidden" onchange="previewPhoto(this)">
                    </div>
                    <div id="photoPreview" class="mt-4 hidden">
                        <img src="" alt="Preview" class="max-h-48 rounded-lg border border-slate-200 object-cover">
                    </div>
                </div>
            </div>
        </section>

        <!-- Location card -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 animate-[fadeUp_0.9s_ease]">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="navigation" class="w-4 h-4 text-emerald-600"></i>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Location Coordinates</h3>
            </div>
            <p class="text-sm text-slate-500 mb-5">Click on the map to set the burial location</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label for="latitude" class="block text-sm font-medium text-slate-700 mb-1.5">Latitude <span class="text-rose-500">*</span></label>
                    <input type="number" id="latitude" name="latitude" step="0.00000001" required readonly
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                </div>
                <div>
                    <label for="longitude" class="block text-sm font-medium text-slate-700 mb-1.5">Longitude <span class="text-rose-500">*</span></label>
                    <input type="number" id="longitude" name="longitude" step="0.00000001" required readonly
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-slate-50 focus:outline-none">
                </div>
            </div>
            <div id="mapPicker" class="map-picker rounded-xl overflow-hidden border border-slate-200" style="height: 380px;"></div>
        </section>

        <!-- Plot Boundary card -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 animate-[fadeUp_0.95s_ease]">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="pen-tool" class="w-4 h-4 text-emerald-600"></i>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Plot Boundary</h3>
            </div>
            <p class="text-sm text-slate-500 mb-5">Draw a free-form polygon around the plot. Click points on the map, then click Finish.</p>
            <input type="hidden" id="polygon" name="polygon">
            <div class="flex flex-wrap gap-2 mb-4">
                <button type="button" id="startPolygonBtn" onclick="polygonDrawer.startDrawing()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                    Draw Boundary
                </button>
                <button type="button" onclick="polygonDrawer.finishPolygon()" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold px-4 py-2.5 transition">
                    Finish Polygon
                </button>
                <button type="button" onclick="polygonDrawer.clearPolygon()" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
                    Clear
                </button>
            </div>
            <div id="polygonMap" class="map-picker rounded-xl overflow-hidden border border-slate-200" style="height: 380px;"></div>
        </section>

        <!-- Action bar -->
        <div class="flex items-center gap-3 pt-2 animate-[fadeUp_1s_ease]">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <i data-lucide="check" class="w-4 h-4"></i>
                Save Record
            </button>
            <a href="records.php"
                class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-5 py-2.5 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Initialize map picker
        const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
        // Actual cemetery boundary polygon (4 corners, expanded ~15% to include all graves)
        const CEMETERY_POLYGON = [
            [6.184703227248634, 125.08388389813996],
            [6.183142672429919, 125.08538436803683],
            [6.182440110293303, 125.08476491148839],
            [6.184002859166614, 125.08321729641679]
        ];
        // Bounding box for fitBounds
        const CEMETERY_BOUNDS = [
            [6.182440110293303, 125.08321729641679],
            [6.184703227248634, 125.08538436803683]
        ];

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

        const mapPicker = L.map('mapPicker', { rotate: true, touchRotate: true, bearing: 315 }).setView(CEMETERY_CENTER, 17);

        L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        }).addTo(mapPicker);

        // Draw cemetery boundary
        L.polygon(CEMETERY_POLYGON, {
            color: '#b55a5a',
            weight: 2,
            fillOpacity: 0,
            dashArray: '5, 10'
        }).addTo(mapPicker);
        
        let marker = null;
        
        // Click to set location
        mapPicker.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            if (!isInsideCemetery(lat, lng)) {
                if (typeof themeUtils !== 'undefined' && themeUtils.showAlert) {
                    themeUtils.showAlert('Please click inside the cemetery boundary (red box).', 'error');
                } else {
                    alert('Please click inside the cemetery boundary (red box).');
                }
                return;
            }

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background: #22c55e; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 8px rgba(74, 222, 128, 0.5);"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(mapPicker);
            }
        });
        
        // Photo preview
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
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
        
        // Form submission
        document.getElementById('addRecordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                themeUtils.showLoading(document.getElementById('addRecordForm'));
                
                const response = await fetch('../api/add_record.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                themeUtils.hideLoading();
                
                if (data.success) {
                    themeUtils.showAlert('Record added successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = 'records.php';
                    }, 1500);
                } else {
                    themeUtils.showAlert(data.error || 'Failed to add record', 'error');
                }
            } catch (error) {
                themeUtils.hideLoading();
                themeUtils.showAlert('An error occurred', 'error');
            }
        });

        // Initialize plot boundary polygon drawer
        setTimeout(() => {
            window.polygonDrawer = themeUtils.createPolygonDrawer('polygonMap', {
                center: CEMETERY_CENTER,
                onChange: function(points) {
                    document.getElementById('polygon').value = points ? JSON.stringify(points) : '';
                }
            });
        }, 300);

        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
