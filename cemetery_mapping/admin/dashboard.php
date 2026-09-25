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

    // Scheduled burials this week (not yet done)
    $scheduledBurials = $pdo->query("
        SELECT id, decedent_name, plot_number, barangay, burial_date, burial_time
        FROM burial_records
        WHERE burial_date IS NOT NULL
          AND is_buried = 0
          AND YEARWEEK(burial_date, 1) = YEARWEEK(CURDATE(), 1)
        ORDER BY burial_date ASC, burial_time ASC
    ")->fetchAll();

    // All scheduled burials for the current month (for calendar view)
    $calMonth = isset($_GET['cal_month']) ? (int)$_GET['cal_month'] : (int)date('n');
    $calYear  = isset($_GET['cal_year']) ? (int)$_GET['cal_year'] : (int)date('Y');
    if ($calMonth < 1 || $calMonth > 12) { $calMonth = (int)date('n'); }
    if ($calYear < 2000 || $calYear > 2100) { $calYear = (int)date('Y'); }
    $calStmt = $pdo->prepare("
        SELECT id, decedent_name, plot_number, burial_date, burial_time, is_buried
        FROM burial_records
        WHERE burial_date IS NOT NULL
          AND MONTH(burial_date) = ? AND YEAR(burial_date) = ?
        ORDER BY burial_date ASC, burial_time ASC
    ");
    $calStmt->execute([$calMonth, $calYear]);
    $calendarBurials = [];
    foreach ($calStmt->fetchAll() as $row) {
        $day = (int)date('j', strtotime($row['burial_date']));
        $calendarBurials[$day][] = $row;
    }

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
    .burial-schedule-grid {
        grid-template-columns: 1fr !important;
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

    <div class="dv2-card" style="margin-bottom: 28px;">
        <h3>
            <span>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Burial Schedule
            </span>
            <span style="font-size:0.82rem; color:#64748b; font-weight:600;"><?php echo count($scheduledBurials ?? []); ?> scheduled this week</span>
        </h3>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px;" class="burial-schedule-grid">
            <!-- Mini Calendar -->
            <div>
                <?php
                $prevM = $calMonth - 1; $prevY = $calYear; if ($prevM < 1) { $prevM = 12; $prevY--; }
                $nextM = $calMonth + 1; $nextY = $calYear; if ($nextM > 12) { $nextM = 1; $nextY++; }
                $firstDay = mktime(0, 0, 0, $calMonth, 1, $calYear);
                $daysInMonth = (int)date('t', $firstDay);
                $startDow = (int)date('N', $firstDay); // 1=Mon
                $monthName = date('F Y', $firstDay);
                $today = (int)date('j'); $isCurrentMonth = ($calMonth === (int)date('n') && $calYear === (int)date('Y'));
                ?>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <a href="?cal_month=<?php echo $prevM; ?>&cal_year=<?php echo $prevY; ?>" style="padding:4px 10px; border:1px solid #e2e8f0; border-radius:8px; color:#10b981; font-weight:700; text-decoration:none;">&lsaquo;</a>
                    <strong style="color:#0f172a; font-size:0.95rem;"><?php echo $monthName; ?></strong>
                    <a href="?cal_month=<?php echo $nextM; ?>&cal_year=<?php echo $nextY; ?>" style="padding:4px 10px; border:1px solid #e2e8f0; border-radius:8px; color:#10b981; font-weight:700; text-decoration:none;">&rsaquo;</a>
                </div>
                <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                    <thead>
                        <tr>
                            <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
                                <th style="padding:6px 2px; font-size:0.65rem; text-transform:uppercase; color:#94a3b8; font-weight:700; text-align:center;"><?php echo $d; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $dayNum = 1;
                        for ($w = 0; $w < 6 && $dayNum <= $daysInMonth; $w++) {
                            echo '<tr>';
                            for ($d = 1; $d <= 7; $d++) {
                                if (($w === 0 && $d < $startDow) || $dayNum > $daysInMonth) {
                                    echo '<td style="padding:4px;"></td>';
                                    continue;
                                }
                                $hasBurial = isset($calendarBurials[$dayNum]);
                                $isToday = $isCurrentMonth && $dayNum === $today;
                                $cellBg = $hasBurial ? '#fef3c7' : ($isToday ? '#ecfdf5' : 'transparent');
                                $cellBorder = $hasBurial ? '1px solid #f59e0b' : ($isToday ? '1px solid #10b981' : '1px solid #f1f5f9');
                                echo '<td style="padding:2px; vertical-align:top;">';
                                echo '<div style="min-height:52px; border-radius:8px; padding:4px; background:' . $cellBg . '; border:' . $cellBorder . ';">';
                                echo '<div style="font-size:0.75rem; font-weight:' . ($isToday ? '800' : '600') . '; color:' . ($isToday ? '#059669' : '#64748b') . '; text-align:center;">' . $dayNum . '</div>';
                                if ($hasBurial) {
                                    foreach ($calendarBurials[$dayNum] as $cb) {
                                        $time = $cb['burial_time'] ? date('g:i A', strtotime($cb['burial_time'])) : '';
                                        $done = $cb['is_buried'] == 1;
                                        echo '<div title="' . htmlspecialchars($cb['decedent_name'] . ($time ? ' @ ' . $time : ''), ENT_QUOTES) . '" style="font-size:0.6rem; line-height:1.2; margin-top:2px; padding:1px 4px; border-radius:4px; background:' . ($done ? '#d1fae5' : '#fde68a') . '; color:' . ($done ? '#065f46' : '#92400e') . '; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' . htmlspecialchars($cb['decedent_name']) . ($time ? ' ' . $time : '') . '</div>';
                                    }
                                }
                                echo '</div></td>';
                                $dayNum++;
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <div style="display:flex; gap:14px; margin-top:10px; font-size:0.7rem; color:#64748b;">
                    <span><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:#fde68a; border:1px solid #f59e0b;"></span> Scheduled</span>
                    <span><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:#d1fae5;"></span> Done</span>
                </div>
            </div>

            <!-- This Week list -->
            <div>
                <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:10px;">This Week</div>
                <?php if (empty($scheduledBurials)): ?>
                    <div class="dv2-empty">No burials scheduled this week</div>
                <?php else: ?>
                    <?php foreach ($scheduledBurials as $b): ?>
                        <div id="burial-row-<?php echo (int)$b['id']; ?>" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border:1px solid #f1f5f9; border-radius:10px; margin-bottom:8px; background:#fffbeb;">
                            <div style="min-width:0;">
                                <div style="font-weight:700; font-size:0.9rem; color:#0f172a;"><?php echo htmlspecialchars($b['decedent_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div style="font-size:0.78rem; color:#64748b;">
                                    <?php echo date('D, M d', strtotime($b['burial_date'])); ?>
                                    <?php echo $b['burial_time'] ? ' @ ' . date('g:i A', strtotime($b['burial_time'])) : ''; ?>
                                    · Plot <?php echo htmlspecialchars($b['plot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                    · <?php echo htmlspecialchars($b['barangay'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                            <button type="button" onclick="markBurialDone(<?php echo (int)$b['id']; ?>, this)" style="padding:6px 16px; background:#10b981; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:0.8rem; cursor:pointer; transition:all 0.2s; flex-shrink:0;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">DONE</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
            <a href="expiring-plots.php" class="dv2-qa">
                <div class="dv2-qa-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <span class="dv2-qa-title-text">Expiring Plots</span>
                    <span class="dv2-qa-desc">Renewals due soon</span>
                </div>
            </a>
        </div>
    </div>
</div>

        </main>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <script>
    async function markBurialDone(recordId, btn) {
        btn.disabled = true;
        btn.textContent = '...';
        try {
            const fd = new FormData();
            fd.append('record_id', recordId);
            const res = await fetch('../api/mark_buried.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                const row = document.getElementById('burial-row-' + recordId);
                if (row) {
                    row.style.transition = 'opacity 0.4s';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 400);
                }
            } else {
                alert(data.message || 'Failed to mark burial as done');
                btn.disabled = false;
                btn.textContent = 'DONE';
            }
        } catch (e) {
            alert('Network error');
            btn.disabled = false;
            btn.textContent = 'DONE';
        }
    }
    </script>
</body>
</html>
