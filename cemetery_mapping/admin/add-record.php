<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Get barangays for dropdown
$barangays = ['Matinao', 'Poblacion', 'San Isidro', 'San Jose', 'San Miguel', 'San Pedro', 'San Roque', 'Santa Cruz'];
?>

<?php require_once 'includes/sidebar.php'; ?>

<div class="glass-card">
    <h2 style="margin-bottom: 24px;">Add New Burial Record</h2>
    
    <form id="addRecordForm" enctype="multipart/form-data">
        <div class="form-grid">
            <div>
                <label for="decedent_name">Decedent Name *</label>
                <input type="text" id="decedent_name" name="decedent_name" class="input-field" required>
            </div>
            
            <div>
                <label for="family_name">Family Name</label>
                <input type="text" id="family_name" name="family_name" class="input-field">
            </div>
            
            <div>
                <label for="birth_date">Birth Date</label>
                <input type="date" id="birth_date" name="birth_date" class="input-field">
            </div>
            
            <div>
                <label for="death_date">Death Date</label>
                <input type="date" id="death_date" name="death_date" class="input-field">
            </div>
            
            <div>
                <label for="plot_number">Plot Number</label>
                <input type="text" id="plot_number" name="plot_number" class="input-field">
            </div>
            
            <div>
                <label for="barangay">Barangay</label>
                <select id="barangay" name="barangay" class="input-field">
                    <option value="">Select Barangay</option>
                    <?php foreach ($barangays as $barangay): ?>
                        <option value="<?php echo $barangay; ?>"><?php echo $barangay; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label>
                    <input type="checkbox" id="is_fenced" name="is_fenced" value="1" style="margin-right: 8px;">
                    Premium/Fenced Plot
                </label>
            </div>
            
            <div class="form-grid-full">
                <label for="memory_space">Memory/Biography</label>
                <textarea id="memory_space" name="memory_space" class="input-field" rows="4" placeholder="Share memories or biographical information..."></textarea>
            </div>
            
            <div class="form-grid-full">
                <label for="photo">Photo</label>
                <div class="file-upload" onclick="document.getElementById('photo').click()">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 12px; color: var(--zinc-400);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p style="color: var(--zinc-400);">Click to upload photo (max 5MB)</p>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg" style="display: none;" onchange="previewPhoto(this)">
                </div>
                <div id="photoPreview" class="file-preview" style="display: none;"></div>
            </div>
        </div>
        
        <h3 style="margin: 30px 0 20px 0;">Location Coordinates</h3>
        <p style="color: var(--zinc-400); margin-bottom: 20px;">Click on the map to set the burial location</p>
        
        <div class="form-grid">
            <div>
                <label for="latitude">Latitude *</label>
                <input type="number" id="latitude" name="latitude" class="input-field" step="0.00000001" required readonly>
            </div>
            
            <div>
                <label for="longitude">Longitude *</label>
                <input type="number" id="longitude" name="longitude" class="input-field" step="0.00000001" required readonly>
            </div>
        </div>
        
        <div id="mapPicker" class="map-picker"></div>
        
        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn-primary">
                <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save Record
            </button>
            <a href="records.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Initialize map picker
        const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
        const CEMETERY_BOUNDS = [
            [6.18244118743717, 125.08357146469357],
            [6.18444118743717, 125.08557146469357]
        ];
        
        const mapPicker = L.map('mapPicker').setView(CEMETERY_CENTER, 17);
        
        L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        }).addTo(mapPicker);
        
        // Draw cemetery boundary
        L.rectangle(CEMETERY_BOUNDS, {
            color: '#ef4444',
            weight: 2,
            fillOpacity: 0,
            dashArray: '5, 10'
        }).addTo(mapPicker);
        
        let marker = null;
        
        // Click to set location
        mapPicker.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background: #667eea; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 8px rgba(102, 126, 234, 0.5);"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(mapPicker);
            }
        });
        
        // Photo preview
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Form submission
        document.getElementById('addRecordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                themeUtils.showLoading(document.querySelector('.glass-card'));
                
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
    </script>
</body>
</html>
