<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';
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
        <i data-lucide="file-bar-chart" class="w-5 h-5"></i>
    </div>
    <div>
        <h2 class="text-xl font-bold text-slate-900">Reports</h2>
        <p class="text-sm text-slate-500">Export cemetery data in various formats for analysis, archiving, or sharing</p>
    </div>
</div>

<!-- Quick Reports Section -->
<div class="flex items-center gap-2 mb-4 mt-2">
    <i data-lucide="zap" class="w-4 h-4 text-emerald-600"></i>
    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700">Quick Reports</h3>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8 animate-fade">
    <!-- All Records Report -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-emerald-300 hover:shadow-md transition relative overflow-hidden group">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-emerald-600 opacity-0 group-hover:opacity-100 transition"></div>
        <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
            <i data-lucide="file-text" class="w-7 h-7"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900 mb-2">All Burial Records</h3>
        <p class="text-sm text-slate-500 mb-5 leading-relaxed">Complete list of all burial records with full details including names, dates, locations, and plot information.</p>
        <div class="flex gap-2 mb-2">
            <button onclick="exportReport('all_records', 'csv')" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                <i data-lucide="file-down" class="w-3.5 h-3.5"></i> CSV
            </button>
            <button onclick="exportReport('all_records', 'pdf')" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> PDF
            </button>
        </div>
        <button onclick="openPrintModal('print-all-records.php', 'All Burial Records')" class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="printer" class="w-3.5 h-3.5"></i> View Print Report
        </button>
    </div>

    <!-- Statistics Report -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-emerald-300 hover:shadow-md transition relative overflow-hidden group">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-emerald-600 opacity-0 group-hover:opacity-100 transition"></div>
        <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
            <i data-lucide="bar-chart-3" class="w-7 h-7"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900 mb-2">Statistics Summary</h3>
        <p class="text-sm text-slate-500 mb-5 leading-relaxed">Statistical analysis by barangay, year, and type with comprehensive charts and distribution data.</p>
        <div class="flex gap-2 mb-2">
            <button onclick="exportReport('statistics', 'csv')" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                <i data-lucide="file-down" class="w-3.5 h-3.5"></i> CSV
            </button>
            <button onclick="exportReport('statistics', 'pdf')" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> PDF
            </button>
        </div>
        <button onclick="openPrintModal('print-statistics.php', 'Statistics Summary')" class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="printer" class="w-3.5 h-3.5"></i> View Print Report
        </button>
    </div>

    <!-- Available Plots Report -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:border-emerald-300 hover:shadow-md transition relative overflow-hidden group">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-emerald-600 opacity-0 group-hover:opacity-100 transition"></div>
        <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
            <i data-lucide="map-pin" class="w-7 h-7"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900 mb-2">Available Plots</h3>
        <p class="text-sm text-slate-500 mb-5 leading-relaxed">List of all available burial plots with coordinates, compartments, and detailed location information.</p>
        <div class="flex gap-2 mb-2">
            <button onclick="exportReport('available_plots', 'csv')" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                <i data-lucide="file-down" class="w-3.5 h-3.5"></i> CSV
            </button>
            <button onclick="exportReport('available_plots', 'pdf')" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 rounded-lg bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> PDF
            </button>
        </div>
        <button onclick="openPrintModal('print-available-plots.php', 'Available Plots')" class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-lg bg-slate-50 border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="printer" class="w-3.5 h-3.5"></i> View Print Report
        </button>
    </div>
</div>

