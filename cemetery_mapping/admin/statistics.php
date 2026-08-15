<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Get comprehensive statistics
try {
    // Total counts
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
    $totalPlots = $pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();
    $totalVisitors = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $premiumPlots = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_fenced = 1")->fetchColumn();
    
    // Records by barangay
    $byBarangay = $pdo->query("
        SELECT barangay, COUNT(*) as count 
        FROM burial_records 
        WHERE barangay IS NOT NULL 
        GROUP BY barangay 
        ORDER BY count DESC
    ")->fetchAll();
    
    // Records by year
    $byYear = $pdo->query("
        SELECT YEAR(death_date) as year, COUNT(*) as count 
        FROM burial_records 
        WHERE death_date IS NOT NULL 
        GROUP BY YEAR(death_date) 
        ORDER BY year DESC 
        LIMIT 10
    ")->fetchAll();
    
    // Monthly trend (last 12 months)
    $monthlyTrend = $pdo->query("
        SELECT DATE_FORMAT(date_added, '%Y-%m') as month, COUNT(*) as count 
        FROM burial_records 
        WHERE date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(date_added, '%Y-%m')
        ORDER BY month ASC
    ")->fetchAll();
    
} catch (PDOException $e) {
    error_log("Statistics error: " . $e->getMessage());
}
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
.admin-layout { background: #ffffff; }
.admin-layout::after { display: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade { animation: fadeUp 0.5s ease both; }
button svg, a svg, button i, a i { pointer-events: none; }
</style>

<!-- Page Header -->
<div class="flex items-center gap-3 mb-6 animate-fade">
    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
    </div>
    <div>
        <h2 class="text-xl font-bold text-slate-900">Statistics</h2>
        <p class="text-sm text-slate-500">Comprehensive cemetery data analytics</p>
    </div>
</div>

<!-- Statistics Overview -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 animate-fade">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="file-text" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo number_format($totalRecords); ?></div><div class="text-xs text-slate-500">Total Records</div></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center"><i data-lucide="crown" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo number_format($premiumPlots); ?></div><div class="text-xs text-slate-500">Premium Plots</div></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo number_format($totalPlots); ?></div><div class="text-xs text-slate-500">Available Plots</div></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i data-lucide="users" class="w-5 h-5"></i></div>
            <div><div class="text-2xl font-bold text-slate-900"><?php echo number_format($totalVisitors); ?></div><div class="text-xs text-slate-500">Visitors</div></div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5 animate-fade">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2"><i data-lucide="pie-chart" class="w-4 h-4 text-emerald-600"></i> Records by Barangay</h3>
        <div style="position: relative; height: 300px;"><canvas id="barangayChart"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2"><i data-lucide="layers" class="w-4 h-4 text-emerald-600"></i> Plot Type Distribution</h3>
        <div style="position: relative; height: 300px;"><canvas id="plotTypeChart"></canvas></div>
    </div>
</div>

<!-- Year Distribution Chart -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-5 animate-fade">
    <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2"><i data-lucide="bar-chart-2" class="w-4 h-4 text-emerald-600"></i> Deaths by Year</h3>
    <div style="position: relative; height: 300px;"><canvas id="yearChart"></canvas></div>
</div>

<!-- Monthly Trend Chart -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-5 animate-fade">
    <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2"><i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i> Monthly Trend (Last 12 Months)</h3>
    <div style="position: relative; height: 250px;"><canvas id="monthlyChart"></canvas></div>
</div>

<!-- Detailed Tables -->
<h2 class="text-lg font-bold text-slate-900 mb-4 mt-8 flex items-center gap-2"><i data-lucide="table" class="w-5 h-5 text-emerald-600"></i> Detailed Statistics</h2>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <!-- Records by Barangay -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-4">Records by Barangay</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="text-left py-2 px-3 text-xs font-semibold uppercase text-slate-500">Barangay</th>
                    <th class="text-left py-2 px-3 text-xs font-semibold uppercase text-slate-500">Count</th>
                    <th class="text-left py-2 px-3 text-xs font-semibold uppercase text-slate-500">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byBarangay as $item): ?>
                    <?php $percentage = ($item['count'] / $totalRecords) * 100; ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="py-2.5 px-3 text-slate-800"><?php echo htmlspecialchars($item['barangay'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="py-2.5 px-3 text-slate-800 font-medium"><?php echo number_format($item['count']); ?></td>
                        <td class="py-2.5 px-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full" style="width: <?php echo $percentage; ?>%;"></div>
                                </div>
                                <span class="text-xs text-slate-500 min-w-[45px]"><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Records by Year -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-4">Deaths by Year</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="text-left py-2 px-3 text-xs font-semibold uppercase text-slate-500">Year</th>
                    <th class="text-left py-2 px-3 text-xs font-semibold uppercase text-slate-500">Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byYear as $item): ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="py-2.5 px-3 text-slate-800"><?php echo $item['year']; ?></td>
                        <td class="py-2.5 px-3 text-slate-800 font-medium"><?php echo number_format($item['count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Monthly Trend Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-5">
    <h3 class="text-sm font-bold text-slate-900 mb-4">Monthly Additions</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="text-left py-2 px-3 text-xs font-semibold uppercase text-slate-500">Month</th>
                    <?php foreach ($monthlyTrend as $item): ?>
                        <th class="text-left py-2 px-3 text-xs font-semibold uppercase text-slate-500"><?php echo date('M Y', strtotime($item['month'] . '-01')); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr class="hover:bg-slate-50">
                    <td class="py-2.5 px-3 font-semibold text-slate-800">Records Added</td>
                    <?php foreach ($monthlyTrend as $item): ?>
                        <td class="py-2.5 px-3 text-slate-700"><?php echo $item['count']; ?></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Export Options -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2"><i data-lucide="download" class="w-4 h-4 text-emerald-600"></i> Export Statistics</h3>
    <div class="flex gap-3 flex-wrap">
        <button onclick="exportCSV()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 transition">
            <i data-lucide="file-down" class="w-4 h-4"></i> Export as CSV
        </button>
        <button onclick="window.open('print-statistics.php', '_blank')" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
            <i data-lucide="printer" class="w-4 h-4"></i> Print Report
        </button>
    </div>
</div>

        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Chart.js configuration for light theme
        Chart.defaults.color = '#64748b';
        Chart.defaults.borderColor = 'rgba(226, 232, 240, 0.8)';
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.95)';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.borderColor = 'rgba(16, 185, 129, 0.5)';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' };
        Chart.defaults.plugins.tooltip.bodyFont = { size: 13 };

        function createGradient(ctx, color1, color2) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        }

        // Barangay Distribution Doughnut Chart
        const barangayCtx = document.getElementById('barangayChart');
        if (barangayCtx) {
            const ctx = barangayCtx.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($byBarangay, 'barangay')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($byBarangay, 'count')); ?>,
                        backgroundColor: [
                            createGradient(ctx, '#10b981', '#059669'),
                            createGradient(ctx, '#3b82f6', '#2563eb'),
                            createGradient(ctx, '#f59e0b', '#d97706'),
                            createGradient(ctx, '#10b981', '#059669'),
                            createGradient(ctx, '#ef4444', '#dc2626'),
                            createGradient(ctx, '#ec4899', '#db2777'),
                            createGradient(ctx, '#6366f1', '#4f46e5'),
                            createGradient(ctx, '#f97316', '#ea580c')
                        ],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverBorderWidth: 4,
                        hoverBorderColor: '#ffffff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'right', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle', font: { size: 13, weight: '500' }, color: '#334155', boxWidth: 12, boxHeight: 12 } },
                        tooltip: { callbacks: { label: function(context) { const label = context.label || ''; const value = context.parsed || 0; const total = context.dataset.data.reduce((a, b) => a + b, 0); const percentage = ((value / total) * 100).toFixed(1); return `${label}: ${value} (${percentage}%)`; } } }
                    },
                    animation: { animateRotate: true, animateScale: true, duration: 1500, easing: 'easeInOutQuart' }
                }
            });
        }

        // Records by Year Bar Chart
        const yearCtx = document.getElementById('yearChart');
        if (yearCtx) {
            const ctx = yearCtx.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.9)');
            gradient.addColorStop(1, 'rgba(5, 150, 105, 0.7)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($byYear, 'year')); ?>,
                    datasets: [{
                        label: 'Deaths',
                        data: <?php echo json_encode(array_column($byYear, 'count')); ?>,
                        backgroundColor: gradient,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(16, 185, 129, 1)',
                        barThickness: 'flex',
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { title: function(context) { return 'Year ' + context[0].label; }, label: function(context) { return 'Deaths: ' + context.parsed.y; } } }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, color: '#64748b', font: { size: 12 } }, grid: { color: 'rgba(226, 232, 240, 0.6)', lineWidth: 1 }, border: { display: false } },
                        x: { ticks: { color: '#64748b', font: { size: 12, weight: '500' } }, grid: { display: false }, border: { display: false } }
                    },
                    animation: { duration: 1500, easing: 'easeInOutQuart' }
                }
            });
        }

        // Monthly Trend Line Chart
        const monthlyCtx = document.getElementById('monthlyChart');
        if (monthlyCtx) {
            const ctx = monthlyCtx.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 250);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
            gradient.addColorStop(0.5, 'rgba(16, 185, 129, 0.2)');
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_map(function($item) { return date('M Y', strtotime($item['month'] . '-01')); }, $monthlyTrend)); ?>,
                    datasets: [{
                        label: 'Records Added',
                        data: <?php echo json_encode(array_column($monthlyTrend, 'count')); ?>,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 9,
                        pointHoverBackgroundColor: 'rgba(5, 150, 105, 1)',
                        pointHoverBorderWidth: 3,
                        pointStyle: 'circle'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { mode: 'index', intersect: false, callbacks: { label: function(context) { return 'Records: ' + context.parsed.y; } } }
                    },
                    interaction: { mode: 'nearest', axis: 'x', intersect: false },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, color: '#64748b', font: { size: 12 } }, grid: { color: 'rgba(226, 232, 240, 0.6)', lineWidth: 1 }, border: { display: false } },
                        x: { ticks: { color: '#64748b', font: { size: 11 }, maxRotation: 45, minRotation: 45 }, grid: { display: false }, border: { display: false } }
                    },
                    animation: { duration: 2000, easing: 'easeInOutQuart' }
                }
            });
        }

        // Plot Type Distribution Pie Chart
        const plotTypeCtx = document.getElementById('plotTypeChart');
        if (plotTypeCtx) {
            const ctx = plotTypeCtx.getContext('2d');
            const totalBurial = <?php echo $totalRecords; ?>;
            const premiumCount = <?php echo $premiumPlots; ?>;
            const standardCount = totalBurial - premiumCount;

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Standard', 'Premium/Fenced', 'Available'],
                    datasets: [{
                        data: [standardCount, premiumCount, <?php echo $totalPlots; ?>],
                        backgroundColor: [
                            createGradient(ctx, '#3b82f6', '#2563eb'),
                            createGradient(ctx, '#f59e0b', '#d97706'),
                            createGradient(ctx, '#10b981', '#059669')
                        ],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverBorderWidth: 4,
                        hoverBorderColor: '#ffffff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle', font: { size: 13, weight: '500' }, color: '#334155', boxWidth: 12, boxHeight: 12 } },
                        tooltip: { callbacks: { label: function(context) { const label = context.label || ''; const value = context.parsed || 0; const total = context.dataset.data.reduce((a, b) => a + b, 0); const percentage = ((value / total) * 100).toFixed(1); return `${label}: ${value} (${percentage}%)`; } } }
                    },
                    animation: { animateRotate: true, animateScale: true, duration: 1500, easing: 'easeInOutQuart' }
                }
            });
        }

        function exportCSV() {
            window.location.href = '../api/export_statistics.php?format=csv';
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>

    <style>
        @media print {
            .sidebar, nav, button, .glass-card:last-of-type, #barangayChart, #yearChart, #plotTypeChart, #monthlyChart { display: none !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { background: white !important; color: #000 !important; margin: 0; padding: 20px; }
            .admin-layout { display: block !important; margin: 0 !important; padding: 0 !important; }
            main { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
            @page { size: A4 portrait; margin: 15mm; }
            body::before { content: "Cemetery Statistics Report"; display: block; font-size: 20px; font-weight: bold; text-align: center; margin-bottom: 5px; color: #10b981; border-bottom: 3px solid #10b981; padding-bottom: 5px; }
            body::after { content: "Generated on <?php echo date('F d, Y h:i A'); ?>"; display: block; font-size: 9px; text-align: center; margin-top: 10px; color: #64748b; border-top: 1px solid #d1d5db; padding-top: 5px; }
        }
    </style>
</body>
</html>
