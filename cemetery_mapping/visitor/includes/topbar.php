<?php
$subtitles = [
    'dashboard' => 'Interactive cemetery map and navigation',
    'my-reservations' => 'View and manage your plot reservations',
    'available-plots' => 'Browse and reserve available plots',
];
$page_subtitle = $subtitles[$current_page] ?? 'Visitor portal';
$page_title = ucfirst(str_replace('-', ' ', $current_page));
if ($page_title === 'My Reservations') { $page_title = 'My Reservations'; }
if ($page_title === 'Available Plots') { $page_title = 'Available Plots'; }
?>

<header class="admin-header">
    <div class="admin-header-left">
        <button type="button" class="admin-header-hamburger" id="mobileMenuToggle" onclick="toggleMobileMenu()" aria-label="Toggle menu">
            <i data-lucide="menu" width="20" height="20"></i>
        </button>
        <div class="admin-header-info">
            <h1><?php echo $page_title; ?></h1>
            <span class="admin-header-subtitle"><?php echo $page_subtitle; ?></span>
        </div>
    </div>
    <div class="admin-header-actions">
        <!-- Notifications -->
        <div class="visitor-notifications">
            <button type="button" class="admin-header-btn" id="visitorNotifBtn" onclick="toggleVisitorNotifications(event)" title="Notifications" aria-label="Notifications">
                <i data-lucide="bell" width="20" height="20"></i>
                <span class="visitor-notif-badge" id="visitorNotifBadge" style="display:none;">0</span>
            </button>
            <div class="visitor-notif-dropdown" id="visitorNotifDropdown">
                <div class="visitor-notif-header">
                    <h3>Notifications</h3>
                    <button type="button" onclick="markVisitorNotifsRead()" class="visitor-notif-markall" title="Mark all as read">Mark all read</button>
                </div>
                <div class="visitor-notif-list" id="visitorNotifList">
                    <div class="visitor-notif-empty">
                        <i data-lucide="bell-off" width="32" height="32"></i>
                        <p>No notifications yet</p>
                    </div>
                </div>
                <a href="my-reservations.php" class="visitor-notif-footer">View all reservations</a>
            </div>
        </div>
        <a href="logout.php" class="admin-header-btn" title="Logout" aria-label="Logout">
            <i data-lucide="log-out" width="20" height="20"></i>
        </a>
    </div>
</header>

<style>
/* Visitor Notifications */
.visitor-notifications { position: relative; }

.visitor-notif-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
    animation: notifPulse 2s ease-in-out infinite;
}

@keyframes notifPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.12); }
}

.visitor-notif-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 360px;
    max-width: calc(100vw - 32px);
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12), 0 8px 20px rgba(0, 0, 0, 0.06);
    z-index: 10001;
    display: none;
    flex-direction: column;
    overflow: hidden;
    opacity: 0;
    transform: translateY(-8px);
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.visitor-notif-dropdown.open {
    display: flex;
    opacity: 1;
    transform: translateY(0);
}

.visitor-notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.visitor-notif-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.visitor-notif-markall {
    background: none;
    border: none;
    color: #10b981;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.visitor-notif-markall:hover {
    background: #f0fdf4;
}

.visitor-notif-list {
    max-height: 380px;
    overflow-y: auto;
    padding: 8px;
}

.visitor-notif-list::-webkit-scrollbar { width: 6px; }
.visitor-notif-list::-webkit-scrollbar-track { background: transparent; }
.visitor-notif-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

.visitor-notif-item {
    display: flex;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
    position: relative;
}

.visitor-notif-item.unread {
    background: #f0fdf4;
    border-color: #d1fae5;
}

.visitor-notif-unread-dot {
    position: absolute;
    top: 14px;
    right: 12px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.visitor-notif-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
}

.visitor-notif-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.visitor-notif-body {
    flex: 1;
    min-width: 0;
}

.visitor-notif-title {
    margin: 0 0 2px 0;
    font-size: 0.85rem;
    font-weight: 700;
    color: #0f172a;
}

.visitor-notif-msg {
    margin: 0 0 4px 0;
    font-size: 0.8rem;
    color: #475569;
    line-height: 1.4;
}

.visitor-notif-time {
    margin: 0;
    font-size: 0.7rem;
    color: #94a3b8;
}

.visitor-notif-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}

.visitor-notif-empty i {
    margin-bottom: 12px;
    color: #cbd5e1;
}

.visitor-notif-empty p {
    margin: 0;
    font-size: 0.85rem;
}

.visitor-notif-footer {
    display: block;
    text-align: center;
    padding: 14px;
    background: #f8fafc;
    color: #10b981;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    border-top: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.visitor-notif-footer:hover {
    background: #f0fdf4;
}

@media (max-width: 640px) {
    .visitor-notif-dropdown {
        position: fixed;
        top: 80px;
        right: 8px;
        left: 8px;
        width: auto;
        max-width: none;
    }
}
</style>

<script>
// Mobile Menu Toggle
function toggleMobileMenu() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

// Sidebar collapse on desktop
function toggleSidebarCollapse() {
    const layout = document.querySelector('.admin-layout');
    layout.classList.toggle('collapsed');
}

function initLucideIcons() {
    if (typeof lucide !== 'undefined') { lucide.createIcons(); return true; }
    return false;
}

