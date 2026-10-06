<?php
session_start();

// Redirect if already logged in (auto-detect from session)
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
if (isset($_SESSION['visitor_id'])) {
    header('Location: visitor/dashboard.php');
    exit;
}

// Handle login form submission — auto-detect role from identifier
$error = '';
$last_identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $last_identifier = $identifier;

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter your username/email and password.';
    } else {
        $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $authenticated = false;

        // Order: if it looks like an email, try visitor first; otherwise try admin first.
        // Always fall back to the other table so login is forgiving.
        $try_order = $is_email ? ['visitor', 'admin'] : ['admin', 'visitor'];

        foreach ($try_order as $try_role) {
            if ($authenticated) break;

            try {
                if ($try_role === 'admin') {
                    // Admin authenticates by username
                    $stmt = $pdo->prepare("SELECT id, username, password, email FROM admin_users WHERE username = ? OR email = ?");
                    $stmt->execute([$identifier, $identifier]);
                    $admin = $stmt->fetch();

                    if ($admin && password_verify($password, $admin['password'])) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['last_activity'] = time();

                        header('Location: admin/dashboard.php');
                        exit;
                    }
                } else {
                    // Visitor authenticates by email
                    $stmt = $pdo->prepare("SELECT id, full_name, email, password, is_active FROM visitors WHERE email = ?");
                    $stmt->execute([$identifier]);
                    $visitor = $stmt->fetch();

                    if ($visitor && password_verify($password, $visitor['password'])) {
                        if ($visitor['is_active'] == 1) {
                            $_SESSION['visitor_id'] = $visitor['id'];
                            $_SESSION['visitor_name'] = $visitor['full_name'];
                            $_SESSION['visitor_email'] = $visitor['email'];
                            $_SESSION['last_activity'] = time();

                            $updateStmt = $pdo->prepare("UPDATE visitors SET last_login = NOW() WHERE id = ?");
                            $updateStmt->execute([$visitor['id']]);

                            $logStmt = $pdo->prepare("INSERT INTO visitor_activity_log (visitor_id, activity_type, ip_address, user_agent) VALUES (?, 'login', ?, ?)");
                            $logStmt->execute([$visitor['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

                            header('Location: visitor/dashboard.php');
                            exit;
                        } else {
                            $error = 'Your account has been deactivated. Please contact the administrator.';
                            $authenticated = true; // stop trying other tables
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log(ucfirst($try_role) . " login error: " . $e->getMessage());
            }
        }

        if (!$authenticated && empty($error)) {
            $error = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico?v=2">
    <link rel="icon" type="image/png" href="assets/images/favicon.png?v=2">
    <title>Sign In — Matinao Memorial Cemetery</title>
    <meta name="description" content="Sign in to the Matinao Memorial Cemetery system — for visitors searching for loved ones and administrators managing cemetery records.">

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
    <link rel="stylesheet" href="assets/css/public-theme.css?v=1">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <style>
        * { font-family: 'Poppins', sans-serif; }
        html { scroll-behavior: smooth; }
        body { background: #f8fafc; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeLeft { from { opacity: 0; transform: translateX(-32px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes pulseSlow { 0%, 100% { opacity: 0.4; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.08); } }
        @keyframes pulseDot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.4); } }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
        .animate-slide-up { animation: slideUp 0.6s ease both; }
        .animate-fade-in { animation: fadeIn 0.8s ease both; }
        .animate-fade-up { animation: fadeUp 0.8s ease both; }
        .animate-fade-left { animation: fadeLeft 0.8s ease both; }
        .animate-pulse-slow { animation: pulseSlow 7s ease-in-out infinite; }
        .animate-pulse-dot { animation: pulseDot 1.8s ease-in-out infinite; }
        .animate-float { animation: float 5s ease-in-out infinite; }
        button svg, a svg, button i, a i { pointer-events: none; }
        .nav-link { position: relative; }
        .nav-link::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: #10b981; border-radius: 2px; transition: width 0.3s ease; }
        .nav-link:hover::after { width: 100%; }
    </style>
</head>
<body class="min-h-screen">

    <!-- ===================== LOGIN LAYOUT ===================== -->
    <div class="min-h-screen flex items-stretch">
        <!-- Left: Photo panel (desktop only) -->
        <div class="hidden lg:flex w-[70%] relative overflow-hidden">
            <!-- Background photo -->
            <img src="assets/images/cemetery-banner.jpg" alt="Matinao Memorial Cemetery" class="absolute inset-0 w-full h-full object-cover">
            <!-- Subtle dark gradient for text readability at top/bottom -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/50"></div>

            <div class="relative z-10 flex flex-col justify-between p-12 xl:p-20 text-white w-full animate-fade-left">
                <!-- Top: logo + badge -->
                <div>
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-full bg-white/90 shadow-lg flex items-center justify-center ring-4 ring-white/10">
                                <img src="assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-10 h-10 rounded-full object-cover">
                            </div>
                            <div>
                                <div class="text-lg font-bold leading-tight">Matinao Memorial</div>
                                <div class="text-sm text-emerald-100 font-medium leading-tight">Cemetery System</div>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur border border-white/15 text-emerald-50 text-xs font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse-dot"></span>
                            Serving Polomolok
                        </div>
                    </div>

                    <h2 class="text-4xl xl:text-5xl font-bold leading-[1.1] mb-6 max-w-2xl drop-shadow-lg">
                        Honoring memories with <span class="relative inline-block">clarity & care<svg class="absolute -bottom-2 left-0 w-full" height="8" viewBox="0 0 200 8" preserveAspectRatio="none"><path d="M0,6 Q100,0 200,6" stroke="#6ee7b7" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.8"/></svg></span>.
                    </h2>
                    <p class="text-white/80 text-lg leading-relaxed max-w-xl drop-shadow-md">
                        Sign in to search burial records, explore the interactive cemetery map, reserve plots, and connect with your loved ones — anytime, anywhere.
                    </p>
                </div>

                <!-- Bottom: quote + location -->
                <div>
                    <div class="flex items-start gap-3 mb-6 max-w-xl">
                        <i data-lucide="quote" class="w-8 h-8 text-white/30 flex-shrink-0"></i>
                        <p class="text-white/70 text-sm italic leading-relaxed">
                            "A peaceful resting place for our loved ones, now made easier to find and visit. This system brings comfort to our families."
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-white/60 text-sm pt-6 border-t border-white/10">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                        <span>Polomolok, South Cotabato, Philippines</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Login form -->
        <div class="w-full lg:w-[30%] flex items-center justify-center p-6 sm:p-10 bg-gradient-to-b from-slate-50 to-white relative">
            <!-- Theme toggle -->
            <button type="button" onclick="toggleTheme()" title="Toggle dark mode" aria-label="Toggle dark mode" class="absolute top-4 right-4 inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition z-20">
                <i data-lucide="moon" class="w-4 h-4 theme-icon-moon"></i>
                <i data-lucide="sun" class="w-4 h-4 theme-icon-sun"></i>
            </button>

            <!-- Subtle decorative accent -->
            <div class="absolute top-0 right-0 w-40 h-40 bg-emerald-100/40 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-emerald-50/50 rounded-full blur-3xl"></div>

            <div class="w-full max-w-md animate-fade-up relative z-10">
                <!-- Mobile logo (visible on small screens) -->
                <div class="lg:hidden text-center mb-8">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-white shadow-lg shadow-emerald-100 border border-emerald-200 flex items-center justify-center">
                        <img src="assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-12 h-12 rounded-full object-cover">
                    </div>
                    <div class="text-sm font-bold text-slate-900">Matinao Memorial</div>
                    <div class="text-xs text-emerald-600 font-medium">Cemetery System</div>
                </div>

                <!-- Heading -->
                <div class="mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                        <i data-lucide="lock" class="w-3 h-3"></i> Secure Sign In
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900">Welcome back</h2>
                    <p class="text-sm text-slate-500 mt-1.5">Sign in to your visitor or admin account</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-2.5 animate-fade-in">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm" class="space-y-5">
                    <!-- Identifier (username or email — auto-detected) -->
                    <div>
                        <label for="identifier" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Username or Email</label>
                        <div class="relative group">
                            <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition"></i>
                            <input
                                type="text"
                                id="identifier"
                                name="identifier"
                                placeholder="Enter your username or email"
                                required
                                autocomplete="username"
                                value="<?php echo htmlspecialchars($last_identifier, ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Password</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-11 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                            >
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition">
                                <i data-lucide="eye" class="w-4 h-4" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 cursor-pointer select-none text-slate-600">
                            <input type="checkbox" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-100">
                            <span class="text-xs">Remember me</span>
                        </label>
                        <a href="#" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition">Forgot password?</a>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-3.5 transition shadow-lg shadow-emerald-200 hover:shadow-emerald-300 hover:-translate-y-0.5 active:translate-y-0">
                        <i data-lucide="log-in" class="w-4 h-4"></i> Sign In
                    </button>
                </form>

                <!-- Divider -->
                <div class="flex items-center gap-4 my-6">
                    <div class="flex-1 h-px bg-slate-200"></div>
                    <span class="text-xs text-slate-400 font-medium">or</span>
                    <div class="flex-1 h-px bg-slate-200"></div>
                </div>

                <!-- Links -->
                <div class="space-y-2.5">
                    <a href="visitor/register.php" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-sm font-semibold py-3 transition hover:-translate-y-0.5">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Create Visitor Account
                    </a>
                    <a href="index.php" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-sm font-semibold py-3 transition">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
                    </a>
                </div>

                <!-- Trust note -->
                <p class="text-center text-xs text-slate-400 mt-8 flex items-center justify-center gap-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                    Protected with encrypted passwords and secure sessions
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
