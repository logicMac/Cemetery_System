<?php
/**
 * Compartment Records Manager
 * Manage multiple burial records within compartments
 */

session_start();
require_once 'includes/header.php';
require_once '../config/database.php';
?>

<div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'includes/topbar.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Header -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Compartment Records Manager</h2>
                        <p class="text-sm text-slate-500">Manage multiple burial records within compartments — family plots & multi-level burials</p>
                    </div>
                </div>

                <!-- Plot Selection -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Select Plot with Compartments</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Plot</label>
                            <select id="plotSelect" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                                <option value="">Choose a plot...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Compartment</label>
                            <select id="compartmentSelect" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition disabled:bg-slate-50 disabled:text-slate-400" disabled>
                                <option value="">Select a plot first...</option>
                            </select>
                        </div>
                    </div>
                    <div id="compartmentInfo" class="mt-5 hidden">
                        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-slate-500">Capacity:</span>
                                    <span id="infoCapacity" class="ml-2 font-semibold text-slate-900">-</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Occupied:</span>
                                    <span id="infoOccupied" class="ml-2 font-semibold text-slate-900">-</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Available:</span>
                                    <span id="infoAvailable" class="ml-2 font-semibold text-emerald-600">-</span>
                                </div>
                                <div>
                                    <button id="btnUpdateCapacity" class="text-sky-600 hover:text-sky-700 font-medium">
                                        <i data-lucide="edit-2" class="inline w-4 h-4 mr-1"></i>
                                        Change Capacity
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compartment Map -->
                <div id="plotMapCard" class="hidden bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="map" class="w-4 h-4 text-emerald-600"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Compartment Map — click a compartment to select it</h3>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1.5"><span style="width:10px;height:10px;background:#10b981;border-radius:2px;display:inline-block;opacity:0.6;"></span> Available</span>
                            <span class="inline-flex items-center gap-1.5"><span style="width:10px;height:10px;background:#ef4444;border-radius:2px;display:inline-block;opacity:0.7;"></span> Has records</span>
                            <span class="inline-flex items-center gap-1.5"><span style="width:10px;height:10px;background:transparent;border:2px solid #3b82f6;border-radius:2px;display:inline-block;"></span> Selected</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id="plotMap" class="rounded-xl overflow-hidden border border-slate-200" style="height:420px;"></div>
                    </div>
                </div>

                <!-- Records in Compartment -->
                <div id="recordsSection" class="hidden">
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-emerald-600"></i>
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Records in Compartment</h3>
                            </div>
                            <button id="btnAddRecord" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Add Record
                            </button>
                        </div>
                        <div class="p-6">
                            <div id="recordsList" class="space-y-3">
                                <!-- Records will be loaded here -->
                            </div>
                            <div id="emptyState" class="text-center py-12 hidden">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8"></i>
                                </div>
                                <p class="text-sm text-slate-500">No records in this compartment yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Record Modal -->
<div id="addRecordModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-slate-900">Add Record to Compartment</h3>
            <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="addRecordForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Search Burial Record
                    </label>
                    <input type="text" id="recordSearch" placeholder="Search by name, plot number..." 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-white text-slate-900">
                    <div id="searchResults" class="mt-2 max-h-64 overflow-y-auto border border-slate-200 rounded-lg hidden"></div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Burial Level
                    </label>
                    <select id="burialLevel" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-white text-slate-900">
                        <option value="ground">Ground Level</option>
                        <option value="first">First Level</option>
                        <option value="second">Second Level</option>
                        <option value="third">Third Level</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Position Notes (optional)
                    </label>
                    <input type="text" id="positionNotes" placeholder="e.g., North side, near entrance..." 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-white text-slate-900">
                </div>
                <input type="hidden" id="selectedRecordId">
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeAddModal()" 
                        class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                        Add to Compartment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Capacity Modal -->
<div id="capacityModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-md w-full">
        <div class="p-6 border-b border-slate-200">
            <h3 class="text-xl font-semibold text-slate-900">Update Compartment Capacity</h3>
        </div>
        <div class="p-6">
            <form id="capacityForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        New Capacity
                    </label>
                    <input type="number" id="newCapacity" min="1" 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-white text-slate-900">
                    <p class="mt-1 text-sm text-slate-500">
                        Must be at least equal to current occupied count
                    </p>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeCapacityModal()" 
                        class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg font-medium">
                        Update Capacity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate.js"></script>
<script src="../assets/js/admin.js"></script>
<script>
let currentCompartmentId = null;
let searchTimeout = null;
let plotMap = null;
let plotMapLayers = [];
let allPlots = [];

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

