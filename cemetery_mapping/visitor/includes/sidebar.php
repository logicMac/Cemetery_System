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

            <div class="sidebar-search">
                <i data-lucide="search" width="14" height="14"></i>
                <input type="text" id="sidebarSearch" placeholder="Search" autocomplete="off">
                <kbd>/</kbd>
            </div>

            <?php
            $isChat = (($_GET['open'] ?? '') === 'chat');
            $groups = [
                'Workspace' => [
                    'icon' => 'layout-dashboard',
                    'items' => [
                        ['dashboard.php', 'map', 'Cemetery Map', 'map', !$isChat && $current_page === 'dashboard', null],
                        ['dashboard.php?open=chat', 'chat', 'AI Assistant', 'bot', $isChat, 'AI'],
                    ],
                ],
                'Information' => [
                    'icon' => 'info',
                    'items' => [
                        ['../index.php#about', 'about', 'About', 'info', false, null],
                        ['../index.php#contact', 'contact', 'Contact', 'mail', false, null],
                    ],
                ],
            ];
            ?>

            <ul class="sidebar-nav" id="sidebarNav">
                <?php foreach ($groups as $groupName => $group):
                    $groupIcon = $group['icon'];
                    $items = $group['items'];
                    $hasActive = false;
                    foreach ($items as $it) { if ($it[4]) { $hasActive = true; break; } }
                ?>
                <li class="sidebar-group <?php echo $hasActive ? 'has-active' : ''; ?>">
                    <div class="sidebar-group-head">
                        <a href="<?php echo $items[0][0]; ?>" class="sidebar-group-label" title="<?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?>">
                            <i data-lucide="<?php echo $groupIcon; ?>" class="sidebar-group-icon" width="17" height="17"></i>
                            <span class="sidebar-group-title"><?php echo $groupName; ?></span>
                        </a>
                    </div>
                    <ul class="sidebar-group-menu" data-group-label="<?php echo $groupName; ?>">
                        <?php foreach ($items as $item): ?>
                        <li>
                            <a href="<?php echo $item[0]; ?>" class="<?php echo $item[4] ? 'active' : ''; ?>" <?php echo $item[4] ? 'aria-current="page"' : ''; ?>>
                                <i data-lucide="<?php echo $item[3]; ?>" width="18" height="18"></i>
                                <?php echo $item[2]; ?>
                                <?php if ($item[5] !== null): ?>
                                    <span class="sidebar-link-badge"><?php echo $item[5]; ?></span>
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
