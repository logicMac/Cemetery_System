/**
 * Visitor Dashboard JavaScript
 * Handles map initialization, markers, search, navigation, and AI assistant
 */

// Global variables
let map;
let markers = {
    burials: L.markerClusterGroup(),
    available: L.layerGroup(),
    search: null,
    userLocation: null,
    destination: null
};
let userLocationWatcher = null;
let routingControl = null;
let routeLine = null;
let allRecords = [];
let allPlots = [];
let filterState = {
    burials: true,
    available: true
};

// Cemetery center coordinates
const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
const CEMETERY_BOUNDS = [
    [6.18144118743717, 125.08257146469357],  // Widened by 100m (was 6.18244, 125.08357)
    [6.18544118743717, 125.08657146469357]   // Widened by 100m (was 6.18444, 125.08557)
];

// Initialize map
function initMap() {
    console.log('Initializing map with rotation...');
    console.log('L.Control.Rotate available:', typeof L.Control !== 'undefined' && typeof L.Control.Rotate !== 'undefined');
    
    map = L.map('map', {
        center: CEMETERY_CENTER,
        zoom: 17,
        minZoom: 10,
        maxZoom: 20,
        rotate: true,
        touchRotate: true,
        bearing: 0
    });
    
    console.log('Map created, checking for rotation support...');
    console.log('Map.setBearing available:', typeof map.setBearing === 'function');
    
    // Add rotation control explicitly
    if (typeof L.Control !== 'undefined' && typeof L.Control.Rotate !== 'undefined') {
        console.log('Adding rotation control...');
        const rotateControl = L.control.rotate({
            position: 'topleft', // Will be positioned with zoom controls via CSS
            closeOnZeroBearing: false
        });
        rotateControl.addTo(map);
        console.log('Rotation control added successfully');
    } else {
        console.error('L.Control.Rotate not available! Check if leaflet-rotate is loaded.');
    }
    
    // Base layers
    const googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: 'Google Satellite'
    });
    
    const googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: 'Google Hybrid'
    });
    
    const googleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: 'Google Streets'
    });
    
    const esriWorld = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Esri World Imagery'
    });
    
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'OpenStreetMap'
    });
    
    // Add default layer
    googleSat.addTo(map);
    
    // Layer control
    const baseLayers = {
        "Google Satellite": googleSat,
        "Google Hybrid": googleHybrid,
        "Google Streets": googleStreets,
        "Esri World Imagery": esriWorld,
        "OpenStreetMap": osm
    };
    
    L.control.layers(baseLayers).addTo(map);
    
    // Fullscreen control (optional - only add if available)
    if (L.Control.Fullscreen) {
        map.addControl(new L.Control.Fullscreen());
    }
    
    // Draw cemetery boundary
    L.rectangle(CEMETERY_BOUNDS, {
        color: '#b55a5a',
        weight: 2,
        fillOpacity: 0,
        dashArray: '5, 10'
    }).addTo(map);
    
    // Add marker layers
    map.addLayer(markers.burials);
    map.addLayer(markers.available);
    
    // Load data
    loadBurialRecords();
    loadAvailablePlots();
}

// Load burial records
async function loadBurialRecords() {
    try {
        const response = await fetch('../api/get_all_records.php');
        const data = await response.json();
        
        if (data.success) {
            allRecords = data.records;
            displayBurialMarkers(data.records);
            updateFilterCounts();
        }
    } catch (error) {
        console.error('Error loading burial records:', error);
        themeUtils.showAlert('Failed to load burial records', 'error');
    }
}

// Display burial markers
function displayBurialMarkers(records) {
    markers.burials.clearLayers();
    
    records.forEach(record => {
        if (record.latitude && record.longitude) {
            const iconColor = record.is_fenced == 1 ? '#c9a86c' : '#5a87a8';
            
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="background: ${iconColor}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            });
            
            const marker = L.marker([record.latitude, record.longitude], { icon })
                .bindPopup(createBurialPopup(record));
            
            marker.recordData = record;
            markers.burials.addLayer(marker);
            
            // If fenced, add a rectangular border around the grave
            if (record.is_fenced == 1) {
                const centerLat = parseFloat(record.latitude);
                const centerLng = parseFloat(record.longitude);
                const boxSize = 2; // 3 meters fence boundary
                
                // Convert meters to lat/lng offset
                const latOffset = boxSize / 111320; // 1 degree latitude ≈ 111320 meters
                const lngOffset = boxSize / (111320 * Math.cos(centerLat * Math.PI / 180));
                
                // Create rectangle bounds
                const bounds = [
                    [centerLat - latOffset, centerLng - lngOffset], // Southwest corner
                    [centerLat + latOffset, centerLng + lngOffset]  // Northeast corner
                ];
                
                // Draw the fence rectangle
                const fenceBox = L.rectangle(bounds, {
                    color: '#c9a86c',
                    weight: 2,
                    fillColor: '#c9a86c',
                    fillOpacity: 0.1,
                    dashArray: '4, 4'
                }).bindPopup(createBurialPopup(record));
                
                markers.burials.addLayer(fenceBox); 
            }
        }
    });
}

