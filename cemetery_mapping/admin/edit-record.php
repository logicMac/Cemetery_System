<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

$record_id = $_GET['id'] ?? 0;

// Get record details
try {
    $stmt = $pdo->prepare("SELECT * FROM burial_records WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        header('Location: records.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Get record error: " . $e->getMessage());
    header('Location: records.php');
    exit;
}

// Get barangays
try {
    $stmt = $pdo->query("SELECT DISTINCT barangay FROM burial_records WHERE barangay IS NOT NULL ORDER BY barangay");
    $barangays = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $barangays = [];
}
?>

<?php require_once 'includes/sidebar.php'; ?>

<div style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
        <a href="records.php" class="btn-secondary" style="padding: 8px 16px;">
            <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Records
        </a>
        <h2 style="margin: 0;">Edit Record: <?php echo htmlspecialchars($record['decedent_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>
</div>

<form id="editRecordForm" enctype="multipart/form-data" class="glass-card" style="padding: 30px;">
    <h3 style="margin: 0 0 20px 0;">Decedent Information</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="form-group">
            <label for="decedent_name">Full Name *</label>
            <input type="text" id="decedent_name" name="decedent_name" class="input-field" required value="<?php echo htmlspecialchars($record['decedent_name'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        
        <div class="form-group">
            <label for="family_name">Family Name</label>
            <input type="text" id="family_name" name="family_name" class="input-field" value="<?php echo htmlspecialchars($record['family_name'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="form-group">
            <label for="birth_date">Birth Date *</label>
            <input type="date" id="birth_date" name="birth_date" class="input-field" required value="<?php echo $record['birth_date']; ?>">
        </div>
        
        <div class="form-group">
            <label for="death_date">Death Date *</label>
            <input type="date" id="death_date" name="death_date" class="input-field" required value="<?php echo $record['death_date']; ?>">
        </div>
    </div>
    
    <h3 style="margin: 30px 0 20px 0;">Location Information</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="form-group">
            <label for="barangay">Barangay *</label>
            <input type="text" id="barangay" name="barangay" class="input-field" list="barangayList" required value="<?php echo htmlspecialchars($record['barangay'], ENT_QUOTES, 'UTF-8'); ?>">
            <datalist id="barangayList">
                <?php foreach ($barangays as $barangay): ?>
                    <option value="<?php echo htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        
        <div class="form-group">
            <label for="plot_number">Plot Number</label>
            <input type="text" id="plot_number" name="plot_number" class="input-field" value="<?php echo htmlspecialchars($record['plot_number'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="form-group">
            <label for="latitude">Latitude *</label>
            <input type="number" id="latitude" name="latitude" class="input-field" step="0.00000001" required value="<?php echo $record['latitude']; ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="longitude">Longitude *</label>
            <input type="number" id="longitude" name="longitude" class="input-field" step="0.00000001" required value="<?php echo $record['longitude']; ?>" readonly>
        </div>
    </div>
    
    <div style="margin-bottom: 20px;">
        <button type="button" onclick="showMapModal()" class="btn-secondary">
            <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
            </svg>
            Update Location on Map
        </button>
    </div>
    
    <h3 style="margin: 30px 0 20px 0;">Additional Information</h3>
    
    <div class="form-group" style="margin-bottom: 20px;">
        <label>
            <input type="checkbox" id="is_fenced" name="is_fenced" value="1" <?php echo $record['is_fenced'] ? 'checked' : ''; ?>>
            Premium / Fenced Plot
        </label>
    </div>
    
    <div class="form-group" style="margin-bottom: 20px;">
        <label for="memory_space">Memory / Message</label>
        <textarea id="memory_space" name="memory_space" class="input-field" rows="4" placeholder="Share a memory or message..."><?php echo htmlspecialchars($record['memory_space'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>
    
    <div class="form-group" style="margin-bottom: 20px;">
        <label for="photo">Update Photo (optional)</label>
        <?php if ($record['photo']): ?>
            <div style="margin-bottom: 10px;">
                <img src="../uploads/photos/<?php echo htmlspecialchars($record['photo'], ENT_QUOTES, 'UTF-8'); ?>" style="max-width: 200px; max-height: 200px; border-radius: 8px; object-fit: cover;">
                <p style="color: var(--zinc-400); font-size: 0.85rem; margin: 5px 0 0 0;">Current photo (leave empty to keep)</p>
            </div>
        <?php endif; ?>
        <input type="file" id="photo" name="photo" class="input-field" accept="image/jpeg,image/png,image/jpg">
    </div>
    
    <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 20px; border-top: 1px solid var(--glass-border);">
        <a href="records.php" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">Save Changes</button>
    </div>
</form>

        </main>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        let modalMap = null;
        let modalMarker = null;
        
        function showMapModal() {
            const currentLat = parseFloat(document.getElementById('latitude').value);
            const currentLng = parseFloat(document.getElementById('longitude').value);
            
            const content = `
                <div id="updateLocationMap" style="height: 400px; border-radius: 8px; margin-bottom: 20px;"></div>
                <p style="color: var(--zinc-400); text-align: center; margin-bottom: 15px;">Click on the map to update the burial location</p>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="themeUtils.closeModal()">Cancel</button>
                    <button type="button" class="btn-primary" onclick="updateLocation()">Update Location</button>
                </div>
            `;
            
            themeUtils.showModal(content, 'Update Location');
            
            setTimeout(() => {
                modalMap = adminUtils.initAdminMap('updateLocationMap');
                modalMap.setView([currentLat, currentLng], 18);
                
                modalMarker = L.marker([currentLat, currentLng], {
                    draggable: true,
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background: #ef4444; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white;"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(modalMap);
                
                modalMap.on('click', function(e) {
                    modalMarker.setLatLng(e.latlng);
                });
                
                modalMarker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                });
            }, 100);
        }
        
        function updateLocation() {
            if (modalMarker) {
                const pos = modalMarker.getLatLng();
                document.getElementById('latitude').value = pos.lat;
                document.getElementById('longitude').value = pos.lng;
                themeUtils.closeModal();
                themeUtils.showAlert('Location updated', 'success');
            }
        }
        
        document.getElementById('editRecordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('id', <?php echo $record_id; ?>);
            
            try {
                const response = await fetch('../api/update_record.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    themeUtils.showAlert('Record updated successfully!', 'success');
                    setTimeout(() => window.location.href = 'records.php', 1500);
                } else {
                    themeUtils.showAlert(data.error || 'Failed to update record', 'error');
                }
            } catch (error) {
                themeUtils.showAlert('An error occurred', 'error');
            }
        });
    </script>
</body>
</html>
