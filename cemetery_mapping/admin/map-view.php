<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

$target_lat = $_GET['lat'] ?? 6.18344118743717;
$target_lng = $_GET['lng'] ?? 125.08457146469357;
$zoom = $_GET['zoom'] ?? 17;
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
.admin-layout { background: #ffffff; }
.admin-layout::after { display: none; }
#adminMap { position: relative; width: 100%; height: calc(100vh - 110px); border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; }
.leaflet-popup-content-wrapper { background: #ffffff; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); border: 1px solid #e2e8f0; }
.leaflet-popup-content { color: #0f172a; }
.leaflet-popup-tip { background: #ffffff; }
button svg, a svg, button i, a i { pointer-events: none; }
</style>

<div class="relative">
    <div id="adminMap"></div>

    <!-- Map Controls Panel -->
    <div class="absolute top-5 right-5 z-[1000] bg-white rounded-2xl border border-slate-200 shadow-lg p-5 w-72 max-w-[calc(100vw-40px)]">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-900">Map Controls</h3>
        </div>

        <!-- Rotation -->
        <div class="mb-4">
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Rotation</label>
            <div class="grid grid-cols-3 gap-2 mb-2">
                <button onclick="rotateMap(-15)" title="Rotate 15° Left" class="flex items-center justify-center py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-600 transition">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </button>
                <button onclick="resetRotation()" title="Reset to North" class="flex items-center justify-center gap-1 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition">
                    <i data-lucide="navigation" class="w-3.5 h-3.5"></i> N
                </button>
                <button onclick="rotateMap(15)" title="Rotate 15° Right" class="flex items-center justify-center py-2 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-600 transition">
                    <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="text-center text-xs text-slate-400" id="bearingDisplay">Bearing: 0°</div>
        </div>

        <!-- Filter -->
        <div class="mb-4">
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Filter</label>
            <select id="filterType" onchange="filterMarkers()" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                <option value="all">Show All</option>
                <option value="burials">Burials Only</option>
                <option value="available">Available Plots Only</option>
                <option value="premium">Premium Only</option>
            </select>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Search</label>
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="mapSearch" placeholder="Name or plot..." onkeyup="searchOnMap()" class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-2 mb-4">
            <button onclick="resetView()" class="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-lg bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 text-sm font-medium transition">
                <i data-lucide="locate-fixed" class="w-4 h-4"></i> Reset View
            </button>
            <a href="dashboard.php" class="inline-flex items-center justify-center gap-2 w-full py-2.5 rounded-lg bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 text-sm font-medium transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
            </a>
        </div>

        <!-- Stats -->
        <div class="pt-4 border-t border-slate-100">
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-50 rounded-lg p-3 text-center">
                    <div class="text-lg font-bold text-slate-900" id="totalCount">0</div>
                    <div class="text-xs text-slate-400">Total</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-3 text-center">
                    <div class="text-lg font-bold text-emerald-600" id="visibleCount">0</div>
                    <div class="text-xs text-slate-400">Visible</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<!-- Load Leaflet first -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Then Leaflet Rotate -->
<script src="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate-src.js"></script>
<!-- Then other Leaflet plugins -->
<script src="https://unpkg.com/leaflet.fullscreen@2.4.0/Control.FullScreen.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="../assets/js/theme.js"></script>
<script>
    console.log('Map view page loaded');
    console.log('Leaflet available:', typeof L !== 'undefined');
    
    const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
    const CEMETERY_BOUNDS = [
        [6.18244118743717, 125.08357146469357],
        [6.18444118743717, 125.08557146469357]
    ];
    
    let map, allMarkers = [], burialMarkers = [], availableMarkers = [];
    
    // Wait for DOM and Leaflet to be ready
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
        initializeMap();
    });
    
    function initializeMap() {
        console.log('Initializing admin map...');
        
        // Initialize map with rotation support
        map = L.map('adminMap', {
            rotate: true,
            touchRotate: true,
            bearing: 0
        }).setView([<?php echo $target_lat; ?>, <?php echo $target_lng; ?>], <?php echo $zoom; ?>);
        
        console.log('Map created');
        console.log('Map rotation support:', typeof map.setBearing === 'function');
        console.log('Map getBearing support:', typeof map.getBearing === 'function');
        
        // Add rotation control explicitly
        if (typeof L.Control !== 'undefined' && typeof L.Control.Rotate !== 'undefined') {
            console.log('Adding rotation control to admin map...');
            try {
                L.control.rotate({
                    position: 'topleft',
                    closeOnZeroBearing: false
                }).addTo(map);
                console.log('Rotation control added successfully');
            } catch (e) {
                console.error('Error adding rotation control:', e);
            }
        } else {
            console.error('L.Control.Rotate not available!');
            console.log('Available controls:', Object.keys(L.Control));
        }
        
        // Tile layers
        const googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        });
        
        const googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        });
        
        const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 20
        });
        
        googleSat.addTo(map);
        
        L.control.layers({
            "Google Satellite": googleSat,
            "Google Hybrid": googleHybrid,
            "OpenStreetMap": osm
        }).addTo(map);
        
        // Fullscreen control (optional - only add if available)
        if (typeof L.Control.Fullscreen !== 'undefined') {
            map.addControl(new L.Control.Fullscreen());
            console.log('Fullscreen control added');
        } else {
            console.log('Fullscreen control not available, skipping');
        }
        
        // Cemetery boundary
        L.rectangle(CEMETERY_BOUNDS, {
            color: '#b55a5a',
            weight: 2,
            fillOpacity: 0,
            dashArray: '5, 10'
        }).addTo(map);
        
        console.log('Map initialized, loading data...');
        
        // Load data
        loadAllData();
        
        // After loading, check if we should zoom to a specific location
        setTimeout(() => {
            const urlParams = new URLSearchParams(window.location.search);
            const targetLat = parseFloat(urlParams.get('lat'));
            const targetLng = parseFloat(urlParams.get('lng'));
            const targetZoom = parseInt(urlParams.get('zoom')) || 19;
            
            if (targetLat && targetLng) {
                console.log('Zooming to target location:', targetLat, targetLng, targetZoom);
                map.setView([targetLat, targetLng], targetZoom);
                
                // Find and open the marker at this location
                allMarkers.forEach(marker => {
                    const markerPos = marker.getLatLng();
                    const distance = map.distance(markerPos, [targetLat, targetLng]);
                    
                    // If marker is within 10 meters, open its popup
                    if (distance < 10) {
                        marker.openPopup();
                        console.log('Opened popup for marker at:', markerPos);
                    }
                });
            }
        }, 1500);
    }
    
    // Load data on page load
    async function loadAllData() {
        console.log('Loading burial records and plots...');
        try {
            // Load burial records
            const burialResponse = await fetch('../api/get_all_records.php');
            const burialData = await burialResponse.json();
            
            console.log('Burial records loaded:', burialData.records ? burialData.records.length : 0);
            
            if (burialData.success) {
                burialData.records.forEach(record => {
                    if (record.latitude && record.longitude) {
                        const color = record.is_fenced == 1 ? '#c9a86c' : '#5a87a8';
                        const marker = L.marker([record.latitude, record.longitude], {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: `<div style="background: ${color}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                                iconSize: [12, 12],
                                iconAnchor: [6, 6]
                            })
                        }).bindPopup(createBurialPopup(record));
                        
                        marker.recordType = 'burial';
                        marker.recordData = record;
                        marker.addTo(map);
                        allMarkers.push(marker);
                        burialMarkers.push(marker);
                    }
                });
            }
            
            // Load available plots
            const plotResponse = await fetch('../api/get_available_plots.php');
            const plotData = await plotResponse.json();
            
            console.log('Available plots loaded:', plotData.plots ? plotData.plots.length : 0);
            
            if (plotData.success) {
                plotData.plots.forEach(plot => {
                    const marker = L.marker([plot.latitude, plot.longitude], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: '<div style="background: #5a9b6f; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
                            iconSize: [14, 14],
                            iconAnchor: [7, 7]
                        })
                    }).bindPopup(createPlotPopup(plot));
                    
                    marker.recordType = 'available';
                    marker.recordData = plot;
                    marker.addTo(map);
                    allMarkers.push(marker);
                    availableMarkers.push(marker);
                    
                    // Draw grid if available
                    if (plot.has_grid == 1 && plot.grid_rows && plot.grid_cols) {
                        drawPlotGrid(plot);
                    }
                });
            }
            
            console.log('Total markers loaded:', allMarkers.length);
            console.log('Burial markers:', burialMarkers.length);
            console.log('Available plot markers:', availableMarkers.length);
            
            updateCounts();
        } catch (error) {
            console.error('Error loading data:', error);
        }
    }
    
    // Draw plot grid overlay
    function drawPlotGrid(plot) {
        const rows = parseInt(plot.grid_rows);
        const cols = parseInt(plot.grid_cols);
        const centerLat = parseFloat(plot.latitude);
        const centerLng = parseFloat(plot.longitude);
        const cellSize = 2; // 2 meters per cell
        
        // Get rotation angle from map or use default cemetery orientation
        // The cemetery appears to be rotated about 45 degrees from north
        const rotationAngle = 45; // Degrees - adjust this to match cemetery orientation
        const angleRad = rotationAngle * Math.PI / 180;
        
        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                // Calculate offset in meters from center
                const offsetX = (col - cols / 2 + 0.5) * cellSize;
                const offsetY = (row - rows / 2 + 0.5) * cellSize;
                
                // Apply rotation
                const rotatedX = offsetX * Math.cos(angleRad) - offsetY * Math.sin(angleRad);
                const rotatedY = offsetX * Math.sin(angleRad) + offsetY * Math.cos(angleRad);
                
                // Convert meters to lat/lng (approximate)
                const lat = centerLat + (rotatedY / 111320);
                const lng = centerLng + (rotatedX / (111320 * Math.cos(centerLat * Math.PI / 180)));
                
                // Calculate corner offsets for rotated rectangle
                const halfCell = cellSize / 2;
                const corners = [
                    {x: -halfCell, y: -halfCell},
                    {x: halfCell, y: -halfCell},
                    {x: halfCell, y: halfCell},
                    {x: -halfCell, y: halfCell}
                ];
                
                // Rotate corners and convert to lat/lng
                const latLngs = corners.map(corner => {
                    const rotX = corner.x * Math.cos(angleRad) - corner.y * Math.sin(angleRad);
                    const rotY = corner.x * Math.sin(angleRad) + corner.y * Math.cos(angleRad);
                    return [
                        lat + (rotY / 111320),
                        lng + (rotX / (111320 * Math.cos(lat * Math.PI / 180)))
                    ];
                });
                
                const cellLabel = String.fromCharCode(65 + row) + (col + 1); // A1, A2, B1, etc.
                
                // Create rotated polygon instead of rectangle
                const cell = L.polygon(latLngs, {
                    color: '#10b981',
                    weight: 2,
                    fillColor: '#10b981',
                    fillOpacity: 0.1
                }).bindPopup(`
                    <div style="min-width:160px;">
                        <h4 style="margin:0 0 6px 0;font-size:0.9rem;font-weight:700;color:#0f172a;">Compartment ${cellLabel}</h4>
                        <div style="font-size:0.8rem;color:#64748b;">
                            <div><strong style="color:#0f172a;">Plot:</strong> ${plot.plot_number || 'N/A'}</div>
                            <div><strong style="color:#0f172a;">Status:</strong> <span style="color:#10b981;font-weight:600;">Available</span></div>
                        </div>
                    </div>
                `);
                
                // Add cell to available markers so it responds to filters
                cell.recordType = 'available';
                cell.recordData = plot;
                allMarkers.push(cell);
                availableMarkers.push(cell);
                cell.addTo(map);
                
                // Add label marker at center of cell
                const labelIcon = L.divIcon({
                    className: 'grid-label',
                    html: `<div style="font-size: 10px; font-weight: bold; color: #5a9b6f; text-shadow: 0 0 3px #000, 0 0 3px #000;">${cellLabel}</div>`,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });
                
                const labelMarker = L.marker([lat, lng], { icon: labelIcon });
                labelMarker.recordType = 'available';
                labelMarker.recordData = plot;
                allMarkers.push(labelMarker);
                availableMarkers.push(labelMarker);
                labelMarker.addTo(map);
            }
        }
    }
    
    function createBurialPopup(record) {
        const photoHtml = record.photo
            ? `<img src="../uploads/photos/${record.photo}" style="width:100%;max-height:150px;object-fit:cover;border-radius:8px;margin-bottom:10px;">`
            : '';
        const badge = record.is_fenced == 1
            ? '<span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.7rem;font-weight:600;background:#fef3c7;color:#92400e;">Premium</span>'
            : '<span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.7rem;font-weight:600;background:#d1fae5;color:#065f46;">Standard</span>';

        return `
            <div style="min-width:220px;">
                ${photoHtml}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">${record.decedent_name}</h4>
                    ${badge}
                </div>
                <div style="font-size:0.8rem;color:#64748b;line-height:1.6;margin-bottom:10px;">
                    <div><strong style="color:#0f172a;">Plot:</strong> ${record.plot_number || 'N/A'}</div>
                    <div><strong style="color:#0f172a;">Barangay:</strong> ${record.barangay || 'N/A'}</div>
                </div>
                <button onclick="window.location.href='records.php?id=${record.id}'" style="width:100%;padding:7px;border:none;border-radius:8px;background:#10b981;color:#fff;font-size:0.8rem;font-weight:600;cursor:pointer;">View Details</button>
            </div>
        `;
    }

    function createPlotPopup(plot) {
        return `
            <div style="min-width:200px;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                    <div style="width:8px;height:8px;border-radius:50%;background:#10b981;"></div>
                    <h4 style="margin:0;font-size:0.95rem;font-weight:700;color:#0f172a;">Available Plot</h4>
                </div>
                <div style="font-size:0.8rem;color:#64748b;line-height:1.6;">
                    <div><strong style="color:#0f172a;">Plot:</strong> ${plot.plot_number || 'N/A'}</div>
                    ${plot.has_grid == 1 ? `<div><strong style="color:#0f172a;">Grid:</strong> ${plot.grid_rows} × ${plot.grid_cols}</div>` : ''}
                    ${plot.notes ? `<div style="margin-top:4px;"><strong style="color:#0f172a;">Notes:</strong> ${plot.notes}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    function filterMarkers() {
        const filter = document.getElementById('filterType').value;
        
        allMarkers.forEach(marker => {
            map.removeLayer(marker);
        });
        
        let markersToShow = [];
        
        switch(filter) {
            case 'burials':
                markersToShow = burialMarkers;
                break;
            case 'available':
                markersToShow = availableMarkers;
                break;
            case 'premium':
                markersToShow = burialMarkers.filter(m => m.recordData.is_fenced == 1);
                break;
            default:
                markersToShow = allMarkers;
        }
        
        markersToShow.forEach(marker => marker.addTo(map));
        updateCounts();
    }
    
    function searchOnMap() {
        const query = document.getElementById('mapSearch').value.toLowerCase();
        
        if (query.length < 2) {
            filterMarkers();
            return;
        }
        
        allMarkers.forEach(marker => map.removeLayer(marker));
        
        const matches = allMarkers.filter(marker => {
            const data = marker.recordData;
            const searchText = (
                (data.decedent_name || '') + ' ' +
                (data.plot_number || '') + ' ' +
                (data.family_name || '') + ' ' +
                (data.barangay || '')
            ).toLowerCase();
            
            return searchText.includes(query);
        });
        
        matches.forEach(marker => marker.addTo(map));
        
        if (matches.length > 0) {
            const group = L.featureGroup(matches);
            map.fitBounds(group.getBounds().pad(0.1));
        }
        
        updateCounts();
    }
    
    function resetView() {
        map.setView(CEMETERY_CENTER, 17);
        document.getElementById('filterType').value = 'all';
        document.getElementById('mapSearch').value = '';
        filterMarkers();
    }
    
    function updateCounts() {
        const visible = allMarkers.filter(m => map.hasLayer(m)).length;
        document.getElementById('totalCount').textContent = allMarkers.length;
        document.getElementById('visibleCount').textContent = visible;
        console.log('Count updated - Total:', allMarkers.length, 'Visible:', visible);
    }
    
    // Rotation functions
    function rotateMap(degrees) {
        console.log('Rotate map called:', degrees);
        if (map && typeof map.setBearing === 'function') {
            const currentBearing = map.getBearing();
            const newBearing = currentBearing + degrees;
            console.log('Setting bearing from', currentBearing, 'to', newBearing);
            map.setBearing(newBearing);
            updateBearingDisplay();
        } else {
            console.error('Map rotation not supported!');
            console.log('Map object:', map);
            console.log('setBearing function:', typeof map.setBearing);
            alert('Map rotation is not available. The leaflet-rotate library may not be loaded.');
        }
    }
    
    function resetRotation() {
        console.log('Reset rotation called');
        if (map && typeof map.setBearing === 'function') {
            map.setBearing(0);
            updateBearingDisplay();
        } else {
            console.error('Map rotation not supported!');
        }
    }
    
    function updateBearingDisplay() {
        if (map && typeof map.getBearing === 'function') {
            const bearing = map.getBearing();
            const bearingEl = document.getElementById('bearingDisplay');
            if (bearingEl) {
                bearingEl.textContent = 'Bearing: ' + bearing.toFixed(0) + '°';
            }
        }
    }
    
    // Update bearing display when map rotates
    setTimeout(() => {
        if (map && typeof map.on === 'function') {
            map.on('rotate', updateBearingDisplay);
            console.log('Rotate event listener added');
        }
        updateBearingDisplay();
    }, 1000);
    
    // Don't call loadAllData here - it's called in initializeMap()
</script>
</main>
</body>
</html>