// Create burial popup content with enhanced details and photo
function createBurialPopup(record) {
    const photoHtml = record.photo 
        ? `<img src="../uploads/photos/${record.photo}" style="width: 100%; max-height: 250px; object-fit: cover; border-radius: 12px; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);" />`
        : `<div style="width: 100%; height: 200px; background: linear-gradient(135deg, #00c853 0%, #059669 100%); border-radius: 12px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center;">
            <svg style="width: 80px; height: 80px; color: rgba(255,255,255,0.5);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </div>`;
    
    const birthDate = record.birth_date ? new Date(record.birth_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
    const deathDate = record.death_date ? new Date(record.death_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
    const age = record.birth_date && record.death_date ? themeUtils.calculateAge(record.birth_date, record.death_date) : 'N/A';
    
    // Escape name for JavaScript
    const escapedName = (record.decedent_name || 'Unknown').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    
    return `
        <div style="min-width: 300px; max-width: 350px;">
            ${photoHtml}
            <div style="text-align: center; margin-bottom: 16px;">
                <h3 style="margin: 0 0 4px 0; font-size: 1.3rem; font-weight: 700;">${record.decedent_name}</h3>
                <p style="margin: 0; font-size: 0.9rem; color: rgba(255,255,255,0.6);">${birthDate} - ${deathDate}</p>
                <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: rgba(255,255,255,0.5);">Lived ${age} years</p>
            </div>
            
            <div style="background: rgba(255,255,255,0.05); border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.85rem;">
                    <div>
                        <p style="margin: 0; color: rgba(255,255,255,0.5); font-size: 0.75rem;">PLOT NUMBER</p>
                        <p style="margin: 2px 0 0 0; font-weight: 600;">${record.plot_number || 'N/A'}</p>
                    </div>
                    <div>
                        <p style="margin: 0; color: rgba(255,255,255,0.5); font-size: 0.75rem;">BARANGAY</p>
                        <p style="margin: 2px 0 0 0; font-weight: 600;">${record.barangay || 'N/A'}</p>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <p style="margin: 0; color: rgba(255,255,255,0.5); font-size: 0.75rem;">FAMILY NAME</p>
                        <p style="margin: 2px 0 0 0; font-weight: 600;">${record.family_name || 'N/A'}</p>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <p style="margin: 0; color: rgba(255,255,255,0.5); font-size: 0.75rem;">TYPE</p>
                        <p style="margin: 2px 0 0 0;">
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; ${record.is_fenced == 1 ? 'background: linear-gradient(135deg, #c9a86c 0%, #a68b52 100%); color: #000;' : 'background: linear-gradient(135deg, #5a87a8 0%, #3e6a9c 100%); color: white;'}">
                                ${record.is_fenced == 1 ? 'Premium / Fenced' : 'Standard'}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            ${record.memory_space ? `
                <div style="background: rgba(0, 230, 118, 0.1); border-left: 3px solid #00c853; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
                    <p style="margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.5px;">Memory</p>
                    <p style="margin: 6px 0 0 0; font-size: 0.9rem; font-style: italic; line-height: 1.5;">${record.memory_space}</p>
                </div>
            ` : ''}
            
            <button onclick="window.navigateToLocation(${record.latitude}, ${record.longitude}, '${escapedName}')" class="btn-primary" style="width: 100%; padding: 12px; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(0, 230, 118, 0.4); cursor: pointer; border: none; border-radius: 12px; background: linear-gradient(135deg, #00c853 0%, #059669 100%); color: white; font-weight: 600; transition: all 0.3s ease;">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                </svg>
                Navigate to This Location
            </button>
        </div>
    `;
}

// Load available plots
async function loadAvailablePlots() {
    try {
        const response = await fetch('../api/get_available_plots.php');
        const data = await response.json();
        
        if (data.success) {
            allPlots = data.plots;
            displayAvailablePlots(data.plots);
            updateFilterCounts();
        }
    } catch (error) {
        console.error('Error loading available plots:', error);
    }
}

// Display available plot markers
async function displayAvailablePlots(plots) {
    markers.available.clearLayers();
    
    for (const plot of plots) {
        const resStatus = plot.reservation_status;
        let markerColor = '#5a9b6f';
        let markerLabel = 'Available';
        
        if (resStatus === 'approved') {
            markerColor = '#b55a5a';
            markerLabel = 'Reserved';
        } else if (resStatus === 'pending') {
            markerColor = '#c9a86c';
            markerLabel = 'Pending';
        }
        
        const icon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: ${markerColor}; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
            iconSize: [14, 14],
            iconAnchor: [7, 7]
        });
        
        const marker = L.marker([plot.latitude, plot.longitude], { icon })
            .bindPopup(createPlotPopup(plot));
        
        marker.plotData = plot;
        markers.available.addLayer(marker);
        
        // Draw grid if available (await to ensure reserved compartments are styled)
        if (plot.has_grid == 1 && plot.grid_rows && plot.grid_cols) {
            await drawPlotGrid(plot);
        }
    }
}