<!-- Custom Date Range Report -->
<div class="flex items-center gap-2 mb-4 mt-8">
    <i data-lucide="calendar-range" class="w-4 h-4 text-emerald-600"></i>
    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700">Custom Date Range Report</h3>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 animate-fade">
    <form id="customReportForm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
                <label for="start_date" class="block text-sm font-medium text-slate-700 mb-1.5">Start Date</label>
                <input type="date" id="start_date" name="start_date" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-slate-700 mb-1.5">End Date</label>
                <input type="date" id="end_date" name="end_date" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
            <div>
                <label for="report_type" class="block text-sm font-medium text-slate-700 mb-1.5">Report Type</label>
                <select id="report_type" name="report_type" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
                    <option value="burials">Burials Added</option>
                    <option value="deaths">Deaths by Date</option>
                </select>
            </div>
            <div>
                <label for="barangay_filter" class="block text-sm font-medium text-slate-700 mb-1.5">Filter by Barangay (Optional)</label>
                <select id="barangay_filter" name="barangay" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition">
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

        <div class="flex gap-3 flex-wrap">
            <button type="button" onclick="generateCustomReport('csv')" class="flex-1 min-w-[200px] inline-flex items-center justify-center gap-2 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition">
                <i data-lucide="file-down" class="w-4 h-4"></i> Export as CSV
            </button>
            <button type="button" onclick="generateCustomReport('pdf')" class="flex-1 min-w-[200px] inline-flex items-center justify-center gap-2 py-3 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold transition">
                <i data-lucide="file-text" class="w-4 h-4"></i> Export as PDF
            </button>
        </div>
    </form>
</div>

<!-- Print Report Modal -->
<div id="printModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" style="background: rgba(15,23,42,0.5); backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[94vh] flex flex-col overflow-hidden animate-fade">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-emerald-50/50 to-white">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 id="printModalTitle" class="text-base font-bold text-slate-900">Print Report</h3>
                    <p class="text-xs text-slate-500 flex items-center gap-1.5">
                        <span id="printStatusDot" class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                        <span id="printStatusText">Loading report...</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Zoom controls -->
                <div class="flex items-center gap-1 mr-2 px-2 py-1 rounded-lg bg-slate-100 border border-slate-200">
                    <button onclick="zoomOut()" title="Zoom out" class="w-7 h-7 inline-flex items-center justify-center rounded text-slate-600 hover:bg-white hover:text-slate-900 transition">
                        <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                    </button>
                    <span id="zoomLevel" class="text-xs font-semibold text-slate-600 min-w-[42px] text-center">100%</span>
                    <button onclick="zoomIn()" title="Zoom in" class="w-7 h-7 inline-flex items-center justify-center rounded text-slate-600 hover:bg-white hover:text-slate-900 transition">
                        <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                    </button>
                    <button onclick="zoomReset()" title="Reset zoom" class="w-7 h-7 inline-flex items-center justify-center rounded text-slate-600 hover:bg-white hover:text-slate-900 transition">
                        <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
                <!-- Open in new tab -->
                <button onclick="openInNewTab()" title="Open in new tab" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                </button>
                <!-- Refresh -->
                <button onclick="refreshIframe()" title="Reload" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
                <!-- Print -->
                <button onclick="printIframe()" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 transition shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print
                </button>
                <!-- Close -->
                <button onclick="closePrintModal()" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        <!-- Modal Body / iframe -->
        <div class="flex-1 overflow-auto bg-slate-100 relative">
            <!-- Loading overlay -->
            <div id="printLoading" class="absolute inset-0 flex items-center justify-center bg-slate-100 z-10">
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full border-4 border-emerald-200 border-t-emerald-600 animate-spin"></div>
                    <p class="text-sm text-slate-500 font-medium">Preparing report...</p>
                </div>
            </div>
            <div id="iframeWrap" class="mx-auto my-4 bg-white shadow-lg transition-all duration-200" style="width: 90%;">
                <iframe id="printIframe" class="w-full border-0 block" style="min-height: 75vh; height: 75vh;"></iframe>
            </div>
        </div>
        <!-- Status bar -->
        <div class="px-5 py-2.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-3 h-3"></i> <span id="printLoadedTime">—</span></span>
                <span class="flex items-center gap-1.5"><i data-lucide="monitor" class="w-3 h-3"></i> A4 Portrait</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-slate-400">Tip: Press <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-200 text-[10px] font-mono">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-200 text-[10px] font-mono">P</kbd> to print</span>
            </div>
        </div>
    </div>
