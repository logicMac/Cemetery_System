<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';
require_once 'includes/sidebar.php';

// Calendar navigation
$calMonth = isset($_GET['cal_month']) ? (int)$_GET['cal_month'] : (int)date('n');
$calYear  = isset($_GET['cal_year']) ? (int)$_GET['cal_year'] : (int)date('Y');
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
    foreach ($calStmt->fetchAll() as $row) {
        $day = (int)date('j', strtotime($row['burial_date']));
        $calendarBurials[$day][] = $row;
    }

    // Stats for the selected month
    $totalScheduled = count($calStmt->fetchAll());
    $calStmt->execute([$calMonth, $calYear]);
    $all = $calStmt->fetchAll();
    $completed = 0;
    $upcoming = 0;
    foreach ($all as $r) {
        if ($r['is_buried'] == 1) $completed++;
        else $upcoming++;
    }

    // Upcoming burials (next 7 days from today)
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
    $upcomingBurials = [];
    error_log('Burial calendar error: ' . $e->getMessage());
}

// Calendar data
$prevM = $calMonth - 1; $prevY = $calYear; if ($prevM < 1) { $prevM = 12; $prevY--; }
$nextM = $calMonth + 1; $nextY = $calYear; if ($nextM > 12) { $nextM = 1; $nextY++; }
$firstDay = mktime(0, 0, 0, $calMonth, 1, $calYear);
$daysInMonth = (int)date('t', $firstDay);
$startDow = (int)date('N', $firstDay); // 1=Mon
$monthName = date('F Y', $firstDay);
$today = (int)date('j');
$isCurrentMonth = ($calMonth === (int)date('n') && $calYear === (int)date('Y'));
$monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
?>

