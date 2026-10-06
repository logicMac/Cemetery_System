<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';
require_once 'includes/sidebar.php';

// Calendar navigation
$calMonth = isset($_GET['cal_month']) ? (int)$_GET['cal_month'] : (int)date('n');
$calYear = isset($_GET['cal_year']) ? (int)$_GET['cal_year'] : (int)date('Y');
if ($calMonth < 1 || $calMonth > 12) { $calMonth = (int)date('n'); }
if ($calYear < 2000 || $calYear > 2100) { $calYear = (int)date('Y'); }

// Fetch scheduled burials for the selected month
try {
 $calStmt = $pdo->prepare("
 SELECT id, decedent_name, plot_number, barangay, burial_date, burial_time, is_buried
 FROM burial_records
 WHERE burial_date IS NOT NULL
 AND MONTH(burial_date) = ? AND YEAR(burial_date) = ?
 ORDER BY burial_date ASC, burial_time ASC
 ");
 $calStmt->execute([$calMonth, $calYear]);
 $calendarBurials = [];
 $all = $calStmt->fetchAll();
 foreach ($all as $row) {
 $day = (int)date('j', strtotime($row['burial_date']));
 $calendarBurials[$day][] = $row;
 }

 $completed = 0;
 $upcoming = 0;
 foreach ($all as $r) {
 if ($r['is_buried'] == 1) $completed++;
 else $upcoming++;
 }
 $totalEvents = array_sum(array_map('count', $calendarBurials));

 // Upcoming burials (next 7 days)
 $upcomingStmt = $pdo->query("
 SELECT id, decedent_name, plot_number, barangay, burial_date, burial_time, is_buried
 FROM burial_records
 WHERE burial_date IS NOT NULL
 AND is_buried = 0
 AND burial_date >= CURDATE()
 AND burial_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
 ORDER BY burial_date ASC, burial_time ASC
 ");
 $upcomingBurials = $upcomingStmt->fetchAll();

} catch (PDOException $e) {
 $calendarBurials = [];
 $completed = 0;
 $upcoming = 0;
 $totalEvents = 0;
 $upcomingBurials = [];
 error_log('Burial calendar error: ' . $e->getMessage());
}

$prevM = $calMonth - 1; $prevY = $calYear; if ($prevM < 1) { $prevM = 12; $prevY--; }
$nextM = $calMonth + 1; $nextY = $calYear; if ($nextM > 12) { $nextM = 1; $nextY++; }
$firstDay = mktime(0, 0, 0, $calMonth, 1, $calYear);
$daysInMonth = (int)date('t', $firstDay);
$startDow = (int)date('N', $firstDay);
$monthName = date('F Y', $firstDay);
$today = (int)date('j');
$isCurrentMonth = ($calMonth === (int)date('n') && $calYear === (int)date('Y'));
?>

<style>
 /* ── Full-width, no padding ── */
 .admin-layout { background: var(--bg-subtle) !important; }
 .admin-layout::after { display: none !important; }
 .admin-layout > .admin-main {
 padding: 84px 0 0 !important;
 max-width: none;
 min-width: 0;
 }

 /* ── Page ── */
 .cal-page { width: 100%; max-width: none; box-sizing: border-box; }
 .cal-grid { background: var(--surface); width: 100%; }
 .cal-bottom { display: flex; width: 100%; }

 /* ── Header ── */
 .cal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; background: var(--surface); border-bottom: 1px solid var(--glass-border); }
 .cal-header h1 { font-size: 1.3rem; font-weight: 800; color: var(--text-strong); margin: 0; display: flex; align-items: center; gap: 8px; }
 .cal-header h1 i { width: 22px; height: 22px; color: #10b981; }
 .cal-header p { color: var(--text-muted); font-size: 0.78rem; margin: 2px 0 0; }

 /* ── Toolbar ── */
 .cal-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 12px 24px; background: var(--surface); border-bottom: 1px solid var(--glass-border); }
 .cal-toolbar-left { display: flex; align-items: center; gap: 8px; }
 .cal-toolbar-right { display: flex; align-items: center; gap: 14px; }

 .cal-month-title { font-size: 1.1rem; font-weight: 800; color: var(--text-strong); }
 .cal-month-sub { font-size: 0.72rem; color: #94a3b8; font-weight: 500; }
 .cal-month-sub .live { display: inline-flex; align-items: center; gap: 4px; color: #10b981; font-weight: 600; }
 .cal-month-sub .live::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: #10b981; display: inline-block; }

 .cal-nav-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--glass-border); color: var(--text-body); font-weight: 700; text-decoration: none; transition: all 0.2s; background: var(--surface); }
 .cal-nav-btn:hover { background: #f0fdf4; border-color: #d1fae5; color: #059669; }
 .cal-today-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; border: 1px solid var(--glass-border); color: var(--text-body); font-size: 0.75rem; font-weight: 600; text-decoration: none; transition: all 0.2s; background: var(--surface); }
 .cal-today-btn:hover { background: #f0fdf4; border-color: #d1fae5; color: #059669; }

 .cal-legend { display: flex; align-items: center; gap: 12px; }
 .cal-legend span { display: flex; align-items: center; gap: 5px; font-size: 0.7rem; color: var(--text-muted); font-weight: 500; }
 .cal-legend .swatch { width: 9px; height: 9px; border-radius: 3px; }

 /* ── Calendar ── */
 .cal-row { display: grid; grid-template-columns: repeat(7, 1fr); border-bottom: 1px solid var(--border-subtle); }
 .cal-row:last-child { border-bottom: none; }
 .cal-row.weekdays { border-bottom: 1px solid var(--glass-border); }
 .cal-row.weekdays .cal-cell { height: auto; padding: 10px 8px; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.08em; text-align: center; border-right: none; background: var(--bg-subtle); cursor: default; }
 .cal-row.weekdays .cal-cell:hover { background: var(--bg-subtle); }

 .cal-cell { min-height: 110px; padding: 8px 10px; font-size: 0.82rem; border-right: 1px solid var(--border-subtle); transition: background 0.15s; cursor: default; position: relative; overflow: hidden; }
 .cal-cell:last-child { border-right: none; }
 .cal-cell:hover { background: var(--bg-subtle); }
 .cal-cell.clickable { cursor: pointer; }
 .cal-cell.clickable:hover { background: #f0fdf4; }
 .cal-cell.empty { background: var(--bg-subtle); }
 .cal-cell.today { background: #f0fdf4 !important; }
 .cal-cell.today .cal-num { color: #fff; background: #10b981; }
 .cal-cell.has-events .cal-num { color: #b45309; font-weight: 700; }
 .cal-cell.completed { background: var(--bg-subtle); }
 .cal-cell.completed .cal-num { color: #047857; font-weight: 700; }

 .cal-num { font-weight: 600; color: var(--text-body); font-size: 0.82rem; display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; }

 /* Event pills */
 .cal-events { margin-top: 4px; display: flex; flex-direction: column; gap: 2px; }
 .cal-ev { font-size: 0.6rem; font-weight: 600; padding: 2px 6px; border-radius: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4; }
 .cal-ev.pend { background: #fef3c7; color: #92400e; }
 .cal-ev.done { background: #dcfce7; color: #166534; }
 .cal-more { font-size: 0.55rem; color: #94a3b8; font-weight: 600; padding-left: 2px; }

 /* ── Bottom section ── */
 .cal-upcoming { flex: 1; background: var(--surface); border-top: 1px solid var(--glass-border); padding: 20px 24px; }
 .cal-upcoming h3 { font-size: 0.75rem; font-weight: 700; color: var(--text-body); text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
 .cal-upcoming h3 i { width: 15px; height: 15px; color: #10b981; }

 .upcoming-scroll { max-height: 260px; overflow-y: auto; padding-right: 4px; }
 .up-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; border: 1px solid var(--border-subtle); background: var(--surface); margin-bottom: 8px; transition: all 0.2s; }
 .up-item:hover { border-color: #d1fae5; box-shadow: 0 2px 8px rgba(16,185,129,0.06); }
 .up-item.urgent { border-color: #fde68a; background: #fffbeb; }
 .up-date { flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f0fdf4; border: 1px solid #d1fae5; }
 .up-date .d { font-size: 1rem; font-weight: 800; color: #059669; line-height: 1; }
 .up-date .m { font-size: 0.52rem; text-transform: uppercase; color: #10b981; font-weight: 700; margin-top: 2px; }
 .up-item.urgent .up-date { background: #fffbeb; border-color: #fde68a; }
 .up-item.urgent .up-date .d { color: #b45309; }
 .up-item.urgent .up-date .m { color: #d97706; }

 .cal-actions { width: 280px; flex-shrink: 0; background: var(--surface); border-top: 1px solid var(--glass-border); border-left: 1px solid var(--glass-border); padding: 20px; display: flex; flex-direction: column; gap: 8px; }
 .cal-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; text-decoration: none; transition: all 0.2s; }
 .cal-btn.primary { background: #10b981; color: #fff; }
 .cal-btn.primary:hover { background: #059669; }
 .cal-btn.secondary { background: var(--surface); color: var(--text-body); border: 1px solid var(--glass-border); }
 .cal-btn.secondary:hover { border-color: #d1fae5; background: #f0fdf4; color: #059669; }

 /* ── Modal ── */
 .day-modal { position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; padding: 20px; }
 .day-modal.active { display: flex; }
 .day-modal-card { background: var(--surface); border-radius: 18px; max-width: 480px; width: 100%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
 .dm-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border-radius: 12px; border: 1px solid var(--border-subtle); margin-bottom: 10px; }
 .dm-item.done { border-color: #bbf7d0; background: #f0fdf4; }
 .dm-item.pend { border-color: #fde68a; background: #fffbeb; }
 .dm-badge { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
 .dm-badge.done { background: #10b981; }
 .dm-badge.pend { background: #f59e0b; }
 .dm-status { font-size: 0.62rem; font-weight: 700; padding: 3px 8px; border-radius: 5px; flex-shrink: 0; }
 .dm-status.done { color: #059669; background: #dcfce7; }
 .dm-status.pend { color: #b45309; background: #fef3c7; }

 /* ── Mobile ── */
 @media (max-width: 900px) {
 .cal-cell { min-height: 70px; padding: 4px 6px; font-size: 0.72rem; }
 .cal-ev { font-size: 0.52rem; padding: 1px 4px; }
 .cal-bottom { flex-direction: column; }
 .cal-actions { width: 100%; border-left: none; }
 }

 /* ── Dark mode ── */
 html[data-theme="dark"] .cal-nav-btn:hover,
 html[data-theme="dark"] .cal-today-btn:hover,
 html[data-theme="dark"] .cal-cell.clickable:hover,
 html[data-theme="dark"] .cal-btn.secondary:hover {
 background: rgba(16, 185, 129, 0.12);
 border-color: rgba(52, 211, 153, 0.4);
 color: #34d399;
 }
 html[data-theme="dark"] .cal-cell.today { background: rgba(16, 185, 129, 0.14) !important; }
 html[data-theme="dark"] .cal-cell.has-events .cal-num { color: #fbbf24; }
 html[data-theme="dark"] .cal-cell.completed .cal-num { color: #34d399; }
 html[data-theme="dark"] .cal-ev.pend { background: rgba(245, 158, 11, 0.18); color: #fcd34d; }
 html[data-theme="dark"] .cal-ev.done { background: rgba(16, 185, 129, 0.2); color: #a7f3d0; }
 html[data-theme="dark"] .up-item:hover { border-color: rgba(52, 211, 153, 0.4); box-shadow: none; }
 html[data-theme="dark"] .up-item.urgent { border-color: rgba(251, 191, 36, 0.35); background: rgba(245, 158, 11, 0.08); }
 html[data-theme="dark"] .up-date { background: rgba(16, 185, 129, 0.14); border-color: rgba(52, 211, 153, 0.3); }
 html[data-theme="dark"] .up-date .d { color: #34d399; }
 html[data-theme="dark"] .up-date .m { color: #34d399; }
 html[data-theme="dark"] .up-item.urgent .up-date { background: rgba(245, 158, 11, 0.14); border-color: rgba(251, 191, 36, 0.35); }
 html[data-theme="dark"] .up-item.urgent .up-date .d { color: #fbbf24; }
 html[data-theme="dark"] .up-item.urgent .up-date .m { color: #fbbf24; }
 html[data-theme="dark"] .up-name { color: #f1f5f9; }
 html[data-theme="dark"] .up-meta { color: #64748b; }
 html[data-theme="dark"] .dm-item.done { border-color: rgba(52, 211, 153, 0.35); background: rgba(16, 185, 129, 0.1); }
 html[data-theme="dark"] .dm-item.pend { border-color: rgba(251, 191, 36, 0.35); background: rgba(245, 158, 11, 0.08); }
 html[data-theme="dark"] .dm-status.done { color: #a7f3d0; background: rgba(16, 185, 129, 0.2); }
 html[data-theme="dark"] .dm-status.pend { color: #fcd34d; background: rgba(245, 158, 11, 0.15); }
 html[data-theme="dark"] .day-modal { background: rgba(2, 6, 23, 0.65); }
</style>

<div class="cal-page">

 <!-- Header -->
 <div class="cal-header">
 <div>
 <h1><i data-lucide="calendar-days"></i> Burial Calendar</h1>
 <p>View and manage scheduled burial ceremonies</p>
 </div>
 <a href="?cal_month=<?php echo (int)date('n'); ?>&cal_year=<?php echo (int)date('Y'); ?>" class="cal-today-btn">
 <i data-lucide="calendar-check" style="width:13px;height:13px;"></i> Today
 </a>
 </div>

 <!-- Toolbar -->
 <div class="cal-toolbar">
 <div class="cal-toolbar-left">
 <a href="?cal_month=<?php echo $prevM; ?>&cal_year=<?php echo $prevY; ?>" class="cal-nav-btn">
 <i data-lucide="chevron-left" style="width:15px;height:15px;"></i>
 </a>
 <div>
 <div class="cal-month-title"><?php echo $monthName; ?></div>
 <div class="cal-month-sub">
 <?php echo $totalEvents; ?> burial<?php echo $totalEvents !== 1 ? 's' : ''; ?>
 <?php if ($isCurrentMonth): ?><span class="live">This month</span><?php endif; ?>
 </div>
 </div>
 <a href="?cal_month=<?php echo $nextM; ?>&cal_year=<?php echo $nextY; ?>" class="cal-nav-btn">
 <i data-lucide="chevron-right" style="width:15px;height:15px;"></i>
 </a>
 </div>
 <div class="cal-toolbar-right">
 <div class="cal-legend">
 <span><span class="swatch" style="background:#10b981;"></span> Today</span>
 <span><span class="swatch" style="background:#f59e0b;"></span> Pending</span>
 <span><span class="swatch" style="background:#059669;"></span> Completed</span>
 <span><span class="swatch" style="background:#fef3c7; border:1px solid #fde68a;"></span> Scheduled</span>
 </div>
 </div>
 </div>

 <!-- Calendar grid — CSS grid, fills full width -->
 <div class="cal-grid">
 <!-- Weekday headers -->
 <div class="cal-row weekdays">
 <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
 <div class="cal-cell"><?php echo $d; ?></div>
 <?php endforeach; ?>
 </div>

 <!-- Day cells -->
 <?php
 $dayNum = 1;
 for ($w = 0; $w < 6 && $dayNum <= $daysInMonth; $w++) {
 echo '<div class="cal-row">';
 for ($d = 1; $d <= 7; $d++) {
 if (($w === 0 && $d < $startDow) || $dayNum > $daysInMonth) {
 echo '<div class="cal-cell empty"></div>';
 continue;
 }
 $events = $calendarBurials[$dayNum] ?? [];
 $hasEvents = count($events) > 0;
 $isToday = $isCurrentMonth && $dayNum === $today;
 $allDone = $hasEvents && count(array_filter($events, fn($e) => $e['is_buried'] == 1)) === count($events);

 $cls = 'cal-cell';
 if ($isToday) $cls .= ' today';
 if ($hasEvents) { $cls .= ' clickable'; $cls .= $allDone ? ' completed' : ' has-events'; }

 echo '<div class="' . $cls . '" onclick="' . ($hasEvents ? 'openDayDetail(' . $dayNum . ')' : '') . '">';
 echo '<span class="cal-num">' . $dayNum . '</span>';
 if ($hasEvents) {
 $maxShow = 3;
 echo '<div class="cal-events">';
 foreach (array_slice($events, 0, $maxShow) as $e) {
 $done = $e['is_buried'] == 1;
 $name = htmlspecialchars($e['decedent_name'], ENT_QUOTES, 'UTF-8');
 $time = $e['burial_time'] ? ' · ' . date('g:i A', strtotime($e['burial_time'])) : '';
 echo '<div class="cal-ev ' . ($done ? 'done' : 'pend') . '">' . $name . $time . '</div>';
 }
 if (count($events) > $maxShow) {
 echo '<div class="cal-more">+' . (count($events) - $maxShow) . ' more</div>';
 }
 echo '</div>';
 }
 echo '</div>';
 $dayNum++;
 }
 echo '</div>';
 }
 ?>
 </div>

 <!-- Bottom: upcoming + actions -->
 <div class="cal-bottom">
 <div class="cal-upcoming">
 <h3><i data-lucide="clock"></i> Upcoming (next 7 days)</h3>
 <?php if (empty($upcomingBurials)): ?>
 <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
 <i data-lucide="calendar-x" style="width:28px;height:28px;color:#cbd5e1;margin:0 auto 10px;display:block;"></i>
 <p style="font-size:0.82rem;margin:0;">No burials scheduled in the next 7 days</p>
 </div>
 <?php else: ?>
 <div class="upcoming-scroll">
 <?php foreach ($upcomingBurials as $b):
 $bDate = strtotime($b['burial_date']);
 $bDay = date('j', $bDate);
 $bMon = date('M', $bDate);
 $daysUntil = ceil(($bDate - strtotime('today')) / 86400);
 $isUrgent = $daysUntil <= 1;
 ?>
 <div class="up-item <?php echo $isUrgent ? 'urgent' : ''; ?>">
 <div class="up-date">
 <div class="d"><?php echo $bDay; ?></div>
 <div class="m"><?php echo $bMon; ?></div>
 </div>
 <div style="flex:1; min-width:0;">
 <div style="font-weight:700;font-size:0.85rem;color: var(--text-strong);margin-bottom:2px;">
 <?php echo htmlspecialchars($b['decedent_name'], ENT_QUOTES, 'UTF-8'); ?>
 </div>
 <div style="font-size:0.75rem;color: var(--text-muted);">
 <?php if ($b['burial_time']): ?>
 <?php echo date('g:i A', strtotime($b['burial_time'])); ?> ·
 <?php endif; ?>
 Plot <?php echo htmlspecialchars($b['plot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
 </div>
 <?php if ($isUrgent): ?>
 <span style="display:inline-flex;align-items:center;gap:3px;font-size:0.6rem;font-weight:700;color:#b45309;background:#fef3c7;padding:2px 8px;border-radius:4px;margin-top:4px;">
 <i data-lucide="alert-circle" style="width:10px;height:10px;"></i>
 <?php echo $daysUntil === 0 ? 'Today' : 'Tomorrow'; ?>
 </span>
 <?php endif; ?>
 </div>
 </div>
 <?php endforeach; ?>
 </div>
 <?php endif; ?>
 </div>

 <div class="cal-actions">
 <a href="records.php" class="cal-btn primary">
 <i data-lucide="file-plus" style="width:14px;height:14px;"></i>
 Schedule a Burial
 </a>
 <a href="records.php" class="cal-btn secondary">
 <i data-lucide="list" style="width:14px;height:14px;"></i>
 View All Records
 </a>
 <a href="map-view.php" class="cal-btn secondary">
 <i data-lucide="map" style="width:14px;height:14px;"></i>
 Open Cemetery Map
 </a>
 </div>
 </div>

</div>
</main>
</div>

<!-- Day detail modal -->
<div class="day-modal" id="dayModal" onclick="if(event.target===this) closeDayDetail()">
 <div class="day-modal-card">
 <div style="padding:20px 24px;border-bottom: 1px solid var(--border-subtle);display:flex;align-items:center;justify-content:space-between;">
 <h3 id="dayModalTitle" style="font-size:1.05rem;font-weight:800;color: var(--text-strong);margin:0;">Burials</h3>
 <button onclick="closeDayDetail()" style="width:32px;height:32px;border-radius:8px;border: 1px solid var(--glass-border);background: var(--surface);cursor:pointer;display:flex;align-items:center;justify-content:center;color: var(--text-muted);">
 <i data-lucide="x" style="width:15px;height:15px;"></i>
 </button>
 </div>
 <div id="dayModalBody" style="padding:20px 24px;"></div>
 </div>
</div>

<script>
const calendarData = <?php echo json_encode($calendarBurials); ?>;
const monthName = <?php echo json_encode($monthName); ?>;

function openDayDetail(day) {
 const events = calendarData[day] || [];
 const modal = document.getElementById('dayModal');
 const title = document.getElementById('dayModalTitle');
 const body = document.getElementById('dayModalBody');

 title.textContent = day + ' ' + monthName.split(' ')[0] + ' — ' + events.length + ' burial' + (events.length > 1 ? 's' : '');

 if (events.length === 0) {
 body.innerHTML = '<div style="text-align:center;padding:32px;"><i data-lucide="calendar-x" style="width:28px;height:28px;color:#cbd5e1;margin:0 auto 10px;display:block;"></i><p style="color:#94a3b8;margin:0;font-size:0.85rem;">No burials on this day.</p></div>';
 if (typeof lucide !== 'undefined') lucide.createIcons();
 } else {
 body.innerHTML = events.map(e => {
 const done = e.is_buried == 1;
 const time = e.burial_time ? new Date('2000-01-01T' + e.burial_time).toLocaleTimeString('en-US', {hour:'numeric',minute:'2-digit',hour12:true}) : '';
 const cls = done ? 'done' : 'pend';
 return `
 <div class="dm-item ${cls}">
 <div class="dm-badge ${cls}">
 <i data-lucide="${done ? 'check' : 'clock'}" style="width:16px;height:16px;color:#fff;"></i>
 </div>
 <div style="flex:1;min-width:0;">
 <div style="font-weight:700;font-size:0.88rem;color: var(--text-strong);margin-bottom:2px;">${escapeHtml(e.decedent_name)}</div>
 <div style="font-size:0.75rem;color: var(--text-muted);">${time ? time + ' · ' : ''}Plot ${escapeHtml(e.plot_number || 'N/A')}</div>
 ${e.barangay ? '<div style="font-size:0.7rem;color:#94a3b8;margin-top:3px;">' + escapeHtml(e.barangay) + '</div>' : ''}
 </div>
 <span class="dm-status ${cls}">${done ? 'Completed' : 'Scheduled'}</span>
 </div>
 `;
 }).join('');
 }

 modal.classList.add('active');
 if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeDayDetail() {
 document.getElementById('dayModal').classList.remove('active');
}

function escapeHtml(str) {
 const div = document.createElement('div');
 div.textContent = str;
 return div.innerHTML;
}
</script>

</body>
</html>
