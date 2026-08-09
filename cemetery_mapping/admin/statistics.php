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

<!-- Statistics Overview -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo number_format($totalRecords); ?></div>
        <div class="stat-label">Total Records</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo number_format($premiumPlots); ?></div>
        <div class="stat-label">Premium Plots</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo number_format($totalPlots); ?></div>
        <div class="stat-label">Available Plots</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo number_format($totalVisitors); ?></div>
        <div class="stat-label">Registered Visitors</div>
    </div>
</div>

<!-- Charts and Tables -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 30px;">
    <!-- Barangay Distribution Chart -->
    <div class="glass-card" style="padding: 30px;">
        <h3 style="margin: 0 0 20px 0;">Records by Barangay</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="barangayChart"></canvas>
        </div>
    </div>
    
    <!-- Plot Type Distribution Chart -->
    <div class="glass-card" style="padding: 30px;">
        <h3 style="margin: 0 0 20px 0;">Plot Type Distribution</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="plotTypeChart"></canvas>
        </div>
    </div>
</div>

<!-- Year Distribution Chart -->
<div class="glass-card" style="padding: 30px; margin-top: 20px;">
    <h3 style="margin: 0 0 20px 0;">Deaths by Year</h3>
    <div style="position: relative; height: 300px;">
        <canvas id="yearChart"></canvas>
    </div>
</div>

<!-- Monthly Trend Chart -->
<div class="glass-card" style="padding: 30px; margin-top: 20px;">
    <h3 style="margin: 0 0 20px 0;">Monthly Trend (Last 12 Months)</h3>
    <div style="position: relative; height: 250px;">
        <canvas id="monthlyChart"></canvas>
    </div>
</div>