// Draw ALL grid plots' compartments on the map; click a cell to select plot + compartment
function drawAllCompartments() {
    const card = document.getElementById('plotMapCard');
    const drawable = allPlots.filter(p => p.latitude && p.longitude);
    if (!drawable.length) {
        card.classList.add('hidden');
        return;
    }
    card.classList.remove('hidden');

    if (!plotMap) {
        plotMap = adminUtils.initAdminMap('plotMap', { bearing: 315, scrollWheelZoom: false });
    }
    const bounds = L.latLngBounds(drawable.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]));
    plotMap.fitBounds(bounds.pad(0.3));
    plotMapLayers.forEach(l => plotMap.removeLayer(l));
    plotMapLayers = [];
    drawable.forEach(plot => drawPlotCompartments(plot));
    setTimeout(() => plotMap.invalidateSize(), 200);
}

function drawPlotCompartments(plot) {
    const centerLat = parseFloat(plot.latitude);
    const centerLng = parseFloat(plot.longitude);

    const rows = parseInt(plot.grid_rows) || 1;
    const cols = parseInt(plot.grid_cols) || 1;
    const cellSize = 2; // 2 meters per cell (same as available-plots/visitor rendering)
    const angleRad = 45 * Math.PI / 180;
    const metersPerLat = 111320;
    const metersPerLng = 111320 * Math.cos(centerLat * Math.PI / 180);

    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const offsetX = (col - cols / 2 + 0.5) * cellSize;
            const offsetY = (row - rows / 2 + 0.5) * cellSize;
            const rotatedX = offsetX * Math.cos(angleRad) - offsetY * Math.sin(angleRad);
            const rotatedY = offsetX * Math.sin(angleRad) + offsetY * Math.cos(angleRad);
            const cellLat = centerLat + (rotatedY / metersPerLat);
            const cellLng = centerLng + (rotatedX / metersPerLng);

            const halfCell = cellSize / 2;
            const latLngs = [
                {x: -halfCell, y: -halfCell},
                {x: halfCell, y: -halfCell},
                {x: halfCell, y: halfCell},
                {x: -halfCell, y: halfCell}
            ].map(c => {
                const rx = c.x * Math.cos(angleRad) - c.y * Math.sin(angleRad);
                const ry = c.x * Math.sin(angleRad) + c.y * Math.cos(angleRad);
                return [cellLat + (ry / metersPerLat), cellLng + (rx / metersPerLng)];
            });

            // Match the DB compartment for this cell (row_index/col_index are 0-based)
            const comp = (plot.compartments || []).find(c =>
                parseInt(c.row_index) === row && parseInt(c.col_index) === col
            ) || (plot.compartments || [])[row * cols + col] || null;

            const occupied = comp && (parseInt(comp.is_occupied) === 1 || parseInt(comp.occupied_count) > 0);
            const selected = comp && currentCompartmentId && String(comp.id) === String(currentCompartmentId);
            const label = comp ? (comp.compartment_number || (String.fromCharCode(65 + row) + (col + 1))) : (String.fromCharCode(65 + row) + (col + 1));

            const style = selected ? {
                color: '#3b82f6', weight: 3, opacity: 1,
                fillColor: occupied ? '#ef4444' : '#10b981', fillOpacity: 0.45
            } : occupied ? {
                color: '#ef4444', weight: 2, opacity: 1,
                fillColor: '#ef4444', fillOpacity: 0.5
            } : {
                color: '#10b981', weight: 1.5, opacity: 0.9,
                fillColor: '#10b981', fillOpacity: 0.2
            };

            const cell = L.polygon(latLngs, style).addTo(plotMap);
            const plotName = plot.plot_number || 'Plot #' + plot.id;
            cell.bindPopup(
                `<strong>${escapeHtml(plotName)}</strong><br>` +
                `<span style="font-size:12px;">Compartment ${label}</span><br>` +
                `<span style="font-size:12px;color:${occupied ? '#991b1b' : '#047857'};">` +
                (comp ? `${comp.occupied_count || 0}/${comp.capacity || 1} occupied — click to manage` : 'No compartment record') +
                `</span>`
            );
            if (comp) {
                cell.on('click', function() {
                    const plotSel = document.getElementById('plotSelect');
                    if (String(plotSel.value) !== String(plot.id)) {
                        plotSel.value = plot.id;
                        plotSel.dispatchEvent(new Event('change'));
                    }
                    const sel = document.getElementById('compartmentSelect');
                    sel.value = comp.id;
                    sel.dispatchEvent(new Event('change'));
                });
            }
            plotMapLayers.push(cell);

            const labelMarker = L.marker([cellLat, cellLng], {
                icon: L.divIcon({
                    className: 'grid-cell-label',
                    html: `<div style="font-size:9px;font-weight:700;color:${occupied ? '#991b1b' : '#047857'};background:rgba(255,255,255,0.85);padding:0 3px;border-radius:2px;white-space:nowrap;">${label}</div>`,
                    iconSize: [30, 12],
                    iconAnchor: [15, 6]
                }),
                interactive: false
            }).addTo(plotMap);
            plotMapLayers.push(labelMarker);
        }
    }
    setTimeout(() => plotMap.invalidateSize(), 200);
}

