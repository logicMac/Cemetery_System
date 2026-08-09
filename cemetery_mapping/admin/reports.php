<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
    .reports-hero {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 16px;
        padding: 40px;
        margin-bottom: 40px;
        text-align: center;
    }
    
    .reports-hero h1 {
        font-size: 2.5rem;
        margin-bottom: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .reports-hero p {
        font-size: 1.1rem;
        color: var(--zinc-400);
        max-width: 600px;
        margin: 0 auto;
    }
    
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .section-header h2 {
        font-size: 1.5rem;
        margin: 0;
    }
    
    .section-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .report-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }
    
    .report-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 28px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .report-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .report-card:hover {
        transform: translateY(-4px);
        border-color: rgba(102, 126, 234, 0.4);
        background: rgba(255, 255, 255, 0.05);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
    }
    
    .report-card:hover::before {
        opacity: 1;
    }
    
    .report-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }
    
    .report-card:hover .report-icon {
        transform: scale(1.1) rotate(5deg);
    }
    
    .report-icon svg {
        width: 32px;
        height: 32px;
    }
    
    .report-card h3 {
        font-size: 1.25rem;
        margin-bottom: 12px;
        color: white;
    }
    
    .report-card p {
        color: var(--zinc-400);
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 24px;
        min-height: 48px;
    }
    
    .button-group {
        display: flex;
        gap: 10px;
        margin-bottom: 12px;
    }
    
    .btn-export {
        flex: 1;
        padding: 10px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .btn-csv {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-csv:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .btn-pdf {
        background: rgba(255, 255, 255, 0.05);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .btn-pdf:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    .btn-print {
        width: 100%;
        padding: 12px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-print:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
        border-color: rgba(102, 126, 234, 0.4);
        transform: translateY(-2px);
    }
    
    .custom-report-section {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 36px;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .form-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .form-field label {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--zinc-300);
    }
    
    .form-field input,
    .form-field select {
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: white;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    
    .form-field input:focus,
    .form-field select:focus {
        outline: none;
        border-color: #667eea;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-actions {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    
    .btn-action {
        flex: 1;
        min-width: 200px;
        padding: 14px 24px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-action-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-action-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-action-secondary {
        background: rgba(255, 255, 255, 0.05);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .btn-action-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    @media (max-width: 768px) {
        .reports-hero {
            padding: 24px;
        }
        
        .reports-hero h1 {
            font-size: 1.8rem;
        }
        
        .report-cards-grid {
            grid-template-columns: 1fr;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn-action {
            width: 100%;
        }
    }
</style>

<!-- Hero Section -->
<div class="reports-hero">
    <h1>📊 Generate Reports</h1>
    <p>Export cemetery data in various formats for analysis, archiving, or sharing. Choose from predefined reports or create custom date-range reports.</p>
</div>

<!-- Quick Reports Section -->
<div class="section-header">
    <div class="section-icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
    </div>
    <h2>Quick Reports</h2>
</div>

<div class="report-cards-grid">
        <!-- All Records Report -->
        <div class="report-card">
            <div class="report-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3>All Burial Records</h3>
            <p>Complete list of all burial records with full details including names, dates, locations, and plot information.</p>
            <div class="button-group">
                <button onclick="exportReport('all_records', 'csv')" class="btn-export btn-csv">
                    📄 CSV
                </button>
                <button onclick="exportReport('all_records', 'pdf')" class="btn-export btn-pdf">
                    📋 PDF
                </button>
            </div>
            <button onclick="window.open('print-all-records.php', '_blank')" class="btn-print">
                🖨️ View Print Report
            </button>
        </div>
        
        <!-- Statistics Report -->
        <div class="report-card">
            <div class="report-icon" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h3>Statistics Summary</h3>
            <p>Statistical analysis by barangay, year, and type with comprehensive charts and distribution data.</p>
            <div class="button-group">
                <button onclick="exportReport('statistics', 'csv')" class="btn-export btn-csv">
                    📄 CSV
                </button>
                <button onclick="exportReport('statistics', 'pdf')" class="btn-export btn-pdf">
                    📋 PDF
                </button>
            </div>
            <button onclick="window.open('print-statistics.php', '_blank')" class="btn-print">
                🖨️ View Print Report
            </button>
        </div>
        
        <!-- Available Plots Report -->
        <div class="report-card">
            <div class="report-icon" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                </svg>
            </div>
            <h3>Available Plots</h3>
            <p>List of all available burial plots with coordinates, compartments, and detailed location information.</p>
            <div class="button-group">
                <button onclick="exportReport('available_plots', 'csv')" class="btn-export btn-csv">
                    📄 CSV
                </button>
                <button onclick="exportReport('available_plots', 'pdf')" class="btn-export btn-pdf">
                    📋 PDF
                </button>
            </div>
            <button onclick="window.open('print-available-plots.php', '_blank')" class="btn-print">
                🖨️ View Print Report
            </button>
        </div>
    </div>

<!-- Custom Date Range Report -->
<div class="section-header" style="margin-top: 50px;">
    <div class="section-icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
    </div>
    <h2>Custom Date Range Report</h2>
</div>

<div class="custom-report-section">
    <form id="customReportForm">
        <div class="form-grid">
            <div class="form-field">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div class="form-field">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-field">
                <label for="report_type">Report Type</label>
                <select id="report_type" name="report_type" required>
                    <option value="burials">Burials Added</option>
                    <option value="deaths">Deaths by Date</option>
                </select>
            </div>
            <div class="form-field">
                <label for="barangay_filter">Filter by Barangay (Optional)</label>
                <select id="barangay_filter" name="barangay">
                    <option value="">All Barangays</option>
                    <option value="Matinao">Matinao</option>
                    <option value="Poblacion">Poblacion</option>
                    <option value="San Isidro">San Isidro</option>
                    <option value="San Jose">San Jose</option>
                    <option value="San Miguel">San Miguel</option>
                    <option value="San Pedro">San Pedro</option>
                    <option value="San Roque">San Roque</option>
                    <option value="Santa Cruz">Santa Cruz</option>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="button" onclick="generateCustomReport('csv')" class="btn-action btn-action-primary">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export as CSV
            </button>
            <button type="button" onclick="generateCustomReport('pdf')" class="btn-action btn-action-secondary">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Export as PDF
            </button>
        </div>
    </form>
</div>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <script>
        async function exportReport(reportType, format) {
            try {
                themeUtils.showAlert('Generating report...', 'info');
                
                const response = await fetch(`../api/generate_report.php?type=${reportType}&format=${format}`);
                
                if (!response.ok) {
                    throw new Error('Export failed');
                }
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `cemetery_${reportType}_${Date.now()}.${format}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                themeUtils.showAlert('Report generated successfully!', 'success');
            } catch (error) {
                themeUtils.showAlert('Failed to generate report', 'error');
            }
        }
        
        async function generateCustomReport(format) {
            const form = document.getElementById('customReportForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            params.append('format', format);
            
            try {
                themeUtils.showAlert('Generating custom report...', 'info');
                
                const response = await fetch(`../api/generate_report.php?${params.toString()}`);
                
                if (!response.ok) {
                    throw new Error('Export failed');
                }
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `cemetery_custom_report_${Date.now()}.${format}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                themeUtils.showAlert('Custom report generated successfully!', 'success');
            } catch (error) {
                themeUtils.showAlert('Failed to generate custom report', 'error');
            }
        }
        
        // Set default dates (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        document.getElementById('end_date').valueAsDate = today;
        document.getElementById('start_date').valueAsDate = thirtyDaysAgo;
    </script>
</body>
</html>
