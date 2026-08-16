<?php
require_once 'config/database.php';

$counts = ['records' => 1248, 'plots' => 86, 'reservations' => 42];
$available_count = 72;

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
    <title>Matinao Memorial Cemetery - Mapping & Management System</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

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
        @keyframes fadeLeft { from { opacity: 0; transform: translateX(-32px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeRight { from { opacity: 0; transform: translateX(32px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-12px); } }
        @keyframes pulseSlow { 0%, 100% { opacity: 0.5; transform: scale(1); } 50% { opacity: 0.7; transform: scale(1.06); } }
        @keyframes pulseDot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.4); } }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(8px); } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
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
    </style>
</head>
<body class="text-slate-800 antialiased">

    <!-- ===================== NAVBAR ===================== -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/70">
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
                <a href="login.php" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700 hover:text-emerald-600 transition px-3 py-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Sign In
                </a>
                <a href="visitor/register.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg px-4 py-2 transition shadow-sm shadow-emerald-200">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Register
                </a>
            </div>
        </div>
    </nav>

    <!-- ===================== HERO ===================== -->
    <section id="home" class="relative min-h-screen flex items-center pt-16 overflow-hidden">
        <!-- Decorative background -->
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-emerald-50 via-white to-teal-50/40"></div>
        <div class="absolute top-20 left-10 w-96 h-96 bg-emerald-100 rounded-full blur-3xl opacity-60 animate-pulse-slow"></div>
        <div class="absolute bottom-20 right-10 w-[28rem] h-[28rem] bg-emerald-50 rounded-full blur-3xl opacity-70 animate-pulse-slow"></div>
        <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-teal-100 rounded-full blur-3xl opacity-40"></div>
        <!-- Grid pattern -->
        <div class="absolute inset-0 -z-10 opacity-[0.04]" style="background-image: linear-gradient(#10b981 1px, transparent 1px), linear-gradient(90deg, #10b981 1px, transparent 1px); background-size: 40px 40px;"></div>

        <div class="max-w-7xl mx-auto px-6 lg:px-10 grid lg:grid-cols-2 gap-12 items-center w-full py-16">
            <!-- Left: text -->
            <div class="animate-fade-up">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/80 backdrop-blur text-emerald-700 text-xs font-semibold mb-6 border border-emerald-200 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse-dot"></span>
                    Modern Cemetery Management System · v1.0
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 leading-[1.08] tracking-tight mb-6">
                    Honoring memories with <span class="text-emerald-600 relative inline-block">clarity & care<svg class="absolute -bottom-2 left-0 w-full" height="8" viewBox="0 0 200 8" preserveAspectRatio="none"><path d="M0,6 Q100,0 200,6" stroke="#10b981" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.5"/></svg></span>.
                </h1>

                <p class="text-slate-600 text-lg leading-relaxed mb-8 max-w-xl">
                    A dignified resting place with modern GPS-enabled mapping technology. Find and honor your loved ones with ease — search burial records, reserve plots, and explore the cemetery interactively.
                </p>

                <!-- CTAs -->
                <div class="flex flex-wrap gap-3 mb-10">
                    <a href="login.php" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-6 py-3.5 transition shadow-lg shadow-emerald-200 hover:shadow-emerald-300">
                        <i data-lucide="log-in" class="w-4 h-4"></i> Visitor Portal
                    </a>
                    <a href="login.php" class="inline-flex items-center gap-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-semibold px-6 py-3.5 transition shadow-sm">
                        <i data-lucide="shield" class="w-4 h-4"></i> Admin Panel
                    </a>
                </div>

                <!-- Trust indicators -->
                <div class="flex flex-wrap items-center gap-8 pt-6 border-t border-emerald-200/70">
                    <div>
                        <div class="text-2xl font-bold text-emerald-700">24/7</div>
                        <div class="text-xs text-slate-500">Available Online</div>
                    </div>
                    <div class="w-px h-10 bg-emerald-200"></div>
                    <div>
                        <div class="text-2xl font-bold text-emerald-700">GPS</div>
                        <div class="text-xs text-slate-500">Enabled Mapping</div>
                    </div>
                    <div class="w-px h-10 bg-emerald-200"></div>
                    <div>
                        <div class="text-2xl font-bold text-emerald-700">AI</div>
                        <div class="text-xs text-slate-500">Powered Support</div>
                    </div>
                </div>
            </div>

            <!-- Right: visual card -->
            <div class="relative animate-fade-up" style="animation-delay: 0.15s;">
                <div class="relative rounded-3xl bg-white border border-slate-200 shadow-2xl shadow-emerald-100/50 overflow-hidden">
                    <!-- Top bar -->
                    <div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50/60">
                        <span class="w-3 h-3 rounded-full bg-rose-300"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-300"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-300"></span>
                        <span class="ml-3 text-xs text-slate-400 font-medium">matinao-memorial.app</span>
                    </div>
                    <!-- Mock dashboard -->
                    <div class="p-5">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="text-sm font-bold text-slate-900">Cemetery Overview</div>
                                <div class="text-xs text-slate-400">Live data preview</div>
                            </div>
                            <div class="px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-semibold">Updated now</div>
                        </div>

                        <!-- Stat cards -->
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="rounded-xl bg-white border border-slate-100 p-3 shadow-sm hover:shadow-md transition-shadow">
                                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2"><i data-lucide="file-text" class="w-3.5 h-3.5"></i></div>
                                <div class="text-lg font-bold text-slate-900"><?php echo number_format($counts['records']); ?></div>
                                <div class="text-[10px] text-slate-500">Records</div>
                            </div>
                            <div class="rounded-xl bg-white border border-slate-100 p-3 shadow-sm hover:shadow-md transition-shadow">
                                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-2"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i></div>
                                <div class="text-lg font-bold text-slate-900"><?php echo number_format($counts['plots']); ?></div>
                                <div class="text-[10px] text-slate-500">Total Plots</div>
                            </div>
                            <div class="rounded-xl bg-white border border-slate-100 p-3 shadow-sm hover:shadow-md transition-shadow">
                                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mb-2"><i data-lucide="calendar-check" class="w-3.5 h-3.5"></i></div>
                                <div class="text-lg font-bold text-slate-900"><?php echo number_format($counts['reservations']); ?></div>
                                <div class="text-[10px] text-slate-500">Reservations</div>
                            </div>
                        </div>

                        <!-- Map preview -->
                        <div class="rounded-xl border border-slate-200 h-44 relative overflow-hidden mb-4">
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

                        <!-- Bottom grid -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="rounded-xl bg-white border border-slate-100 p-3 shadow-sm">
                                <div class="text-[10px] font-semibold text-slate-500 mb-2">Records by Month</div>
                                <div class="flex items-end gap-1.5 h-12">
                                    <div class="flex-1 rounded-t-sm bg-emerald-100" style="height: 30%;"></div>
                                    <div class="flex-1 rounded-t-sm bg-emerald-200" style="height: 45%;"></div>
                                    <div class="flex-1 rounded-t-sm bg-emerald-300" style="height: 35%;"></div>
                                    <div class="flex-1 rounded-t-sm bg-emerald-400" style="height: 65%;"></div>
                                    <div class="flex-1 rounded-t-sm bg-emerald-500" style="height: 80%;"></div>
                                    <div class="flex-1 rounded-t-sm bg-emerald-400" style="height: 55%;"></div>
                                    <div class="flex-1 rounded-t-sm bg-emerald-300" style="height: 40%;"></div>
                                </div>
                            </div>
                            <div class="rounded-xl bg-white border border-slate-100 p-3 shadow-sm flex flex-col justify-between">
                                <div class="text-[10px] font-semibold text-slate-500">Availability</div>
                                <div class="flex items-end justify-between gap-2">
                                    <div>
                                        <div class="text-xl font-bold text-emerald-600"><?php echo $available_count; ?></div>
                                        <div class="text-[10px] text-slate-500">plots open</div>
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

                        <!-- AI assistant preview -->
                        <div class="rounded-xl bg-gradient-to-r from-violet-50 to-white border border-violet-100 p-3 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center flex-shrink-0"><i data-lucide="bot" class="w-4 h-4"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold text-slate-800">AI Assistant</div>
                                <div class="text-[11px] text-slate-500 truncate">Ask anything about plots, records, or directions...</div>
                            </div>
                            <span class="px-2 py-1 rounded-full bg-white border border-violet-100 text-[10px] font-semibold text-violet-600 flex-shrink-0">Groq</span>
                        </div>
                    </div>
                </div>

                <!-- Floating accent -->
                <div class="absolute -top-5 -right-5 w-24 h-24 rounded-2xl bg-white shadow-xl border border-emerald-100 flex flex-col items-center justify-center animate-float">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-1"><i data-lucide="shield-check" class="w-5 h-5"></i></div>
                    <div class="text-[10px] font-semibold text-slate-600">Secure</div>
                </div>
            </div>
        </div>

        <!-- Scroll indicator -->
        <a href="#features" class="absolute bottom-6 left-1/2 -translate-x-1/2 text-slate-400 hover:text-emerald-600 transition animate-bounce-slow">
            <i data-lucide="chevron-down" class="w-7 h-7"></i>
        </a>
    </section>

    <!-- ===================== FEATURES ===================== -->
    <section id="features" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-10">
            <!-- Heading -->
            <div class="text-center max-w-2xl mx-auto mb-16 reveal reveal-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i> Powerful Features
                </div>
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4 tracking-tight">Everything you need to manage a modern cemetery</h2>
                <p class="text-slate-500 text-base leading-relaxed">Experience modern cemetery management with cutting-edge technology — from interactive maps to AI-powered insights.</p>
            </div>

            <!-- Feature cards -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 reveal-stagger reveal reveal-up">
                <!-- 1 -->
                <div class="group rounded-2xl bg-white border border-slate-200 p-7 hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform"><i data-lucide="map" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Interactive Mapping</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Navigate the cemetery with precision using GPS-enabled mapping. Locate plots, view compartments, and explore satellite imagery.</p>
                </div>
                <!-- 2 -->
                <div class="group rounded-2xl bg-white border border-slate-200 p-7 hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform"><i data-lucide="search" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Quick Search</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Find burial records instantly by name, plot number, or family name with our powerful search engine.</p>
                </div>
                <!-- 3 -->
                <div class="group rounded-2xl bg-white border border-slate-200 p-7 hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform"><i data-lucide="sparkles" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">AI Assistant</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Get instant help and guidance from our intelligent AI assistant — available 24/7 to answer your cemetery-related questions.</p>
                </div>
                <!-- 4 -->
                <div class="group rounded-2xl bg-white border border-slate-200 p-7 hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform"><i data-lucide="calendar-check" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Plot Reservations</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Reserve and manage cemetery plots online. Track reservation status, payments, and approvals in one place.</p>
                </div>
                <!-- 5 -->
                <div class="group rounded-2xl bg-white border border-slate-200 p-7 hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform"><i data-lucide="bar-chart-3" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Analytics & Reports</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Generate detailed statistics and reports on burial records, plot availability, and reservation trends with one click.</p>
                </div>
                <!-- 6 -->
                <div class="group rounded-2xl bg-white border border-slate-200 p-7 hover:shadow-xl hover:border-emerald-200 hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform"><i data-lucide="shield-check" class="w-6 h-6"></i></div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Secure & Reliable</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Built with security best practices — bcrypt password hashing, session protection, and role-based access control.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== HOW IT WORKS ===================== -->
    <section class="py-24 bg-gradient-to-br from-slate-50 via-white to-emerald-50/40 relative overflow-hidden">
        <div class="absolute bottom-10 left-10 w-80 h-80 bg-emerald-100 rounded-full blur-3xl opacity-40 animate-pulse-slow"></div>
        <div class="max-w-7xl mx-auto px-6 lg:px-10 relative">
            <div class="text-center max-w-2xl mx-auto mb-16 reveal reveal-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                    <i data-lucide="route" class="w-3.5 h-3.5"></i> How It Works
                </div>
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4 tracking-tight">Get started in three simple steps</h2>
                <p class="text-slate-500 text-base leading-relaxed">From registration to finding your loved ones — it's quick, easy, and secure.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative reveal-stagger reveal reveal-up">
                <!-- Connecting line (desktop) -->
                <div class="hidden md:block absolute top-12 left-[16%] right-[16%] h-0.5 bg-gradient-to-r from-emerald-200 via-emerald-300 to-emerald-200"></div>

                <!-- Step 1 -->
                <div class="relative text-center animate-fade-up">
                    <div class="relative inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white border-2 border-emerald-200 shadow-lg shadow-emerald-100 mb-5">
                        <i data-lucide="user-plus" class="w-9 h-9 text-emerald-600"></i>
                        <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-md">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Create an account</h3>
                    <p class="text-sm text-slate-500 leading-relaxed max-w-xs mx-auto">Register as a visitor with your name, email, and password. It's free and takes less than a minute.</p>
                </div>

                <!-- Step 2 -->
                <div class="relative text-center animate-fade-up" style="animation-delay: 0.1s;">
                    <div class="relative inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white border-2 border-emerald-200 shadow-lg shadow-emerald-100 mb-5">
                        <i data-lucide="search" class="w-9 h-9 text-emerald-600"></i>
                        <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-md">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Search & explore</h3>
                    <p class="text-sm text-slate-500 leading-relaxed max-w-xs mx-auto">Search burial records by name or plot number, and explore the interactive GPS-enabled cemetery map.</p>
                </div>

                <!-- Step 3 -->
                <div class="relative text-center animate-fade-up" style="animation-delay: 0.2s;">
                    <div class="relative inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white border-2 border-emerald-200 shadow-lg shadow-emerald-100 mb-5">
                        <i data-lucide="calendar-check" class="w-9 h-9 text-emerald-600"></i>
                        <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-md">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Reserve & connect</h3>
                    <p class="text-sm text-slate-500 leading-relaxed max-w-xs mx-auto">Reserve plots online, track reservation status, and get AI-assisted help anytime you need it.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== SERVICES ===================== -->
    <section id="services" class="py-24 bg-gradient-to-br from-emerald-50/60 via-white to-teal-50/40 relative overflow-hidden">
        <div class="absolute top-10 right-10 w-80 h-80 bg-emerald-100 rounded-full blur-3xl opacity-50 animate-pulse-slow"></div>
        <div class="max-w-7xl mx-auto px-6 lg:px-10 relative">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left text -->
                <div class="reveal reveal-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i> Our Services
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-5 tracking-tight">Two portals, one unified system</h2>
                    <p class="text-slate-600 text-base leading-relaxed mb-8">Whether you're a family member looking for a loved one or an administrator managing the cemetery, we've built a dedicated experience for you.</p>

                    <div class="space-y-4 reveal-stagger reveal reveal-up">
                        <!-- Visitor -->
                        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all">
                            <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0"><i data-lucide="user" class="w-5 h-5"></i></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-base font-bold text-slate-900">Visitor Portal</h3>
                                    <a href="login.php" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">Sign in <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></a>
                                </div>
                                <p class="text-sm text-slate-500 leading-relaxed">Search burial records, explore the interactive cemetery map, reserve plots, and chat with the AI assistant.</p>
                            </div>
                        </div>
                        <!-- Admin -->
                        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all">
                            <div class="w-11 h-11 rounded-xl bg-slate-900 text-white flex items-center justify-center flex-shrink-0"><i data-lucide="shield" class="w-5 h-5"></i></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-base font-bold text-slate-900">Admin Panel</h3>
                                    <a href="login.php" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">Sign in <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></a>
                                </div>
                                <p class="text-sm text-slate-500 leading-relaxed">Manage burial records, approve reservations, view analytics, configure settings, and generate reports.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: stats grid -->
                <div class="grid grid-cols-2 gap-4 reveal-stagger reveal reveal-right">
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                        <div class="text-3xl font-bold text-slate-900">1,200+</div>
                        <div class="text-xs text-slate-500 mt-1">Burial records managed</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center mb-3"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
                        <div class="text-3xl font-bold text-slate-900">80+</div>
                        <div class="text-xs text-slate-500 mt-1">Plots mapped with GPS</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-3"><i data-lucide="calendar-check" class="w-5 h-5"></i></div>
                        <div class="text-3xl font-bold text-slate-900">40+</div>
                        <div class="text-xs text-slate-500 mt-1">Reservations processed</div>
                    </div>
                    <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
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
                <i data-lucide="info" class="w-3.5 h-3.5"></i> About Us
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-6 tracking-tight reveal reveal-up">A peaceful resting place, modernized for today</h2>
            <p class="text-slate-600 text-lg leading-relaxed max-w-3xl mx-auto reveal reveal-up">
                Matinao Memorial Cemetery provides a dignified and peaceful resting place for loved ones, now enhanced with modern mapping and management technology. Our system bridges tradition and innovation — helping families find and honor their loved ones while giving administrators the tools they need to manage records, plots, and reservations efficiently.
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
    <section class="py-16 bg-gradient-to-r from-emerald-600 to-teal-600 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 32px 32px;"></div>
        <div class="max-w-5xl mx-auto px-6 lg:px-10 text-center relative reveal reveal-scale">
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3">Ready to get started?</h2>
            <p class="text-emerald-50 text-base mb-7 max-w-xl mx-auto">Register a visitor account to search records and reserve plots, or sign in to the admin panel to manage the cemetery.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="visitor/register.php" class="inline-flex items-center gap-2 rounded-xl bg-white text-emerald-700 hover:bg-emerald-50 text-sm font-semibold px-6 py-3.5 transition shadow-lg">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Create Visitor Account
                </a>
                <a href="login.php" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700/40 hover:bg-emerald-700/60 border border-white/30 text-white text-sm font-semibold px-6 py-3.5 transition backdrop-blur">
                    <i data-lucide="shield" class="w-4 h-4"></i> Admin Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- ===================== FOOTER ===================== -->
    <footer id="contact" class="bg-slate-900 text-slate-300">
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
                    <p class="text-xs text-slate-500 mt-4 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-400"></i> Matinao, Philippines</p>
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
