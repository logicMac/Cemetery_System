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
                    'items' => [
                        ['add-record.php', 'add-record', 'Add Record', 'plus-circle'],
                        ['records.php', 'records', 'All Records', 'file-text'],
                    ],
                ],
                'Cemetery Map' => [
                    'icon' => 'map',
                    'items' => [
                        ['map-view.php', 'map-view', 'Map View', 'map'],
                        ['available-plots.php', 'available-plots', 'Available Plots', 'map-pin'],
                    ],
                ],
                'Reservations' => [
                    'icon' => 'calendar',
                    'items' => [
                        ['reservations_simple.php', 'reservations_simple', 'Reservations', 'calendar-check'],
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
                    ],
                ],
            ];
            ?>
            
            <ul class="sidebar-nav" id="sidebarNav">
                <?php foreach ($groups as $groupName => $group): 
                    $groupIcon = $group['icon'];
                    $items = $group['items'];
                    $hasActive = false;
                    foreach ($items as $item) {
                        if ($current_page === $item[1]) {
                            $hasActive = true;
                            break;
                        }
                    }
                ?>
                <li class="sidebar-group <?php echo $hasActive ? 'is-open' : ''; ?>">
                    <button type="button" class="sidebar-group-toggle" aria-expanded="<?php echo $hasActive ? 'true' : 'false'; ?>">
                        <span class="sidebar-group-left">
                            <i data-lucide="<?php echo $groupIcon; ?>" width="18" height="18"></i>
                            <span class="sidebar-group-title"><?php echo $groupName; ?></span>
                        </span>
                        <i data-lucide="chevron-down" class="sidebar-chevron" width="16" height="16"></i>
                    </button>
                    <ul class="sidebar-group-menu">
                        <?php foreach ($items as $item): 
                            $href = $item[0];
                            $page = $item[1];
                            $label = $item[2];
                            $icon = $item[3];
                            $isActive = $current_page === $page;
                        ?>
                        <li>
                            <a href="<?php echo $href; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                                <i data-lucide="<?php echo $icon; ?>" width="18" height="18"></i>
                                <?php echo $label; ?>
                                <?php if ($page === 'assistant'): ?>
                                    <span style="position: absolute; top: 8px; right: 12px; background: #10b981; color: white; font-size: 0.6rem; padding: 2px 6px; border-radius: 6px; font-weight: 700;">AI</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                </div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?php echo $admin_username; ?></span>
                    <span class="sidebar-user-role">Administrator</span>
                </div>
            </div>
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

                // Re-calculate open menus after transition so they don't get cut off
                window.setTimeout(() => {
                    document.querySelectorAll('.sidebar-group.is-open .sidebar-group-menu').forEach(menu => {
                        menu.style.maxHeight = menu.scrollHeight + 'px';
                    });
                }, 360);
            }

            // Initialize Lucide icons first, then set up accordion heights
            function initLucideIcons() {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                    return true;
                }
                return false;
            }

            // Sidebar Accordion — runs immediately (sidebar DOM is already parsed)
            function initSidebarAccordion() {
                const toggles = document.querySelectorAll('.sidebar-group-toggle');

                toggles.forEach(toggle => {
                    // Skip if already initialized (prevents double-binding on reloads)
                    if (toggle.dataset.initialized === 'true') return;

                    const group = toggle.closest('.sidebar-group');

                    toggle.addEventListener('click', function() {
                        const isOpen = group.classList.contains('is-open');

                        // Close others (accordion behavior)
                        document.querySelectorAll('.sidebar-group.is-open').forEach(openGroup => {
                            if (openGroup !== group) {
                                openGroup.classList.remove('is-open');
                                openGroup.querySelector('.sidebar-group-toggle').setAttribute('aria-expanded', 'false');
                            }
                        });

                        if (isOpen) {
                            group.classList.remove('is-open');
                            toggle.setAttribute('aria-expanded', 'false');
                        } else {
                            group.classList.add('is-open');
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    });

                    toggle.dataset.initialized = 'true';
                });

                // Close menu when clicking on a link
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

            // Recalculate open menu heights after icons render (no longer needed with CSS approach)
            function recalcOpenMenuHeights() {}

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

            // --- Initialize immediately (sidebar DOM is already available) ---
            initSidebarAccordion();
            updateMenuToggle();
            window.addEventListener('resize', updateMenuToggle);

            // --- Handle Lucide icons (may not be loaded yet on first visit) ---
            if (!initLucideIcons()) {
                // Lucide not loaded yet — poll until it's ready, then render icons and fix heights
                var lucideAttempts = 0;
                var lucidePoll = setInterval(function() {
                    lucideAttempts++;
                    if (initLucideIcons()) {
                        clearInterval(lucidePoll);
                        // Icons are now rendered — recalculate heights since icons changed layout
                        recalcOpenMenuHeights();
                    } else if (lucideAttempts > 50) {
                        clearInterval(lucidePoll); // give up after ~5s
                    }
                }, 100);
            } else {
                // Lucide was already loaded — icons rendered, recalc heights to be safe
                recalcOpenMenuHeights();
            }

            // --- Also run on DOMContentLoaded as a safety net ---
            document.addEventListener('DOMContentLoaded', function() {
                initSidebarAccordion();
                if (initLucideIcons()) {
                    recalcOpenMenuHeights();
                }
                updateMenuToggle();
            });
            </script>