// Create available plot popup
function createPlotPopup(plot) {
    const photoHtml = plot.photo 
        ? `<img src="../uploads/plots/${plot.photo}" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 12px;" />`
        : '';
    
    const resStatus = plot.reservation_status;
    let statusBadge = '';
    let headerColor = '#5a9b6f';
    let headerText = 'Available Plot';
    let headerSub = 'Ready for reservation';
    let reserveBtnHtml = '';
    
    if (resStatus === 'approved') {
        headerColor = '#b55a5a';
        headerText = 'Reserved Plot';
        headerSub = 'Already reserved';
        statusBadge = `<span style="background: rgba(181, 90, 90, 0.2); color: #b55a5a; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; border: 1px solid rgba(181, 90, 90, 0.3);">RESERVED</span>`;
        reserveBtnHtml = `<div style="flex: 1; padding: 10px; background: rgba(181, 90, 90, 0.1); color: rgba(181, 90, 90, 0.5); border: 1px solid rgba(181, 90, 90, 0.2); border-radius: 8px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: not-allowed; opacity: 0.6;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Reserved
        </div>`;
    } else if (resStatus === 'pending') {
        headerColor = '#c9a86c';
        headerText = 'Pending Reservation';
        headerSub = 'Awaiting approval';
        statusBadge = `<span style="background: rgba(201, 168, 108, 0.2); color: #c9a86c; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; border: 1px solid rgba(201, 168, 108, 0.3);">PENDING</span>`;
        reserveBtnHtml = `<div style="flex: 1; padding: 10px; background: rgba(201, 168, 108, 0.1); color: rgba(201, 168, 108, 0.5); border: 1px solid rgba(201, 168, 108, 0.2); border-radius: 8px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: not-allowed; opacity: 0.6;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Pending Approval
        </div>`;
    } else {
        reserveBtnHtml = `<button onclick="openReservationModal(${plot.id}, '${(plot.plot_number || '').replace(/'/g, "\\'")}');" 
            style="flex: 1; padding: 10px; background: linear-gradient(135deg, #00c853 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.9rem; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 6px;"
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0, 230, 118, 0.4)';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Reserve Plot
        </button>`;
    }
    
    return `
        <div style="min-width: 280px; padding: 8px;">
            ${photoHtml}
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, ${headerColor} 0%, ${headerColor}dd 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    </svg>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0; font-size: 1.1rem; color: ${headerColor};">${headerText}</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.85rem; color: var(--zinc-400);">${headerSub}</p>
                </div>
                ${statusBadge}
            </div>
            
            <div style="margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <svg style="width: 16px; height: 16px; color: var(--zinc-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <strong style="font-size: 0.9rem;">Plot Number:</strong>
                    <span>${plot.plot_number || 'N/A'}</span>
                </div>
                ${plot.has_grid == 1 ? `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg style="width: 16px; height: 16px; color: var(--zinc-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                        <strong style="font-size: 0.9rem;">Compartments:</strong>
                        <span>${plot.grid_rows} × ${plot.grid_cols}</span>
                    </div>
                ` : ''}
            </div>
            
            ${plot.notes ? `
                <p style="margin: 8px 0 12px 0; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 6px; color: var(--zinc-400); font-size: 0.85rem; line-height: 1.4;">
                    ${plot.notes}
                </p>
            ` : ''}
            
            <div style="display: flex; gap: 8px;">
                ${reserveBtnHtml}
                <button onclick="navigateToLocation(${plot.latitude}, ${plot.longitude})" 
                    style="padding: 10px 14px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center;"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)';"
                    onmouseout="this.style.background='rgba(255,255,255,0.05)';">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

// Draw plot grid overlay
async function drawPlotGrid(plot) {
    const rows = parseInt(plot.grid_rows);
    const cols = parseInt(plot.grid_cols);
    const centerLat = parseFloat(plot.latitude);
    const centerLng = parseFloat(plot.longitude);
    const cellSize = 2; // 2 meters per cell
    
    // Get reserved compartments for this plot
    let reservedCompartments = [];
    try {
        const response = await fetch(`../api/get_reserved_compartments.php?plot_id=${plot.id}`);
        const data = await response.json();
        console.log('Reserved API for plot', plot.id, plot.plot_number, ':', data);
        if (data.success) {
            reservedCompartments = data.reserved || [];
            console.log('Reserved compartment numbers:', reservedCompartments);
        }
    } catch (error) {
        console.error('Error fetching reserved compartments:', error);
    }
    
    // Cemetery orientation angle - adjust to match real cemetery layout
    const rotationAngle = 45; // Degrees
    const angleRad = rotationAngle * Math.PI / 180;
    
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            // Calculate offset in meters from center
            const offsetX = (col - cols / 2 + 0.5) * cellSize;
            const offsetY = (row - rows / 2 + 0.5) * cellSize;
            
            // Apply rotation
            const rotatedX = offsetX * Math.cos(angleRad) - offsetY * Math.sin(angleRad);
            const rotatedY = offsetX * Math.sin(angleRad) + offsetY * Math.cos(angleRad);
            
            // Convert meters to lat/lng
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
            
            const cellLabel = String.fromCharCode(65 + row) + (col + 1);
            const compartmentNum = row * cols + col + 1;
            
            // Check if this compartment is reserved
            const isReserved = reservedCompartments.includes(compartmentNum);
            
            if (isReserved) {
                console.log('Marking compartment', compartmentNum, 'as reserved for plot', plot.id);
            }
            
            // Style based on reservation status
            const cellStyle = isReserved ? {
                color: '#ff0000',
                weight: 5,
                opacity: 1,
                fillColor: '#ff0000',
                fillOpacity: 0.7,
                dashArray: null,
                className: 'compartment-cell-overlay reserved'
            } : {
                color: '#00c853',
                weight: 3,
                opacity: 0.9,
                fillColor: '#00c853',
                fillOpacity: 0.35,
                dashArray: '5, 5',
                className: 'compartment-cell-overlay'
            };
            
            const statusText = isReserved ? 'Reserved' : 'Available';
            const statusColor = isReserved ? '#b55a5a' : '#00c853';
            
            // Create rotated polygon with enhanced visibility
            const cell = L.polygon(latLngs, cellStyle).bindPopup(`
                <div style="text-align: center; padding: 8px;">
                    <strong style="font-size: 1.1rem; color: ${statusColor}; display: block; margin-bottom: 6px;">Compartment ${cellLabel}</strong>
                    <span style="font-size: 0.9rem; color: rgba(255,255,255,0.7);">Number: #${compartmentNum}</span>
                    <div style="margin-top: 6px; padding: 4px 10px; background: ${isReserved ? 'rgba(181, 90, 90, 0.2)' : 'rgba(0, 230, 118, 0.2)'}; border-radius: 12px; display: inline-block; font-size: 0.8rem; font-weight: 600; color: ${statusColor}; border: 1px solid ${isReserved ? 'rgba(181, 90, 90, 0.3)' : 'rgba(0, 230, 118, 0.3)'};">
                        ${statusText}
                    </div>
                </div>
            `);
            
            // Add hover effect
            cell.on('mouseover', function() {
                this.setStyle({
                    fillOpacity: 0.6,
                    weight: 4,
                    color: isReserved ? '#dc2626' : '#059669'
                });
            });
            
            cell.on('mouseout', function() {
                this.setStyle(cellStyle);
            });
            
            markers.available.addLayer(cell);
        }
    }
}

// Search functionality
async function performSearch() {
    const query = document.getElementById('searchInput').value.trim();
    
    if (query.length < 2) {
        themeUtils.showAlert('Please enter at least 2 characters', 'info');
        return;
    }
    
    try {
        const response = await fetch(`../api/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            displaySearchResults(data.results);
            // Show the search results panel
            document.getElementById('searchResultsPanel').classList.add('active');
        }
    } catch (error) {
        console.error('Search error:', error);
        themeUtils.showAlert('Search failed', 'error');
    }
}

