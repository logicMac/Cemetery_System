<?php
require_once 'config/database.php';

$counts = ['records' => 0, 'plots' => 0, 'reservations' => 0];
$available_count = 0;

try {
    $records = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
    $plots = $pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();
    $reservations = $pdo->query("SELECT COUNT(*) FROM plot_reservations")->fetchColumn();
    $available = $pdo->query("SELECT COUNT(*) FROM available_plots ap WHERE NOT EXISTS (SELECT 1 FROM plot_reservations pr WHERE pr.plot_id = ap.id AND pr.status IN ('pending','approved') AND pr.compartment_id IS NULL)")->fetchColumn();

    $counts = [
        'records' => (int)$records,
        'plots' => (int)$plots,
        'reservations' => (int)$reservations
    ];
    $available_count = (int)$available;
} catch (PDOException $e) {
    error_log('Index stats error: ' . $e->getMessage());
}
$percentage = $counts['plots'] > 0 ? round(($available_count / $counts['plots']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico?v=2">
    <link rel="icon" type="image/png" href="assets/images/favicon.png?v=2">
    <title>Matinao Memorial Cemetery — Mapping & Management System</title>
    <meta name="description" content="Matinao Memorial Cemetery in Polomolok, South Cotabato — find loved ones, reserve plots, and explore our grounds with GPS-enabled interactive mapping.">

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
    function toggleTheme() {
        var el = document.documentElement;
        var dark = el.getAttribute('data-theme') === 'dark';
        if (dark) {
            el.removeAttribute('data-theme');
            try { localStorage.setItem('cm-theme', 'light'); } catch (e) {}
        } else {
            el.setAttribute('data-theme', 'dark');
            try { localStorage.setItem('cm-theme', 'dark'); } catch (e) {}
        }
    }
    </script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Public theme (light/dark) -->
    <link rel="stylesheet" href="assets/css/public-theme.css?v=3">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        * { font-family: 'Poppins', sans-serif; }
        html { scroll-behavior: smooth; }
        body { background: #f8fafc; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-12px); } }
        @keyframes pulseSlow { 0%, 100% { opacity: 0.5; transform: scale(1); } 50% { opacity: 0.7; transform: scale(1.06); } }
        @keyframes pulseDot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.4); } }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(8px); } }
        .animate-fade-in { animation: fadeIn 1s ease both; }
        .animate-fade-up { animation: fadeUp 0.8s ease both; }
        .animate-fade-down { animation: fadeDown 0.7s ease both; }
        .animate-float { animation: float 5s ease-in-out infinite; }
        .animate-pulse-slow { animation: pulseSlow 7s ease-in-out infinite; }
        .animate-pulse-dot { animation: pulseDot 1.8s ease-in-out infinite; }
        .animate-bounce-slow { animation: bounce 2s ease-in-out infinite; }

        /* Scroll-triggered reveal animations */
        .reveal { opacity: 0; transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal-up { transform: translateY(40px); }
        .reveal-left { transform: translateX(-50px); }
        .reveal-right { transform: translateX(50px); }
        .reveal-scale { transform: scale(0.9); }
        .reveal.visible { opacity: 1; transform: translate(0, 0) scale(1); }
        .reveal-stagger > * { opacity: 0; transform: translateY(30px); transition: opacity 0.6s ease, transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal-stagger.visible > * { opacity: 1; transform: translateY(0); }
        .reveal-stagger.visible > *:nth-child(1) { transition-delay: 0s; }
        .reveal-stagger.visible > *:nth-child(2) { transition-delay: 0.1s; }
        .reveal-stagger.visible > *:nth-child(3) { transition-delay: 0.2s; }
        .reveal-stagger.visible > *:nth-child(4) { transition-delay: 0.3s; }
        .reveal-stagger.visible > *:nth-child(5) { transition-delay: 0.4s; }
        .reveal-stagger.visible > *:nth-child(6) { transition-delay: 0.5s; }

        button svg, a svg, button i, a i { pointer-events: none; }
        .nav-link { position: relative; }
        .nav-link::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: #10b981; border-radius: 2px; transition: width 0.3s ease; }
        .nav-link:hover::after { width: 100%; }

        /* Subtle texture for sections */
        .texture-grid {
            background-image: linear-gradient(#10b981 1px, transparent 1px), linear-gradient(90deg, #10b981 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Feature card top accent */
        .feature-card { position: relative; }
        .feature-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7);
            opacity: 0; transition: opacity 0.35s ease; border-radius: 1rem 1rem 0 0;
        }
        .feature-card:hover::before { opacity: 1; }

        /* Step connector */
        .step-connector {
            background-image: linear-gradient(90deg, #34d399 55%, transparent 45%);
            background-size: 14px 2px; background-repeat: repeat-x; background-position: center;
        }

        /* Mint hero wash (light mode; dark variant lives in public-theme.css) */
        .hero-bg {
            background: radial-gradient(1100px 520px at 50% -60px, #c9f5e1 0%, #e8fbf2 38%, #f8fafc 78%);
        }
        .hero-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image: linear-gradient(#10b981 1px, transparent 1px), linear-gradient(90deg, #10b981 1px, transparent 1px);
            background-size: 44px 44px;
            opacity: 0.05;
            -webkit-mask-image: radial-gradient(ellipse at 50% 0%, black 0%, transparent 70%);
            mask-image: radial-gradient(ellipse at 50% 0%, black 0%, transparent 70%);
        }
    </style>
</head>
<body class="text-slate-800 antialiased">

    <!-- ===================== NAVBAR ===================== -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/70 transition-all">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 h-16 flex items-center justify-between">
            <!-- Logo -->
            <a href="#" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white shadow-md border border-emerald-200 flex items-center justify-center">
                    <img src="assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-8 h-8 rounded-full object-cover">
                </div>
                <div class="hidden sm:block">
                    <div class="text-sm font-bold text-slate-900 leading-tight">Matinao Memorial</div>
                    <div class="text-[11px] text-emerald-600 font-medium leading-tight">Cemetery System</div>
                </div>
            </a>

            <!-- Desktop nav -->
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#home" class="nav-link hover:text-emerald-600 transition">Home</a>
                <a href="#features" class="nav-link hover:text-emerald-600 transition">Features</a>
                <a href="#services" class="nav-link hover:text-emerald-600 transition">Services</a>
                <a href="#about" class="nav-link hover:text-emerald-600 transition">About</a>
                <a href="#contact" class="nav-link hover:text-emerald-600 transition">Contact</a>
            </div>

            <!-- CTA -->
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleTheme()" title="Toggle dark mode" aria-label="Toggle dark mode" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                    <i data-lucide="moon" class="w-4 h-4 theme-icon-moon"></i>
                    <i data-lucide="sun" class="w-4 h-4 theme-icon-sun"></i>
                </button>
                <a href="login.php" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700 hover:text-emerald-600 transition px-3 py-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Sign In
                </a>
                <a href="visitor/register.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-full px-5 py-2 transition shadow-sm shadow-emerald-200">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Register
                </a>
            </div>
        </div>
    </nav>

    <!-- ===================== HERO ===================== -->
    <section id="home" class="relative pt-28 pb-20 overflow-hidden hero-bg">

        <div class="max-w-4xl mx-auto px-6 lg:px-10 text-center relative">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/80 backdrop-blur text-emerald-700 text-xs font-semibold mb-7 border border-emerald-200 shadow-sm animate-fade-down">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse-dot"></span>
                Serving Polomolok, South Cotabato
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-[4.2rem] font-bold leading-[1.05] tracking-tight mb-6 animate-fade-up">
                <span class="text-slate-900">Everything Your Cemetery</span><br>
                <span class="text-emerald-500">Needs to Remember.</span>
            </h1>

            <p class="text-slate-500 text-base sm:text-lg leading-relaxed mb-9 max-w-2xl mx-auto animate-fade-up" style="animation-delay: 0.1s;">
                GPS-enabled plot mapping, instant burial record search, online reservations, and an AI guide — unified in one connected system.
            </p>

            <!-- CTAs -->
            <div class="flex flex-wrap justify-center gap-3 mb-16 animate-fade-up" style="animation-delay: 0.2s;">
                <a href="login.php" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-7 py-3.5 transition shadow-lg shadow-emerald-200 hover:shadow-emerald-300">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Visitor Portal
                </a>
                <a href="#features" class="inline-flex items-center gap-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-semibold px-7 py-3.5 transition shadow-sm">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i> Explore the Features
                </a>
            </div>
        </div>

        <!-- App showcase -->
        <div class="max-w-6xl mx-auto px-6 lg:px-10 relative animate-fade-up" style="animation-delay: 0.3s;">
            <div class="rounded-2xl bg-white border border-slate-200 shadow-2xl shadow-emerald-100/60 overflow-hidden">
                <!-- Browser chrome -->
                <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50/60">
                    <span class="w-3 h-3 rounded-full bg-rose-300"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-300"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-300"></span>
                    <div class="ml-3 flex-1 max-w-xs">
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-md bg-white border border-slate-200 text-[10px] text-slate-400 font-medium">
                            <i data-lucide="lock" class="w-3 h-3 text-emerald-500"></i>
                            matinao-memorial.app/dashboard
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-[230px_1fr] text-left">
                    <!-- Faux sidebar -->
                    <aside class="hidden lg:flex flex-col border-r border-slate-100 bg-slate-50/50 p-4">
                        <div class="flex items-center gap-2.5 px-2 mb-6">
                            <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center"><i data-lucide="map-pin" class="w-4 h-4"></i></div>
                            <div>
                                <div class="text-xs font-bold text-slate-900 leading-tight">Matinao Memorial</div>
                                <div class="text-[10px] text-emerald-600 font-medium leading-tight">Admin Panel</div>
                            </div>
                        </div>
                        <nav class="space-y-1 text-xs font-medium text-slate-500 flex-1">
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-emerald-600 text-white shadow-sm"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard</div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-100 transition"><i data-lucide="map" class="w-4 h-4"></i> Map View</div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-100 transition"><i data-lucide="file-text" class="w-4 h-4"></i> Burial Records</div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-100 transition"><i data-lucide="grid-3x3" class="w-4 h-4"></i> Plot Grids</div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-100 transition"><i data-lucide="calendar-check" class="w-4 h-4"></i> Reservations</div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-100 transition"><i data-lucide="refresh-cw" class="w-4 h-4"></i> Renewals</div>
                            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-slate-100 transition"><i data-lucide="bar-chart-3" class="w-4 h-4"></i> Reports</div>
                        </nav>
                        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-500"><i data-lucide="settings" class="w-4 h-4"></i> Settings</div>
                    </aside>

                    <!-- Mock dashboard -->
                    <div class="p-5 sm:p-6">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <div class="text-sm font-bold text-slate-900">Cemetery Overview</div>
                                <div class="text-xs text-slate-400">Live data preview</div>
                            </div>
                            <div class="px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-semibold">Updated now</div>
                        </div>

                        <!-- Stat cards -->
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="rounded-xl bg-white border border-slate-100 p-3.5 shadow-sm hover:shadow-md transition-shadow">
                                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2"><i data-lucide="file-text" class="w-3.5 h-3.5"></i></div>
                                <div class="text-lg font-bold text-slate-900"><?php echo number_format($counts['records']); ?></div>
                                <div class="text-[10px] text-slate-500">Records</div>
                            </div>
                            <div class="rounded-xl bg-white border border-slate-100 p-3.5 shadow-sm hover:shadow-md transition-shadow">
                                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-2"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i></div>
                                <div class="text-lg font-bold text-slate-900"><?php echo number_format($counts['plots']); ?></div>
                                <div class="text-[10px] text-slate-500">Total Plots</div>
                            </div>
                            <div class="rounded-xl bg-white border border-slate-100 p-3.5 shadow-sm hover:shadow-md transition-shadow">
                                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mb-2"><i data-lucide="calendar-check" class="w-3.5 h-3.5"></i></div>
                                <div class="text-lg font-bold text-slate-900"><?php echo number_format($counts['reservations']); ?></div>
                                <div class="text-[10px] text-slate-500">Reservations</div>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-5 gap-4">
                            <!-- Map preview -->
                            <div class="md:col-span-3 rounded-xl border border-slate-200 h-56 relative overflow-hidden">
                                <div id="heroMap" class="absolute inset-0"></div>

                                <!-- Map label -->
                                <div class="absolute top-3 left-3 z-[1000] inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/90 backdrop-blur text-slate-700 text-[10px] font-semibold shadow-sm">
                                    <i data-lucide="map" class="w-3 h-3 text-emerald-600"></i>
                                    Interactive Cemetery Map
                                </div>

                                <!-- Legend -->
                                <div class="absolute bottom-3 right-3 z-[1000] flex flex-col gap-1.5 text-[9px] text-slate-600 bg-white/90 backdrop-blur px-2.5 py-2 rounded-lg shadow-sm border border-slate-100">
                                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Available</span>
                                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Reserved</span>
                                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-400"></span> Occupied</span>
                                </div>
                            </div>

                            <!-- Right column -->
                            <div class="md:col-span-2 flex flex-col gap-4">
                                <div class="rounded-xl bg-white border border-slate-100 p-3.5 shadow-sm flex-1">
                                    <div class="text-[10px] font-semibold text-slate-500 mb-2">Records by Month</div>
                                    <div class="flex items-end gap-1.5 h-16">
                                        <div class="flex-1 rounded-t-sm bg-emerald-100" style="height: 30%;"></div>
                                        <div class="flex-1 rounded-t-sm bg-emerald-200" style="height: 45%;"></div>
                                        <div class="flex-1 rounded-t-sm bg-emerald-300" style="height: 35%;"></div>
                                        <div class="flex-1 rounded-t-sm bg-emerald-400" style="height: 65%;"></div>
                                        <div class="flex-1 rounded-t-sm bg-emerald-500" style="height: 80%;"></div>
                                        <div class="flex-1 rounded-t-sm bg-emerald-400" style="height: 55%;"></div>
                                        <div class="flex-1 rounded-t-sm bg-emerald-300" style="height: 40%;"></div>
                                    </div>
                                </div>
                                <div class="rounded-xl bg-white border border-slate-100 p-3.5 shadow-sm">
                                    <div class="flex items-end justify-between gap-2">
                                        <div>
                                            <div class="text-[10px] font-semibold text-slate-500 mb-1">Availability</div>
                                            <div class="text-xl font-bold text-emerald-600"><?php echo $available_count; ?> <span class="text-[10px] font-medium text-slate-400">plots open</span></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-[10px] font-semibold text-slate-600"><?php echo $percentage; ?>%</div>
                                            <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1">
                                                <div class="h-full bg-emerald-500 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI assistant preview -->
                        <div class="rounded-xl bg-violet-50 border border-violet-100 p-3 mt-4 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center flex-shrink-0"><i data-lucide="bot" class="w-4 h-4"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-800">MemoryGuide Assistant</div>
                                <div class="text-[11px] text-slate-500 truncate">Ask anything about plots, records, or directions...</div>
                            </div>
                            <span class="px-2 py-1 rounded-full bg-white border border-violet-100 text-[10px] font-semibold text-violet-600 flex-shrink-0">AI</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== FEATURES ===================== -->
    <section id="features" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-10">
            <!-- Heading -->
            <div class="text-center max-w-2xl mx-auto mb-16 reveal reveal-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i> What We Offer
                </div>
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4 tracking-tight">Tools that keep families connected and records organized</h2>
                <p class="text-slate-500 text-base leading-relaxed">From GPS-enabled plot mapping to AI-assisted search, every feature is designed to make cemetery management simpler and more respectful.</p>
            </div>

            <!-- Feature cards -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 reveal-stagger reveal reveal-up">
                <!-- 1 -->
                <div class="feature-card group rounded-2xl bg-white border border-slate-200 p-7 overflow-hidden hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1.5 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:rotate-3 transition-transform"><i data-lucide="map" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Interactive Cemetery Map</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Pan, zoom, and rotate through the cemetery grounds. Every plot is pinned with GPS coordinates so families can find their loved ones quickly.</p>
                </div>
                <!-- 2 -->
                <div class="feature-card group rounded-2xl bg-white border border-slate-200 p-7 overflow-hidden hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1.5 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:rotate-3 transition-transform"><i data-lucide="search" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Burial Record Search</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Look up burial records by name, family name, or plot number. Results appear instantly with location details and photos when available.</p>
                </div>
                <!-- 3 -->
                <div class="feature-card group rounded-2xl bg-white border border-slate-200 p-7 overflow-hidden hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1.5 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:rotate-3 transition-transform"><i data-lucide="sparkles" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">MemoryGuide AI Assistant</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Ask questions in plain language — "Where is my lola buried?" — and our AI assistant will search records and guide you to the exact location.</p>
                </div>
                <!-- 4 -->
                <div class="feature-card group rounded-2xl bg-white border border-slate-200 p-7 overflow-hidden hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1.5 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:rotate-3 transition-transform"><i data-lucide="calendar-check" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Online Plot Reservations</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Reserve available plots online and track the status of your request. Admins can review, approve, and manage reservations in one place.</p>
                </div>
                <!-- 5 -->
                <div class="feature-card group rounded-2xl bg-white border border-slate-200 p-7 overflow-hidden hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1.5 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:rotate-3 transition-transform"><i data-lucide="bar-chart-3" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Reports & Analytics</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Generate statistics on burial records, plot availability, and reservation trends. Export reports for documentation and planning.</p>
                </div>
                <!-- 6 -->
                <div class="feature-card group rounded-2xl bg-white border border-slate-200 p-7 overflow-hidden hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1.5 transition-all duration-300">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:rotate-3 transition-transform"><i data-lucide="shield-check" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Secure & Private</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Passwords are hashed with bcrypt, sessions are protected, and access is role-based — so sensitive data stays in the right hands.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== HOW IT WORKS ===================== -->
    <section class="py-24 bg-slate-50 relative overflow-hidden">
        <div class="absolute bottom-10 left-10 w-80 h-80 bg-emerald-100 rounded-full blur-3xl opacity-40 animate-pulse-slow"></div>
        <div class="max-w-7xl mx-auto px-6 lg:px-10 relative">
            <div class="text-center max-w-2xl mx-auto mb-16 reveal reveal-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                    <i data-lucide="route" class="w-3.5 h-3.5"></i> Getting Started
                </div>
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4 tracking-tight">Three steps to find and honor your loved ones</h2>
                <p class="text-slate-500 text-base leading-relaxed">It's quick to get started — register an account, search for a burial record, and navigate directly to the plot.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative reveal-stagger reveal reveal-up">
                <!-- Connecting line (desktop) -->
                <div class="hidden md:block absolute top-12 left-[16%] right-[16%] h-0.5 step-connector opacity-70"></div>

                <!-- Step 1 -->
                <div class="relative text-center animate-fade-up">
                    <div class="relative inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white border-2 border-emerald-200 shadow-lg shadow-emerald-100 mb-5 transition-all duration-300 hover:scale-105 hover:border-emerald-300">
                        <i data-lucide="user-plus" class="w-9 h-9 text-emerald-600"></i>
                        <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-md">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Register an account</h3>
                    <p class="text-sm text-slate-500 leading-relaxed max-w-xs mx-auto">Create a free visitor account with your name, email, and password. It takes less than a minute.</p>
                </div>

                <!-- Step 2 -->
                <div class="relative text-center animate-fade-up" style="animation-delay: 0.1s;">
                    <div class="relative inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white border-2 border-emerald-200 shadow-lg shadow-emerald-100 mb-5 transition-all duration-300 hover:scale-105 hover:border-emerald-300">
                        <i data-lucide="search" class="w-9 h-9 text-emerald-600"></i>
                        <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-md">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Search for a loved one</h3>
                    <p class="text-sm text-slate-500 leading-relaxed max-w-xs mx-auto">Look up burial records by name or plot number. The interactive map shows exactly where the plot is located.</p>
                </div>

                <!-- Step 3 -->
                <div class="relative text-center animate-fade-up" style="animation-delay: 0.2s;">
                    <div class="relative inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white border-2 border-emerald-200 shadow-lg shadow-emerald-100 mb-5 transition-all duration-300 hover:scale-105 hover:border-emerald-300">
                        <i data-lucide="navigation" class="w-9 h-9 text-emerald-600"></i>
                        <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-md">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Navigate to the plot</h3>
                    <p class="text-sm text-slate-500 leading-relaxed max-w-xs mx-auto">Follow the map to the exact location. You can also reserve a plot or ask the AI assistant for help anytime.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== SERVICES ===================== -->
    <section id="services" class="py-24 bg-emerald-50/40 relative overflow-hidden">
        <div class="absolute top-10 right-10 w-80 h-80 bg-emerald-100 rounded-full blur-3xl opacity-50 animate-pulse-slow"></div>
        <div class="max-w-7xl mx-auto px-6 lg:px-10 relative">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left text -->
                <div class="reveal reveal-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i> Two Portals
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-5 tracking-tight">One system for families and administrators</h2>
                    <p class="text-slate-600 text-base leading-relaxed mb-8">Whether you're visiting a loved one's grave or managing cemetery operations, there's a dedicated portal built for your needs.</p>

                    <div class="space-y-4 reveal-stagger reveal reveal-up">
                        <!-- Visitor -->
                        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-emerald-200 hover:-translate-y-0.5 transition-all duration-300">
                            <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0"><i data-lucide="user" class="w-5 h-5"></i></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-base font-bold text-slate-900">Visitor Portal</h3>
                                    <a href="login.php" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">Sign in <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></a>
                                </div>
                                <p class="text-sm text-slate-500 leading-relaxed">Search burial records, explore the interactive cemetery map, reserve plots, and chat with the MemoryGuide AI assistant.</p>
                            </div>
                        </div>
                        <!-- Admin -->
                        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-emerald-200 hover:-translate-y-0.5 transition-all duration-300">
                            <div class="w-11 h-11 rounded-xl bg-slate-900 text-white flex items-center justify-center flex-shrink-0"><i data-lucide="shield" class="w-5 h-5"></i></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-base font-bold text-slate-900">Admin Panel</h3>
                                    <a href="login.php" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">Sign in <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></a>
                                </div>
                                <p class="text-sm text-slate-500 leading-relaxed">Manage burial records, approve reservations, view analytics, draw plot grids, configure settings, and generate reports.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: stats grid -->
                <div class="grid grid-cols-2 gap-4 reveal-stagger reveal reveal-right">
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm hover:shadow-lg hover:border-emerald-200 hover:-translate-y-1 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                        <div class="text-3xl font-bold text-slate-900"><?php echo number_format($counts['records']); ?></div>
                        <div class="text-xs text-slate-500 mt-1">Burial records managed</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm hover:shadow-lg hover:border-emerald-200 hover:-translate-y-1 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center mb-3"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
                        <div class="text-3xl font-bold text-slate-900"><?php echo number_format($counts['plots']); ?></div>
                        <div class="text-xs text-slate-500 mt-1">Plots mapped with GPS</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm hover:shadow-lg hover:border-emerald-200 hover:-translate-y-1 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-3"><i data-lucide="calendar-check" class="w-5 h-5"></i></div>
                        <div class="text-3xl font-bold text-slate-900"><?php echo number_format($counts['reservations']); ?></div>
                        <div class="text-xs text-slate-500 mt-1">Reservations processed</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm hover:shadow-lg hover:border-emerald-200 hover:-translate-y-1 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mb-3"><i data-lucide="sparkles" class="w-5 h-5"></i></div>
                        <div class="text-3xl font-bold text-slate-900">24/7</div>
                        <div class="text-xs text-slate-500 mt-1">AI assistant available</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== ABOUT ===================== -->
    <section id="about" class="py-24 bg-white">
        <div class="max-w-5xl mx-auto px-6 lg:px-10 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold mb-4 reveal reveal-up">
                <i data-lucide="info" class="w-3.5 h-3.5"></i> About the Cemetery
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-6 tracking-tight reveal reveal-up">A peaceful resting place, modernized for today's families</h2>
            <p class="text-slate-600 text-lg leading-relaxed max-w-3xl mx-auto reveal reveal-up">
                Matinao Memorial Cemetery in Polomolok, South Cotabato has served families for years with dignity and care. Our mapping and management system brings the cemetery into the digital age — helping families find their loved ones quickly and giving administrators the tools to manage records, plots, and reservations efficiently.
            </p>

            <!-- Value pills -->
            <div class="flex flex-wrap justify-center gap-3 mt-10 reveal-stagger reveal reveal-up">
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium"><i data-lucide="heart" class="w-4 h-4"></i> Dignified care</span>
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium"><i data-lucide="zap" class="w-4 h-4"></i> Modern technology</span>
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium"><i data-lucide="lock" class="w-4 h-4"></i> Secure & private</span>
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium"><i data-lucide="users" class="w-4 h-4"></i> For families & admins</span>
            </div>
        </div>
    </section>

    <!-- ===================== CTA BANNER ===================== -->
    <section class="py-16 bg-gradient-to-br from-emerald-600 via-emerald-600 to-teal-700 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 texture-grid"></div>
        <div class="max-w-5xl mx-auto px-6 lg:px-10 text-center relative reveal reveal-scale">
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3">Ready to get started?</h2>
            <p class="text-emerald-50 text-base mb-7 max-w-xl mx-auto">Register a visitor account to search records and reserve plots, or sign in to the admin panel to manage the cemetery.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="visitor/register.php" class="inline-flex items-center gap-2 rounded-xl bg-white text-emerald-700 hover:bg-emerald-50 text-sm font-semibold px-6 py-3.5 transition shadow-lg">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Create Visitor Account
                </a>
                <a href="login.php" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 border border-white/30 text-white text-sm font-semibold px-6 py-3.5 transition">
                    <i data-lucide="shield" class="w-4 h-4"></i> Admin Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- ===================== FOOTER ===================== -->
    <footer id="contact" class="bg-slate-900 text-slate-300 border-t-2 border-emerald-600/60">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 py-14">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">
                <!-- Brand -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-white shadow flex items-center justify-center">
                            <img src="assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-8 h-8 rounded-full object-cover">
                        </div>
                        <div>
                            <div class="text-sm font-bold text-white leading-tight">Matinao Memorial</div>
                            <div class="text-[11px] text-emerald-400 font-medium leading-tight">Cemetery System</div>
                        </div>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed">A dignified resting place with modern cemetery management technology.</p>
                    <p class="text-xs text-slate-500 mt-4 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-400"></i> Polomolok, South Cotabato</p>
                </div>

                <!-- Quick links -->
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Quick Links</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="login.php" class="text-slate-400 hover:text-emerald-400 transition flex items-center gap-1.5"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i> Visitor Portal</a></li>
                        <li><a href="login.php" class="text-slate-400 hover:text-emerald-400 transition flex items-center gap-1.5"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i> Admin Login</a></li>
                        <li><a href="visitor/register.php" class="text-slate-400 hover:text-emerald-400 transition flex items-center gap-1.5"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i> Register Account</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Services</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="#features" class="text-slate-400 hover:text-emerald-400 transition flex items-center gap-1.5"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i> Cemetery Mapping</a></li>
                        <li><a href="#features" class="text-slate-400 hover:text-emerald-400 transition flex items-center gap-1.5"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i> Burial Records</a></li>
                        <li><a href="#features" class="text-slate-400 hover:text-emerald-400 transition flex items-center gap-1.5"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i> Plot Reservations</a></li>
                        <li><a href="#features" class="text-slate-400 hover:text-emerald-400 transition flex items-center gap-1.5"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i> AI Assistant</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Contact</h4>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-2.5 text-slate-400"><i data-lucide="mail" class="w-4 h-4 text-emerald-400 mt-0.5"></i> info@matinaocemetery.com</li>
                        <li class="flex items-start gap-2.5 text-slate-400"><i data-lucide="phone" class="w-4 h-4 text-emerald-400 mt-0.5"></i> +63 XXX XXX XXXX</li>
                        <li class="flex items-start gap-2.5 text-slate-400"><i data-lucide="clock" class="w-4 h-4 text-emerald-400 mt-0.5"></i> Available 24/7 online</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-500">&copy; <?php echo date('Y'); ?> Matinao Memorial Cemetery. All rights reserved.</p>
                <p class="text-xs text-slate-500 flex items-center gap-1.5">Built with <i data-lucide="heart" class="w-3.5 h-3.5 text-emerald-400"></i> using PHP, MySQL & Tailwind CSS</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Scroll-triggered reveal animations
            const reveals = document.querySelectorAll('.reveal');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
            reveals.forEach(el => observer.observe(el));

            // Navbar background change on scroll
            const nav = document.querySelector('nav');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 20) {
                    nav.classList.add('shadow-md', 'bg-white/95');
                    nav.classList.remove('bg-white/80');
                } else {
                    nav.classList.remove('shadow-md', 'bg-white/95');
                    nav.classList.add('bg-white/80');
                }
            });

            // Smooth scroll for nav links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            // Initialize hero preview map
            if (typeof L !== 'undefined') {
                const heroMap = L.map('heroMap', {
                    center: [6.18344118743717, 125.08457146469357],
                    zoom: 17,
                    minZoom: 15,
                    maxZoom: 20,
                    zoomControl: false,
                    attributionControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    touchZoom: false,
                    boxZoom: false,
                    keyboard: false
                });

                L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
                    maxZoom: 20,
                    subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
                }).addTo(heroMap);

                // Sample plot markers
                const samplePlots = [
                    { lat: 6.1832, lng: 125.0843, status: 'available' },
                    { lat: 6.1836, lng: 125.0848, status: 'available' },
                    { lat: 6.1831, lng: 125.0850, status: 'reserved' },
                    { lat: 6.1838, lng: 125.0842, status: 'available' },
                    { lat: 6.1835, lng: 125.0846, status: 'occupied' },
                    { lat: 6.1833, lng: 125.0849, status: 'available' },
                    { lat: 6.1837, lng: 125.0851, status: 'reserved' },
                    { lat: 6.1834, lng: 125.0844, status: 'available' }
                ];

                const colors = {
                    available: '#10b981',
                    reserved: '#f59e0b',
                    occupied: '#94a3b8'
                };

                samplePlots.forEach(p => {
                    L.circleMarker([p.lat, p.lng], {
                        radius: 6,
                        fillColor: colors[p.status],
                        color: '#ffffff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.9
                    }).addTo(heroMap);
                });

                // Disable interactions after load
                setTimeout(() => { heroMap.invalidateSize(); }, 200);
            }
        });
    </script>
</body>
</html>
