<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Get statistics
try {
    // Total burial records
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();

    // Available plots
    $availablePlots = $pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();

    // Records this month
    $thisMonth = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE MONTH(date_added) = MONTH(CURRENT_DATE()) AND YEAR(date_added) = YEAR(CURRENT_DATE())")->fetchColumn();

    // Total visitors
    $totalVisitors = $pdo->query("SELECT COUNT(*) FROM visitors WHERE is_active = 1")->fetchColumn();

    // Recent records
    $recentRecords = $pdo->query("SELECT decedent_name, plot_number, date_added FROM burial_records ORDER BY date_added DESC LIMIT 5")->fetchAll();

    // Records by barangay
    $byBarangay = $pdo->query("SELECT barangay, COUNT(*) as count FROM burial_records WHERE barangay IS NOT NULL GROUP BY barangay ORDER BY count DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
/* Dashboard v2 — mint green + white */
.admin-layout {
    background: #ffffff;
}

.admin-layout::after {
    display: none;
}



/* Animations */
@keyframes dv2FadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes dv2Pop {
    0%   { opacity: 0; transform: scale(0.94); }
    100% { opacity: 1; transform: scale(1); }
}

.dv2 > * {
    opacity: 0;
    animation: dv2FadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.dv2 > *:nth-child(1) { animation-delay: 0.04s; }
.dv2 > *:nth-child(2) { animation-delay: 0.10s; }
.dv2 > *:nth-child(3) { animation-delay: 0.16s; }
.dv2 > *:nth-child(4) { animation-delay: 0.22s; }

.dv2-stat,
.dv2-card,
.dv2-qa {
    animation: dv2Pop 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.dv2-stats .dv2-stat:nth-child(1) { animation-delay: 0.12s; }
.dv2-stats .dv2-stat:nth-child(2) { animation-delay: 0.20s; }
.dv2-stats .dv2-stat:nth-child(3) { animation-delay: 0.28s; }
.dv2-stats .dv2-stat:nth-child(4) { animation-delay: 0.36s; }

.dv2-grid .dv2-card:nth-child(1) { animation-delay: 0.24s; }
.dv2-grid .dv2-card:nth-child(2) { animation-delay: 0.34s; }

.dv2-qa-row .dv2-qa:nth-child(1) { animation-delay: 0.30s; }
.dv2-qa-row .dv2-qa:nth-child(2) { animation-delay: 0.38s; }
.dv2-qa-row .dv2-qa:nth-child(3) { animation-delay: 0.46s; }
.dv2-qa-row .dv2-qa:nth-child(4) { animation-delay: 0.54s; }

/* Hero banner */
.dv2-hero {
    border-radius: 20px;
    padding: 40px 40px;
    color: #fff;
    margin-bottom: 28px;
    box-shadow: 0 12px 40px rgba(16,185,129,0.22);
    position: relative;
    overflow: hidden;
    background-image: url('../assets/images/cemetery-banner.jpg');
    background-size: cover;
    background-position: center;
    text-align: center;
}

.dv2-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.45) 60%, rgba(0,0,0,0.5) 100%);
    z-index: 0;
}

.dv2-hero-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.dv2-hero-left { position: relative; z-index: 2; }

.dv2-hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.18);
    color: #fff;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 14px;
    backdrop-filter: blur(6px);
    text-shadow: 0 1px 3px rgba(0,0,0,0.25);
}

.dv2-hero h1 {
    font-size: 2.3rem;
    font-weight: 800;
    margin: 0 0 6px;
    color: #fff;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.dv2-hero-date {
    opacity: 0.9;
    font-size: 0.95rem;
    margin-bottom: 18px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.25);
}

.dv2-hero-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    justify-content: center;
}

.dv2-hero-badges span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.15);
    color: #fff;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 600;
    text-shadow: 0 1px 3px rgba(0,0,0,0.25);
}

.dv2-hero-badges svg {
    width: 14px;
    height: 14px;
}

.dv2-hero-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

.dv2-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 20px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.dv2-btn-light {
    background: #fff;
    color: #059669;
}

.dv2-btn-light:hover {
    background: #f0fdf4;
    transform: translateY(-2px);
}

.dv2-btn-outline {
    background: rgba(255,255,255,0.12);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.35);
}

.dv2-btn-outline:hover {
    background: rgba(255,255,255,0.22);
}

.dv2-hero-right {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: center;
    align-items: center;
}

.dv2-hero-avatar {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.35);
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.2rem;
    font-weight: 800;
    color: #10b981;
    box-shadow: 0 16px 40px rgba(0,0,0,0.12);
    overflow: hidden;
}