// Display search results with photos
function displaySearchResults(results) {
    const container = document.getElementById('searchResults');
    const panel = document.getElementById('searchResultsPanel');
    
    if (results.length === 0) {
        container.innerHTML = '<div class="glass-card"><p style="text-align: center; color: var(--zinc-400);">No results found</p></div>';
        panel.classList.add('active');
        return;
    }
    
    panel.classList.add('active');
    
    container.innerHTML = results.map(result => {
        const photoHtml = result.photo 
            ? `<img src="../uploads/photos/${result.photo}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; margin-right: 12px;" />`
            : `<div style="width: 60px; height: 60px; background: linear-gradient(135deg, #00c853 0%, #059669 100%); border-radius: 8px; margin-right: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg style="width: 30px; height: 30px; color: rgba(255,255,255,0.7);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>`;
        
        const birthYear = result.birth_date ? new Date(result.birth_date).getFullYear() : '?';
        const deathYear = result.death_date ? new Date(result.death_date).getFullYear() : '?';
        
        return `
            <div class="search-result-item" onclick="showSearchResult(${result.latitude}, ${result.longitude}, ${result.id})" style="display: flex; align-items: center; cursor: pointer;">
                ${photoHtml}
                <div style="flex: 1; min-width: 0;">
                    <h4 style="margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 600;">${result.decedent_name}</h4>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--zinc-400);">
                        ${birthYear} - ${deathYear} | Plot: ${result.plot_number || 'N/A'}
                    </p>
                    <p style="margin: 2px 0 0 0; font-size: 0.75rem; color: var(--zinc-500);">
                        ${result.barangay || 'N/A'} ${result.family_name ? '• ' + result.family_name : ''}
                    </p>
                </div>
                <svg style="width: 20px; height: 20px; color: #00c853; flex-shrink: 0; margin-left: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        `;
    }).join('');
}

