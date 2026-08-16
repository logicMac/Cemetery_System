        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileMenu()"></div>

        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-logo">
                <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo">
                <div class="sidebar-logo-text">
                    <h2>Matinao Memorial</h2>
                    <p>Visitor Portal</p>
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
                        ['dashboard.php', 'dashboard', 'Cemetery Map', 'map'],
                    ],
                ],
                'Plots' => [
                    'icon' => 'map-pin',
                    'items' => [
                        ['available-plots.php', 'available-plots', 'Available Plots', 'map-pin'],
                    ],
                ],
                'Reservations' => [
                    'icon' => 'calendar',
                    'items' => [
                        ['my-reservations.php', 'my-reservations', 'My Reservations', 'calendar-check'],
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
                            <i data-lucide="<?php echo $groupIcon; ?>" class="sidebar-group-icon" width="20" height="20"></i>
                            <span class="sidebar-group-name"><?php echo $groupName; ?></span>
                        </span>
                        <i data-lucide="chevron-down" class="sidebar-group-chevron" width="14" height="14"></i>
                    </button>
                    <ul class="sidebar-group-menu">
                        <?php foreach ($items as $item): ?>
                        <li class="sidebar-group-item <?php echo $current_page === $item[1] ? 'active' : ''; ?>">
                            <a href="<?php echo $item[0]; ?>" class="sidebar-link">
                                <i data-lucide="<?php echo $item[3]; ?>" class="sidebar-link-icon" width="18" height="18"></i>
                                <span class="sidebar-link-text"><?php echo $item[2]; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    <?php echo strtoupper(substr($visitor_name, 0, 1)); ?>
                </div>
                <div class="sidebar-user-info">
                    <p class="sidebar-user-name"><?php echo $visitor_name; ?></p>
                    <p class="sidebar-user-role">Visitor</p>
                </div>
                <button type="button" class="sidebar-user-logout" onclick="toggleSidebarCollapse()" title="Collapse sidebar" aria-label="Collapse sidebar">
                    <i data-lucide="chevrons-left" width="18" height="18"></i>
                </button>
            </div>
        </aside>

        <main class="admin-main">
