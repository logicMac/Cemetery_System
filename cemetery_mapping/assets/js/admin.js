/**
 * Admin Panel JavaScript
 * Common utilities and functions for admin interface
 */

// Initialize admin features on page load
document.addEventListener('DOMContentLoaded', function() {
    initSidebarToggle();
    initFormValidation();
    initDataTables();
});

// Sidebar toggle for mobile
function initSidebarToggle() {
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    
    if (!sidebar || !main) return;
    
    // Create toggle button for mobile
    if (window.innerWidth <= 1024) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn-secondary';
        toggleBtn.style.cssText = 'position: fixed; top: 20px; left: 20px; z-index: 1001;';
        toggleBtn.innerHTML = `
            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        `;
        
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        
        document.body.appendChild(toggleBtn);
    }
}

// Form validation
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                themeUtils.showAlert('Please fill in all required fields correctly', 'error');
            }
        });
    });
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            field.style.borderColor = '';
        }
    });
    
    return isValid;
}

// Data table enhancements
function initDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Add sorting capability
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            if (header.textContent.trim() !== 'Actions' && header.textContent.trim() !== 'Photo') {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => sortTable(table, index));
            }
        });
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const sortedRows = rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Try numeric comparison first
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }
        
        // Fall back to string comparison
        return aValue.localeCompare(bValue);
    });
    
    // Clear and re-append sorted rows
    tbody.innerHTML = '';
    sortedRows.forEach(row => tbody.appendChild(row));
}

// File upload preview
function setupFilePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (!input || !preview) return;
    
    input.addEventListener('change', function() {
        const file = this.files[0];
        
        if (file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                themeUtils.showAlert('File size must be less than 5MB', 'error');
                this.value = '';
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!validTypes.includes(file.type)) {
                themeUtils.showAlert('Only JPEG and PNG images are allowed', 'error');
                this.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
}

// Batch operations
function selectAllRecords(checkbox) {
    const checkboxes = document.querySelectorAll('.record-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBatchActions();
}

function updateBatchActions() {
    const selected = document.querySelectorAll('.record-checkbox:checked');
    const batchActions = document.getElementById('batch-actions');
    
    if (batchActions) {
        batchActions.style.display = selected.length > 0 ? 'block' : 'none';
    }
}

async function batchDelete() {
    const selected = Array.from(document.querySelectorAll('.record-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selected.length === 0) {
        themeUtils.showAlert('No records selected', 'info');
        return;
    }
    
    themeUtils.confirm(
        `Are you sure you want to delete ${selected.length} record(s)?`,
        async () => {
            try {
                const response = await fetch('../api/batch_delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: selected })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    themeUtils.showAlert(`${data.deleted} record(s) deleted successfully`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    themeUtils.showAlert(data.error || 'Failed to delete records', 'error');
                }
            } catch (error) {
                themeUtils.showAlert('An error occurred', 'error');
            }
        }
    );
}

// Export functions
async function exportData(format, endpoint) {
    try {
        themeUtils.showAlert('Preparing export...', 'info');
        
        const response = await fetch(`${endpoint}?format=${format}`);
        const blob = await response.blob();
        
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `cemetery_data_${Date.now()}.${format}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        themeUtils.showAlert('Export completed', 'success');
    } catch (error) {
        themeUtils.showAlert('Export failed', 'error');
    }
}

// Map utilities for admin
function initAdminMap(containerId, options = {}) {
    const CEMETERY_CENTER = [6.18344118743717, 125.08457146469357];
    const CEMETERY_BOUNDS = [
        [6.18244118743717, 125.08357146469357],
        [6.18444118743717, 125.08557146469357]
    ];
    
    const map = L.map(containerId, {
        center: options.center || CEMETERY_CENTER,
        zoom: options.zoom || 17,
        minZoom: 10,
        maxZoom: 20
    });
    
    L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
    }).addTo(map);
    
    // Draw cemetery boundary
    L.rectangle(CEMETERY_BOUNDS, {
        color: '#ef4444',
        weight: 2,
        fillOpacity: 0,
        dashArray: '5, 10'
    }).addTo(map);
    
    return map;
}

// Grid builder for plot compartments
function initGridBuilder(rows, cols, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
    container.innerHTML = '';
    
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const cell = document.createElement('div');
            cell.className = 'grid-cell';
            cell.textContent = String.fromCharCode(65 + row) + (col + 1);
            cell.dataset.row = row;
            cell.dataset.col = col;
            
            cell.addEventListener('click', function() {
                this.classList.toggle('occupied');
            });
            
            container.appendChild(cell);
        }
    }
}

// Session timeout warning
let sessionTimeout;
let warningTimeout;

function initSessionMonitor() {
    const TIMEOUT = 30 * 60 * 1000; // 30 minutes
    const WARNING = 5 * 60 * 1000; // 5 minutes before timeout
    
    function resetTimers() {
        clearTimeout(sessionTimeout);
        clearTimeout(warningTimeout);
        
        warningTimeout = setTimeout(() => {
            themeUtils.showAlert('Your session will expire in 5 minutes', 'info');
        }, TIMEOUT - WARNING);
        
        sessionTimeout = setTimeout(() => {
            themeUtils.showAlert('Session expired. Redirecting to login...', 'error');
            setTimeout(() => {
                window.location.href = 'login.php?timeout=1';
            }, 2000);
        }, TIMEOUT);
    }
    
    // Reset on user activity
    ['mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetTimers, true);
    });
    
    resetTimers();
}

// Initialize session monitor
initSessionMonitor();

// Export utilities
window.adminUtils = {
    setupFilePreview,
    selectAllRecords,
    updateBatchActions,
    batchDelete,
    exportData,
    initAdminMap,
    initGridBuilder
};