// Load plots with compartments
async function loadPlots() {
    try {
        const response = await fetch('../api/get_available_plots.php');
        const data = await response.json();
        
        if (data.success) {
            allPlots = data.plots.filter(p => p.has_grid && p.total_compartments > 0);
            const plotSelect = document.getElementById('plotSelect');
            plotSelect.innerHTML = '<option value="">Choose a plot...</option>';
            
            data.plots.forEach(plot => {
                if (plot.has_grid && plot.total_compartments > 0) {
                    const option = document.createElement('option');
                    option.value = plot.id;
                    option.textContent = `${plot.plot_number || 'Plot #' + plot.id} (${plot.total_compartments} compartments)`;
                    option.dataset.plot = JSON.stringify(plot);
                    plotSelect.appendChild(option);
                }
            });

            drawAllCompartments();
        }
    } catch (error) {
        console.error('Error loading plots:', error);
    }
}

// Load compartments for selected plot
document.getElementById('plotSelect').addEventListener('change', async function() {
    const compartmentSelect = document.getElementById('compartmentSelect');
    const selectedOption = this.options[this.selectedIndex];
    
    if (!this.value) {
        compartmentSelect.disabled = true;
        compartmentSelect.innerHTML = '<option value="">Select a plot first...</option>';
        document.getElementById('compartmentInfo').classList.add('hidden');
        document.getElementById('recordsSection').classList.add('hidden');
        return;
    }
    
    const plot = JSON.parse(selectedOption.dataset.plot);
    currentCompartmentId = null;
    compartmentSelect.disabled = false;
    compartmentSelect.innerHTML = '<option value="">Choose a compartment...</option>';
    
    if (plot.compartments) {
        plot.compartments.forEach(comp => {
            const option = document.createElement('option');
            option.value = comp.id;
            option.textContent = `${comp.compartment_number} - ${comp.occupied_count || 0}/${comp.capacity || 1} occupied`;
            option.dataset.compartment = JSON.stringify(comp);
            compartmentSelect.appendChild(option);
        });
    }

    drawAllCompartments();
});

// Load records when compartment is selected
document.getElementById('compartmentSelect').addEventListener('change', async function() {
    if (!this.value) {
        document.getElementById('compartmentInfo').classList.add('hidden');
        document.getElementById('recordsSection').classList.add('hidden');
        return;
    }
    
    const selectedOption = this.options[this.selectedIndex];
    const compartment = JSON.parse(selectedOption.dataset.compartment);
    currentCompartmentId = this.value;
    
    // Show compartment info
    document.getElementById('infoCapacity').textContent = compartment.capacity || 1;
    document.getElementById('infoOccupied').textContent = compartment.occupied_count || 0;
    document.getElementById('infoAvailable').textContent = (compartment.capacity || 1) - (compartment.occupied_count || 0);
    document.getElementById('compartmentInfo').classList.remove('hidden');
    document.getElementById('recordsSection').classList.remove('hidden');
    
    // Re-highlight the selected compartment on the map
    drawAllCompartments();
    
    // Load records
    await loadRecords();
});

