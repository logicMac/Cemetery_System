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
    <title>Sign In - Matinao Memorial Cemetery</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

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
        @keyframes pulseSlow { 0%, 100% { opacity: 0.4; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.08); } }
        @keyframes pulseDot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.4); } }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
        .animate-slide-up { animation: slideUp 0.6s ease both; }
        .animate-fade-in { animation: fadeIn 0.8s ease both; }
        .animate-fade-up { animation: fadeUp 0.8s ease both; }
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

    <!-- ===================== NAVBAR ===================== -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/70">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 h-16 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white shadow-md border border-emerald-200 flex items-center justify-center">
                    <img src="assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-8 h-8 rounded-full object-cover">
                </div>
                <div class="hidden sm:block">
                    <div class="text-sm font-bold text-slate-900 leading-tight">Matinao Memorial</div>
                    <div class="text-[11px] text-emerald-600 font-medium leading-tight">Cemetery System</div>
                </div>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="index.php#home" class="nav-link hover:text-emerald-600 transition">Home</a>
                <a href="index.php#features" class="nav-link hover:text-emerald-600 transition">Features</a>
                <a href="index.php#services" class="nav-link hover:text-emerald-600 transition">Services</a>
                <a href="index.php#about" class="nav-link hover:text-emerald-600 transition">About</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="visitor/register.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg px-4 py-2 transition shadow-sm shadow-emerald-200">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Register
                </a>
            </div>
        </div>
    </nav>

    <div class="min-h-screen flex items-center justify-center p-6 pt-24 pb-12 bg-white">
        <div class="w-full max-w-md animate-fade-up">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 overflow-hidden">
                <div class="p-8 sm:p-10">
                    <!-- Logo + Heading -->
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white shadow-lg shadow-emerald-100 border border-emerald-200 flex items-center justify-center">
                            <img src="assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-12 h-12 rounded-full object-cover">
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900">Welcome back</h2>
                        <p class="text-sm text-slate-500 mt-1">Sign in to your account</p>
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
                            <label for="identifier" class="block text-sm font-medium text-slate-700 mb-1.5">Username or Email</label>
                            <div class="relative">
                                <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input
                                    type="text"
                                    id="identifier"
                                    name="identifier"
                                    placeholder="Enter your username or email"
                                    required
                                    autocomplete="username"
                                    value="<?php echo htmlspecialchars($last_identifier, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                                >
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                            <div class="relative">
                                <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
                                    class="w-full rounded-xl border border-slate-300 pl-10 pr-11 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                                >
                                <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
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
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-3.5 transition shadow-lg shadow-emerald-200 hover:shadow-emerald-300">
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
                        <a href="visitor/register.php" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-sm font-semibold py-3 transition">
                            <i data-lucide="user-plus" class="w-4 h-4"></i> Create Visitor Account
                        </a>
                        <a href="index.php" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-sm font-semibold py-3 transition">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
                        </a>
                    </div>
                </div>
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
        });
    </script>
</body>
</html>