.dv2-hero-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Stats row */
.dv2-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}

.dv2-stat {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.03);
    transition: transform 0.2s, box-shadow 0.2s;
}

.dv2-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.06);
}

.dv2-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #f0fdf4;
    color: #10b981;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
}

.dv2-stat-icon svg {
    width: 18px;
    height: 18px;
}

.dv2-stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
}

.dv2-stat-label {
    font-size: 0.85rem;
    color: #10b981;
    font-weight: 600;
}

/* Grid cards */
.dv2-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.dv2-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.03);
}

.dv2-card h3 {
    margin: 0 0 16px;
    font-size: 1rem;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
}

.dv2-card h3 > span {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.dv2-card h3 svg {
    width: 18px;
    height: 18px;
    color: #10b981;
}

.dv2-card a {
    color: #10b981;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
}

.dv2-card a:hover {
    text-decoration: underline;
}

.dv2-table {
    width: 100%;
    border-collapse: collapse;
}

.dv2-table th {
    text-align: left;
    padding: 10px 12px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    border-bottom: 1px solid #f1f5f9;
    font-weight: 700;
}

.dv2-table td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-size: 0.92rem;
}

.dv2-table tr:last-child td {
    border-bottom: none;
}

.dv2-empty {
    text-align: center;
    color: #94a3b8;
    padding: 24px;
    font-size: 0.9rem;
}

/* Quick access tiles */
.dv2-qa-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dv2-qa-title svg {
    width: 18px;
    height: 18px;
    color: #10b981;
}

.dv2-qa-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}

.dv2-qa {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 18px;
    text-decoration: none;
    color: #0f172a;
    transition: all 0.2s;
}

.dv2-qa:hover {
    border-color: #10b981;
    box-shadow: 0 10px 28px rgba(16,185,129,0.10);
    transform: translateY(-3px);
}

.dv2-qa-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f0fdf4;
    color: #10b981;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dv2-qa-icon svg {
    width: 20px;
    height: 20px;
}

.dv2-qa-title-text {
    font-weight: 700;
    font-size: 0.95rem;
    display: block;
    margin-bottom: 2px;
}

.dv2-qa-desc {
    font-size: 0.8rem;
    color: #64748b;
}

@media (max-width: 900px) {
    .dv2-hero {
        grid-template-columns: 1fr;
        padding: 28px;
    }
    .dv2-hero-right {
        justify-content: flex-start;
    }
    .admin-main {
        padding: 20px;
    }
}
</style>

