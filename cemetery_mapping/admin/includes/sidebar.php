        <!-- Mobile Menu Toggle Button -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()" style="display: none;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileMenu()"></div>
        
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-logo">
                <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" style="width: 100px; height: 100px; margin: 0 auto 16px; display: block; border-radius: 50%; object-fit: cover;">
                <h2>Matinao Memorial</h2>
                <p style="color: var(--zinc-400); font-size: 0.85rem; margin-top: 4px;">Admin Panel</p>
            </div>
            
            <ul class="sidebar-nav">
                <li>
                    <a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="add-record.php" class="<?php echo $current_page === 'add-record' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Record
                    </a>
                </li>
                <li>
                    <a href="records.php" class="<?php echo $current_page === 'records' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        All Records
                    </a>
                </li>
                <li>
                    <a href="map-view.php" class="<?php echo $current_page === 'map-view' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        Map View
                    </a>
                </li>
                <li>
                    <a href="available-plots.php" class="<?php echo $current_page === 'available-plots' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Available Plots
                    </a>
                </li>
                <li>
                    <a href="statistics.php" class="<?php echo $current_page === 'statistics' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Statistics
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="<?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Reports
                    </a>
                </li>
                <li>
                    <a href="reservations_simple.php" class="<?php echo $current_page === 'reservations_simple' || $current_page === 'reservations' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Reservations
                    </a>
                </li>
                <li>
                    <a href="assistant.php" class="<?php echo $current_page === 'assistant' ? 'active' : ''; ?>" style="position: relative;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        AI Assistant
                        <span style="position: absolute; top: 8px; right: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 8px; font-weight: 600;">AI</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Settings
                    </a>
                </li>
                <li style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--glass-border);">
                    <a href="logout.php">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1><?php echo ucfirst(str_replace('-', ' ', $current_page)); ?></h1>
                <div class="admin-user">
                    <div class="admin-user-avatar">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <span><?php echo $admin_username; ?></span>
                </div>
            </div>
            
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
            
            // Close menu when clicking on a link
            document.addEventListener('DOMContentLoaded', function() {
                const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 1024) {
                            toggleMobileMenu();
                        }
                    });
                });
                
                // Show mobile menu toggle on small screens
                function updateMenuToggle() {
                    const toggleBtn = document.getElementById('mobileMenuToggle');
                    if (window.innerWidth <= 1024) {
                        toggleBtn.style.display = 'block';
                    } else {
                        toggleBtn.style.display = 'none';
                        // Ensure sidebar is visible on desktop
                        document.getElementById('adminSidebar').classList.remove('open');
                        document.getElementById('sidebarOverlay').classList.remove('active');
                        document.body.style.overflow = '';
                    }
                }
                
                updateMenuToggle();
                window.addEventListener('resize', updateMenuToggle);
            });
            </script>