// Show search result on map
window.showSearchResult = function(lat, lng, recordId) {
    // Remove previous search marker
    if (markers.search) {
        map.removeLayer(markers.search);
    }
    
    // Create red marker
    const icon = L.divIcon({
        className: 'custom-marker',
        html: `<div style="background: #b55a5a; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 8px rgba(181, 90, 90, 0.5);"></div>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });
    
    markers.search = L.marker([lat, lng], { icon }).addTo(map);
    
    // Find and show popup
    const record = allRecords.find(r => r.id == recordId);
    if (record) {
        markers.search.bindPopup(createBurialPopup(record)).openPopup();
    }
    
    // Fly to location
    map.flyTo([lat, lng], 19, { duration: 1.5 });
};

// Navigation functionality with proper routing
window.navigateToLocation = function(lat, lng, destinationName = 'Destination') {
    console.log('Navigate called:', lat, lng, destinationName);
    
    if (!navigator.geolocation) {
        themeUtils.showAlert('Geolocation is not supported by your browser', 'error');
        return;
    }
    
    themeUtils.showAlert('Getting your location...', 'info');
    
    // Get user's current location
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;
            
            console.log('User location:', userLat, userLng);
            console.log('Destination:', lat, lng);
            
            // Remove existing routing control
            if (routingControl) {
                try {
                    map.removeControl(routingControl);
                } catch (e) {
                    console.error('Error removing old route:', e);
                }
                routingControl = null;
            }
            
            // Remove existing user location marker
            if (markers.userLocation) {
                map.removeLayer(markers.userLocation);
            }
            
            // Create user location marker with walking icon
            const userIcon = L.divIcon({
                className: 'user-location-marker-custom',
                html: `<div style="position: relative;">
                    <div style="width: 40px; height: 40px; background: #4285F4; border: 4px solid white; border-radius: 50%; box-shadow: 0 2px 10px rgba(66, 133, 244, 0.5); display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 20px; height: 20px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 23h2.1l1.8-8 2.1 2v6h2v-7.5l-2.1-2 .6-3C14.8 12 16.8 13 19 13v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1L6 8.3V13h2V9.6l1.8-.7"/>
                        </svg>
                    </div>
                    <div style="position: absolute; bottom: -5px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 8px solid white;"></div>
                </div>`,
                iconSize: [40, 48],
                iconAnchor: [20, 48]
            });
            
            markers.userLocation = L.marker([userLat, userLng], { icon: userIcon })
                .bindPopup('<strong>Your Location</strong>')
                .addTo(map);
            
            // Create destination marker with pin icon
            if (markers.destination) {
                map.removeLayer(markers.destination);
            }
            
            const destIcon = L.divIcon({
                className: 'destination-marker',
                html: `<div style="position: relative;">
                    <svg style="width: 40px; height: 40px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" viewBox="0 0 24 24" fill="#EA4335">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 40]
            });
            
            markers.destination = L.marker([lat, lng], { icon: destIcon })
                .bindPopup(`<strong>${destinationName}</strong>`)
                .addTo(map);
            
            console.log('User marker added');
            
            // Check if routing library is available
            if (typeof L.Routing === 'undefined' || typeof L.Routing.control === 'undefined') {
                console.warn('Routing library not available, using fallback');
                handleRoutingFallback(userLat, userLng, lat, lng, destinationName);
                return;
            }
            
            // Create routing control with OSRM
            try {
                console.log('Creating routing control...');
                
                routingControl = L.Routing.control({
                    waypoints: [
                        L.latLng(userLat, userLng),
                        L.latLng(lat, lng)
                    ],
                    router: L.Routing.osrmv1({
                        serviceUrl: 'https://router.project-osrm.org/route/v1',
                        profile: 'foot' // walking route
                    }),
                    routeWhileDragging: false,
                    addWaypoints: false,
                    draggableWaypoints: false,
                    fitSelectedRoutes: true,
                    showAlternatives: false,
                    lineOptions: {
                        styles: [
                            {
                                color: '#4285F4', // Google Maps blue
                                opacity: 0.9,
                                weight: 8,
                                className: 'route-line-animated'
                            }
                        ],
                        extendToWaypoints: true,
                        missingRouteTolerance: 0
                    },
                    createMarker: function(i, waypoint, n) {
                        // Don't create default markers (we have custom ones)
                        return null;
                    }
                }).addTo(map);
                
                console.log('Routing control created');
                
                // Customize routing instructions panel
                routingControl.on('routesfound', function(e) {
                    console.log('Route found!', e);
                    const routes = e.routes;
                    const summary = routes[0].summary;
                    
                    // Calculate distance and time
                    const distanceKm = (summary.totalDistance / 1000).toFixed(2);
                    const timeMin = Math.round(summary.totalTime / 60);
                    
                    // Show success message
                    themeUtils.showAlert(
                        `Route found! Distance: ${distanceKm} km, Estimated time: ${timeMin} minutes`,
                        'success'
                    );
                    
                    // Update user marker popup with distance info
                    if (markers.userLocation) {
                        markers.userLocation.setPopupContent(
                            `<div style="text-align: center;">
                                <strong>Your Location</strong><br>
                                <span style="font-size: 0.85rem; color: rgba(255,255,255,0.7);">
                                    ${distanceKm} km to ${destinationName}
                                </span>
                            </div>`
                        );
                    }
                    
                    // Add close button to routing container
                    setTimeout(() => {
                        const routingContainer = document.querySelector('.leaflet-routing-container');
                        if (routingContainer && !routingContainer.querySelector('.routing-close-btn')) {
                            const closeBtn = document.createElement('button');
                            closeBtn.className = 'routing-close-btn';
                            closeBtn.innerHTML = '×';
                            closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; background: rgba(181, 90, 90, 0.8); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 20px; line-height: 1; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; z-index: 10000;';
                            closeBtn.onmouseover = () => closeBtn.style.background = 'rgba(181, 90, 90, 1)';
                            closeBtn.onmouseout = () => closeBtn.style.background = 'rgba(181, 90, 90, 0.8)';
                            closeBtn.onclick = () => {
                                if (routingControl) {
                                    map.removeControl(routingControl);
                                    routingControl = null;
                                }
                                if (markers.userLocation) {
                                    map.removeLayer(markers.userLocation);
                                    markers.userLocation = null;
                                }
                                if (markers.destination) {
                                    map.removeLayer(markers.destination);
                                    markers.destination = null;
                                }
                                if (userLocationWatcher) {
                                    navigator.geolocation.clearWatch(userLocationWatcher);
                                    userLocationWatcher = null;
                                }
                            };
                            routingContainer.style.position = 'relative';
                            routingContainer.insertBefore(closeBtn, routingContainer.firstChild);
                        }
                    }, 100);
                });
                
                // Handle routing errors
                routingControl.on('routingerror', function(e) {
                    console.error('Routing error:', e);
                    handleRoutingFallback(userLat, userLng, lat, lng, destinationName);
                });
                
            } catch (error) {
                console.error('Error creating routing control:', error);
                handleRoutingFallback(userLat, userLng, lat, lng, destinationName);
            }
            
            // Fallback function
            function handleRoutingFallback(userLat, userLng, lat, lng, name) {
                console.log('Using fallback routing');
                
                // Remove routing control if exists
                if (routingControl) {
                    try {
                        map.removeControl(routingControl);
                    } catch (e) {
                        console.error('Error removing routing control:', e);
                    }
                    routingControl = null;
                }
                
                // Remove old route line if exists
                if (routeLine) {
                    map.removeLayer(routeLine);
                }
                
                // Draw smooth curved line as fallback (Google Maps style)
                const midLat = (userLat + lat) / 2;
                const midLng = (userLng + lng) / 2;
                
                // Create a slight curve
                const offset = 0.001;
                const curveLat = midLat + offset;
                const curveLng = midLng + offset;
                
                routeLine = L.polyline([
                    [userLat, userLng],
                    [curveLat, curveLng],
                    [lat, lng]
                ], {
                    color: '#4285F4',
                    weight: 8,
                    opacity: 0.9,
                    smoothFactor: 3,
                    className: 'route-line-fallback'
                }).addTo(map);
                
                console.log('Fallback line drawn');
                
                // Calculate straight-line distance
                const distance = themeUtils.calculateDistance(userLat, userLng, lat, lng);
                const formattedDistance = themeUtils.formatDistance(distance);
                
                themeUtils.showAlert(
                    `Showing direct path to ${name}. Distance: ${formattedDistance}`,
                    'info'
                );
                
                // Fit bounds to show both points
                map.fitBounds([
                    [userLat, userLng],
                    [lat, lng]
                ], { padding: [80, 80] });
                
                // Update user marker popup
                if (markers.userLocation) {
                    markers.userLocation.setPopupContent(
                        `<div style="text-align: center;">
                            <strong>Your Location</strong><br>
                            <span style="font-size: 0.85rem; color: rgba(255,255,255,0.7);">
                                ${formattedDistance} to ${name}
                            </span>
                        </div>`
                    ).openPopup();
                }
            }
            
            // Start watching user location for real-time updates
            if (userLocationWatcher) {
                navigator.geolocation.clearWatch(userLocationWatcher);
            }
            
            userLocationWatcher = navigator.geolocation.watchPosition(
                (pos) => {
                    const newLat = pos.coords.latitude;
                    const newLng = pos.coords.longitude;
                    
                    // Update user marker position
                    if (markers.userLocation) {
                        markers.userLocation.setLatLng([newLat, newLng]);
                    }
                    
                    // Update route if user moved significantly (more than 5 meters)
                    const movedDistance = themeUtils.calculateDistance(userLat, userLng, newLat, newLng);
                    if (movedDistance > 5 && routingControl) {
                        routingControl.setWaypoints([
                            L.latLng(newLat, newLng),
                            L.latLng(lat, lng)
                        ]);
                    }
                },
                (error) => {
                    console.error('Location watch error:', error);
                },
                { 
                    enableHighAccuracy: true, 
                    maximumAge: 2000,  // Cache for 2 seconds max
                    timeout: 15000  // Increased timeout
                }
            );
            
        },
        (error) => {
            console.error('Geolocation error:', error);
            let errorMessage = 'Unable to get your location. ';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += 'Please enable location permissions in your browser settings.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += 'Location information unavailable. Make sure GPS is enabled.';
                    break;
                case error.TIMEOUT:
                    errorMessage += 'Location request timed out. Please try again.';
                    break;
                default:
                    errorMessage += 'An unknown error occurred.';
            }
            
            themeUtils.showAlert(errorMessage, 'error');
        },
        { 
            enableHighAccuracy: true, 
            maximumAge: 0,  // Don't use cached position
            timeout: 15000  // Increased timeout for better accuracy
        }
    );
};

