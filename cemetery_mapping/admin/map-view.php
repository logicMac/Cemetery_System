<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

$target_lat = $_GET['lat'] ?? 6.18344118743717;
$target_lng = $_GET['lng'] ?? 125.08457146469357;
$zoom = $_GET['zoom'] ?? 17;
?>

<?php require_once 'includes/sidebar.php'; ?>

<!-- Sidebar Collapse Toggle (desktop only) -->
<button class="sidebar-collapse-btn" onclick="toggleSidebarCollapse()" title="Toggle sidebar">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
    </svg>
</button>
<script>
function toggleSidebarCollapse() {
    document.body.classList.toggle('sidebar-collapsed');
    setTimeout(() => { if (map) map.invalidateSize(); }, 300);
}
</script>

<style>
    #adminMap { position: relative; width: 100%; height: calc(100vh - 80px); }

    /* Sidebar collapse toggle */
    .sidebar-collapse-btn {
        position: fixed;
        top: 20px;
        left: 288px;
        z-index: 1001;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        width: 32px;
        height: 32px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        transition: left 0.3s ease;
        color: white;
    }
    .sidebar-collapse-btn:hover {
        transform: scale(1.1);
    }
    .sidebar-collapse-btn svg {
        width: 18px;
        height: 18px;
        transition: transform 0.3s ease;
    }

    /* Collapsed state */
    body.sidebar-collapsed .admin-sidebar {
        transform: translateX(-100%);
    }
    body.sidebar-collapsed .admin-main {
        margin-left: 0;
    }
    body.sidebar-collapsed .sidebar-collapse-btn {
        left: 20px;
    }
    body.sidebar-collapsed .sidebar-collapse-btn svg {
        transform: rotate(180deg);
    }

    @media (max-width: 1024px) {
        .sidebar-collapse-btn { display: none; }
    }

    .map-overlay {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 20px;
        max-width: 300px;
    }
    .leaflet-popup-content-wrapper {
        background: rgba(0, 0, 0, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .leaflet-popup-content { color: white; }
    .leaflet-popup-tip { background: rgba(0, 0, 0, 0.9); }
</style>

<div id="adminMap"></div>

<!-- Control Panel -->
<div class="map-overlay">
    <h3 style="margin: 0 0 16px 0;">Map Controls</h3>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; margin-bottom: 8px; font-size: 0.9rem;">Rotation</label>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px;">
            <button onclick="rotateMap(-15)" class="btn-secondary" style="padding: 8px; font-size: 0.85rem;" title="Rotate 15° Left">
                <svg style="width: 16px; height: 16px; transform: rotate(180deg);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </button>
            <button onclick="resetRotation()" class="btn-primary" style="padding: 8px; font-size: 0.85rem;" title="Reset to North">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                N
            </button>
            <button onclick="rotateMap(15)" class="btn-secondary" style="padding: 8px; font-size: 0.85rem;" title="Rotate 15° Right">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </button>
        </div>
        <div style="text-align: center;">
            <span id="bearingDisplay" style="font-size: 0.85rem; color: var(--zinc-400);">Bearing: 0°</span>
        </div>
    </div>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; margin-bottom: 8px; font-size: 0.9rem;">Filter</label>
        <select id="filterType" class="input-field" onchange="filterMarkers()">
            <option value="all">Show All</option>
            <option value="burials">Burials Only</option>
            <option value="available">Available Plots Only</option>
            <option value="premium">Premium Only</option>
        </select>
    </div>
    
    <div style="margin-bottom: 16px;">
        <label style="display: block; margin-bottom: 8px; font-size: 0.9rem;">Search</label>
        <input type="text" id="mapSearch" class="input-field" placeholder="Search name or plot..." onkeyup="searchOnMap()">
    </div>
    
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <button onclick="resetView()" class="btn-secondary" style="width: 100%;">
            <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Reset View
        </button>
        <a href="dashboard.php" class="btn-secondary" style="width: 100%; text-align: center;">
            <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Dashboard
        </a>
    </div>
    
    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
        <p style="font-size: 0.85rem; color: var(--zinc-400); margin: 0;">
            <strong>Total Records:</strong> <span id="totalCount">0</span><br>
            <strong>Visible:</strong> <span id="visibleCount">0</span>
        </p>
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
            color: '#ef4444',
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
                        const color = record.is_fenced == 1 ? '#fbbf24' : '#3b82f6';
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
                            html: '<div style="background: #22c55e; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
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
                    color: '#22c55e',
                    weight: 2,
                    fillColor: '#22c55e',
                    fillOpacity: 0.1
                }).bindPopup(`
                    <div style="min-width: 150px;">
                        <h4 style="margin: 0 0 8px 0;">Compartment ${cellLabel}</h4>
                        <p style="margin: 0; font-size: 0.85rem;">
                            <strong>Plot:</strong> ${plot.plot_number || 'N/A'}<br>
                            <strong>Status:</strong> Available
                        </p>
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
                    html: `<div style="font-size: 10px; font-weight: bold; color: #22c55e; text-shadow: 0 0 3px #000, 0 0 3px #000;">${cellLabel}</div>`,
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
            ? `<img src="../uploads/photos/${record.photo}" style="width: 100%; max-height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 12px;">`
            : '';
        
        return `
            <div style="min-width: 200px;">
                ${photoHtml}
                <h4 style="margin: 0 0 8px 0;">${record.decedent_name}</h4>
                <p style="margin: 4px 0; font-size: 0.85rem;">
                    <strong>Plot:</strong> ${record.plot_number || 'N/A'}<br>
                    <strong>Type:</strong> ${record.is_fenced == 1 ? 'Premium' : 'Standard'}<br>
                    <strong>Barangay:</strong> ${record.barangay || 'N/A'}
                </p>
                <button onclick="window.location.href='records.php?id=${record.id}'" class="btn-primary" style="width: 100%; margin-top: 8px; padding: 6px;">
                    View Details
                </button>
            </div>
        `;
    }
    
    function createPlotPopup(plot) {
        return `
            <div style="min-width: 200px;">
                <h4 style="margin: 0 0 8px 0; color: #22c55e;">Available Plot</h4>
                <p style="margin: 4px 0; font-size: 0.85rem;">
                    <strong>Plot:</strong> ${plot.plot_number || 'N/A'}<br>
                    ${plot.has_grid == 1 ? `<strong>Grid:</strong> ${plot.grid_rows} × ${plot.grid_cols}<br>` : ''}
                    ${plot.notes ? `<strong>Notes:</strong> ${plot.notes}` : ''}
                </p>
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
