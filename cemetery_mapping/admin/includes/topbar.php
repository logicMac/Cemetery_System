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
        <button type="button" class="admin-header-btn" title="Notifications" id="notifBtn" aria-label="Notifications">
            <i data-lucide="bell" width="20" height="20"></i>
            <span class="admin-header-badge" id="notifBadge">0</span>
        </button>
        <a href="logout.php" class="admin-header-btn" title="Logout" aria-label="Logout">
            <i data-lucide="log-out" width="20" height="20"></i>
        </a>
    </div>
</header>