// Load records in compartment
async function loadRecords() {
    try {
        const response = await fetch(`../api/compartment_records.php?action=list&compartment_id=${currentCompartmentId}`);
        const data = await response.json();
        
        const recordsList = document.getElementById('recordsList');
        const emptyState = document.getElementById('emptyState');
        
        if (data.success && data.data.records.length > 0) {
            recordsList.innerHTML = data.data.records.map(record => `
                <div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50/50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-slate-900">${record.decedent_name}</h3>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-sm text-slate-500">
                                <div><span class="font-medium">Birth:</span> ${record.birth_date || 'N/A'}</div>
                                <div><span class="font-medium">Death:</span> ${record.death_date || 'N/A'}</div>
                                <div><span class="font-medium">Level:</span> ${record.burial_level}</div>
                                <div><span class="font-medium">Family:</span> ${record.family_name || 'N/A'}</div>
                            </div>
                            ${record.position_notes ? `<p class="mt-2 text-sm text-slate-500">${record.position_notes}</p>` : ''}
                        </div>
                        <button onclick="removeRecord(${record.id})" 
                            class="ml-4 text-rose-500 hover:text-rose-600">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            recordsList.classList.remove('hidden');
            emptyState.classList.add('hidden');
            lucide.createIcons();
        } else {
            recordsList.classList.add('hidden');
            emptyState.classList.remove('hidden');
            lucide.createIcons();
        }
    } catch (error) {
        console.error('Error loading records:', error);
    }
}

// Add record modal
document.getElementById('btnAddRecord').addEventListener('click', () => {
    document.getElementById('addRecordModal').classList.remove('hidden');
});

function closeAddModal() {
    document.getElementById('addRecordModal').classList.add('hidden');
    document.getElementById('addRecordForm').reset();
    document.getElementById('searchResults').classList.add('hidden');
    document.getElementById('selectedRecordId').value = '';
}

// Search records
document.getElementById('recordSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length < 2) {
        document.getElementById('searchResults').classList.add('hidden');
        return;
    }
    
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`../api/search.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            const resultsDiv = document.getElementById('searchResults');
            if (data.success && data.results.length > 0) {
                resultsDiv.innerHTML = data.results.map(record => `
                    <div onclick="selectRecord(${record.id}, '${record.decedent_name.replace(/'/g, "\\'")}')  " 
                        class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-200 last:border-0">
                        <div class="font-medium text-slate-900">${record.decedent_name}</div>
                        <div class="text-sm text-slate-500">
                            ${record.plot_number || 'No plot'} | ${record.death_date || 'No date'}
                        </div>
                    </div>
                `).join('');
                resultsDiv.classList.remove('hidden');
            } else {
                resultsDiv.innerHTML = '<div class="p-3 text-slate-500">No records found</div>';
                resultsDiv.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error searching records:', error);
        }
    }, 300);
});

function selectRecord(id, name) {
    document.getElementById('selectedRecordId').value = id;
    document.getElementById('recordSearch').value = name;
    document.getElementById('searchResults').classList.add('hidden');
}

// Add record form submit
document.getElementById('addRecordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const recordId = document.getElementById('selectedRecordId').value;
    if (!recordId) {
        alert('Please select a burial record');
        return;
    }
    
    const formData = {
        action: 'add',
        compartment_id: currentCompartmentId,
        record_id: recordId,
        burial_level: document.getElementById('burialLevel').value,
        position_notes: document.getElementById('positionNotes').value
    };
    
    try {
        const response = await fetch('../api/compartment_records.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const data = await response.json();
        
        if (data.success) {
            closeAddModal();
            await loadRecords();
            // Refresh compartment info
            document.getElementById('compartmentSelect').dispatchEvent(new Event('change'));
            alert('Record added successfully');
        } else {
            alert(data.message || 'Failed to add record');
        }
    } catch (error) {
        console.error('Error adding record:', error);
        alert('Error adding record');
    }
});

// Remove record
async function removeRecord(id) {
    if (!confirm('Remove this record from the compartment?')) return;
    
    try {
        const response = await fetch('../api/compartment_records.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', id })
        });
        const data = await response.json();
        
        if (data.success) {
            await loadRecords();
            // Refresh compartment info
            document.getElementById('compartmentSelect').dispatchEvent(new Event('change'));
            alert('Record removed successfully');
        } else {
            alert(data.message || 'Failed to remove record');
        }
    } catch (error) {
        console.error('Error removing record:', error);
        alert('Error removing record');
    }
}

// Update capacity modal
document.getElementById('btnUpdateCapacity').addEventListener('click', () => {
    const currentCapacity = parseInt(document.getElementById('infoCapacity').textContent);
    document.getElementById('newCapacity').value = currentCapacity;
    document.getElementById('capacityModal').classList.remove('hidden');
});

function closeCapacityModal() {
    document.getElementById('capacityModal').classList.add('hidden');
}

document.getElementById('capacityForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const newCapacity = parseInt(document.getElementById('newCapacity').value);
    
    try {
        const response = await fetch('../api/compartment_records.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'update_capacity', 
                compartment_id: currentCompartmentId,
                capacity: newCapacity
            })
        });
        const data = await response.json();
        
        if (data.success) {
            closeCapacityModal();
            // Refresh compartment selection
            document.getElementById('plotSelect').dispatchEvent(new Event('change'));
            alert('Capacity updated successfully');
        } else {
            alert(data.message || 'Failed to update capacity');
        }
    } catch (error) {
        console.error('Error updating capacity:', error);
        alert('Error updating capacity');
    }
});

// Initialize
loadPlots();
lucide.createIcons();
</script>

</body>
</html>