function initSidebarAccordion() {
    const toggles = document.querySelectorAll('.sidebar-group-toggle');
    toggles.forEach(toggle => {
        if (toggle.dataset.initialized === 'true') return;
        const group = toggle.closest('.sidebar-group');
        toggle.addEventListener('click', function() {
            const isOpen = group.classList.contains('is-open');
            document.querySelectorAll('.sidebar-group.is-open').forEach(openGroup => {
                if (openGroup !== group) {
                    openGroup.classList.remove('is-open');
                    openGroup.querySelector('.sidebar-group-toggle').setAttribute('aria-expanded', 'false');
                }
            });
            if (isOpen) {
                group.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                group.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
        toggle.dataset.initialized = 'true';
    });

    document.querySelectorAll('.sidebar-group-menu a').forEach(link => {
        if (link.dataset.initialized === 'true') return;
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) toggleMobileMenu();
        });
        link.dataset.initialized = 'true';
    });
}

function recalcOpenMenuHeights() {}

function updateMenuToggle() {
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const collapseBtn = document.getElementById('sidebarCollapse');
    if (window.innerWidth <= 1024) {
        if (toggleBtn) toggleBtn.style.display = 'flex';
        if (collapseBtn) collapseBtn.style.display = 'none';
        document.querySelector('.admin-layout').classList.remove('collapsed');
    } else {
        if (toggleBtn) toggleBtn.style.display = 'none';
        if (collapseBtn) collapseBtn.style.display = 'flex';
        document.getElementById('adminSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }
}

initSidebarAccordion();
updateMenuToggle();
window.addEventListener('resize', updateMenuToggle);

if (!initLucideIcons()) {
    let attempts = 0;
    const poll = setInterval(() => {
        attempts++;
        if (initLucideIcons()) { clearInterval(poll); recalcOpenMenuHeights(); }
        else if (attempts > 50) clearInterval(poll);
    }, 100);
} else {
    recalcOpenMenuHeights();
}

document.addEventListener('DOMContentLoaded', function() {
    initSidebarAccordion();
    if (initLucideIcons()) recalcOpenMenuHeights();
    updateMenuToggle();
    loadVisitorNotifications();
    // Refresh notifications every 60 seconds
    setInterval(loadVisitorNotifications, 60000);
});

// ==========================================
// VISITOR NOTIFICATIONS
// ==========================================
let visitorNotifs = [];
let visitorNotifsRead = false;

function toggleVisitorNotifications(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('visitorNotifDropdown');
    dropdown.classList.toggle('open');
    if (dropdown.classList.contains('open') && !visitorNotifsRead) {
        markVisitorNotifsRead();
    }
}

document.addEventListener('click', function(e) {
    const wrap = document.querySelector('.visitor-notifications');
    const dropdown = document.getElementById('visitorNotifDropdown');
    if (dropdown && dropdown.classList.contains('open') && wrap && !wrap.contains(e.target)) {
        dropdown.classList.remove('open');
    }
});

async function loadVisitorNotifications() {
    try {
        const response = await fetch('../api/visitor_notifications.php');
        const data = await response.json();
        if (data.success) {
            visitorNotifs = data.notifications || [];
            renderVisitorNotifications(visitorNotifs, data.unread_count || 0);
        }
    } catch (e) {
        console.error('Failed to load notifications:', e);
    }
}

function renderVisitorNotifications(notifs, unreadCount) {
    const list = document.getElementById('visitorNotifList');
    const badge = document.getElementById('visitorNotifBadge');

    if (!notifs.length) {
        list.innerHTML = `
            <div class="visitor-notif-empty">
                <i data-lucide="bell-off" width="32" height="32"></i>
                <p>No notifications yet</p>
            </div>
        `;
        badge.style.display = 'none';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    if (unreadCount > 0) {
        badge.textContent = unreadCount;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }

    list.innerHTML = notifs.map(n => {
        const time = n.date ? timeAgo(n.date) : '';
        const unreadClass = n.unread ? ' unread' : '';
        const unreadDot = n.unread ? '<span class="visitor-notif-unread-dot"></span>' : '';
        return `
            <div class="visitor-notif-item${unreadClass}" onclick="window.location.href='my-reservations.php'">
                <div class="visitor-notif-icon" style="background: ${n.color}15; color: ${n.color};">
                    <i data-lucide="${n.icon}" width="20" height="20"></i>
                </div>
                <div class="visitor-notif-body">
                    <p class="visitor-notif-title">${n.title}</p>
                    <p class="visitor-notif-msg">${n.message}</p>
                    <p class="visitor-notif-time">${time}</p>
                </div>
                ${unreadDot}
            </div>
        `;
    }).join('');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function markVisitorNotifsRead() {
    try {
        await fetch('../api/visitor_notifications.php?action=mark_read');
        const badge = document.getElementById('visitorNotifBadge');
        badge.style.display = 'none';
        // Re-render to remove unread dots
        visitorNotifs = visitorNotifs.map(n => ({ ...n, unread: false }));
        renderVisitorNotifications(visitorNotifs, 0);
    } catch (e) {
        console.error('Failed to mark notifications as read:', e);
    }
}

function timeAgo(dateStr) {
    const date = new Date(dateStr);
    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
    if (seconds < 60) return 'Just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return minutes + 'm ago';
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return hours + 'h ago';
    const days = Math.floor(hours / 24);
    if (days < 7) return days + 'd ago';
    return date.toLocaleDateString();
}
</script>