<!-- Detailed Tables -->
<h2 style="margin: 40px 0 20px 0;">Detailed Statistics</h2>
<div class="print-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
    <!-- Records by Barangay -->
    <div class="data-table-container">
        <h3 style="margin-bottom: 20px;">Records by Barangay (Table)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Barangay</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byBarangay as $item): ?>
                    <?php $percentage = ($item['count'] / $totalRecords) * 100; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['barangay'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format($item['count']); ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                    <div style="width: <?php echo $percentage; ?>%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                                </div>
                                <span style="min-width: 50px;"><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Records by Year -->
    <div class="data-table-container">
        <h3 style="margin-bottom: 20px;">Deaths by Year (Table)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byYear as $item): ?>
                    <tr>
                        <td><?php echo $item['year']; ?></td>
                        <td><?php echo number_format($item['count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Monthly Trend Table -->
<div class="data-table-container" style="margin-top: 30px;">
    <h3 style="margin-bottom: 20px;">Monthly Additions (Table)</h3>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <?php foreach ($monthlyTrend as $item): ?>
                        <th><?php echo date('M Y', strtotime($item['month'] . '-01')); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Records Added</strong></td>
                    <?php foreach ($monthlyTrend as $item): ?>
                        <td><?php echo $item['count']; ?></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Export Options -->
<div class="glass-card" style="margin-top: 30px;">
    <h3 style="margin-bottom: 20px;">Export Statistics</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <button onclick="exportCSV()" class="btn-primary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export as CSV
        </button>
        <button onclick="window.open('print-statistics.php', '_blank')" class="btn-secondary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print Report
        </button>
    </div>
</div>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Enhanced Chart.js configuration with modern styling
        Chart.defaults.color = '#a1a1aa';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(10, 10, 20, 0.95)';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.borderColor = 'rgba(102, 126, 234, 0.5)';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' };
        Chart.defaults.plugins.tooltip.bodyFont = { size: 13 };
        
        // Modern gradient colors
        const gradientColors = {
            purple: ['#667eea', '#764ba2'],
            blue: ['#3b82f6', '#2563eb'],
            green: ['#22c55e', '#16a34a'],
            yellow: ['#fbbf24', '#f59e0b'],
            red: ['#ef4444', '#dc2626'],
            pink: ['#ec4899', '#db2777'],
            indigo: ['#6366f1', '#4f46e5'],
            orange: ['#f97316', '#ea580c']
        };
        
        // Create gradient helper
        function createGradient(ctx, color1, color2) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        }
        
        // Barangay Distribution Doughnut Chart with Enhanced Design
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
                            createGradient(ctx, '#667eea', '#764ba2'),
                            createGradient(ctx, '#3b82f6', '#2563eb'),
                            createGradient(ctx, '#fbbf24', '#f59e0b'),
                            createGradient(ctx, '#22c55e', '#16a34a'),
                            createGradient(ctx, '#ef4444', '#dc2626'),
                            createGradient(ctx, '#ec4899', '#db2777'),
                            createGradient(ctx, '#6366f1', '#4f46e5'),
                            createGradient(ctx, '#f97316', '#ea580c')
                        ],
                        borderWidth: 3,
                        borderColor: 'rgba(10, 10, 20, 0.5)',
                        hoverBorderWidth: 4,
                        hoverBorderColor: '#fff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 13, weight: '500' },
                                color: '#d4d4d8',
                                boxWidth: 12,
                                boxHeight: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }
        
        // Records by Year Bar Chart with Gradient
        const yearCtx = document.getElementById('yearChart');
        if (yearCtx) {
            const ctx = yearCtx.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(102, 126, 234, 0.9)');
            gradient.addColorStop(1, 'rgba(118, 75, 162, 0.7)');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($byYear, 'year')); ?>,
                    datasets: [{
                        label: 'Deaths',
                        data: <?php echo json_encode(array_column($byYear, 'count')); ?>,
                        backgroundColor: gradient,
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(102, 126, 234, 1)',
                        barThickness: 'flex',
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    return 'Year ' + context[0].label;
                                },
                                label: function(context) {
                                    return 'Deaths: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#a1a1aa',
                                font: { size: 12 }
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                lineWidth: 1
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            ticks: {
                                color: '#a1a1aa',
                                font: { size: 12, weight: '500' }
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }
        
        // Monthly Trend Line Chart with Area Fill and Gradient
        const monthlyCtx = document.getElementById('monthlyChart');
        if (monthlyCtx) {
            const ctx = monthlyCtx.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 250);
            gradient.addColorStop(0, 'rgba(102, 126, 234, 0.4)');
            gradient.addColorStop(0.5, 'rgba(102, 126, 234, 0.2)');
            gradient.addColorStop(1, 'rgba(102, 126, 234, 0.0)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_map(function($item) {
                        return date('M Y', strtotime($item['month'] . '-01'));
                    }, $monthlyTrend)); ?>,
                    datasets: [{
                        label: 'Records Added',
                        data: <?php echo json_encode(array_column($monthlyTrend, 'count')); ?>,
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(102, 126, 234, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 9,
                        pointHoverBackgroundColor: 'rgba(118, 75, 162, 1)',
                        pointHoverBorderWidth: 3,
                        pointStyle: 'circle'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Records: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#a1a1aa',
                                font: { size: 12 }
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                lineWidth: 1
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            ticks: {
                                color: '#a1a1aa',
                                font: { size: 11 },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }
        
        // Plot Type Distribution Pie Chart with Modern Design
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
                            createGradient(ctx, '#fbbf24', '#f59e0b'),
                            createGradient(ctx, '#22c55e', '#16a34a')
                        ],
                        borderWidth: 3,
                        borderColor: 'rgba(10, 10, 20, 0.5)',
                        hoverBorderWidth: 4,
                        hoverBorderColor: '#fff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 13, weight: '500' },
                                color: '#d4d4d8',
                                boxWidth: 12,
                                boxHeight: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }
        
        function exportCSV() {
            window.location.href = '../api/export_statistics.php?format=csv';
        }
    </script>
    
    <style>
        @media print {
            /* Hide non-print elements */
            .sidebar,
            nav,
            .btn-primary,
            .btn-secondary,
            button,
            .glass-card:last-of-type,
            #barangayChart,
            #yearChart,
            #plotTypeChart,
            #monthlyChart {
                display: none !important;
            }
            
            /* Reset page styles for print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            body {
                background: white !important;
                color: #000 !important;
                margin: 0;
                padding: 20px;
            }
            
            .admin-layout {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            main {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            
            /* Print header */
            .dashboard-grid {
                page-break-inside: avoid;
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 15px !important;
                margin-bottom: 20px !important;
            }
            
            .stat-card {
                background: #f3f4f6 !important;
                border: 1px solid #d1d5db !important;
                border-radius: 8px !important;
                padding: 15px !important;
                text-align: center !important;
                page-break-inside: avoid;
            }
            
            .stat-value {
                font-size: 24px !important;
                font-weight: bold !important;
                color: #667eea !important;
                margin: 8px 0 !important;
            }
            
            .stat-label {
                font-size: 11px !important;
                color: #6b7280 !important;
                text-transform: uppercase !important;
            }
            
            .stat-icon {
                display: none !important;
            }
            
            /* Tables layout */
            .data-table-container {
                page-break-inside: avoid;
                margin-bottom: 15px !important;
                background: white !important;
                border: 1px solid #d1d5db !important;
                border-radius: 4px !important;
                padding: 10px !important;
            }
            
            .data-table-container h3 {
                color: #000 !important;
                font-size: 13px !important;
                margin: 0 0 10px 0 !important;
                border-bottom: 2px solid #667eea !important;
                padding-bottom: 5px !important;
            }
            
            .data-table {
                width: 100% !important;
                font-size: 9px !important;
                border-collapse: collapse !important;
            }
            
            .data-table th {
                background: #f3f4f6 !important;
                color: #000 !important;
                padding: 6px !important;
                text-align: left !important;
                border: 1px solid #d1d5db !important;
                font-weight: 600 !important;
            }
            
            .data-table td {
                padding: 5px !important;
                border: 1px solid #e5e7eb !important;
                color: #000 !important;
            }
            
            .data-table tbody tr:nth-child(even) {
                background: #f9fafb !important;
            }
            
            /* Compact layout for print */
            h2 {
                display: none !important;
            }
            
            .glass-card {
                background: white !important;
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Two column layout for tables */
            .print-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 15px !important;
                margin-top: 15px !important;
            }
            
            /* Print header */
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
            
            /* Add print title */
            body::before {
                content: "Cemetery Statistics Report";
                display: block;
                font-size: 20px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 5px;
                color: #667eea;
                border-bottom: 3px solid #667eea;
                padding-bottom: 5px;
            }
            
            body::after {
                content: "Generated on <?php echo date('F d, Y h:i A'); ?>";
                display: block;
                font-size: 9px;
                text-align: center;
                margin-top: 10px;
                color: #6b7280;
                border-top: 1px solid #d1d5db;
                padding-top: 5px;
            }
            
            /* Hide monthly trend horizontal table */
            .data-table-container:last-of-type {
                display: none !important;
            }
            
            /* Progress bars in print */
            .data-table-container div[style*="background: rgba(255,255,255,0.1)"] {
                background: #e5e7eb !important;
            }
            
            .data-table-container div[style*="background: linear-gradient"] {
                background: #667eea !important;
            }
        }
    </style>
</body>
</html>
