/**
 * Visitor Dashboard JavaScript
 * Handles map initialization, markers, search, navigation, and AI assistant
 */

// Global variables
let AVAILABLE_PLOTS_ONLY = false;
let map;
let markers = {
    burials: L.layerGroup(),
    available: L.layerGroup(),
    grids: L.layerGroup(),
    search: null,
    searchCell: null,
    userLocation: null,
    destination: null
};
let userLocationWatcher = null;
let routingControl = null;
let routeLine = null;
let routeArrows = null;
let routeInfoBadge = null;
let allRecords = [];
let allPlots = [];
let lastSearchResults = [];
let filterState = {
    burials: true,
    available: true
};

// Cemetery center coordinates
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
        bearing: 315,
        zoomControl: false,
        attributionControl: false
    });

    // Fit the cemetery bounds on load so the whole area is visible
    // regardless of screen size (with padding around the edges)
    setTimeout(() => {
        map.fitBounds(CEMETERY_BOUNDS, {
            padding: [50, 50],
            maxZoom: 19,
            animate: false
        });
        if (typeof map.setBearing === 'function') {
            map.setBearing(315);
            updateBearingDisplay();
        }
    }, 100);
    
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
    
    // Layer control - custom dropdown in rotation panel instead
    const baseLayers = {
        "Google Satellite": googleSat,
        "Google Hybrid": googleHybrid,
        "Google Streets": googleStreets,
        "Esri World Imagery": esriWorld,
        "OpenStreetMap": osm
    };
    window._baseLayers = baseLayers;
    window._currentLayer = googleSat;
    
    // Fullscreen control (optional - only add if available)
    if (L.Control.Fullscreen) {
        map.addControl(new L.Control.Fullscreen());
    }
    
    // Draw cemetery boundary (actual polygon shape, not a rectangle)
    L.polygon(CEMETERY_POLYGON, {
        color: '#b55a5a',
        weight: 2,
        fillOpacity: 0,
        dashArray: '5, 10'
    }).addTo(map);

    // Add marker layers
    map.addLayer(markers.burials);
    map.addLayer(markers.grids);

    // Load data
    if (!AVAILABLE_PLOTS_ONLY) {
        loadBurialRecords();
    }
    loadStandaloneGrids();
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
            const rcBadge = (record.grid_name && record.cell_row)
                ? `<div style="position:absolute;top:15px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;white-space:nowrap;border:1px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.4);">R${record.cell_row}-C${record.cell_col}</div>`
                : '';

            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="position:relative;width:12px;height:12px;"><div style="background: ${iconColor}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>${rcBadge}</div>`,
                iconSize: [12, 12],
                iconAnchor: [6, 6]
            });
            
            const marker = L.marker([record.latitude, record.longitude], { icon })
                .bindPopup(createBurialPopup(record));
            
            marker.recordData = record;
            markers.burials.addLayer(marker);
            
            // Draw custom polygon boundary if one was saved for this record
            if (record.polygon) {
                drawPolygon(record.polygon, createBurialPopup(record), '#5a87a8');
            }

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

// Helper to draw a free-form plot polygon on the visitor map
function drawPolygon(polygonData, popupHtml, color = '#22c55e', targetLayer = markers.burials) {
    try {
        let coords = polygonData;
        if (typeof coords === 'string') {
            coords = JSON.parse(coords);
        }
        if (!Array.isArray(coords) || coords.length < 3) return;

        // Ensure each point is a [lat, lng] pair
        const latlngs = coords.map(p => Array.isArray(p) ? [parseFloat(p[0]), parseFloat(p[1])] : [parseFloat(p.lat), parseFloat(p.lng)]).filter(p => !isNaN(p[0]) && !isNaN(p[1]));
        if (latlngs.length < 3) return;

        const polygon = L.polygon(latlngs, {
            color: color,
            weight: 2,
            fillColor: color,
            fillOpacity: 0.12,
            dashArray: null
        }).bindPopup(popupHtml);

        targetLayer.addLayer(polygon);
    } catch (e) {
        console.error('Error drawing polygon:', e);
    }
}