// AI Assistant functions
function toggleChat() {
    const chatContainer = document.getElementById('chatContainer');
    const chatToggle = document.getElementById('chatToggle');
    
    if (chatContainer.style.display === 'none') {
        chatContainer.style.display = 'flex';
        chatToggle.style.display = 'none';
    } else {
        chatContainer.style.display = 'none';
        chatToggle.style.display = 'flex';
    }
}

function handleChatKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    addChatMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    const typingId = addChatMessage('Thinking...', 'assistant');
    
    try {
        const response = await fetch('../api/visitor_assistant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, records: allRecords, plots: allPlots })
        });
        
        const data = await response.json();
        
        // Remove typing indicator
        document.getElementById(typingId).remove();
        
        if (data.success) {
            addChatMessage(data.response, 'assistant');
            
            // Check for navigation command
            if (data.navigation) {
                const destinationName = data.navigation.name || 'Destination';
                // Automatically navigate with route
                setTimeout(() => {
                    navigateToLocation(data.navigation.lat, data.navigation.lng, destinationName);
                    // Close chat to show the map and route
                    toggleChat();
                }, 1500); // Give user time to read the response
            }
        } else {
            addChatMessage('Sorry, I encountered an error. Please try again.', 'assistant');
        }
    } catch (error) {
        document.getElementById(typingId).remove();
        addChatMessage('Sorry, I could not process your request.', 'assistant');
    }
}

function addChatMessage(text, sender) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageId = 'msg-' + Date.now();
    
    const messageDiv = document.createElement('div');
    messageDiv.id = messageId;
    messageDiv.className = `chat-message ${sender}`;
    
    // For assistant messages, add logo and create flex layout
    if (sender === 'assistant') {
        messageDiv.style.display = 'flex';
        messageDiv.style.gap = '10px';
        messageDiv.style.alignItems = 'start';
        
        const logo = document.createElement('img');
        logo.src = '../assets/images/ai-assistant-logo.svg';
        logo.alt = 'AI';
        logo.style.width = '28px';
        logo.style.height = '28px';
        logo.style.flexShrink = '0';
        logo.style.marginTop = '2px';
        
        const textDiv = document.createElement('div');
        textDiv.textContent = text;
        
        messageDiv.appendChild(logo);
        messageDiv.appendChild(textDiv);
    } else {
        // For user messages, just add text
        messageDiv.textContent = text;
    }
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    return messageId;
}

// Search on Enter key
let searchDebounceTimer;
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchDebounceTimer);
            const query = e.target.value.trim();
            
            if (query.length === 0) {
                const panel = document.getElementById('searchResultsPanel');
                if (panel) panel.classList.remove('active');
                return;
            }
            
            if (query.length < 2) {
                return;
            }
            
            searchDebounceTimer = setTimeout(() => {
                performSearch();
            }, 400);
        });
    }
    
    // Update filter counts
    updateFilterCounts();
});

