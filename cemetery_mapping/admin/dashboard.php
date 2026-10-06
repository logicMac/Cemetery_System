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

 // Records this month / last month (for the delta badge)
 $thisMonth = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE MONTH(date_added) = MONTH(CURRENT_DATE()) AND YEAR(date_added) = YEAR(CURRENT_DATE())")->fetchColumn();
 $lastMonthRecords = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE date_added >= DATE_FORMAT(CURRENT_DATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND date_added < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')")->fetchColumn();

 // Total visitors + new this/last month
 $totalVisitors = $pdo->query("SELECT COUNT(*) FROM visitors WHERE is_active = 1")->fetchColumn();
 $visitorsThisMonth = $pdo->query("SELECT COUNT(*) FROM visitors WHERE created_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')")->fetchColumn();
 $visitorsLastMonth = $pdo->query("SELECT COUNT(*) FROM visitors WHERE created_at >= DATE_FORMAT(CURRENT_DATE() - INTERVAL 1 MONTH, '%Y-%m-01') AND created_at < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')")->fetchColumn();

 // Recent records (with barangay + burial status for the table)
 $recentRecords = $pdo->query("SELECT decedent_name, plot_number, barangay, is_buried, date_added FROM burial_records ORDER BY date_added DESC LIMIT 8")->fetchAll();

 // Burials added per day over the last 90 days (chart series)
 $burialSeries = [];
 $seriesRows = $pdo->query("SELECT DATE(date_added) AS d, COUNT(*) AS c FROM burial_records WHERE date_added >= CURDATE() - INTERVAL 89 DAY GROUP BY DATE(date_added)")->fetchAll();
 foreach ($seriesRows as $r) { $burialSeries[$r['d']] = (int)$r['c']; }

 // Scheduled this week vs last week
 $schedLastWeek = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE burial_date IS NOT NULL AND YEARWEEK(burial_date, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)")->fetchColumn();

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
 $calYear = isset($_GET['cal_year']) ? (int)$_GET['cal_year'] : (int)date('Y');
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

// Delta badge values (% change vs previous period)
$recDelta = ($lastMonthRecords ?? 0) > 0 ? round((($thisMonth ?? 0) - $lastMonthRecords) / $lastMonthRecords * 100) : (($thisMonth ?? 0) > 0 ? 100 : 0);
$visDelta = ($visitorsLastMonth ?? 0) > 0 ? round((($visitorsThisMonth ?? 0) - $visitorsLastMonth) / $visitorsLastMonth * 100) : (($visitorsThisMonth ?? 0) > 0 ? 100 : 0);
$schedThisWeek = count($scheduledBurials ?? []);
$schedDelta = ($schedLastWeek ?? 0) > 0 ? round(($schedThisWeek - $schedLastWeek) / $schedLastWeek * 100) : ($schedThisWeek > 0 ? 100 : 0);

// Header quick action — rendered by the shared topbar
$header_action = '<a href="add-record.php" class="header-action-btn"><i data-lucide="plus" width="15" height="15"></i> Quick Create</a>';
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
/* Dashboard v2 — mint green + white */
.admin-layout {
 background: var(--surface);
}

.admin-layout::after {
 display: none;
}



/* Stat cards — label + delta badge, big number, trend line */
.dv2-stats {
 display: grid;
 grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
 gap: 16px;
 margin-bottom: 20px;
}

.dv2-stat {
 background: var(--surface);
 border: 1px solid var(--glass-border);
 border-radius: 12px;
 padding: 18px 20px 16px;
}

.dv2-stat-head {
 display: flex;
 align-items: center;
 justify-content: space-between;
 gap: 8px;
 margin-bottom: 8px;
}

.dv2-stat-label {
 font-size: 0.78rem;
 font-weight: 500;
 color: var(--text-muted);
}

.dv2-delta {
 display: inline-flex;
 align-items: center;
 gap: 3px;
 padding: 2px 8px;
 border-radius: 999px;
 border: 1px solid var(--border-subtle);
 font-size: 0.68rem;
 font-weight: 600;
 font-variant-numeric: tabular-nums;
 white-space: nowrap;
}

.dv2-delta svg { width: 11px; height: 11px; }
.dv2-delta.up { color: #047857; }
.dv2-delta.down { color: #dc2626; }
.dv2-delta.flat { color: var(--text-muted); }

.dv2-stat-value {
 font-size: 1.7rem;
 font-weight: 700;
 color: var(--text-strong);
 letter-spacing: -0.02em;
 font-variant-numeric: tabular-nums;
 line-height: 1.15;
 margin-bottom: 8px;
}

.dv2-stat-trend {
 display: flex;
 align-items: center;
 gap: 5px;
 font-size: 0.76rem;
 font-weight: 600;
 color: var(--text-body);
}

.dv2-stat-trend svg { width: 13px; height: 13px; }
.dv2-stat-trend.up { color: #047857; }
.dv2-stat-trend.down { color: #dc2626; }

.dv2-stat-sub {
 font-size: 0.72rem;
 color: var(--text-muted);
 margin-top: 2px;
}

/* Chart card */
.dv2-chart-card {
 margin-bottom: 20px;
}

.dv2-chart-head {
 display: flex;
 align-items: flex-start;
 justify-content: space-between;
 gap: 16px;
 flex-wrap: wrap;
 margin-bottom: 6px;
}

.dv2-chart-head h3 {
 margin: 0 0 2px;
 font-size: 0.95rem;
 font-weight: 700;
 color: var(--text-strong);
}

.dv2-chart-head p {
 margin: 0;
 font-size: 0.76rem;
 color: var(--text-muted);
}

.dv2-range-tabs {
 display: inline-flex;
 gap: 2px;
 background: var(--bg-subtle);
 border: 1px solid var(--border-subtle);
 border-radius: 8px;
 padding: 3px;
}

.dv2-range-tabs button {
 border: none;
 background: transparent;
 padding: 5px 12px;
 border-radius: 6px;
 font-size: 0.75rem;
 font-weight: 500;
 color: var(--text-muted);
 cursor: pointer;
 font-family: 'Poppins', sans-serif;
 transition: background 0.12s ease, color 0.12s ease;
}

.dv2-range-tabs button:hover { color: var(--text-strong); }

.dv2-range-tabs button.active {
 background: var(--surface);
 color: var(--text-strong);
 font-weight: 600;
 box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}

.dv2-chart-wrap {
 position: relative;
 height: 260px;
}

/* Table card with tabs */
.dv2-table-card {
 padding: 0;
 overflow: hidden;
 margin-bottom: 20px;
}

.dv2-tabs {
 display: flex;
 align-items: center;
 justify-content: space-between;
 gap: 12px;
 padding: 12px 16px;
 border-bottom: 1px solid var(--border-subtle);
}

.dv2-tab-group {
 display: inline-flex;
 gap: 4px;
}

.dv2-tab {
 border: none;
 background: transparent;
 padding: 6px 14px;
 border-radius: 999px;
 font-size: 0.78rem;
 font-weight: 500;
 color: var(--text-muted);
 cursor: pointer;
 font-family: 'Poppins', sans-serif;
 transition: background 0.12s ease, color 0.12s ease;
}

.dv2-tab:hover { color: var(--text-strong); }

.dv2-tab.active {
 background: var(--bg-subtle);
 color: var(--text-strong);
 font-weight: 600;
}

.dv2-table-card .dv2-table {
 margin: 0;
}

.dv2-table-card .dv2-table th:first-child,
.dv2-table-card .dv2-table td:first-child {
 padding-left: 18px;
}

.dv2-table-card .dv2-table th:last-child,
.dv2-table-card .dv2-table td:last-child {
 padding-right: 18px;
}

.dv2-status {
 display: inline-flex;
 align-items: center;
 gap: 5px;
 padding: 2px 9px;
 border-radius: 999px;
 font-size: 0.68rem;
 font-weight: 600;
 border: 1px solid transparent;
}

.dv2-status::before {
 content: '';
 width: 6px;
 height: 6px;
 border-radius: 50%;
}

.dv2-status.done { color: #047857; border-color: rgba(5,150,105,0.3); }
.dv2-status.done::before { background: #10b981; }
.dv2-status.sched { color: #b45309; border-color: rgba(217,119,6,0.3); }
.dv2-status.sched::before { background: #f59e0b; }

.dv2-tab-link {
 color: #059669;
 font-size: 0.78rem;
 font-weight: 600;
 text-decoration: none;
 white-space: nowrap;
}

.dv2-tab-link:hover { text-decoration: underline; }

/* Cards */
.dv2-card {
 background: var(--surface);
 border: 1px solid var(--glass-border);
 border-radius: 12px;
 padding: 22px;
}

.dv2-card h3 {
 margin: 0 0 16px;
 font-size: 1rem;
 color: var(--text-strong);
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
 border-bottom: 1px solid var(--border-subtle);
 font-weight: 700;
}

.dv2-table td {
 padding: 12px;
 border-bottom: 1px solid var(--border-subtle);
 color: var(--text-body);
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
 color: var(--text-strong);
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
 background: var(--surface);
 border: 1px solid var(--glass-border);
 border-radius: 12px;
 padding: 16px;
 text-decoration: none;
 color: var(--text-strong);
 transition: background 0.15s ease, border-color 0.15s ease;
}

.dv2-qa:hover {
 border-color: #a7f3d0;
 background: var(--bg-subtle);
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
 color: var(--text-muted);
}

/* Mini calendar + schedule — single emerald accent, no rainbow */
.dv2-card-count {
 font-size: 0.78rem;
 color: var(--text-muted);
 font-weight: 600;
}

.dv2-cal-head {
 display: flex;
 align-items: center;
 justify-content: space-between;
 margin-bottom: 12px;
}

.dv2-cal-nav {
 padding: 4px 10px;
 border: 1px solid var(--glass-border);
 border-radius: 8px;
 color: #059669;
 font-weight: 700;
 text-decoration: none;
 transition: background 0.15s ease;
}

.dv2-cal-nav:hover {
 background: #ecfdf5;
}

.dv2-cal-month {
 color: var(--text-strong);
 font-size: 0.92rem;
}

.dv2-cal {
 width: 100%;
 border-collapse: collapse;
 table-layout: fixed;
}

.dv2-cal-dow {
 padding: 6px 2px;
 font-size: 0.62rem;
 text-transform: uppercase;
 color: #94a3b8;
 font-weight: 600;
 text-align: center;
 letter-spacing: 0.06em;
}

.dv2-cal-cell {
 min-height: 52px;
 border-radius: 8px;
 padding: 4px;
 border: 1px solid var(--border-subtle);
}

.dv2-cal-cell.has-burial {
 border-color: #a7f3d0;
 background: #f8fffb;
}

.dv2-cal-cell.is-today {
 border-color: #10b981;
 background: #ecfdf5;
}

.dv2-cal-num {
 font-size: 0.75rem;
 font-weight: 600;
 color: var(--text-muted);
 text-align: center;
}

.dv2-cal-num.is-today {
 color: #047857;
 font-weight: 800;
}

.dv2-cal-pill {
 font-size: 0.6rem;
 line-height: 1.2;
 margin-top: 2px;
 padding: 1px 4px;
 border-radius: 4px;
 overflow: hidden;
 text-overflow: ellipsis;
 white-space: nowrap;
}

.dv2-cal-pill.pend {
 background: var(--surface);
 border: 1px solid #6ee7b7;
 color: #047857;
}

.dv2-cal-pill.done {
 background: #d1fae5;
 color: #065f46;
}

.dv2-cal-legend {
 display: flex;
 gap: 14px;
 margin-top: 10px;
 font-size: 0.7rem;
 color: var(--text-muted);
}

.dv2-cal-dot {
 display: inline-block;
 width: 10px;
 height: 10px;
 border-radius: 3px;
 margin-right: 4px;
 vertical-align: -1px;
}

.dv2-cal-dot.pend { background: var(--surface); border: 1px solid #6ee7b7; }
.dv2-cal-dot.done { background: #d1fae5; }

.dv2-week-label {
 font-size: 0.72rem;
 font-weight: 700;
 color: var(--text-muted);
 text-transform: uppercase;
 letter-spacing: 0.06em;
 margin-bottom: 10px;
}

.dv2-week-row {
 display: flex;
 align-items: center;
 justify-content: space-between;
 gap: 10px;
 padding: 10px 12px;
 border: 1px solid var(--border-subtle);
 border-radius: 10px;
 margin-bottom: 8px;
 background: var(--surface);
}

.dv2-week-name {
 font-weight: 600;
 font-size: 0.88rem;
 color: var(--text-strong);
}

.dv2-week-meta {
 font-size: 0.76rem;
 color: var(--text-muted);
}

.dv2-done-btn {
 padding: 6px 16px;
 background: #059669;
 color: #fff;
 border: none;
 border-radius: 8px;
 font-weight: 600;
 font-size: 0.78rem;
 cursor: pointer;
 transition: background 0.15s ease;
 flex-shrink: 0;
 font-family: 'Poppins', sans-serif;
}

.dv2-done-btn:hover {
 background: #047857;
}

/* Dark mode — dashboard components */
html[data-theme="dark"] .dv2-card,
html[data-theme="dark"] .dv2-qa {
 background: #1e293b;
 border-color: var(--text-body);
}

html[data-theme="dark"] .dv2-qa:hover {
 border-color: rgba(52, 211, 153, 0.4);
 background: #16202f;
}

html[data-theme="dark"] .dv2-card h3,
html[data-theme="dark"] .dv2-qa-title-text,
html[data-theme="dark"] .dv2-cal-month,
html[data-theme="dark"] .dv2-week-name {
 color: #f1f5f9;
}

html[data-theme="dark"] .dv2-table td {
 color: #cbd5e1;
 border-bottom-color: #24344d;
}

html[data-theme="dark"] .dv2-table th {
 color: #94a3b8;
 border-bottom-color: #24344d;
}

html[data-theme="dark"] .dv2-card a,
html[data-theme="dark"] .dv2-cal-nav {
 color: #34d399;
}

html[data-theme="dark"] .dv2-cal-nav {
 border-color: var(--text-body);
}

html[data-theme="dark"] .dv2-cal-nav:hover {
 background: rgba(16, 185, 129, 0.12);
}

html[data-theme="dark"] .dv2-cal-cell {
 border-color: #24344d;
}

html[data-theme="dark"] .dv2-cal-cell.has-burial {
 border-color: rgba(52, 211, 153, 0.35);
 background: rgba(16, 185, 129, 0.08);
}

html[data-theme="dark"] .dv2-cal-cell.is-today {
 border-color: #10b981;
 background: rgba(16, 185, 129, 0.14);
}

html[data-theme="dark"] .dv2-cal-num {
 color: #94a3b8;
}

html[data-theme="dark"] .dv2-cal-num.is-today {
 color: #6ee7b7;
}

html[data-theme="dark"] .dv2-cal-pill.pend {
 background: transparent;
 border-color: rgba(52, 211, 153, 0.45);
 color: #6ee7b7;
}

html[data-theme="dark"] .dv2-cal-pill.done {
 background: rgba(16, 185, 129, 0.2);
 color: #a7f3d0;
}

html[data-theme="dark"] .dv2-cal-dot.pend {
 background: transparent;
 border-color: rgba(52, 211, 153, 0.45);
}

html[data-theme="dark"] .dv2-cal-dot.done {
 background: rgba(16, 185, 129, 0.4);
}

html[data-theme="dark"] .dv2-card-count,
html[data-theme="dark"] .dv2-cal-legend,
html[data-theme="dark"] .dv2-week-label,
html[data-theme="dark"] .dv2-week-meta,
html[data-theme="dark"] .dv2-qa-desc,
html[data-theme="dark"] .dv2-empty {
 color: var(--text-muted);
}

html[data-theme="dark"] .dv2-week-row {
 background: #16202f;
 border-color: #24344d;
}

html[data-theme="dark"] .dv2-qa-icon,
html[data-theme="dark"] .dv2-card h3 svg,
html[data-theme="dark"] .dv2-qa-title svg {
 background: rgba(16, 185, 129, 0.14);
 color: #34d399;
}

html[data-theme="dark"] .dv2-delta.up,
html[data-theme="dark"] .dv2-stat-trend.up {
 color: #34d399;
}

html[data-theme="dark"] .dv2-delta.down,
html[data-theme="dark"] .dv2-stat-trend.down {
 color: #f87171;
}

html[data-theme="dark"] .dv2-range-tabs button.active {
 box-shadow: none;
}

html[data-theme="dark"] .dv2-status.done { color: #34d399; border-color: rgba(52,211,153,0.3); }
html[data-theme="dark"] .dv2-status.sched { color: #fbbf24; border-color: rgba(251,191,36,0.3); }
html[data-theme="dark"] .dv2-tab-link { color: #34d399; }

@media (max-width: 900px) {
 .admin-main {
 padding: 20px;
 }
 .burial-schedule-grid {
 grid-template-columns: 1fr !important;
 }
 .dv2-chart-wrap {
 height: 210px;
 }
 .dv2-chart-head {
 flex-direction: column;
 }
}
</style>

<div class="dv2">
 <?php
 $arrowUp = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17L17 7M7 7h10v10"/></svg>';
 $arrowDown = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7l10 10M17 7v10H7"/></svg>';
 $statCards = [
     ['Total Records', (int)($totalRecords ?? 0), $recDelta, ($thisMonth ?? 0) . ' added this month', 'All burial records on file'],
     ['Available Plots', (int)($availablePlots ?? 0), null, 'Open plots right now', 'Ready for reservation'],
     ['Active Visitors', (int)($totalVisitors ?? 0), $visDelta, ($visitorsThisMonth ?? 0) . ' joined this month', 'Registered portal accounts'],
     ['Scheduled This Week', $schedThisWeek, $schedDelta, ($schedLastWeek ?? 0) . ' scheduled last week', 'Pending burials Mon-Sun'],
 ];
 ?>
 <div class="dv2-stats">
 <?php foreach ($statCards as $sc):
     $deltaCls = $sc[2] === null ? 'flat' : ($sc[2] >= 0 ? 'up' : 'down');
     $deltaIcon = $sc[2] === null ? '' : ($sc[2] >= 0 ? $arrowUp : $arrowDown);
 ?>
 <div class="dv2-stat">
 <div class="dv2-stat-head">
 <span class="dv2-stat-label"><?php echo $sc[0]; ?></span>
 <?php if ($sc[2] !== null): ?>
 <span class="dv2-delta <?php echo $deltaCls; ?>"><?php echo $deltaIcon; ?><?php echo ($sc[2] >= 0 ? '+' : '') . $sc[2]; ?>%</span>
 <?php endif; ?>
 </div>
 <div class="dv2-stat-value"><?php echo number_format($sc[1]); ?></div>
 <div class="dv2-stat-trend <?php echo $deltaCls; ?>"><?php echo $deltaIcon; ?><?php echo $sc[3]; ?></div>
 <div class="dv2-stat-sub"><?php echo $sc[4]; ?></div>
 </div>
 <?php endforeach; ?>
 </div>

 <div class="dv2-card dv2-chart-card">
 <div class="dv2-chart-head">
 <div>
 <h3>Burial Records</h3>
 <p>Records added over time</p>
 </div>
 <div class="dv2-range-tabs" id="chartRangeTabs">
 <button type="button" data-range="90" class="active">Last 3 months</button>
 <button type="button" data-range="30">Last 30 days</button>
 <button type="button" data-range="7">Last 7 days</button>
 </div>
 </div>
 <div class="dv2-chart-wrap"><canvas id="burialChart"></canvas></div>
 </div>

 <div class="dv2-card dv2-table-card">
 <div class="dv2-tabs">
 <div class="dv2-tab-group">
 <button type="button" class="dv2-tab active" data-tab="recent">Recent Records</button>
 <button type="button" class="dv2-tab" data-tab="barangay">By Barangay</button>
 </div>
 <a href="records.php" class="dv2-tab-link">View all records &rarr;</a>
 </div>

 <div class="dv2-tab-pane" data-pane="recent">
 <table class="dv2-table">
 <thead>
 <tr>
 <th>Name</th>
 <th>Plot</th>
 <th>Barangay</th>
 <th>Date Added</th>
 <th>Status</th>
 </tr>
 </thead>
 <tbody>
 <?php if (empty($recentRecords)): ?>
 <tr>
 <td colspan="5" class="dv2-empty">No records found</td>
 </tr>
 <?php else: ?>
 <?php foreach ($recentRecords as $record): ?>
 <tr>
 <td><?php echo htmlspecialchars($record['decedent_name'], ENT_QUOTES, 'UTF-8'); ?></td>
 <td><?php echo htmlspecialchars($record['plot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
 <td><?php echo htmlspecialchars($record['barangay'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
 <td><?php echo date('M d, Y', strtotime($record['date_added'])); ?></td>
 <td><span class="dv2-status <?php echo ($record['is_buried'] ?? 0) == 1 ? 'done' : 'sched'; ?>"><?php echo ($record['is_buried'] ?? 0) == 1 ? 'Buried' : 'Scheduled'; ?></span></td>
 </tr>
 <?php endforeach; ?>
 <?php endif; ?>
 </tbody>
 </table>
 </div>

 <div class="dv2-tab-pane" data-pane="barangay" style="display:none;">
 <table class="dv2-table">
 <thead>
 <tr>
 <th>Barangay</th>
 <th>Records</th>
 <th>Share</th>
 </tr>
 </thead>
 <tbody>
 <?php if (empty($byBarangay)): ?>
 <tr>
 <td colspan="3" class="dv2-empty">No data available</td>
 </tr>
 <?php else: ?>
 <?php
 $brgyTotal = max(1, (int)($totalRecords ?? 0));
 foreach ($byBarangay as $item): $pct = round($item['count'] / $brgyTotal * 100, 1);
 ?>
 <tr>
 <td><?php echo htmlspecialchars($item['barangay'], ENT_QUOTES, 'UTF-8'); ?></td>
 <td><?php echo number_format($item['count']); ?></td>
 <td>
 <div style="display:flex; align-items:center; gap:8px;">
 <div style="flex:1; max-width:140px; height:6px; border-radius:999px; background:var(--border-subtle); overflow:hidden;">
 <div style="height:100%; width:<?php echo $pct; ?>%; background:#059669; border-radius:999px;"></div>
 </div>
 <span style="font-size:0.72rem; color:var(--text-muted); font-variant-numeric:tabular-nums;"><?php echo $pct; ?>%</span>
 </div>
 </td>
 </tr>
 <?php endforeach; ?>
 <?php endif; ?>
 </tbody>
 </table>
 </div>
 </div>

  <div class="dv2-card" style="margin-bottom: 24px;">
 <h3>
 <span>
 <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
 Burial Schedule
 </span>
 <span class="dv2-card-count"><?php echo count($scheduledBurials ?? []); ?> scheduled this week</span>
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
 <div class="dv2-cal-head">
 <a href="?cal_month=<?php echo $prevM; ?>&cal_year=<?php echo $prevY; ?>" class="dv2-cal-nav">&lsaquo;</a>
 <strong class="dv2-cal-month"><?php echo $monthName; ?></strong>
 <a href="?cal_month=<?php echo $nextM; ?>&cal_year=<?php echo $nextY; ?>" class="dv2-cal-nav">&rsaquo;</a>
 </div>
 <table class="dv2-cal">
 <thead>
 <tr>
 <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
 <th class="dv2-cal-dow"><?php echo $d; ?></th>
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
 echo '<td style="padding:2px;"></td>';
 continue;
 }
 $hasBurial = isset($calendarBurials[$dayNum]);
 $isToday = $isCurrentMonth && $dayNum === $today;
 $cellCls = 'dv2-cal-cell' . ($hasBurial ? ' has-burial' : '') . ($isToday ? ' is-today' : '');
 $numCls = 'dv2-cal-num' . ($isToday ? ' is-today' : '');
 echo '<td style="padding:2px; vertical-align:top;">';
 echo '<div class="' . $cellCls . '">';
 echo '<div class="' . $numCls . '">' . $dayNum . '</div>';
 if ($hasBurial) {
 foreach ($calendarBurials[$dayNum] as $cb) {
 $time = $cb['burial_time'] ? date('g:i A', strtotime($cb['burial_time'])) : '';
 $done = $cb['is_buried'] == 1;
 echo '<div class="dv2-cal-pill ' . ($done ? 'done' : 'pend') . '" title="' . htmlspecialchars($cb['decedent_name'] . ($time ? ' @ ' . $time : ''), ENT_QUOTES) . '">' . htmlspecialchars($cb['decedent_name']) . ($time ? ' ' . $time : '') . '</div>';
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
 <div class="dv2-cal-legend">
 <span><span class="dv2-cal-dot pend"></span> Scheduled</span>
 <span><span class="dv2-cal-dot done"></span> Done</span>
 </div>
 </div>

 <!-- This Week list -->
 <div>
 <div class="dv2-week-label">This Week</div>
 <?php if (empty($scheduledBurials)): ?>
 <div class="dv2-empty">No burials scheduled this week</div>
 <?php else: ?>
 <?php foreach ($scheduledBurials as $b): ?>
 <div id="burial-row-<?php echo (int)$b['id']; ?>" class="dv2-week-row">
 <div style="min-width:0;">
 <div class="dv2-week-name"><?php echo htmlspecialchars($b['decedent_name'], ENT_QUOTES, 'UTF-8'); ?></div>
 <div class="dv2-week-meta">
 <?php echo date('D, M d', strtotime($b['burial_date'])); ?>
 <?php echo $b['burial_time'] ? ' @ ' . date('g:i A', strtotime($b['burial_time'])) : ''; ?>
 · Plot <?php echo htmlspecialchars($b['plot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
 · <?php echo htmlspecialchars($b['barangay'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
 </div>
 </div>
 <button type="button" onclick="markBurialDone(<?php echo (int)$b['id']; ?>, this)" class="dv2-done-btn">DONE</button>
 </div>
 <?php endforeach; ?>
 <?php endif; ?>
 </div>
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
 <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
 <script>
 const burialSeries = <?php echo json_encode($burialSeries ?? []); ?>;

 // Build a contiguous day list for the last N days, zero-filling gaps
 function seriesFor(days) {
 const labels = [], data = [];
 for (let i = days - 1; i >= 0; i--) {
 const d = new Date();
 d.setDate(d.getDate() - i);
 const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
 data.push(burialSeries[key] || 0);
 labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
 }
 return { labels, data };
 }

 const css = v => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
 let burialChart = null;

 function renderBurialChart(days) {
 const { labels, data } = seriesFor(days);
 const dark = document.documentElement.getAttribute('data-theme') === 'dark';
 const grid = dark ? 'rgba(148,163,184,0.12)' : 'rgba(15,23,42,0.06)';
 const tick = dark ? '#94a3b8' : '#94a3b8';
 const line = '#059669';
 const ctx = document.getElementById('burialChart').getContext('2d');
 const fill = ctx.createLinearGradient(0, 0, 0, 240);
 fill.addColorStop(0, 'rgba(5,150,105,0.18)');
 fill.addColorStop(1, 'rgba(5,150,105,0)');
 if (burialChart) burialChart.destroy();
 burialChart = new Chart(ctx, {
 type: 'line',
 data: { labels, datasets: [{ data, borderColor: line, backgroundColor: fill, fill: true, tension: 0.35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, pointBackgroundColor: line }] },
 options: {
 responsive: true,
 maintainAspectRatio: false,
 interaction: { mode: 'index', intersect: false },
 plugins: {
 legend: { display: false },
 tooltip: {
 backgroundColor: dark ? '#1e293b' : '#0f172a',
 titleColor: '#fff', bodyColor: '#e2e8f0',
 padding: 10, cornerRadius: 8, displayColors: false,
 callbacks: { label: c => c.parsed.y + ' record' + (c.parsed.y === 1 ? '' : 's') }
 }
 },
 scales: {
 x: { grid: { display: false }, border: { display: false }, ticks: { color: tick, font: { family: 'Poppins', size: 10 }, maxTicksLimit: 8, maxRotation: 0 } },
 y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { color: tick, font: { family: 'Poppins', size: 10 }, precision: 0, maxTicksLimit: 5 } }
 }
 }
 });
 }

 let chartRange = 90;
 renderBurialChart(chartRange);
 document.querySelectorAll('#chartRangeTabs button').forEach(btn => {
 btn.addEventListener('click', () => {
 document.querySelectorAll('#chartRangeTabs button').forEach(b => b.classList.remove('active'));
 btn.classList.add('active');
 chartRange = parseInt(btn.dataset.range, 10);
 renderBurialChart(chartRange);
 });
 });

 // Re-render on theme toggle so grid/tick colors follow
 const themeObserver = new MutationObserver(() => renderBurialChart(chartRange));
 themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

 // Table card tabs
 document.querySelectorAll('.dv2-tab').forEach(tab => {
 tab.addEventListener('click', () => {
 document.querySelectorAll('.dv2-tab').forEach(t => t.classList.remove('active'));
 tab.classList.add('active');
 document.querySelectorAll('.dv2-tab-pane').forEach(p => {
 p.style.display = p.dataset.pane === tab.dataset.tab ? '' : 'none';
 });
 });
 });

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
