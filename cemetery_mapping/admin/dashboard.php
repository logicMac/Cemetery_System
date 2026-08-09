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

<!-- Dashboard Content -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo number_format($totalRecords); ?></div>
        <div class="stat-label">Total Burial Records</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo number_format($availablePlots); ?></div>
        <div class="stat-label">Available Plots</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo number_format($thisMonth); ?></div>
        <div class="stat-label">Records This Month</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo number_format($totalVisitors); ?></div>
        <div class="stat-label">Active Visitors</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
    <!-- Recent Records -->
    <div class="data-table-container">
        <h3 style="margin-bottom: 20px;">Recent Records</h3>
        <table class="data-table">
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
                        <td colspan="3" style="text-align: center;">No records found</td>
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
        <a href="records.php" class="btn-primary" style="display: inline-block; margin-top: 16px;">View All Records</a>
    </div>
    
    <!-- Records by Barangay -->
    <div class="data-table-container">
        <h3 style="margin-bottom: 20px;">Records by Barangay</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Barangay</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($byBarangay)): ?>
                    <tr>
                        <td colspan="2" style="text-align: center;">No data available</td>
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
        <a href="statistics.php" class="btn-primary" style="display: inline-block; margin-top: 16px;">View Statistics</a>
    </div>
</div>

<!-- Quick Actions -->
<div class="glass-card" style="margin-top: 30px;">
    <h3 style="margin-bottom: 20px;">Quick Actions</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <a href="add-record.php" class="btn-primary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Record
        </a>
        <a href="available-plots.php" class="btn-secondary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            </svg>
            Manage Plots
        </a>
        <a href="map-view.php" class="btn-secondary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
            </svg>
            View Map
        </a>
        <a href="reports.php" class="btn-secondary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Generate Report
        </a>
    </div>
</div>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
</body>
</html>
