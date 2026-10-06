        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileMenu()"></div>
        
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-logo">
                <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo">
                <div class="sidebar-logo-text">
                    <h2>Matinao Memorial</h2>
                    <p>Admin Panel</p>
                </div>
                <button type="button" class="sidebar-collapse" id="sidebarCollapse" onclick="toggleSidebarCollapse()" aria-label="Collapse sidebar">
                    <i data-lucide="menu" width="18" height="18" class="sidebar-collapse-icon"></i>
                </button>
            </div>

            <div class="sidebar-search">
                <i data-lucide="search" width="14" height="14"></i>
                <input type="text" id="sidebarSearch" placeholder="Search" autocomplete="off">
                <kbd>/</kbd>
            </div>

            <?php
            $groups = [
                'Main' => [
                    'icon' => 'home',
                    'items' => [
                        ['dashboard.php', 'dashboard', 'Dashboard', 'layout-dashboard'],
                    ],
                ],
                'Records' => [
                    'icon' => 'file-plus',
                    'add' => 'add-record.php',
                    'items' => [
                        ['records.php', 'records', 'All Records', 'file-text'],
                        ['burial-calendar.php', 'burial-calendar', 'Burial Calendar', 'calendar-days'],
                    ],
                ],
                'Cemetery Map' => [
                    'icon' => 'map',
                    'add' => 'available-plots.php',
                    'items' => [
                        ['map-view.php', 'map-view', 'Map View', 'map'],
                        ['available-plots.php', 'available-plots', 'Available Plots', 'map-pin'],
                        ['plot-grids.php', 'plot-grids', 'Plot Grids', 'grid'],
                    ],
                ],
                'Renewals' => [
                    'icon' => 'refresh-cw',
                    'items' => [
                        ['renewals.php', 'renewals', 'Renewal History', 'refresh-cw'],
                        ['expiring-plots.php', 'expiring-plots', 'Expiring Plots', 'alert-triangle'],
                    ],
                ],
                'Analytics' => [
                    'icon' => 'bar-chart-2',
                    'items' => [
                        ['statistics.php', 'statistics', 'Statistics', 'bar-chart-3'],
                        ['reports.php', 'reports', 'Reports', 'pie-chart'],
                    ],
                ],
                'Tools' => [
                    'icon' => 'bot',
                    'items' => [
                        ['assistant.php', 'assistant', 'AI Assistant', 'bot'],
                    ],
                ],
                'System' => [
                    'icon' => 'settings',
                    'items' => [
                        ['settings.php', 'settings', 'Settings', 'settings'],
                        ['api-keys.php', 'api-keys', 'AI API Keys', 'key'],
                    ],
                ],
            ];

            // Live counts shown beside nav items (like task counters)
            $navCounts = [];
            if (isset($pdo)) {
                try {
                    $navCounts['records'] = (int)$pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
                    $navCounts['available-plots'] = (int)$pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();
                    $navCounts['expiring-plots'] =
                        (int)$pdo->query("SELECT COUNT(*) FROM burial_records WHERE expiration_date IS NOT NULL AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn()
                        + (int)$pdo->query("SELECT COUNT(*) FROM available_plots WHERE expiration_date IS NOT NULL AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
                } catch (Throwable $e) {
                    $navCounts = [];
                }
            }
            ?>
            
            <ul class="sidebar-nav" id="sidebarNav">
                <?php foreach ($groups as $groupName => $group): 
                    $groupIcon = $group['icon'];
                    $items = $group['items'];
                ?>
                <li class="sidebar-group <?php echo in_array($current_page, array_column($items, 1), true) ? 'has-active' : ''; ?>">
                    <div class="sidebar-group-head">
                        <a href="<?php echo $items[0][0]; ?>" class="sidebar-group-label" title="<?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?>">
                            <i data-lucide="<?php echo $groupIcon; ?>" class="sidebar-group-icon" width="17" height="17"></i>
                            <span class="sidebar-group-title"><?php echo $groupName; ?></span>
                        </a>
                        <?php if (!empty($group['add'])): ?>
                            <a href="<?php echo $group['add']; ?>" class="sidebar-group-add" title="Add new" aria-label="Add new">
                                <i data-lucide="plus" width="13" height="13"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <ul class="sidebar-group-menu" data-group-label="<?php echo $groupName; ?>">
                        <?php foreach ($items as $item): 
                            $href = $item[0];
                            $page = $item[1];
                            $label = $item[2];
                            $icon = $item[3];
                            $isActive = $current_page === $page;
                            $count = $navCounts[$page] ?? null;
                        ?>
                        <li>
                            <a href="<?php echo $href; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                                <i data-lucide="<?php echo $icon; ?>" width="18" height="18"></i>
                                <?php echo $label; ?>
                                <?php if ($page === 'assistant'): ?>
                                    <span class="sidebar-link-badge">AI</span>
                                <?php endif; ?>
                                <?php if ($count !== null): ?>
                                    <span class="sidebar-link-count"><?php echo $count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="admin-main">
            <?php require_once 'topbar.php'; ?>

            <script>
            // Mobile Menu Toggle Function
            function toggleMobileMenu() {
                const sidebar = document.getElementById('adminSidebar');
                const overlay = document.getElementById('sidebarOverlay');

                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');

                // Prevent body scroll when menu is open
                if (sidebar.classList.contains('open')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }

            // Sidebar collapse/expand on desktop
            function toggleSidebarCollapse() {
                const layout = document.querySelector('.admin-layout');
                layout.classList.toggle('collapsed');
            }

            // Initialize Lucide icons first, then set up sidebar navigation
            function initLucideIcons() {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                    return true;
                }
                return false;
            }

            function initSidebarLinks() {
                const sidebarLinks = document.querySelectorAll('.sidebar-group-menu a');
                sidebarLinks.forEach(link => {
                    if (link.dataset.initialized === 'true') return;
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 1024) {
                            toggleMobileMenu();
                        }
                    });
                    link.dataset.initialized = 'true';
                });
            }

            // Mobile/desktop toggle visibility
            function updateMenuToggle() {
                const toggleBtn = document.getElementById('mobileMenuToggle');
                const collapseBtn = document.getElementById('sidebarCollapse');
                if (window.innerWidth <= 1024) {
                    if (toggleBtn) toggleBtn.style.display = 'flex';
                    if (collapseBtn) collapseBtn.style.display = 'none';
                    document.querySelector('.admin-layout').classList.remove('collapsed');
                } else {
                    if (toggleBtn) toggleBtn.style.display = 'none';
                    if (collapseBtn) collapseBtn.style.display = 'flex';
                    document.getElementById('adminSidebar').classList.remove('open');
                    document.getElementById('sidebarOverlay').classList.remove('active');
                    document.body.style.overflow = '';
                }
            }

            // Sidebar search — filters nav links, "/" focuses it
            function initSidebarSearch() {
                var input = document.getElementById('sidebarSearch');
                if (!input) return;

                input.addEventListener('input', function() {
                    var q = input.value.trim().toLowerCase();
                    document.querySelectorAll('#sidebarNav .sidebar-group').forEach(function(group) {
                        var anyVisible = false;
                        group.querySelectorAll('.sidebar-group-menu > li').forEach(function(li) {
                            var show = q === '' || li.textContent.toLowerCase().indexOf(q) !== -1;
                            li.style.display = show ? '' : 'none';
                            if (show) anyVisible = true;
                        });
                        group.style.display = anyVisible ? '' : 'none';
                    });
                });

                document.addEventListener('keydown', function(e) {
                    var tag = (document.activeElement && document.activeElement.tagName) || '';
                    if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(tag)) {
                        e.preventDefault();
                        input.focus();
                    }
                    if (e.key === 'Escape' && document.activeElement === input) {
                        input.value = '';
                        input.dispatchEvent(new Event('input'));
                        input.blur();
                    }
                });
            }

            // --- Initialize immediately (sidebar DOM is already available) ---
            initSidebarLinks();
            initSidebarSearch();
            updateMenuToggle();
            window.addEventListener('resize', updateMenuToggle);

            // --- Handle Lucide icons (may not be loaded yet on first visit) ---
            if (!initLucideIcons()) {
                // Lucide not loaded yet — poll until it's ready, then render icons
                var lucideAttempts = 0;
                var lucidePoll = setInterval(function() {
                    lucideAttempts++;
                    if (initLucideIcons()) {
                        clearInterval(lucidePoll);
                        // Icons are now rendered
                    } else if (lucideAttempts > 50) {
                        clearInterval(lucidePoll); // give up after ~5s
                    }
                }, 100);
            }

            // --- Also run on DOMContentLoaded as a safety net ---
            document.addEventListener('DOMContentLoaded', function() {
                initSidebarLinks();
                initLucideIcons();
                updateMenuToggle();
            });
            </script>

