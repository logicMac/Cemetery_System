<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Get all available plots
try {
    $stmt = $pdo->query("
        SELECT id, plot_number, latitude, longitude, notes, photo, 
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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="margin: 0;">Available Plots Management</h2>
    <button onclick="showAddPlotModal()" class="btn-primary">
        <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Add Available Plot
    </button>
</div>

<!-- Plots Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
    <?php if (empty($plots)): ?>
        <div class="glass-card" style="grid-column: 1 / -1; text-align: center; padding: 60px;">
            <svg style="width: 80px; height: 80px; margin: 0 auto 20px; color: var(--zinc-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <h3 style="color: var(--zinc-400);">No Available Plots</h3>
            <p style="color: var(--zinc-400); margin-top: 10px;">Click "Add Available Plot" to create your first plot</p>
        </div>
    <?php else: ?>
        <?php foreach ($plots as $plot): ?>
            <div class="glass-card" style="position: relative;">
                <?php if ($plot['photo']): ?>
                    <img src="../uploads/plots/<?php echo htmlspecialchars($plot['photo'], ENT_QUOTES, 'UTF-8'); ?>" 
                         style="width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 16px;">
                <?php else: ?>
                    <div style="width: 100%; height: 200px; background: var(--glass-bg); border-radius: 12px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 60px; height: 60px; color: var(--zinc-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <div style="position: absolute; top: 16px; right: 16px;">
                    <span class="badge-available">Available</span>
                </div>
                
                <h3 style="margin-bottom: 12px;">
                    <?php echo htmlspecialchars($plot['plot_number'] ?: 'Plot #' . $plot['id'], ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                
                <div style="margin-bottom: 16px;">
                    <p style="margin: 4px 0; font-size: 0.9rem; color: var(--zinc-400);">
                        <strong>Coordinates:</strong> <?php echo $plot['latitude']; ?>, <?php echo $plot['longitude']; ?>
                    </p>
                    <?php if ($plot['has_grid']): ?>
                        <p style="margin: 4px 0; font-size: 0.9rem; color: var(--zinc-400);">
                            <strong>Grid:</strong> <?php echo $plot['grid_rows']; ?> × <?php echo $plot['grid_cols']; ?> 
                            (<?php echo $plot['compartment_count']; ?> compartments)
                        </p>
                    <?php endif; ?>
                    <?php if ($plot['notes']): ?>
                        <p style="margin: 8px 0; font-size: 0.9rem; font-style: italic; color: var(--zinc-400);">
                            <?php echo htmlspecialchars($plot['notes'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    <?php endif; ?>
                    <p style="margin: 4px 0; font-size: 0.85rem; color: var(--zinc-400);">
                        Added: <?php echo date('M d, Y', strtotime($plot['date_added'])); ?>
                    </p>
                </div>
                
                <div class="action-buttons">
                    <button class="btn-icon btn-view" onclick="viewPlotOnMap(<?php echo $plot['latitude']; ?>, <?php echo $plot['longitude']; ?>)" title="View on Map">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                    </button>
                    <?php if ($plot['has_grid']): ?>
                        <button class="btn-icon btn-view" onclick="viewGrid(<?php echo $plot['id']; ?>)" title="View Grid">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"></path>
                            </svg>
                        </button>
                    <?php endif; ?>
                    <button class="btn-icon btn-edit" onclick="editPlot(<?php echo $plot['id']; ?>)" title="Edit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button class="btn-icon btn-delete" onclick="deletePlot(<?php echo $plot['id']; ?>, '<?php echo htmlspecialchars($plot['plot_number'] ?: 'Plot #' . $plot['id'], ENT_QUOTES, 'UTF-8'); ?>')" title="Delete">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        function showAddPlotModal() {
            const content = `
                <form id="addPlotForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="plot_number">Plot Number</label>
                        <input type="text" id="plot_number" name="plot_number" class="input-field" placeholder="e.g., A-101">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes/Description</label>
                        <textarea id="notes" name="notes" class="input-field" rows="3" placeholder="Additional information..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="plot_photo">Photo</label>
                        <input type="file" id="plot_photo" name="photo" class="input-field" accept="image/jpeg,image/png,image/jpg">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="has_grid" name="has_grid" onchange="toggleGridOptions()">
                            Enable Grid (Multiple Compartments)
                        </label>
                    </div>
                    
                    <div id="gridOptions" style="display: none;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="grid_rows">Rows (1-10)</label>
                                <input type="number" id="grid_rows" name="grid_rows" class="input-field" min="1" max="10" value="2">
                            </div>
                            <div class="form-group">
                                <label for="grid_cols">Columns (1-10)</label>
                                <input type="number" id="grid_cols" name="grid_cols" class="input-field" min="1" max="10" value="2">
                            </div>
                        </div>
                    </div>
                    
                    <h4 style="margin: 20px 0 10px 0;">Location (Click on map)</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="latitude">Latitude *</label>
                            <input type="number" id="latitude" name="latitude" class="input-field" step="0.00000001" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude *</label>
                            <input type="number" id="longitude" name="longitude" class="input-field" step="0.00000001" required readonly>
                        </div>
                    </div>
                    
                    <div id="modalMap" style="height: 300px; border-radius: 8px; margin-bottom: 20px;"></div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn-secondary" onclick="themeUtils.closeModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Save Plot</button>
                    </div>
                </form>
            `;
            
            themeUtils.showModal(content, 'Add Available Plot');
            
            // Initialize map
            setTimeout(() => {
                const map = adminUtils.initAdminMap('modalMap');
                let marker = null;
                
                map.on('click', function(e) {
                    document.getElementById('latitude').value = e.latlng.lat;
                    document.getElementById('longitude').value = e.latlng.lng;
                    
                    if (marker) {
                        marker.setLatLng(e.latlng);
                    } else {
                        marker = L.marker(e.latlng, {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: '<div style="background: #22c55e; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white;"></div>',
                                iconSize: [16, 16],
                                iconAnchor: [8, 8]
                            })
                        }).addTo(map);
                    }
                });
            }, 100);
            
            // Form submission
            document.getElementById('addPlotForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                try {
                    const response = await fetch('../api/add_available_plot.php', {
                        method: 'POST',
                        body: formData
                    });
                    
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
            themeUtils.showAlert('Edit functionality coming soon', 'info');
        }
        
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
    </script>
</body>
</html>