// Filter toggle function
window.toggleFilter = function(type) {
    const checkbox = document.getElementById(`filter${type.charAt(0).toUpperCase() + type.slice(1)}`);
    filterState[type] = checkbox.checked;
    
    if (type === 'burials') {
        if (filterState.burials) {
            map.addLayer(markers.burials);
        } else {
            map.removeLayer(markers.burials);
        }
    } else if (type === 'available') {
        if (filterState.available) {
            map.addLayer(markers.available);
        } else {
            map.removeLayer(markers.available);
        }
    }
};

// Toggle filter panel collapse/expand
window.toggleFilterPanel = function() {
    const panel = document.getElementById('filterPanel');
    const icon = document.getElementById('filterToggleIcon');
    
    panel.classList.toggle('collapsed');
    
    if (panel.classList.contains('collapsed')) {
        // Change icon to expand (chevron right)
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>';
    } else {
        // Change icon to collapse (chevron down)
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>';
    }
};

// Update filter counts
function updateFilterCounts() {
    document.getElementById('burialsCount').textContent = allRecords.length;
    document.getElementById('availableCount').textContent = allPlots.length;
}

// Initialize map on page load
initMap();


// ==========================================
// RESERVATION SYSTEM
// ==========================================

let pricingData = [];
let currentPlotData = null;

// Load pricing data
async function loadPricing() {
    try {
        const response = await fetch('../api/get_pricing.php');
        const data = await response.json();
        
        if (data.success) {
            pricingData = data.pricing;
        }
    } catch (error) {
        console.error('Error loading pricing:', error);
    }
}

