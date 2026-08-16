<?php
// Topbar / Header component
// Usage: require_once 'includes/topbar.php';
// Expects $current_page and $admin_username to be set by the caller.

$subtitles = [
    'dashboard' => 'Overview of cemetery records, plots, and visitor activity',
    'add-record' => 'Register a new burial record',
    'records' => 'View and manage all burial records',
    'map-view' => 'Interactive cemetery map view',
    'available-plots' => 'Manage available burial plots',
    'statistics' => 'Cemetery statistics and analytics',
    'reports' => 'Generate and export reports',
    'reservations' => 'Manage visitor reservations',
    'reservations_simple' => 'Manage visitor reservations',
    'assistant' => 'AI-powered assistant',
    'settings' => 'System configuration and settings',
];
$page_subtitle = $subtitles[$current_page] ?? 'Overview of cemetery management';
$page_title = ucfirst(str_replace('-', ' ', $current_page));
// Override title for pages with underscores or custom names
$title_overrides = [
    'reservations_simple' => 'Reservations',
];
if (isset($title_overrides[$current_page])) {
    $page_title = $title_overrides[$current_page];
}
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
    <div class="admin-header-actions" style="position: relative;">
        <button type="button" class="admin-header-btn" title="Notifications" id="notifBtn" aria-label="Notifications" onclick="toggleNotifDropdown()">
            <i data-lucide="bell" width="20" height="20"></i>
            <span class="admin-header-badge" id="notifBadge">0</span>
        </button>
        <div id="notifDropdown" class="notif-dropdown" style="display:none;">
            <div class="notif-header">
                <span class="notif-title">Notifications</span>
                <span id="notifCountText" class="notif-subtitle">0 new</span>
            </div>
            <div id="notifList" class="notif-list">
                <div class="notif-empty">No new notifications</div>
            </div>
        </div>
        <a href="logout.php" class="admin-header-btn" title="Logout" aria-label="Logout">
            <i data-lucide="log-out" width="20" height="20"></i>
        </a>
    </div>
</header>

<style>
.notif-dropdown {
    position: absolute;
    top: 54px;
    right: 0;
    width: 320px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(0,0,0,0.1);
    z-index: 1000;
    overflow: hidden;
    animation: notifFadeIn 0.2s ease;
}
@keyframes notifFadeIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
.notif-header {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.notif-title { font-weight: 700; font-size: 0.95rem; color: #0f172a; }
.notif-subtitle { font-size: 0.75rem; color: #64748b; }
.notif-list { max-height: 320px; overflow-y: auto; }
.notif-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    transition: background 0.15s;
    text-decoration: none;
    color: inherit;
}
.notif-item:hover { background: #f8fafc; }
.notif-icon { width: 32px; height: 32px; border-radius: 8px; background: #f0fdf4; color: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.notif-icon svg { width: 16px; height: 16px; }
.notif-body { flex: 1; }
.notif-body p { margin: 0; font-size: 0.82rem; color: #334155; line-height: 1.4; }
.notif-body p strong { color: #0f172a; }
.notif-time { font-size: 0.7rem; color: #94a3b8; margin-top: 3px; }
.notif-empty { padding: 24px 16px; text-align: center; color: #94a3b8; font-size: 0.85rem; }
</style>

<script>
let notifDropdownOpen = false;

async function loadNotifications() {
    try {
        const res = await fetch('../api/admin_notifications.php');
        const data = await res.json();
        if (!data.success) return;

        const badge = document.getElementById('notifBadge');
        badge.textContent = data.count;
        badge.style.display = data.count > 0 ? 'flex' : 'none';

        document.getElementById('notifCountText').textContent = data.count + ' new';

        const list = document.getElementById('notifList');
        let items = [];

        data.recent_visitors.forEach(v => {
            const date = new Date(v.created_at);
            const time = date.toLocaleString('en-US', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit', hour12:true });
            items.push(`
                <div class="notif-item" style="cursor:default;">
                    <div class="notif-icon" style="background:#f0f9ff;color:#0ea5e9;"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg></div>
                    <div class="notif-body">
                        <p><strong>${esc(v.full_name)}</strong> registered as visitor</p>
                        <div class="notif-time">${time}</div>
                    </div>
                </div>
            `);
        });

        data.recent_reservations.forEach(r => {
            const date = new Date(r.reservation_date);
            const time = date.toLocaleString('en-US', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit', hour12:true });
            items.push(`
                <a href="reservations_simple.php?status=pending" class="notif-item">
                    <div class="notif-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>
                    <div class="notif-body">
                        <p><strong>${esc(r.visitor_name)}</strong> submitted a reservation</p>
                        <div class="notif-time">${time}</div>
                    </div>
                </a>
            `);
        });

        data.recent_payments.forEach(p => {
            const date = new Date(p.payment_date);
            const time = date.toLocaleString('en-US', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit', hour12:true });
            const amount = Number(p.amount).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
            items.push(`
                <a href="reservations_simple.php?status=approved" class="notif-item">
                    <div class="notif-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg></div>
                    <div class="notif-body">
                        <p><strong>${esc(p.visitor_name)}</strong> paid ₱${amount}</p>
                        <div class="notif-time">${time}</div>
                    </div>
                </a>
            `);
        });

        list.innerHTML = items.length ? items.join('') : '<div class="notif-empty">No new notifications</div>';
    } catch (e) {
        console.error('Failed to load notifications', e);
    }
}

function toggleNotifDropdown() {
    const dropdown = document.getElementById('notifDropdown');
    notifDropdownOpen = !notifDropdownOpen;
    dropdown.style.display = notifDropdownOpen ? 'block' : 'none';
    if (notifDropdownOpen) loadNotifications();
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notifDropdown');
    const btn = document.getElementById('notifBtn');
    if (notifDropdownOpen && !btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
        notifDropdownOpen = false;
    }
});

function esc(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }

// Load badge count on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setInterval(loadNotifications, 60000); // refresh every 60s
});
</script>