<div class="dv2">
    <div class="dv2-hero">
        <!-- Dark overlay on top of photo for text readability -->
        <div class="dv2-hero-overlay"></div>
        <!-- Decorative SVG background -->
        <svg class="dv2-hero-svg" viewBox="0 0 800 400" preserveAspectRatio="xMidYMid slice" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Soft circles -->
            <circle cx="680" cy="80" r="120" fill="rgba(255,255,255,0.06)"/>
            <circle cx="720" cy="320" r="80" fill="rgba(255,255,255,0.05)"/>
            <circle cx="120" cy="350" r="60" fill="rgba(255,255,255,0.04)"/>
            <!-- Dot grid pattern (top-right) -->
            <g fill="rgba(255,255,255,0.08)">
                <circle cx="560" cy="40" r="2"/><circle cx="590" cy="40" r="2"/><circle cx="620" cy="40" r="2"/><circle cx="650" cy="40" r="2"/><circle cx="680" cy="40" r="2"/><circle cx="710" cy="40" r="2"/><circle cx="740" cy="40" r="2"/><circle cx="770" cy="40" r="2"/>
                <circle cx="560" cy="70" r="2"/><circle cx="590" cy="70" r="2"/><circle cx="620" cy="70" r="2"/><circle cx="650" cy="70" r="2"/><circle cx="680" cy="70" r="2"/><circle cx="710" cy="70" r="2"/><circle cx="740" cy="70" r="2"/><circle cx="770" cy="70" r="2"/>
                <circle cx="560" cy="100" r="2"/><circle cx="590" cy="100" r="2"/><circle cx="620" cy="100" r="2"/><circle cx="650" cy="100" r="2"/><circle cx="680" cy="100" r="2"/><circle cx="710" cy="100" r="2"/><circle cx="740" cy="100" r="2"/><circle cx="770" cy="100" r="2"/>
                <circle cx="560" cy="130" r="2"/><circle cx="590" cy="130" r="2"/><circle cx="620" cy="130" r="2"/><circle cx="650" cy="130" r="2"/><circle cx="680" cy="130" r="2"/><circle cx="710" cy="130" r="2"/><circle cx="740" cy="130" r="2"/><circle cx="770" cy="130" r="2"/>
            </g>
            <!-- Wave paths (bottom) -->
            <path d="M0,340 Q150,310 300,340 T600,340 T900,340 L900,400 L0,400 Z" fill="rgba(255,255,255,0.05)"/>
            <path d="M0,360 Q150,335 300,360 T600,360 T900,360 L900,400 L0,400 Z" fill="rgba(255,255,255,0.04)"/>
            <!-- Decorative lines (left side) -->
            <g stroke="rgba(255,255,255,0.06)" stroke-width="1">
                <line x1="0" y1="60" x2="180" y2="60"/>
                <line x1="0" y1="80" x2="120" y2="80"/>
                <line x1="0" y1="100" x2="150" y2="100"/>
            </g>
        </svg>
        <div class="dv2-hero-left">
            <div class="dv2-hero-tag">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Admin Panel
            </div>
            <h1>Welcome, <?php echo $admin_username; ?>!</h1>
            <div class="dv2-hero-date"><?php echo date('l, F d, Y'); ?></div>
            <div class="dv2-hero-badges">
                <span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <?php echo number_format($totalRecords); ?> Records
                </span>
                <span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?php echo number_format($availablePlots); ?> Plots
                </span>
                <span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <?php echo number_format($totalVisitors); ?> Visitors
                </span>
            </div>
            <div class="dv2-hero-actions">
                <a href="records.php" class="dv2-btn dv2-btn-light">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    View Records
                </a>
                <a href="reports.php" class="dv2-btn dv2-btn-outline">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Reports
                </a>
            </div>
        </div>
    </div>

    <div class="dv2-stats">
        <div class="dv2-stat">
            <div class="dv2-stat-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="dv2-stat-value"><?php echo number_format($totalRecords); ?></div>
            <div class="dv2-stat-label">Total Records</div>
        </div>
        <div class="dv2-stat">
            <div class="dv2-stat-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="dv2-stat-value"><?php echo number_format($availablePlots); ?></div>
            <div class="dv2-stat-label">Available Plots</div>
        </div>
        <div class="dv2-stat">
            <div class="dv2-stat-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="dv2-stat-value"><?php echo number_format($thisMonth); ?></div>
            <div class="dv2-stat-label">Records This Month</div>
        </div>
        <div class="dv2-stat">
            <div class="dv2-stat-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div class="dv2-stat-value"><?php echo number_format($totalVisitors); ?></div>
            <div class="dv2-stat-label">Active Visitors</div>
        </div>
    </div>

    <div class="dv2-grid">
        <div class="dv2-card">
            <h3>
                <span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Recent Records
                </span>
                <a href="records.php">View All →</a>
            </h3>
            <table class="dv2-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Plot</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentRecords)): ?>
                        <tr>
                            <td colspan="3" class="dv2-empty">No records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentRecords as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['decedent_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($record['plot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($record['date_added'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="dv2-card">
            <h3>
                <span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    Records by Barangay
                </span>
                <a href="statistics.php">View Stats →</a>
            </h3>
            <table class="dv2-table">
                <thead>
                    <tr>
                        <th>Barangay</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($byBarangay)): ?>
                        <tr>
                            <td colspan="2" class="dv2-empty">No data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($byBarangay as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['barangay'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo number_format($item['count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="dv2-card" style="margin-bottom: 24px;">
        <h3 class="dv2-qa-title">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Quick Access
        </h3>
        <div class="dv2-qa-row">
            <a href="add-record.php" class="dv2-qa">
                <div class="dv2-qa-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div>
                    <span class="dv2-qa-title-text">Add Record</span>
                    <span class="dv2-qa-desc">Register a burial record</span>
                </div>
            </a>
            <a href="available-plots.php" class="dv2-qa">
                <div class="dv2-qa-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                </div>
                <div>
                    <span class="dv2-qa-title-text">Manage Plots</span>
                    <span class="dv2-qa-desc">View available plots</span>
                </div>
            </a>
            <a href="map-view.php" class="dv2-qa">
                <div class="dv2-qa-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                </div>
                <div>
                    <span class="dv2-qa-title-text">View Map</span>
                    <span class="dv2-qa-desc">Interactive cemetery map</span>
                </div>
            </a>
            <a href="reservations_simple.php" class="dv2-qa">
                <div class="dv2-qa-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
                <div>
                    <span class="dv2-qa-title-text">Reservations</span>
                    <span class="dv2-qa-desc">Manage reservations</span>
                </div>
            </a>
        </div>
    </div>
</div>

        </main>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
</body>
</html>