<style>
    .cal-wrap { max-width: 1400px; margin: 0 auto; }
    .cal-card { background:#fff; border:1px solid #e2e8f0; border-radius:20px; overflow:hidden; }
    .cal-grid { display:grid; grid-template-columns: 1fr 400px; gap:0; }
    @media (max-width: 900px) { .cal-grid { grid-template-columns: 1fr; } }

    .cal-main { padding: 32px 40px; }
    .cal-side { padding: 32px 28px; background:#fff; border-left:1px solid #e2e8f0; }
    @media (max-width: 900px) { .cal-side { border-left:none; border-top:1px solid #e2e8f0; } }

    .cal-table { width:100%; border-collapse:separate; border-spacing:5px; table-layout:fixed; }
    .cal-table th { padding:10px 0; font-size:0.75rem; text-transform:uppercase; color:#94a3b8; font-weight:700; text-align:center; letter-spacing:0.05em; }
    .cal-table td { aspect-ratio: 1.2 / 1; vertical-align:top; border-radius:12px; padding:8px 10px; font-size:0.85rem; transition: all 0.2s ease; cursor:default; }
    .cal-day { background:#fff; border:1px solid #f1f5f9; }
    .cal-day:hover { background:#f0fdf4; border-color:#d1fae5; }
    .cal-day-num { font-weight:700; color:#475569; font-size:0.85rem; }
    .cal-empty { background:transparent; }
    .cal-today { background:#ecfdf5 !important; border:2px solid #10b981 !important; }
    .cal-today .cal-day-num { color:#059669; }
    .cal-has-event { background:#fffbeb; border:1px solid #fde68a; }
    .cal-has-event:hover { background:#fef3c7; }
    .cal-has-event .cal-day-num { color:#b45309; }
    .cal-completed { background:#f0fdf4; border:1px solid #bbf7d0; }
    .cal-completed .cal-day-num { color:#047857; }

    .cal-event-dot { display:inline-block; width:6px; height:6px; border-radius:50%; margin-top:4px; }
    .cal-event-count { font-size:0.65rem; color:#94a3b8; font-weight:600; margin-top:2px; }

    .cal-nav-btn { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; border:1px solid #e2e8f0; color:#10b981; font-weight:700; text-decoration:none; transition:all 0.2s ease; background:#fff; }
    .cal-nav-btn:hover { background:#f0fdf4; border-color:#d1fae5; }
    .cal-today-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; border:1px solid #e2e8f0; color:#475569; font-size:0.8rem; font-weight:600; text-decoration:none; transition:all 0.2s ease; background:#fff; }
    .cal-today-btn:hover { background:#f0fdf4; border-color:#d1fae5; color:#059669; }

    .stat-pill { display:inline-flex; align-items:center; gap:8px; padding:6px 14px; border-radius:999px; font-size:0.78rem; font-weight:600; }
    .stat-pill .dot { width:8px; height:8px; border-radius:50%; }

    .upcoming-item { display:flex; align-items:flex-start; gap:12px; padding:14px; border-radius:14px; border:1px solid #e2e8f0; background:#fff; margin-bottom:10px; transition:all 0.2s ease; }
    .upcoming-item:hover { border-color:#d1fae5; box-shadow:0 2px 12px rgba(16,185,129,0.08); }
    .upcoming-date { flex-shrink:0; width:48px; height:48px; border-radius:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#f0fdf4; border:1px solid #d1fae5; }
    .upcoming-date .d { font-size:1.1rem; font-weight:800; color:#059669; line-height:1; }
    .upcoming-date .m { font-size:0.6rem; text-transform:uppercase; color:#10b981; font-weight:700; margin-top:2px; }

    .day-detail-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; }
    .day-detail-overlay.active { display:flex; }
    .day-detail-card { background:#fff; border-radius:20px; max-width:480px; width:100%; max-height:80vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
</style>

<div class="admin-main">
    <div class="cal-wrap">

        <!-- Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
            <div>
                <h1 style="font-size:1.75rem; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:10px;">
                    <i data-lucide="calendar-days" style="width:28px; height:28px; color:#10b981;"></i>
                    Burial Calendar
                </h1>
                <p style="color:#64748b; font-size:0.875rem; margin:4px 0 0;">View and manage scheduled burial ceremonies</p>
            </div>
            <a href="?cal_month=<?php echo (int)date('n'); ?>&cal_year=<?php echo (int)date('Y'); ?>" class="cal-today-btn">
                <i data-lucide="calendar-check" style="width:14px; height:14px;"></i> Today
            </a>
        </div>

        <!-- Stat pills -->
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
            <span class="stat-pill" style="background:#f0fdf4; color:#059669; border:1px solid #d1fae5;">
                <span class="dot" style="background:#10b981;"></span>
                <?php echo $upcoming; ?> upcoming this month
            </span>
            <span class="stat-pill" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                <span class="dot" style="background:#f59e0b;"></span>
                <?php echo count($calendarBurials); ?> total scheduled
            </span>
            <span class="stat-pill" style="background:#f0fdf4; color:#047857; border:1px solid #bbf7d0;">
                <span class="dot" style="background:#059669;"></span>
                <?php echo $completed; ?> completed
            </span>
        </div>

        <div class="cal-card">
            <div class="cal-grid">

                <!-- Calendar -->
                <div class="cal-main">
                    <!-- Month navigation -->
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                        <a href="?cal_month=<?php echo $prevM; ?>&cal_year=<?php echo $prevY; ?>" class="cal-nav-btn">
                            <i data-lucide="chevron-left" style="width:18px; height:18px;"></i>
                        </a>
                        <div style="text-align:center;">
                            <div style="font-size:1.25rem; font-weight:800; color:#0f172a;"><?php echo $monthName; ?></div>
                            <div style="font-size:0.75rem; color:#94a3b8; font-weight:500; margin-top:2px;">
                                <?php echo count($calendarBurials); ?> scheduled <?php echo $isCurrentMonth ? '· This month' : ''; ?>
                            </div>
                        </div>
                        <a href="?cal_month=<?php echo $nextM; ?>&cal_year=<?php echo $nextY; ?>" class="cal-nav-btn">
                            <i data-lucide="chevron-right" style="width:18px; height:18px;"></i>
                        </a>
                    </div>

                    <!-- Calendar table -->
                    <table class="cal-table">
                        <thead>
                            <tr>
                                <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
                                    <th><?php echo $d; ?></th>
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
                                        echo '<td class="cal-empty"></td>';
                                        continue;
                                    }
                                    $events = $calendarBurials[$dayNum] ?? [];
                                    $hasEvents = count($events) > 0;
                                    $allDone = $hasEvents && count(array_filter($events, fn($e) => $e['is_buried'] == 1)) === count($events);
                                    $isToday = $isCurrentMonth && $dayNum === $today;

                                    $classes = 'cal-day';
                                    if ($isToday) $classes .= ' cal-today';
                                    elseif ($allDone) $classes .= ' cal-completed';
                                    elseif ($hasEvents) $classes .= ' cal-has-event';

                                    echo '<td class="' . $classes . '" onclick="' . ($hasEvents ? 'openDayDetail(' . $dayNum . ')' : '') . '">';
                                    echo '<div class="cal-day-num">' . $dayNum . '</div>';
                                    if ($hasEvents) {
                                        $pending = count(array_filter($events, fn($e) => $e['is_buried'] == 0));
                                        $done = count($events) - $pending;
                                        echo '<div style="display:flex; gap:3px; margin-top:4px;">';
                                        if ($pending > 0) echo '<span class="cal-event-dot" style="background:#f59e0b;"></span>';
                                        if ($done > 0) echo '<span class="cal-event-dot" style="background:#10b981;"></span>';
                                        echo '</div>';
                                        echo '<div class="cal-event-count">' . count($events) . ' burial' . (count($events) > 1 ? 's' : '') . '</div>';
                                    }
                                    echo '</td>';
                                    $dayNum++;
                                }
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Legend -->
                    <div style="display:flex; flex-wrap:wrap; gap:16px; margin-top:20px; padding-top:16px; border-top:1px solid #f1f5f9;">
                        <span style="display:flex; align-items:center; gap:6px; font-size:0.75rem; color:#64748b;">
                            <span style="width:12px; height:12px; border-radius:4px; background:#ecfdf5; border:2px solid #10b981;"></span> Today
                        </span>
                        <span style="display:flex; align-items:center; gap:6px; font-size:0.75rem; color:#64748b;">
                            <span style="width:12px; height:12px; border-radius:4px; background:#fffbeb; border:1px solid #fde68a;"></span> Scheduled
                        </span>
                        <span style="display:flex; align-items:center; gap:6px; font-size:0.75rem; color:#64748b;">
                            <span style="width:12px; height:12px; border-radius:4px; background:#f0fdf4; border:1px solid #bbf7d0;"></span> Completed
                        </span>
                    </div>
                </div>

                <!-- Side panel: upcoming -->
                <div class="cal-side">
                    <h3 style="font-size:0.8rem; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.05em; margin:0 0 16px; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="clock" style="width:16px; height:16px; color:#10b981;"></i>
                        Upcoming (7 days)
                    </h3>

                    <?php if (empty($upcomingBurials)): ?>
                        <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                            <i data-lucide="calendar-x" style="width:32px; height:32px; margin:0 auto 12px; display:block; color:#cbd5e1;"></i>
                            <p style="font-size:0.85rem; margin:0;">No burials scheduled in the next 7 days</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcomingBurials as $b):
                            $bDate = strtotime($b['burial_date']);
                            $bDay = date('j', $bDate);
                            $bMon = date('M', $bDate);
                            $isPast = $bDate < strtotime('today');
                        ?>
                            <div class="upcoming-item">
                                <div class="upcoming-date" style="<?php echo $isPast ? 'background:#fffbeb;border-color:#fde68a;' : ''; ?>">
                                    <div class="d" style="<?php echo $isPast ? 'color:#b45309;' : ''; ?>"><?php echo $bDay; ?></div>
                                    <div class="m" style="<?php echo $isPast ? 'color:#d97706;' : ''; ?>"><?php echo $bMon; ?></div>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:700; font-size:0.875rem; color:#0f172a; margin-bottom:2px;">
                                        <?php echo htmlspecialchars($b['decedent_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:#64748b; line-height:1.4;">
                                        <?php if ($b['burial_time']): ?>
                                            <?php echo date('g:i A', strtotime($b['burial_time'])); ?> ·
                                        <?php endif; ?>
                                        Plot <?php echo htmlspecialchars($b['plot_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($b['barangay'])): ?>
                                        <div style="font-size:0.7rem; color:#94a3b8; margin-top:2px;">
                                            <?php echo htmlspecialchars($b['barangay'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Quick link -->
                    <a href="records.php" style="display:flex; align-items:center; justify-content:center; gap:8px; margin-top:20px; padding:12px; border-radius:12px; background:#10b981; color:#fff; text-decoration:none; font-size:0.8rem; font-weight:600; transition:all 0.2s ease;">
                        <i data-lucide="file-plus" style="width:14px; height:14px;"></i>
                        Schedule a Burial
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Day detail modal -->
<div class="day-detail-overlay" id="dayDetailOverlay" onclick="if(event.target===this) closeDayDetail()">
    <div class="day-detail-card">
        <div style="padding:20px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <h3 id="dayDetailTitle" style="font-size:1.1rem; font-weight:700; color:#0f172a; margin:0;">Burials on this day</h3>
            <button onclick="closeDayDetail()" style="width:32px; height:32px; border-radius:8px; border:1px solid #e2e8f0; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#64748b;">
                <i data-lucide="x" style="width:16px; height:16px;"></i>
            </button>
        </div>
        <div id="dayDetailBody" style="padding:20px 24px;"></div>
    </div>
</div>

<script>
const calendarData = <?php echo json_encode($calendarBurials); ?>;
const monthName = <?php echo json_encode($monthName); ?>;

function openDayDetail(day) {
    const events = calendarData[day] || [];
    const overlay = document.getElementById('dayDetailOverlay');
    const title = document.getElementById('dayDetailTitle');
    const body = document.getElementById('dayDetailBody');

    title.textContent = day + ' ' + monthName.split(' ')[0] + ' — ' + events.length + ' burial' + (events.length > 1 ? 's' : '');

    if (events.length === 0) {
        body.innerHTML = '<p style="color:#94a3b8; text-align:center; padding:20px;">No burials on this day.</p>';
    } else {
        body.innerHTML = events.map(e => {
            const done = e.is_buried == 1;
            const time = e.burial_time ? new Date('2000-01-01T' + e.burial_time).toLocaleTimeString('en-US', {hour:'numeric',minute:'2-digit',hour12:true}) : '';
            return `
                <div style="display:flex; align-items:flex-start; gap:12px; padding:14px; border-radius:14px; border:1px solid #e2e8f0; margin-bottom:10px; background:${done ? '#f0fdf4' : '#fffbeb'};">
                    <div style="width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:${done ? '#10b981' : '#f59e0b'};">
                        <i data-lucide="${done ? 'check' : 'clock'}" style="width:18px; height:18px; color:#fff;"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; font-size:0.9rem; color:#0f172a; margin-bottom:2px;">
                            ${escapeHtml(e.decedent_name)}
                            ${done ? '<span style="font-size:0.65rem; font-weight:600; color:#059669; background:#d1fae5; padding:2px 8px; border-radius:999px; margin-left:6px;">Completed</span>' : ''}
                        </div>
                        <div style="font-size:0.78rem; color:#64748b;">
                            ${time ? time + ' · ' : ''}Plot ${escapeHtml(e.plot_number || 'N/A')}
                            ${e.barangay ? ' · ' + escapeHtml(e.barangay) : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    overlay.classList.add('active');
    lucide.createIcons();
}

function closeDayDetail() {
    document.getElementById('dayDetailOverlay').classList.remove('active');
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

</body>
</html>