// Create burial popup content with enhanced details and photo
function createBurialPopup(record) {
    const photoHtml = record.photo 
        ? `<img src="../uploads/photos/${record.photo}" style="width: 100%; max-height: 250px; object-fit: cover; border-radius: 12px; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);" />`
        : `<div style="width: 100%; height: 200px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center;">
            <svg style="width: 80px; height: 80px; color: rgba(255,255,255,0.7);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </div>`;
    
    const birthDate = record.birth_date ? new Date(record.birth_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
    const deathDate = record.death_date ? new Date(record.death_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
    const age = record.birth_date && record.death_date ? themeUtils.calculateAge(record.birth_date, record.death_date) : 'N/A';
    const burialDate = record.burial_date ? new Date(record.burial_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '';
    const burialTime = record.burial_time ? String(record.burial_time).substring(0, 5) : '';
    const burialText = burialDate ? burialDate + (burialTime ? ' • ' + burialTime : '') : 'N/A';

    // Compartment location block (apartment/niche structures)
    const isCompartment = record.grid_name && record.cell_row;
    const compartmentHtml = isCompartment ? `
        <div style="background: #eff6ff; border-left: 3px solid #3b82f6; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
            <p style="margin: 0; font-size: 0.75rem; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.5px;">Compartment Location</p>
            <p style="margin: 6px 0 0 0; font-size: 0.9rem; color: #0f172a;">
                <strong>${record.grid_name}</strong> — Row ${record.cell_row}, Column ${record.cell_col}
                <span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700;background:#1e293b;color:#fff;">R${record.cell_row}-C${record.cell_col}</span>
            </p>
        </div>` : '';

    // Renewal status based on expiration_date
    let renewalHtml = '';
    if (record.expiration_date) {
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const exp = new Date(record.expiration_date); exp.setHours(0, 0, 0, 0);
        const expStr = exp.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        const monthsLeft = (exp.getFullYear() - today.getFullYear()) * 12 + (exp.getMonth() - today.getMonth()) - (exp.getDate() < today.getDate() ? 1 : 0);
        let badge, note;
        if (monthsLeft < 0) {
            badge = '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;">RENEWAL OVERDUE</span>';
            note = 'This plot expired on ' + expStr + '. Please inform the family to renew at the cemetery office.';
        } else if (monthsLeft <= 6) {
            badge = '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;background:#fffbeb;color:#b45309;border:1px solid #fde68a;">RENEWAL DUE SOON</span>';
            note = monthsLeft + ' month' + (monthsLeft === 1 ? '' : 's') + ' remaining before renewal (expires ' + expStr + ').';
        } else {
            badge = '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;background:#f0fdf4;color:#047857;border:1px solid #6ee7b7;">ACTIVE</span>';
            note = monthsLeft + ' months remaining before renewal (expires ' + expStr + ').';
        }
        renewalHtml = `
            <div style="background: #f8fafc; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <p style="margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem;">PLOT RENEWAL STATUS</p>
                ${badge}
                <p style="margin: 8px 0 0 0; font-size: 0.82rem; color: #475569; line-height: 1.5;">${note}</p>
            </div>`;
    }

    // Escape name for JavaScript
    const escapedName = (record.decedent_name || 'Unknown').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    const subLabel = isCompartment ? `${record.grid_name} · R${record.cell_row}-C${record.cell_col}` : '';
    const escapedSub = subLabel.replace(/'/g, "\\'").replace(/"/g, '&quot;');
    
    return `
        <div style="min-width: 300px; max-width: 350px;">
            ${photoHtml}
            <div style="text-align: center; margin-bottom: 16px;">
                <h3 style="margin: 0 0 4px 0; font-size: 1.3rem; font-weight: 700; color: #0f172a;">${record.decedent_name}</h3>
                <p style="margin: 0; font-size: 0.9rem; color: #475569;">${birthDate} - ${deathDate}</p>
                <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b;">Lived ${age} years</p>
            </div>
            
            ${compartmentHtml}

            <div style="background: #f8fafc; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.85rem;">
                    <div>
                        <p style="margin: 0; color: #64748b; font-size: 0.75rem;">PLOT NUMBER</p>
                        <p style="margin: 2px 0 0 0; font-weight: 600; color: #0f172a;">${record.plot_number || 'N/A'}</p>
                    </div>
                    <div>
                        <p style="margin: 0; color: #64748b; font-size: 0.75rem;">BARANGAY</p>
                        <p style="margin: 2px 0 0 0; font-weight: 600; color: #0f172a;">${record.barangay || 'N/A'}</p>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <p style="margin: 0; color: #64748b; font-size: 0.75rem;">FAMILY NAME</p>
                        <p style="margin: 2px 0 0 0; font-weight: 600; color: #0f172a;">${record.family_name || 'N/A'}</p>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <p style="margin: 0; color: #64748b; font-size: 0.75rem;">BURIAL DATE &amp; TIME</p>
                        <p style="margin: 2px 0 0 0; font-weight: 600; color: #0f172a;">${burialText}</p>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <p style="margin: 0; color: #64748b; font-size: 0.75rem;">TYPE</p>
                        <p style="margin: 2px 0 0 0;">
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; ${record.is_fenced == 1 ? 'background: #fffbeb; color: #b45309; border: 1px solid #fde68a;' : 'background: #f0fdf4; color: #047857; border: 1px solid #6ee7b7;'}">
                                ${record.is_fenced == 1 ? 'Premium / Fenced' : 'Standard'}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            ${renewalHtml}

            ${record.memory_space ? `
                <div style="background: #f0fdf4; border-left: 3px solid #10b981; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
                    <p style="margin: 0; font-size: 0.75rem; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">Memory</p>
                    <p style="margin: 6px 0 0 0; font-size: 0.9rem; font-style: italic; line-height: 1.5; color: #0f172a;">${record.memory_space}</p>
                </div>
            ` : ''}
            
            <button onclick="window.navigateToLocation(${record.latitude}, ${record.longitude}, '${escapedName}', '${escapedSub}')" class="btn-primary" style="width: 100%; padding: 12px; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; border-radius: 12px; background: #10b981; color: white; font-weight: 600; transition: all 0.3s ease;"
                onmouseover="this.style.backgroundColor='#059669';"
                onmouseout="this.style.backgroundColor='#10b981';">
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

// Load standalone grids from plot_grids table (not tied to available_plots)
async function loadStandaloneGrids() {
    try {
        const response = await fetch('../api/grids.php?action=list');
        const data = await response.json();
        if (data.success && data.grids) {
            markers.grids.clearLayers();
            for (const grid of data.grids) {
                // Skip grids that are linked to a burial record (those are shown via records)
                // and skip grids without center coordinates
                if (!grid.center_lat || !grid.center_lng) continue;
                await drawStandaloneGrid(grid);
            }
        }
    } catch (error) {
        console.error('Error loading standalone grids:', error);
    }
}

// Draw a standalone grid on the visitor map
async function drawStandaloneGrid(grid) {
    const rows = parseInt(grid.rows);
    const cols = parseInt(grid.cols);
    const centerLat = parseFloat(grid.center_lat);
    const centerLng = parseFloat(grid.center_lng);
    const cellSize = 2; // 2 meters per cell (same as admin)
    const rotationAngle = 45; // degrees (same as admin/visitor grid rendering)
    const angleRad = rotationAngle * Math.PI / 180;
    const metersPerLat = 111320;
    const metersPerLng = 111320 * Math.cos(centerLat * Math.PI / 180);

    // Fetch cells with record assignments
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

            const label = String.fromCharCode(65 + row) + (col + 1);
            const compartmentNum = row * cols + col + 1;
            const cellData = cellsData.find(c => c.row === (row + 1) && c.col === (col + 1));
            const isOccupied = cellData && cellData.record_id;

            const cellStyle = isOccupied ? {
                color: '#ef4444', weight: 2, opacity: 1,
                fillColor: '#ef4444', fillOpacity: 0.5, dashArray: null
            } : {
                color: '#10b981', weight: 1.5, opacity: 0.8,
                fillColor: '#10b981', fillOpacity: 0.15, dashArray: '4, 4'
            };

            const statusText = isOccupied ? 'Occupied' : 'Available';
            const decedentInfo = isOccupied && cellData.decedent_name
                ? `<br><span style="font-size:0.85rem;">${cellData.decedent_name}</span>`
                : '';

            const cell = L.polygon(latLngs, cellStyle).bindPopup(`
                <div style="text-align:center;padding:4px;">
                    <strong style="font-size:1rem;color:${isOccupied ? '#ef4444' : '#10b981'};display:block;margin-bottom:4px;">${gridName}</strong>
                    <span style="font-size:0.9rem;">Cell ${label} (#${compartmentNum})</span><br>
                    <span style="font-size:0.85rem;color:#475569;">Row ${row + 1}, Column ${col + 1}</span>
                    ${decedentInfo}
                    <div style="margin-top:6px;padding:3px 10px;background:${isOccupied ? 'rgba(239,68,68,0.15)' : 'rgba(16,185,129,0.15)'};border-radius:12px;display:inline-block;font-size:0.8rem;font-weight:600;color:${isOccupied ? '#ef4444' : '#10b981'};border:1px solid ${isOccupied ? 'rgba(239,68,68,0.3)' : 'rgba(16,185,129,0.3)'};">
                        ${statusText}
                    </div>
                </div>
            `);

            // Permanent row/column tag on occupied compartments so guests
            // can see exactly where the deceased is located
            if (isOccupied) {
                cell.bindTooltip('R' + (row + 1) + '-C' + (col + 1), {
                    permanent: true,
                    direction: 'center',
                    className: 'cell-rc-label',
                    interactive: false
                });
            }

            cell.on('mouseover', function() {
                this.setStyle({ fillOpacity: isOccupied ? 0.65 : 0.3, weight: 3 });
            });
            cell.on('mouseout', function() {
                this.setStyle(cellStyle);
            });

            markers.grids.addLayer(cell);
        }
    }
}

// Display available plot markers
async function displayAvailablePlots(plots) {
    markers.available.clearLayers();
    
    for (const plot of plots) {
        const resStatus = plot.reservation_status;
        const hasGrid = plot.has_grid == 1;
        let markerColor = '#5a9b6f';
        let markerLabel = 'Available';

        // Only show as reserved/pending for non-grid plots
        // Grid plots can still have available compartments
        if (!hasGrid && resStatus === 'approved') {
            markerColor = '#b55a5a';
            markerLabel = 'Reserved';
        } else if (!hasGrid && resStatus === 'pending') {
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

        // Draw free-form polygon boundary if one was saved for this plot
        if (plot.polygon) {
            drawPolygon(plot.polygon, createPlotPopup(plot), '#22c55e', markers.available);
        }

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
    const hasGrid = plot.has_grid == 1;
    let statusBadge = '';
    let headerIconColor = '#10b981';
    let headerIconBg = '#f0fdf4';
    let headerText = 'Available Plot';
    let headerSub = 'Ready for reservation';
    let reserveBtnHtml = '';

    // For grid plots, always allow reservation (user picks a compartment in the modal)
    // For non-grid plots, block if already pending/approved
    const isBlocked = !hasGrid && (resStatus === 'approved' || resStatus === 'pending');

    if (isBlocked && resStatus === 'approved') {
        headerIconColor = '#b91c1c';
        headerIconBg = '#fef2f2';
        headerText = 'Reserved Plot';
        headerSub = 'Already reserved';
        statusBadge = `<span style="background: #fef2f2; color: #b91c1c; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; border: 1px solid #fecaca;">RESERVED</span>`;
        reserveBtnHtml = `<div style="flex: 1; padding: 10px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 8px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: not-allowed; opacity: 0.9;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Reserved
        </div>`;
    } else if (isBlocked && resStatus === 'pending') {
        headerIconColor = '#b45309';
        headerIconBg = '#fffbeb';
        headerText = 'Pending Reservation';
        headerSub = 'Awaiting approval';
        statusBadge = `<span style="background: #fffbeb; color: #b45309; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; border: 1px solid #fde68a;">PENDING</span>`;
        reserveBtnHtml = `<div style="flex: 1; padding: 10px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; border-radius: 8px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: not-allowed; opacity: 0.9;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Pending Approval
        </div>`;
    } else {
        // Show status badge for grid plots that have some reservations
        if (hasGrid && resStatus === 'approved') {
            statusBadge = `<span style="background: #fef2f2; color: #b91c1c; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; border: 1px solid #fecaca;">SOME RESERVED</span>`;
        } else if (hasGrid && resStatus === 'pending') {
            statusBadge = `<span style="background: #fffbeb; color: #b45309; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; border: 1px solid #fde68a;">SOME PENDING</span>`;
        }
        reserveBtnHtml = `<button onclick="openReservationModal(${plot.id}, '${(plot.plot_number || '').replace(/'/g, "\\'")}');"
            style="flex: 1; padding: 10px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.9rem; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 6px;"
            onmouseover="this.style.backgroundColor='#059669';"
            onmouseout="this.style.backgroundColor='#10b981';">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Reserve Plot
        </button>`;
    }
    
    return `
        <div style="min-width: 280px; padding: 8px;">
            ${photoHtml}
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                <div style="width: 40px; height: 40px; background: ${headerIconBg}; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 24px; height: 24px; color: ${headerIconColor};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    </svg>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0; font-size: 1.1rem; color: #0f172a;">${headerText}</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.85rem; color: #64748b;">${headerSub}</p>
                </div>
                ${statusBadge}
            </div>
            
            <div style="background: #f8fafc; border-radius: 8px; padding: 10px; margin-bottom: 12px; color: #0f172a;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <svg style="width: 16px; height: 16px; color: #64748b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <strong style="font-size: 0.9rem; color: #0f172a;">Plot Number:</strong>
                    <span style="color: #475569;">${plot.plot_number || 'N/A'}</span>
                </div>
                ${plot.has_grid == 1 ? `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg style="width: 16px; height: 16px; color: #64748b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                        <strong style="font-size: 0.9rem; color: #0f172a;">Compartments:</strong>
                        <span style="color: #475569;">${plot.grid_rows} × ${plot.grid_cols}</span>
                    </div>
                ` : ''}
            </div>
            
            ${plot.notes ? `
                <p style="margin: 8px 0 12px 0; padding: 10px; background: #f8fafc; border-radius: 6px; color: #475569; font-size: 0.85rem; line-height: 1.4;">
                    ${plot.notes}
                </p>
            ` : ''}
            
            <div style="display: flex; gap: 8px;">
                ${reserveBtnHtml}
                <button onclick="navigateToLocation(${plot.latitude}, ${plot.longitude})" 
                    style="padding: 10px 14px; background: #f0fdf4; color: #047857; border: 1px solid #6ee7b7; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center;"
                    onmouseover="this.style.background='#d1fae5';"
                    onmouseout="this.style.background='#f0fdf4';">
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

    if (AVAILABLE_PLOTS_ONLY) {
        const filtered = allPlots.filter(p =>
            (p.plot_number || '').toLowerCase().includes(query.toLowerCase()) ||
            (p.notes || '').toLowerCase().includes(query.toLowerCase())
        );
        displayAvailablePlotSearchResults(filtered);
        document.getElementById('searchResultsPanel').classList.add('active');
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

// Display available plot search results
function displayAvailablePlotSearchResults(plots) {
    const container = document.getElementById('searchResults');
    const panel = document.getElementById('searchResultsPanel');

    if (plots.length === 0) {
        container.innerHTML = '<div class="glass-card"><p style="text-align: center; color: #94a3b8;">No available plots found</p></div>';
        panel.classList.add('active');
        return;
    }

    panel.classList.add('active');
    container.innerHTML = plots.map(plot => {
        let statusLabel = 'Available';
        let statusColor = '#10b981';
        if (plot.reservation_status === 'approved') { statusLabel = 'Reserved'; statusColor = '#b91c1c'; }
        else if (plot.reservation_status === 'pending') { statusLabel = 'Pending'; statusColor = '#b45309'; }
        return `
            <div class="search-result-item" onclick="showAvailablePlotResult(${plot.latitude}, ${plot.longitude}, ${plot.id})" style="display: flex; align-items: center; cursor: pointer;">
                <div style="width: 40px; height: 40px; background: ${statusColor}20; border-radius: 8px; margin-right: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: 20px; height: 20px; color: ${statusColor};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    </svg>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <p style="margin: 0; font-weight: 600; color: #0f172a; font-size: 0.9rem;">Plot ${plot.plot_number || '#' + plot.id}</p>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: ${statusColor};">${statusLabel}</p>
                </div>
            </div>
        `;
    }).join('');
}

// Show an available plot search result on the map
function showAvailablePlotResult(lat, lng, plotId) {
    map.setView([lat, lng], 19);
    document.getElementById('searchResultsPanel').classList.remove('active');
}

// Display search results with photos
function displaySearchResults(results) {
    const container = document.getElementById('searchResults');
    const panel = document.getElementById('searchResultsPanel');
    lastSearchResults = results;

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
        const compartmentLine = (result.grid_name && result.cell_row)
            ? `<p style="margin: 2px 0 0 0; font-size: 0.75rem; color: #2563eb; font-weight: 600;">
                🏢 ${result.grid_name} • Row ${result.cell_row}, Col ${result.cell_col}
            </p>`
            : '';

        return `
            <div class="search-result-item" onclick="showSearchResult(${result.latitude}, ${result.longitude}, ${result.id})" style="display: flex; align-items: center; cursor: pointer;">
                ${photoHtml}
                <div style="flex: 1; min-width: 0;">
                    <h4 style="margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 600;">${result.decedent_name}</h4>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--zinc-400);">
                        ${birthYear} - ${deathYear} | Plot: ${result.plot_number || 'N/A'}
                    </p>
                    ${compartmentLine}
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

// Show search result on map — drops a prominent 📍 pin at the grave location.
// If the deceased is inside a compartment structure, the exact cell is
// highlighted and labelled with its row/column instead.
window.showSearchResult = function(lat, lng, recordId) {
    // Remove previous search marker + cell highlight
    if (markers.search) {
        map.removeLayer(markers.search);
        markers.search = null;
    }
    if (markers.searchCell) {
        map.removeLayer(markers.searchCell);
        markers.searchCell = null;
    }

    // Find record data (prefer live records, fall back to search payload)
    const record = allRecords.find(r => r.id == recordId)
        || lastSearchResults.find(r => r.id == recordId);

    // 📍 pin marker
    const pinIcon = L.divIcon({
        className: 'search-pin-marker',
        html: `<div style="position:relative;">
            <div style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:46px;height:46px;border-radius:50%;background:rgba(234,67,53,0.18);animation:routePulseRing 2s ease-out infinite;"></div>
            <div style="position:relative;font-size:34px;line-height:1;filter:drop-shadow(0 3px 4px rgba(0,0,0,0.45));">📍</div>
        </div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 32]
    });

    // If the record sits in a compartment, pin the exact cell center
    const cellInfo = record ? computeCellCenter(record) : null;
    const target = cellInfo ? cellInfo.center : [lat, lng];

    markers.search = L.marker(target, { icon: pinIcon, zIndexOffset: 1200 }).addTo(map);

    if (record) {
        markers.search.bindPopup(createBurialPopup(record)).openPopup();
    }

    // Highlight the specific compartment cell (row/column indicator)
    if (cellInfo) {
        markers.searchCell = L.layerGroup().addTo(map);
        L.polygon(cellInfo.latLngs, {
            color: '#f59e0b',
            weight: 3,
            opacity: 1,
            fillColor: '#f59e0b',
            fillOpacity: 0.35,
            dashArray: null
        }).addTo(markers.searchCell);

        const rcIcon = L.divIcon({
            className: 'compartment-target-label',
            html: `<div style="background:#1e293b;color:#fff;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:700;white-space:nowrap;border:2px solid #f59e0b;box-shadow:0 2px 8px rgba(0,0,0,0.4);">${record.grid_name} · R${record.cell_row}-C${record.cell_col}</div>`,
            iconSize: [0, 0],
            iconAnchor: [0, -14]
        });
        L.marker(cellInfo.center, { icon: rcIcon, interactive: false, zIndexOffset: 1300 })
            .addTo(markers.searchCell);

        map.flyTo(cellInfo.center, 20, { duration: 1.5 });
    } else {
        map.flyTo([lat, lng], 19, { duration: 1.5 });
    }
};

// Compute the lat/lng center + corners of a record's assigned grid cell.
// Uses the same geometry as drawStandaloneGrid (2m cells, 45° rotation).
function computeCellCenter(record) {
    if (!record.grid_lat || !record.grid_lng || !record.cell_row || !record.cell_col) return null;
    const rows = parseInt(record.grid_rows);
    const cols = parseInt(record.grid_cols);
    const centerLat = parseFloat(record.grid_lat);
    const centerLng = parseFloat(record.grid_lng);
    if (!rows || !cols || isNaN(centerLat) || isNaN(centerLng)) return null;

    const cellSize = 2;
    const angleRad = 45 * Math.PI / 180;
    const metersPerLat = 111320;
    const metersPerLng = 111320 * Math.cos(centerLat * Math.PI / 180);

    // DB row_idx/col_idx are 1-based; grid drawing iterates 0-based
    const r = parseInt(record.cell_row) - 1;
    const c = parseInt(record.cell_col) - 1;

    const offsetX = (c - cols / 2 + 0.5) * cellSize;
    const offsetY = (r - rows / 2 + 0.5) * cellSize;
    const rotatedX = offsetX * Math.cos(angleRad) - offsetY * Math.sin(angleRad);
    const rotatedY = offsetX * Math.sin(angleRad) + offsetY * Math.cos(angleRad);
    const cellLat = centerLat + (rotatedY / metersPerLat);
    const cellLng = centerLng + (rotatedX / metersPerLng);

    const halfCell = cellSize / 2;
    const corners = [
        { x: -halfCell, y: -halfCell },
        { x: halfCell, y: -halfCell },
        { x: halfCell, y: halfCell },
        { x: -halfCell, y: halfCell }
    ];
    const latLngs = corners.map(pt => {
        const rx = pt.x * Math.cos(angleRad) - pt.y * Math.sin(angleRad);
        const ry = pt.x * Math.sin(angleRad) + pt.y * Math.cos(angleRad);
        return [cellLat + (ry / metersPerLat), cellLng + (rx / metersPerLng)];
    });

    return { center: [cellLat, cellLng], latLngs: latLngs };
}

// Add direction arrows along a route polyline so the user can see travel direction
function addRouteDirectionArrows(routeCoords) {
    if (routeArrows) { map.removeLayer(routeArrows); routeArrows = null; }
    if (!routeCoords || routeCoords.length < 2) return;

    const arrowLayer = L.layerGroup();
    // Place an arrow every ~3 segments, but at least every 100px-ish
    const step = Math.max(1, Math.floor(routeCoords.length / 8));

    for (let i = step; i < routeCoords.length - 1; i += step) {
        const a = routeCoords[i - 1];
        const b = routeCoords[i];
        const midLat = (a[0] + b[0]) / 2;
        const midLng = (a[1] + b[1]) / 2;
        // Bearing from a to b (in degrees, 0 = north, clockwise)
        const dLng = (b[1] - a[1]) * Math.PI / 180;
        const y = Math.sin(dLng) * Math.cos(b[0] * Math.PI / 180);
        const x = Math.cos(a[0] * Math.PI / 180) * Math.sin(b[0] * Math.PI / 180) -
                  Math.sin(a[0] * Math.PI / 180) * Math.cos(b[0] * Math.PI / 180) * Math.cos(dLng);
        let bearing = Math.atan2(y, x) * 180 / Math.PI;
        // The map itself is rotated 315°, so subtract the map bearing so arrows align with the visible path
        const mapBearing = (typeof map.getBearing === 'function') ? map.getBearing() : 0;
        bearing = bearing - mapBearing;

        const arrowIcon = L.divIcon({
            className: 'route-arrow',
            html: `<div style="transform: rotate(${bearing}deg); color:#ffffff; font-size:16px; line-height:1; text-shadow:0 1px 3px rgba(0,0,0,0.4);">&#9650;</div>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });
        L.marker([midLat, midLng], { icon: arrowIcon, interactive: false }).addTo(arrowLayer);
    }
    arrowLayer.addTo(map);
    routeArrows = arrowLayer;
}

// Show a floating info badge on the map with distance and time
function showRouteInfoBadge(lat, lng, distanceText, timeText, destinationName) {
    if (routeInfoBadge) { map.removeLayer(routeInfoBadge); routeInfoBadge = null; }
    const badgeIcon = L.divIcon({
        className: 'route-info-badge',
        html: `<div style="background:#1a73e8; color:#fff; padding:8px 14px; border-radius:24px; font-size:12px; font-weight:600; white-space:nowrap; box-shadow:0 4px 12px rgba(26,115,232,0.4); display:flex; align-items:center; gap:8px; border:2px solid #fff;">
            <svg style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
            <span>${distanceText} &middot; ${timeText}</span>
        </div>`,
        iconSize: [0, 0],
        iconAnchor: [0, 0]
    });
    routeInfoBadge = L.marker([lat, lng], { icon: badgeIcon, interactive: false, zIndexOffset: 1000 }).addTo(map);
}

// Clear the active route, navigation markers, and location watcher
window.clearRoute = function() {
    console.log('Clearing route...');

    // Remove OSRM routing control
    if (routingControl) {
        try {
            map.removeControl(routingControl);
        } catch (e) {
            console.error('Error removing routing control:', e);
        }
        routingControl = null;
    }

    // Remove fallback route line
    if (routeLine) {
        map.removeLayer(routeLine);
        routeLine = null;
    }

    // Remove route direction arrows
    if (routeArrows) {
        map.removeLayer(routeArrows);
        routeArrows = null;
    }

    // Remove floating route info badge
    if (routeInfoBadge) {
        map.removeLayer(routeInfoBadge);
        routeInfoBadge = null;
    }

    // Remove user location marker
    if (markers.userLocation) {
        map.removeLayer(markers.userLocation);
        markers.userLocation = null;
    }

    // Remove destination marker
    if (markers.destination) {
        map.removeLayer(markers.destination);
        markers.destination = null;
    }

    // Stop watching user location
    if (userLocationWatcher) {
        navigator.geolocation.clearWatch(userLocationWatcher);
        userLocationWatcher = null;
    }

    // Hide the clear-route button
    const btn = document.getElementById('clearRouteBtn');
    if (btn) btn.style.display = 'none';

    themeUtils.showAlert('Route cleared', 'info');
};

// Show the floating "Clear Route" button (creates it if needed)
function showClearRouteButton() {
    let btn = document.getElementById('clearRouteBtn');
    if (!btn) {
        btn = document.createElement('button');
        btn.id = 'clearRouteBtn';
        btn.type = 'button';
        btn.title = 'Clear route and stop navigation';
        btn.innerHTML = `
            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>Clear Route</span>
        `;
        btn.style.cssText = `
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1500;
            display: none;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #fff;
            color: #ea4335;
            border: 2px solid #ea4335;
            border-radius: 999px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(234, 67, 53, 0.25);
            transition: all 0.25s ease;
        `;
        btn.onmouseover = () => { btn.style.background = '#ea4335'; btn.style.color = '#fff'; btn.style.transform = 'translateX(-50%) translateY(-2px)'; btn.style.boxShadow = '0 8px 24px rgba(234, 67, 53, 0.4)'; };
        btn.onmouseout  = () => { btn.style.background = '#fff'; btn.style.color = '#ea4335'; btn.style.transform = 'translateX(-50%)'; btn.style.boxShadow = '0 4px 16px rgba(234, 67, 53, 0.25)'; };
        btn.onclick = () => window.clearRoute();
        document.body.appendChild(btn);
    }
    btn.style.display = 'flex';
}

// Navigation functionality with proper routing
window.navigateToLocation = function(lat, lng, destinationName = 'Destination', subLabel = '') {
    console.log('Navigate called:', lat, lng, destinationName);

    if (!navigator.geolocation) {
        themeUtils.showAlert('Geolocation is not supported by your browser', 'error');
        return;
    }

    // Show the clear-route button so the user can stop navigation without reloading
    showClearRouteButton();

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
            
            // Create user location marker — Google Maps style pulsing blue dot
            const userIcon = L.divIcon({
                className: 'user-location-marker-custom',
                html: `<div style="position: relative; width: 20px; height: 20px;">
                    <div style="position: absolute; inset: -12px; border-radius: 50%; background: rgba(66, 133, 244, 0.2); animation: routePulseRing 2s ease-out infinite;"></div>
                    <div style="position: absolute; inset: -6px; border-radius: 50%; background: rgba(66, 133, 244, 0.35); animation: routePulseRing 2s ease-out infinite 0.5s;"></div>
                    <div style="position: relative; width: 20px; height: 20px; background: #4285F4; border: 3px solid #fff; border-radius: 50%; box-shadow: 0 2px 8px rgba(66, 133, 244, 0.6);"></div>
                </div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
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
                    <div style="position: absolute; left: 50%; top: 50%; transform: translate(-50%,-50%); width: 50px; height: 50px; border-radius: 50%; background: rgba(234, 67, 53, 0.15); animation: routePulseRing 2s ease-out infinite;"></div>
                    <svg style="width: 36px; height: 36px; filter: drop-shadow(0 3px 6px rgba(234,67,53,0.5)); position: relative; z-index: 1;" viewBox="0 0 24 24" fill="#EA4335">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 36]
            });
            
            markers.destination = L.marker([lat, lng], { icon: destIcon })
                .bindPopup(`<div style="text-align:center;"><strong>${destinationName}</strong>${subLabel ? `<br><span style="font-size:0.85rem;color:#2563eb;font-weight:600;">${subLabel}</span>` : ''}</div>`)
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
                            // Dark casing/outline (Google Maps style)
                            { color: '#1a237e', opacity: 0.35, weight: 12, className: 'route-casing' },
                            // Bright blue main line
                            { color: '#4285F4', opacity: 1, weight: 7, className: 'route-main' },
                            // Light blue inner highlight
                            { color: '#a8c7fa', opacity: 0.9, weight: 3, className: 'route-inner' }
                        ],
                        extendToWaypoints: true,
                        missingRouteTolerance: 0,
                        missingRouteStyles: [
                            // Off-road segments (where no road data exists) — keep them blue
                            { color: '#1a237e', opacity: 0.35, weight: 12, className: 'route-casing' },
                            { color: '#4285F4', opacity: 1, weight: 7, dashArray: '8,8', className: 'route-main' },
                            { color: '#a8c7fa', opacity: 0.9, weight: 3, dashArray: '8,8', className: 'route-inner' }
                        ]
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
                    const routeCoords = routes[0].coordinates
                        ? routes[0].coordinates.map(c => [c.lat, c.lng])
                        : [];

                    // Calculate distance and time
                    const distanceKm = (summary.totalDistance / 1000).toFixed(2);
                    const timeMin = Math.round(summary.totalTime / 60);
                    const distanceText = parseFloat(distanceKm) >= 1 ? `${distanceKm} km` : `${Math.round(summary.totalDistance)} m`;
                    const timeText = timeMin >= 1 ? `${timeMin} min` : `${Math.round(summary.totalTime)} sec`;

                    // Show success message
                    themeUtils.showAlert(
                        `Route found! Distance: ${distanceText}, Estimated time: ${timeText}`,
                        'success'
                    );

                    // Add direction arrows along the route
                    if (routeCoords.length > 0) {
                        addRouteDirectionArrows(routeCoords);
                    }

                    // Show floating info badge near the midpoint of the route
                    if (routeCoords.length > 0) {
                        const mid = routeCoords[Math.floor(routeCoords.length / 2)];
                        showRouteInfoBadge(mid[0], mid[1], distanceText, timeText, destinationName);
                    }

                    // Update user marker popup with distance info
                    if (markers.userLocation) {
                        markers.userLocation.setPopupContent(
                            `<div style="text-align: center;">
                                <strong>Your Location</strong><br>
                                <span style="font-size: 0.85rem; color: rgba(255,255,255,0.7);">
                                    ${distanceText} to ${destinationName}
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
                            closeBtn.innerHTML = '&times;';
                            closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; background: rgba(181, 90, 90, 0.8); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 20px; line-height: 1; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; z-index: 10000;';
                            closeBtn.onmouseover = () => closeBtn.style.background = 'rgba(181, 90, 90, 1)';
                            closeBtn.onmouseout = () => closeBtn.style.background = 'rgba(181, 90, 90, 0.8)';
                            closeBtn.onclick = () => window.clearRoute();
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
                    routeLine = null;
                }

                // Draw smooth curved line as fallback (Google Maps style — layered)
                const midLat = (userLat + lat) / 2;
                const midLng = (userLng + lng) / 2;

                // Create a slight curve
                const offset = 0.001;
                const curveLat = midLat + offset;
                const curveLng = midLng + offset;

                const routeCoords = [
                    [userLat, userLng],
                    [curveLat, curveLng],
                    [lat, lng]
                ];

                // Layered polylines: casing + main + inner highlight
                const routeCasing = L.polyline(routeCoords, {
                    color: '#1a237e',
                    weight: 12,
                    opacity: 0.35,
                    smoothFactor: 3,
                    lineCap: 'round',
                    className: 'route-casing'
                });

                const routeMain = L.polyline(routeCoords, {
                    color: '#4285F4',
                    weight: 7,
                    opacity: 1,
                    smoothFactor: 3,
                    lineCap: 'round',
                    className: 'route-main'
                });

                const routeInner = L.polyline(routeCoords, {
                    color: '#a8c7fa',
                    weight: 3,
                    opacity: 0.9,
                    smoothFactor: 3,
                    lineCap: 'round',
                    className: 'route-inner'
                });

                // Group + add so clearRoute can remove all layers at once
                routeLine = L.layerGroup([routeCasing, routeMain, routeInner]).addTo(map);

                // Add direction arrows along the fallback route
                addRouteDirectionArrows(routeCoords);

                console.log('Fallback line drawn');

                // Calculate straight-line distance
                const distance = themeUtils.calculateDistance(userLat, userLng, lat, lng);
                const formattedDistance = themeUtils.formatDistance(distance);

                // Estimate walking time (~1.4 m/s)
                const timeSec = Math.round(distance / 1.4);
                const timeText = timeSec >= 60 ? `${Math.round(timeSec / 60)} min` : `${timeSec} sec`;

                // Show floating info badge near the midpoint
                showRouteInfoBadge(curveLat, curveLng, formattedDistance, timeText, name);

                themeUtils.showAlert(
                    `Showing direct path to ${name}. Distance: ${formattedDistance}, Est. time: ${timeText}`,
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

let isVoiceMessage = false;

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();

    console.log('sendMessage called. message:', message, 'isVoice:', isVoiceMessage);
    if (!message) {
        console.warn('sendMessage: empty message, returning');
        return;
    }

    // Add user message
    addChatMessage(message, 'user');
    input.value = '';

    // Show typing indicator
    const typingId = addTypingIndicator();
    
    try {
        console.log('Fetching visitor_assistant.php...');
        const response = await fetch('../api/visitor_assistant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, records: allRecords, plots: allPlots })
        });
        
        const data = await response.json();
        console.log('API response:', data);
        
        // Remove typing indicator
        document.getElementById(typingId).remove();
        
        if (data.success) {
            if (isVoiceMessage) {
                // Voice mode: show talking animation, speak the response, don't show text
                showTalkingAnimation(data.response);
            } else {
                // Text mode: show text response, don't speak
                addChatMessage(data.response, 'assistant');
            }
            
            // Check for navigation command
            if (data.navigation) {
                const destinationName = data.navigation.name || 'Destination';
                // Automatically navigate with route
                setTimeout(() => {
                    navigateToLocation(data.navigation.lat, data.navigation.lng, destinationName);
                    // Close chat to show the map and route
                    toggleChat();
                }, isVoiceMessage ? 3000 : 1500);
            }
        } else {
            // Show the actual error response from the API if available
            const errorMsg = data.response || data.error || 'Sorry, I encountered an error. Please try again.';
            console.warn('AI API error:', data);
            addChatMessage(errorMsg, 'assistant');
        }
        // Reset voice flag
        isVoiceMessage = false;
    } catch (error) {
        document.getElementById(typingId).remove();
        console.error('AI fetch error:', error);
        addChatMessage('Sorry, I could not process your request. Please check your connection.', 'assistant');
        isVoiceMessage = false;
    }
}

// Show talking animation while speaking
function showTalkingAnimation(text) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageId = 'msg-talking-' + Date.now();

    const messageDiv = document.createElement('div');
    messageDiv.id = messageId;
    messageDiv.className = 'chat-message assistant talking-active';

    const logo = document.createElement('img');
    logo.src = '../assets/images/ai-assistant-logo.svg';
    logo.alt = 'AI';
    logo.className = 'chat-avatar talking-avatar';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble talking-bubble';
    bubble.innerHTML = `
        <div class="talking-animation">
            <div class="talking-wave"><span></span><span></span><span></span><span></span><span></span></div>
            <span class="talking-label">Speaking...</span>
        </div>
    `;

    messageDiv.appendChild(logo);
    messageDiv.appendChild(bubble);
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    // Speak the text
    speakText(text, true);

    // Remove animation when speech ends
    const checkSpeechEnd = setInterval(() => {
        if (!('speechSynthesis' in window) || !speechSynthesis.speaking) {
            clearInterval(checkSpeechEnd);
            messageDiv.remove();
            // After speaking, show the text response briefly
            addChatMessage(text, 'assistant');
        }
    }, 200);

    // Fallback: if TTS not available, show text after 2s
    if (!('speechSynthesis' in window)) {
        setTimeout(() => {
            clearInterval(checkSpeechEnd);
            messageDiv.remove();
            addChatMessage(text, 'assistant');
        }, 2000);
    }
}

function addChatMessage(text, sender) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageId = 'msg-' + Date.now();

    const messageDiv = document.createElement('div');
    messageDiv.id = messageId;
    messageDiv.className = `chat-message ${sender}`;

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.textContent = text;

    if (sender === 'assistant') {
        const logo = document.createElement('img');
        logo.src = '../assets/images/ai-assistant-logo.svg';
        logo.alt = 'AI';
        logo.className = 'chat-avatar';
        messageDiv.appendChild(logo);
        messageDiv.appendChild(bubble);
    } else {
        messageDiv.appendChild(bubble);
    }

    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    return messageId;
}

// Show animated typing indicator
function addTypingIndicator() {
    const messagesContainer = document.getElementById('chatMessages');
    const messageId = 'msg-typing-' + Date.now();

    const messageDiv = document.createElement('div');
    messageDiv.id = messageId;
    messageDiv.className = 'chat-message assistant typing';

    const logo = document.createElement('img');
    logo.src = '../assets/images/ai-assistant-logo.svg';
    logo.alt = 'AI';
    logo.className = 'chat-avatar';

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.innerHTML = `
        <div class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>
    `;

    messageDiv.appendChild(logo);
    messageDiv.appendChild(bubble);
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

// Update filter counts (legacy; hidden on new visitor layout)
function updateFilterCounts() {
    const burialsEl = document.getElementById('burialsCount');
    const availableEl = document.getElementById('availableCount');
    if (burialsEl) burialsEl.textContent = allRecords.length;
    if (availableEl) availableEl.textContent = allPlots.length;
}

// Initialize map on page load
initMap();


// Toggle navigation bar (legacy, no longer used with new visitor layout)
function toggleNavBar() {
    const topBar = document.getElementById('topBar');
    if (topBar) topBar.classList.toggle('collapsed');
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
        map.setBearing(315);
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

// ============ VOICE INPUT (Speech Recognition) ============
let recognition = null;
let isListening = false;
let voiceStatusEl = null;
let finalTranscript = '';
let manualStop = false;
let restartTimer = null;
let networkRetries = 0;
const MAX_NETWORK_RETRIES = 5;

function initSpeechRecognition() {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) return null;
    const rec = new SR();
    rec.continuous = false;
    rec.interimResults = true;
    rec.lang = 'en-US';
    rec.maxAlternatives = 1;

    rec.onstart = function() {
        isListening = true;
        updateVoiceButton();
        showVoiceStatus('Listening... speak now');
        console.log('Recognition started');
    };

    rec.onresult = function(e) {
        let interim = '';
        for (let i = e.resultIndex; i < e.results.length; i++) {
            const transcript = e.results[i][0].transcript;
            if (e.results[i].isFinal) {
                finalTranscript += transcript;
            } else {
                interim += transcript;
            }
        }
        const displayText = finalTranscript + interim;
        const input = document.getElementById('chatInput');
        if (displayText && input) {
            input.value = displayText;
        }
        showVoiceStatus(finalTranscript ? 'Heard: "' + finalTranscript.trim() + '"' : 'Listening...');
    };

    rec.onend = function() {
        console.log('Recognition ended. finalTranscript:', finalTranscript, 'manualStop:', manualStop, 'networkRetries:', networkRetries);
        
        // If user didn't manually stop and we have no transcript, auto-restart
        if (!manualStop && !finalTranscript.trim() && networkRetries < MAX_NETWORK_RETRIES) {
            console.log('Auto-restarting recognition... (retry ' + (networkRetries + 1) + '/' + MAX_NETWORK_RETRIES + ')');
            restartTimer = setTimeout(() => {
                try { rec.start(); } catch(e) { console.warn('Retry failed:', e); }
            }, 1000);
            return;
        }
        
        // Normal end
        isListening = false;
        updateVoiceButton();
        const input = document.getElementById('chatInput');
        const capturedText = (input && input.value.trim()) ? input.value.trim() : finalTranscript.trim();
        if (capturedText && input) {
            input.value = capturedText;
            showVoiceStatus('Sending: "' + capturedText + '"');
            setTimeout(() => {
                console.log('Auto-send triggered. input.value:', input.value);
                if (input.value.trim()) {
                    isVoiceMessage = true;
                    sendMessage();
                    hideVoiceStatus();
                }
            }, 2000);
        } else if (networkRetries >= MAX_NETWORK_RETRIES) {
            showVoiceStatus('Voice service unavailable. Your browser cannot reach Google\'s speech servers. Check your internet connection.');
            setTimeout(hideVoiceStatus, 6000);
        } else if (manualStop) {
            hideVoiceStatus();
        }
        manualStop = false;
    };

    rec.onerror = function(e) {
        console.warn('Speech recognition error:', e.error);
        if (e.error === 'not-allowed') {
            isListening = false;
            manualStop = true;
            updateVoiceButton();
            hideVoiceStatus();
            alert('Microphone access denied. Please allow microphone permissions in your browser settings:\n\n1. Click the lock/info icon next to the URL\n2. Allow microphone access\n3. Refresh the page');
        } else if (e.error === 'no-speech') {
            // Don't stop — let onend auto-restart
            console.log('no-speech error, will auto-restart');
        } else if (e.error === 'network') {
            // Network error — retry up to MAX_NETWORK_RETRIES
            networkRetries++;
            console.warn('Network error, retry ' + networkRetries + '/' + MAX_NETWORK_RETRIES);
            if (networkRetries < MAX_NETWORK_RETRIES) {
                showVoiceStatus('Reconnecting to voice service... (' + networkRetries + '/' + MAX_NETWORK_RETRIES + ')');
                // Don't set manualStop — let onend auto-restart
            } else {
                isListening = false;
                manualStop = true;
                updateVoiceButton();
                showVoiceStatus('Voice service unavailable. Check your internet connection.');
                setTimeout(hideVoiceStatus, 4000);
            }
        } else if (e.error === 'aborted') {
            console.log('Recognition aborted');
        } else if (e.error === 'audio-capture') {
            isListening = false;
            manualStop = true;
            updateVoiceButton();
            hideVoiceStatus();
            alert('Microphone not found. Please check your microphone is connected and not in use by another app.');
        } else {
            console.warn('Other speech error:', e.error);
        }
    };

    return rec;
}

function toggleVoiceInput() {
    if (!recognition) recognition = initSpeechRecognition();
    if (!recognition) {
        alert('Voice input is not supported in your browser. Please use Chrome, Edge, or Safari.');
        return;
    }
    if (isListening) {
        // User manually stopped
        manualStop = true;
        if (restartTimer) { clearTimeout(restartTimer); restartTimer = null; }
        try { recognition.stop(); } catch(e) {}
        isListening = false;
        updateVoiceButton();
        // Use whatever is in the input (interim + final combined)
        const input = document.getElementById('chatInput');
        const capturedText = (input && input.value.trim()) ? input.value.trim() : finalTranscript.trim();
        if (capturedText && input) {
            input.value = capturedText;
            showVoiceStatus('Sending: "' + capturedText + '"');
            setTimeout(() => {
                if (input.value.trim()) {
                    isVoiceMessage = true;
                    sendMessage();
                    hideVoiceStatus();
                }
            }, 2000);
        } else {
            hideVoiceStatus();
        }
    } else {
        try {
            finalTranscript = '';
            manualStop = false;
            networkRetries = 0;
            const input = document.getElementById('chatInput');
            if (input) input.value = '';
            recognition.start();
            console.log('Voice recognition started by user');
        } catch (err) {
            console.warn('Recognition start error:', err);
            // If already running, stop first then start
            try { recognition.stop(); } catch(e) {}
            setTimeout(() => {
                try { recognition.start(); } catch(e2) { console.warn('Retry start failed:', e2); }
            }, 200);
        }
    }
}

function showVoiceStatus(text) {
    if (!voiceStatusEl) {
        voiceStatusEl = document.createElement('div');
        voiceStatusEl.style.cssText = 'position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:#0f172a; color:#fff; padding:10px 20px; border-radius:999px; font-size:0.85rem; font-weight:600; z-index:9999; display:flex; align-items:center; gap:8px; box-shadow:0 4px 20px rgba(0,0,0,0.2);';
        document.body.appendChild(voiceStatusEl);
    }
    voiceStatusEl.innerHTML = '<span style="width:8px; height:8px; border-radius:50%; background:#ef4444; animation:pulse 1s infinite;"></span>' + text;
    voiceStatusEl.style.display = 'flex';
}

function hideVoiceStatus() {
    if (voiceStatusEl) {
        voiceStatusEl.style.display = 'none';
    }
}

function updateVoiceButton() {
    const btn = document.getElementById('voiceBtn');
    const icon = document.getElementById('voiceIcon');
    if (!btn || !icon) return;
    if (isListening) {
        btn.style.color = '#ef4444';
        btn.style.background = '#fee2e2';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 016 0v6a3 3 0 01-3 3z"/>';
    } else {
        btn.style.color = '#94a3b8';
        btn.style.background = 'transparent';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 016 0v6a3 3 0 01-3 3z"/>';
    }
}

// ============ VOICE OUTPUT (Text-to-Speech) ============
let ttsEnabled = false;

function toggleTTS() {
    ttsEnabled = !ttsEnabled;
    const btn = document.getElementById('ttsToggle');
    const icon = document.getElementById('ttsIcon');
    if (!btn || !icon) return;
    if (ttsEnabled) {
        btn.style.color = '#10b981';
        btn.style.background = '#f0fdf4';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5L6 9H2v6h4l5 4V5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.54 8.46a5 5 0 010 7.07M19.07 4.93a10 10 0 010 14.14"/>';
    } else {
        btn.style.color = '#94a3b8';
        btn.style.background = 'transparent';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5L6 9H2v6h4l5 4V5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 9l-6 6M17 9l6 6"/>';
        if ('speechSynthesis' in window) speechSynthesis.cancel();
    }
}

function getBestVoice() {
    if (!('speechSynthesis' in window)) return null;
    const voices = speechSynthesis.getVoices();
    if (!voices || voices.length === 0) return null;

    // Priority list of natural-sounding voices
    const preferredNames = [
        'Google US English',
        'Microsoft Aria Online (Natural) - English (United States)',
        'Microsoft Jenny Online (Natural) - English (United States)',
        'Microsoft Guy Online (Natural) - English (United States)',
        'Microsoft Zira - English (United States)',
        'Samantha',
        'Alex',
        'Google UK English Female',
        'Google UK English Male',
    ];

    for (const name of preferredNames) {
        const found = voices.find(v => v.name === name);
        if (found) return found;
    }

    // Fallback: any en-US female voice
    const enUS = voices.find(v => v.lang === 'en-US' && v.name.toLowerCase().includes('female'));
    if (enUS) return enUS;

    // Fallback: any en-US voice
    const anyEnUS = voices.find(v => v.lang === 'en-US');
    if (anyEnUS) return anyEnUS;

    // Fallback: any English voice
    const anyEn = voices.find(v => v.lang && v.lang.startsWith('en'));
    if (anyEn) return anyEn;

    return voices[0];
}

function speakText(text, forceSpeak) {
    if (!forceSpeak && !ttsEnabled) return;
    if (!('speechSynthesis' in window)) return;
    speechSynthesis.cancel();
    const clean = text.replace(/\*\*/g, '').replace(/\*/g, '').replace(/[#`_~]/g, '').replace(/\n/g, ' ');
    const utter = new SpeechSynthesisUtterance(clean);
    const voice = getBestVoice();
    if (voice) {
        utter.voice = voice;
        utter.lang = voice.lang;
    } else {
        utter.lang = 'en-US';
    }
    utter.rate = 0.95;
    utter.pitch = 1.0;
    utter.volume = 1;
    speechSynthesis.speak(utter);
}

// Load voices when available (Chrome loads them async)
if ('speechSynthesis' in window) {
    speechSynthesis.onvoiceschanged = function() {
        console.log('TTS voices loaded:', speechSynthesis.getVoices().length, 'available');
    };
}
