<?php
// Check if visitor is logged in
if (!isset($_SESSION['visitor_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$_SESSION['last_activity'] = time();

$visitor_name = htmlspecialchars($_SESSION['visitor_name'] ?? 'Visitor', ENT_QUOTES, 'UTF-8');
$visitor_email = htmlspecialchars($_SESSION['visitor_email'] ?? '', ENT_QUOTES, 'UTF-8');
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, interactive-widget=resizes-content">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/images/favicon.ico?v=2">
    <link rel="icon" type="image/png" href="../assets/images/favicon.png?v=2">
    <title><?php echo ucfirst(str_replace('-', ' ', $current_page)); ?> - Visitor Portal</title>

    <script>
    // Apply saved theme before first paint (prevents flash of wrong theme)
    (function () {
        try {
            var t = localStorage.getItem('cm-theme');
            if (t === 'dark' || (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        } catch (e) {}
    })();
    function toggleTheme(e) {
        var el = document.documentElement;
        var dark = el.getAttribute('data-theme') === 'dark';
        function applyTheme() {
            if (dark) {
                el.removeAttribute('data-theme');
                try { localStorage.setItem('cm-theme', 'light'); } catch (err) {}
            } else {
                el.setAttribute('data-theme', 'dark');
                try { localStorage.setItem('cm-theme', 'dark'); } catch (err) {}
            }
        }

        // Circular reveal via View Transitions API (falls back to instant switch)
        if (!document.startViewTransition || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            applyTheme();
            return;
        }
        var x = window.innerWidth / 2, y = window.innerHeight / 2;
        if (e && e.clientX) { x = e.clientX; y = e.clientY; }
        else if (e && e.currentTarget) {
            var r = e.currentTarget.getBoundingClientRect();
            x = r.left + r.width / 2; y = r.top + r.height / 2;
        }
        var transition = document.startViewTransition(applyTheme);
        transition.ready.then(function () {
            var radius = Math.hypot(Math.max(x, window.innerWidth - x), Math.max(y, window.innerHeight - y));
            el.animate(
                { clipPath: ['circle(0px at ' + x + 'px ' + y + 'px)', 'circle(' + radius + 'px at ' + x + 'px ' + y + 'px)'] },
                { duration: 500, easing: 'ease-in-out', pseudoElement: '::view-transition-new(root)' }
            );
        }).catch(function () {});
    }
    </script>

    <!-- Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- Lenis smooth scroll -->
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.3.11/dist/lenis.min.js"></script>
    <script src="../assets/js/lenis-init.js?v=1"></script>

    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/theme.css?v=8">
    <link rel="stylesheet" href="../assets/css/admin.css?v=26">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css?v=3">

    <style>
        /* Visitor portal layout fixes */
        html, body, .admin-layout { min-height: 100vh; }
        .admin-main { position: relative; z-index: 1; }
        .admin-sidebar { z-index: 200; }
        .admin-header { z-index: 10000; }
    </style>
</head>
<body>
    <div class="admin-layout">