</div>

        </main>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <script>
        let currentPrintUrl = '';
        let zoomLevel = 100;

        async function exportReport(reportType, format) {
            try {
                themeUtils.showAlert('Generating report...', 'info');
                const response = await fetch(`../api/generate_report.php?type=${reportType}&format=${format}`);
                if (!response.ok) throw new Error('Export failed');
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
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            params.append('format', format);
            try {
                themeUtils.showAlert('Generating custom report...', 'info');
                const response = await fetch(`../api/generate_report.php?${params.toString()}`);
                if (!response.ok) throw new Error('Export failed');
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

        // Print modal functions
        function openPrintModal(url, title) {
            const modal = document.getElementById('printModal');
            const iframe = document.getElementById('printIframe');
            currentPrintUrl = url;
            document.getElementById('printModalTitle').textContent = title;
            // Reset state
            zoomLevel = 100;
            updateZoom();
            setStatus('loading', 'Loading report...');
            document.getElementById('printLoadedTime').textContent = '—';
            document.getElementById('printLoading').style.display = 'flex';
            iframe.src = url;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closePrintModal() {
            const modal = document.getElementById('printModal');
            const iframe = document.getElementById('printIframe');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            iframe.src = 'about:blank';
            document.body.style.overflow = '';
            currentPrintUrl = '';
        }

        function printIframe() {
            const iframe = document.getElementById('printIframe');
            try {
                if (!iframe.contentWindow || iframe.src === 'about:blank') {
                    themeUtils.showAlert('Please wait for the report to load before printing.', 'info');
                    return;
                }
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                themeUtils.showAlert('Please wait for the report to load before printing.', 'info');
            }
        }

        function openInNewTab() {
            if (currentPrintUrl) window.open(currentPrintUrl, '_blank');
        }

        function refreshIframe() {
            const iframe = document.getElementById('printIframe');
            if (currentPrintUrl) {
                setStatus('loading', 'Reloading...');
                document.getElementById('printLoading').style.display = 'flex';
                iframe.src = currentPrintUrl;
            }
        }

        function zoomIn() {
            zoomLevel = Math.min(zoomLevel + 10, 200);
            updateZoom();
        }

        function zoomOut() {
            zoomLevel = Math.max(zoomLevel - 10, 50);
            updateZoom();
        }

        function zoomReset() {
            zoomLevel = 100;
            updateZoom();
        }

        function updateZoom() {
            const wrap = document.getElementById('iframeWrap');
            wrap.style.width = (90 * (zoomLevel / 100)) + '%';
            document.getElementById('zoomLevel').textContent = zoomLevel + '%';
        }

        function setStatus(state, text) {
            const dot = document.getElementById('printStatusDot');
            const txt = document.getElementById('printStatusText');
            txt.textContent = text;
            if (state === 'loading') { dot.className = 'w-1.5 h-1.5 rounded-full bg-amber-400 inline-block animate-pulse'; }
            else if (state === 'ready') { dot.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block'; }
            else if (state === 'error') { dot.className = 'w-1.5 h-1.5 rounded-full bg-rose-500 inline-block'; }
        }

        // Iframe load handler
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('printModal');
            const iframe = document.getElementById('printIframe');

            modal.addEventListener('click', function(e) {
                if (e.target === modal) closePrintModal();
            });

            iframe.addEventListener('load', function() {
                document.getElementById('printLoading').style.display = 'none';
                setStatus('ready', 'Report ready to print');
                const now = new Date();
                document.getElementById('printLoadedTime').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (modal.classList.contains('hidden')) return;
                if (e.key === 'Escape') { closePrintModal(); }
                else if (e.key === 'p' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); printIframe(); }
                else if (e.key === '+' || e.key === '=') { e.preventDefault(); zoomIn(); }
                else if (e.key === '-' || e.key === '_') { e.preventDefault(); zoomOut(); }
                else if (e.key === '0') { e.preventDefault(); zoomReset(); }
                else if (e.key === 'r' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); refreshIframe(); }
            });

            // Set default dates (last 30 days)
            const today = new Date();
            const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
            document.getElementById('end_date').valueAsDate = today;
            document.getElementById('start_date').valueAsDate = thirtyDaysAgo;

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