// Open reservation modal (make it global)
window.openReservationModal = function(plotId, plotNumber, compartmentId = null) {
    if (!pricingData.length) {
        showSuccessModal('⚠️ Loading pricing information, please wait...', false);
        loadPricing().then(() => {
            openReservationModal(plotId, plotNumber, compartmentId);
        });
        return;
    }
    
    // Find plot data from allPlots
    currentPlotData = allPlots.find(p => p.id == plotId);
    
    document.getElementById('plot_id').value = plotId;
    
    // Display plot info
    let plotInfoHtml = `
        <p style="margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px;">
            <strong>Plot Number:</strong> 
            <span style="color: #00c853; font-weight: 600;">${plotNumber || 'Plot #' + plotId}</span>
        </p>
    `;
    
    document.getElementById('plotInfo').innerHTML = plotInfoHtml;
    
    // Show compartment selector if plot has grid
    const compartmentSelector = document.getElementById('compartmentSelector');
    if (currentPlotData && currentPlotData.has_grid == 1 && currentPlotData.grid_rows && currentPlotData.grid_cols) {
        compartmentSelector.style.display = 'block';
        renderCompartmentGrid(currentPlotData, compartmentId);
    } else {
        compartmentSelector.style.display = 'none';
        document.getElementById('compartment_id').value = '';
    }
    
    // Populate reservation type dropdown with pricing
    const typeSelect = document.getElementById('reservation_type');
    typeSelect.innerHTML = '<option value="" style="background: #0a0a0a; color: white;">Select Type</option>' + 
        pricingData.map(p => `
            <option value="${p.plot_type}" data-price="${p.price}" style="background: #0a0a0a; color: white; padding: 12px;">
                ${p.plot_type.charAt(0).toUpperCase() + p.plot_type.slice(1)} - ₱${parseFloat(p.price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
            </option>
        `).join('');
    
    // Show price when type selected
    typeSelect.onchange = function() {
        const selected = this.options[this.selectedIndex];
        const price = selected.getAttribute('data-price');
        const priceDisplay = document.getElementById('priceDisplay');
        if (price) {
            priceDisplay.style.display = 'block';
            priceDisplay.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.9rem;">Total Amount:</span>
                    <strong style="font-size: 1.3rem;">₱${parseFloat(price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                </div>
            `;
        } else {
            priceDisplay.style.display = 'none';
            priceDisplay.innerHTML = '';
        }
    };
    
    document.getElementById('reservationModal').style.display = 'flex';
}

// Render compartment grid
async function renderCompartmentGrid(plot, preselectedId = null) {
    const grid = document.getElementById('compartmentGrid');
    const rows = parseInt(plot.grid_rows);
    const cols = parseInt(plot.grid_cols);
    
    // Set grid layout
    grid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
    
    // Get reserved compartments
    let reservedCompartments = [];
    try {
        const response = await fetch(`../api/get_reserved_compartments.php?plot_id=${plot.id}`);
        const data = await response.json();
        if (data.success) {
            reservedCompartments = data.reserved || [];
        }
    } catch (error) {
        console.error('Error loading reserved compartments:', error);
    }
    
    // Create cells
    let html = '';
    let compartmentNum = 1;
    
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const label = String.fromCharCode(65 + row) + (col + 1); // A1, A2, B1, etc.
            const isReserved = reservedCompartments.includes(compartmentNum);
            const isSelected = compartmentNum == preselectedId;
            
            html += `
                <div class="compartment-cell ${isReserved ? 'reserved' : ''} ${isSelected ? 'selected' : ''}" 
                     data-compartment="${compartmentNum}"
                     onclick="${isReserved ? '' : 'selectCompartment(' + compartmentNum + ', this)'}">
                    <div style="font-size: 0.9rem;">${label}</div>
                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 4px;">#${compartmentNum}</div>
                </div>
            `;
            compartmentNum++;
        }
    }
    
    grid.innerHTML = html;
    
    // Set initial compartment if preselected
    if (preselectedId) {
        document.getElementById('compartment_id').value = preselectedId;
    }
}

// Select compartment
window.selectCompartment = function(compartmentNum, element) {
    // Remove selected class from all cells
    document.querySelectorAll('.compartment-cell').forEach(cell => {
        cell.classList.remove('selected');
    });
    
    // Add selected class to clicked cell
    element.classList.add('selected');
    
    // Set hidden field value
    document.getElementById('compartment_id').value = compartmentNum;
}

// Close reservation modal (make it global)
window.closeReservationModal = function() {
    document.getElementById('reservationModal').style.display = 'none';
    document.getElementById('reservationForm').reset();
    document.getElementById('priceDisplay').innerHTML = '';
    document.getElementById('priceDisplay').style.display = 'none';
    document.getElementById('compartment_id').value = '';
    currentPlotData = null;
}

// Show success modal
function showSuccessModal(message, isSuccess = true) {
    const modal = document.getElementById('successModal');
    const messageDiv = document.getElementById('successMessage');
    const iconDiv = document.getElementById('successIcon');
    const titleDiv = document.getElementById('successTitle');
    
    messageDiv.innerHTML = message;
    
    if (isSuccess) {
        // Success styling
        iconDiv.style.background = 'linear-gradient(135deg, #5a9b6f 0%, #059669 100%)';
        iconDiv.innerHTML = `
            <svg style="width: 48px; height: 48px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        `;
        titleDiv.textContent = 'Reservation Successful!';
        titleDiv.style.color = '#5a9b6f';
    } else {
        // Error styling
        iconDiv.style.background = 'linear-gradient(135deg, #b55a5a 0%, #dc2626 100%)';
        iconDiv.innerHTML = `
            <svg style="width: 48px; height: 48px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        `;
        titleDiv.textContent = 'Reservation Failed';
        titleDiv.style.color = '#b55a5a';
    }
    
    modal.style.display = 'flex';
}

// Close success modal
window.closeSuccessModal = function() {
    document.getElementById('successModal').style.display = 'none';
}

// Submit reservation
document.addEventListener('DOMContentLoaded', function() {
    // Load pricing on page load
    loadPricing();
    
    const reservationForm = document.getElementById('reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg style="width: 20px; height: 20px; animation: spin 1s linear infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Processing...
            `;
            
            try {
                const response = await fetch('../api/create_reservation.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let successMessage = `
                        <div style="text-align: left;">
                            <p style="margin: 0 0 16px 0; font-size: 1.1rem; font-weight: 600; color: white;">📋 Reservation Details:</p>
                            <div style="background: rgba(0,0,0,0.3); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                                <p style="margin: 0 0 8px 0;">
                                    <span style="color: rgba(255,255,255,0.6);">Plot:</span> 
                                    <strong style="color: #00c853;">${data.plot_number || 'N/A'}</strong>
                                </p>
                    `;
                    
                    if (data.compartment) {
                        const label = getCompartmentLabel(data.compartment, currentPlotData);
                        successMessage += `
                            <p style="margin: 0 0 8px 0;">
                                <span style="color: rgba(255,255,255,0.6);">Compartment:</span> 
                                <strong style="color: #00c853;">${label} (#${data.compartment})</strong>
                            </p>
                        `;
                    }
                    
                    successMessage += `
                                <p style="margin: 0;">
                                    <span style="color: rgba(255,255,255,0.6);">Total Amount:</span> 
                                    <strong style="color: #5a9b6f; font-size: 1.2rem;">₱${parseFloat(data.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                                </p>
                            </div>
                            <p style="margin: 0 0 12px 0; font-size: 1.1rem; font-weight: 600; color: white;">💡 Next Steps:</p>
                            <ol style="margin: 0; padding-left: 20px; color: rgba(255,255,255,0.8); line-height: 1.8;">
                                <li>Wait for admin approval</li>
                                <li>After approval, submit payment</li>
                                <li>Track status in "My Reservations"</li>
                            </ol>
                        </div>
                    `;
                    
                    showSuccessModal(successMessage, true);
                    closeReservationModal();
                } else {
                    showSuccessModal(`
                        <div style="text-align: center;">
                            <p style="margin: 0; font-size: 1.1rem; line-height: 1.6; color: rgba(255,255,255,0.9);">
                                ${data.message}
                            </p>
                        </div>
                    `, false);
                    
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Submit Reservation
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                showSuccessModal(`
                    <div style="text-align: center;">
                        <p style="margin: 0; font-size: 1.1rem; line-height: 1.6; color: rgba(255,255,255,0.9);">
                            Error submitting reservation. Please check your connection and try again.
                        </p>
                    </div>
                `, false);
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = `
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Submit Reservation
                `;
            }
        });
    }
});

// Helper function to get compartment label (A1, B2, etc.)
function getCompartmentLabel(compartmentNum, plotData) {
    if (!plotData || !plotData.grid_cols) return `#${compartmentNum}`;
    
    const cols = parseInt(plotData.grid_cols);
    const row = Math.floor((compartmentNum - 1) / cols);
    const col = (compartmentNum - 1) % cols;
    
    return String.fromCharCode(65 + row) + (col + 1);
}

// Toggle navigation bar
function toggleNavBar() {
    const topBar = document.getElementById('topBar');
    topBar.classList.toggle('collapsed');
}

// Toggle search bar
function toggleSearchBar() {
    const searchBar = document.getElementById('searchBar');
    searchBar.classList.toggle('collapsed');
}


// Rotation functions (copied from admin map)
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
        themeUtils.showAlert('Map rotation is not available', 'error');
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
            bearingEl.textContent = bearing.toFixed(0) + '°';
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
}, 2000);

// Toggle rotation panel
function toggleRotationPanel() {
    const panel = document.getElementById('rotationPanel');
    const icon = document.getElementById('rotationToggleIcon');
    
    if (panel.classList.contains('collapsed')) {
        panel.classList.remove('collapsed');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>';
    } else {
        panel.classList.add('collapsed');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>';
    }
}
