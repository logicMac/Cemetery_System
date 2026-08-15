/**
 * Theme Utilities
 * Common functions and utilities used across the application
 */

// Show alert message
function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.animation = 'slideIn 0.3s ease';
    
    alertContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }, duration);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 400px;
    `;
    document.body.appendChild(container);
    return container;
}

// Show loading spinner
function showLoading(container) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.id = 'loading-spinner';
    spinner.style.margin = '20px auto';
    
    if (typeof container === 'string') {
        container = document.querySelector(container);
    }
    
    if (container) {
        container.appendChild(spinner);
    }
    
    return spinner;
}

function hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) spinner.remove();
}

// Modal functions
function showModal(content, title = '') {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.id = 'modal-overlay';
    
    const modal = document.createElement('div');
    modal.className = 'modal-content';
    
    if (title) {
        const titleEl = document.createElement('h3');
        titleEl.textContent = title;
        titleEl.style.marginBottom = '20px';
        modal.appendChild(titleEl);
    }
    
    if (typeof content === 'string') {
        modal.innerHTML += content;
    } else {
        modal.appendChild(content);
    }
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Close on overlay click
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeModal();
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', handleEscapeKey);
}

function closeModal() {
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.classList.remove('active');
        setTimeout(() => overlay.remove(), 300);
    }
    document.removeEventListener('keydown', handleEscapeKey);
}

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
}

// Confirm dialog
function confirm(message, onConfirm, onCancel) {
    const content = document.createElement('div');
    content.innerHTML = `
        <p style="margin-bottom: 20px; color: var(--zinc-400);">${message}</p>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn-secondary" id="cancel-btn">Cancel</button>
            <button class="btn-primary" id="confirm-btn">Confirm</button>
        </div>
    `;
    
    showModal(content, 'Confirm Action');
    
    document.getElementById('confirm-btn').addEventListener('click', () => {
        closeModal();
        if (onConfirm) onConfirm();
    });
    
    document.getElementById('cancel-btn').addEventListener('click', () => {
        closeModal();
        if (onCancel) onCancel();
    });
}

// Format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Calculate age
function calculateAge(birthDate, deathDate) {
    if (!birthDate) return 'N/A';
    
    const birth = new Date(birthDate);
    const death = deathDate ? new Date(deathDate) : new Date();
    
    let age = death.getFullYear() - birth.getFullYear();
    const monthDiff = death.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && death.getDate() < birth.getDate())) {
        age--;
    }
    
    return age;
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Validate email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate password strength
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    if (strength <= 2) return 'weak';
    if (strength <= 4) return 'medium';
    return 'strong';
}

// Update password strength indicator
function updatePasswordStrength(inputId, barId, textId) {
    const input = document.getElementById(inputId);
    const bar = document.getElementById(barId);
    const text = document.getElementById(textId);
    
    if (!input || !bar) return;
    
    input.addEventListener('input', () => {
        const password = input.value;
        const strength = checkPasswordStrength(password);
        
        bar.className = `password-strength-bar ${strength}`;
        
        if (text) {
            if (password.length === 0) {
                text.textContent = '';
            } else if (strength === 'weak') {
                text.textContent = 'Weak password';
                text.style.color = '#b55a5a';
            } else if (strength === 'medium') {
                text.textContent = 'Medium password';
                text.style.color = '#c9a86c';
            } else {
                text.textContent = 'Strong password';
                text.style.color = '#5a9b6f';
            }
        }
    });
}

// Haversine distance calculation (in meters)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

// Format distance for display
function formatDistance(meters) {
    if (meters < 1000) {
        return Math.round(meters) + ' m';
    } else {
        return (meters / 1000).toFixed(2) + ' km';
    }
}

// Export to global scope
window.themeUtils = {
    showAlert,
    showLoading,
    hideLoading,
    showModal,
    closeModal,
    confirm,
    formatDate,
    calculateAge,
    debounce,
    validateEmail,
    checkPasswordStrength,
    updatePasswordStrength,
    calculateDistance,
    formatDistance
};
