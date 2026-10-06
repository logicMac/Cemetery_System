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

            <ul class="sidebar-nav visitor-nav" id="sidebarNav">
                <li class="visitor-nav-heading">Workspace</li>
                <li class="visitor-nav-item <?php echo (($_GET['open'] ?? '') !== 'chat' && $current_page === 'dashboard') ? 'active' : ''; ?>">
                    <a href="dashboard.php" class="sidebar-link" title="Cemetery Map" <?php echo (($_GET['open'] ?? '') !== 'chat' && $current_page === 'dashboard') ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="map" class="sidebar-link-icon" width="18" height="18"></i>
                        <span class="sidebar-link-text">Cemetery Map</span>
                    </a>
                </li>
                <li class="visitor-nav-item <?php echo (($_GET['open'] ?? '') === 'chat') ? 'active' : ''; ?>">
                    <a href="dashboard.php?open=chat" class="sidebar-link" title="AI Assistant" <?php echo (($_GET['open'] ?? '') === 'chat') ? 'aria-current="page"' : ''; ?>>
                        <i data-lucide="bot" class="sidebar-link-icon" width="18" height="18"></i>
                        <span class="sidebar-link-text">AI Assistant</span>
                        <span class="sidebar-link-badge">AI</span>
                    </a>
                </li>
                <li class="visitor-nav-heading">Information</li>
                <li class="visitor-nav-item">
                    <a href="../index.php#about" class="sidebar-link" title="About">
                        <i data-lucide="info" class="sidebar-link-icon" width="18" height="18"></i>
                        <span class="sidebar-link-text">About</span>
                    </a>
                </li>
                <li class="visitor-nav-item">
                    <a href="../index.php#contact" class="sidebar-link" title="Contact">
                        <i data-lucide="mail" class="sidebar-link-icon" width="18" height="18"></i>
                        <span class="sidebar-link-text">Contact</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="admin-main">
