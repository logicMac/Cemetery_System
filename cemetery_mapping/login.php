<?php
session_start();

// Determine preselected role from query string
$role = $_GET['role'] ?? 'admin';
if (!in_array($role, ['admin', 'visitor'], true)) {
    $role = 'admin';
}

// Redirect if already logged in as the matching role
if ($role === 'admin' && isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
if ($role === 'visitor' && isset($_SESSION['visitor_id'])) {
    header('Location: visitor/dashboard.php');
    exit;
}

// Handle login form submission
$error = '';
$submitted_role = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';

    $submitted_role = $_POST['role'] ?? 'admin';
    if (!in_array($submitted_role, ['admin', 'visitor'], true)) {
        $submitted_role = 'admin';
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($submitted_role === 'admin') {
        // Admin uses username
        if (empty($identifier) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, password, email FROM admin_users WHERE username = ?");
                $stmt->execute([$identifier]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['last_activity'] = time();

                    header('Location: admin/dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } catch (PDOException $e) {
                error_log("Admin login error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    } else {
        // Visitor uses email
        if (empty($identifier) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            try {
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
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            } catch (PDOException $e) {
                error_log("Visitor login error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

$selected_role = $submitted_role === 'visitor' ? 'visitor' : $role;
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
        .animate-slide-up { animation: slideUp 0.6s ease both; }
        .animate-fade-in { animation: fadeIn 0.8s ease both; }
        .animate-fade-up { animation: fadeUp 0.8s ease both; }
        .animate-pulse-slow { animation: pulseSlow 7s ease-in-out infinite; }
        .animate-pulse-dot { animation: pulseDot 1.8s ease-in-out infinite; }
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

    <div class="min-h-screen flex pt-16">
        <!-- LEFT PANEL (70%) - Branding -->
        <div class="hidden lg:flex lg:w-[70%] relative bg-gradient-to-br from-emerald-50 via-emerald-100/40 to-teal-100/50 items-center justify-center p-12 overflow-hidden">
            <!-- Decorative blobs -->
            <div class="absolute top-10 left-10 w-80 h-80 bg-emerald-200 rounded-full blur-3xl opacity-60 animate-pulse-slow"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-emerald-100 rounded-full blur-3xl opacity-70 animate-pulse-slow"></div>
            <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-teal-100 rounded-full blur-3xl opacity-50"></div>
            <div class="absolute bottom-1/4 left-1/3 w-64 h-64 bg-emerald-300/40 rounded-full blur-3xl opacity-50 animate-pulse-slow"></div>

            <!-- Subtle grid pattern overlay -->
            <div class="absolute inset-0 opacity-[0.05]" style="background-image: linear-gradient(#10b981 1px, transparent 1px), linear-gradient(90deg, #10b981 1px, transparent 1px); background-size: 32px 32px;"></div>

            <div class="relative z-10 max-w-xl animate-fade-up">
                <!-- Logo + Brand -->
                <div class="flex items-center gap-4 mb-12">
                    <div class="w-16 h-16 rounded-full bg-white shadow-xl shadow-emerald-200 border-2 border-emerald-200 flex items-center justify-center">
                        <img src="assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-12 h-12 rounded-full object-cover">
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Matinao Memorial</h1>
                        <p class="text-sm text-emerald-700 font-medium">Cemetery Mapping & Management System</p>
                    </div>
                </div>

                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/80 backdrop-blur text-emerald-700 text-xs font-semibold mb-6 border border-emerald-200 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse-dot"></span>
                    Unified Portal · v1.0
                </div>

                <!-- Headline -->
                <h2 class="text-5xl font-bold text-slate-900 leading-[1.1] mb-5 tracking-tight">
                    One portal for <br><span class="text-emerald-700 relative inline-block">everyone<svg class="absolute -bottom-2 left-0 w-full" height="8" viewBox="0 0 200 8" preserveAspectRatio="none"><path d="M0,6 Q100,0 200,6" stroke="#10b981" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.6"/></svg></span> who cares.
                </h2>
                <p class="text-slate-600 text-base leading-relaxed mb-10 max-w-lg">
                    A single sign-in for administrators and visitors — manage burial records, explore the cemetery map, reserve plots, and get AI-assisted insights.
                </p>

                <!-- Feature pills -->
                <div class="grid grid-cols-2 gap-3 max-w-lg mb-10">
                    <div class="group flex items-center gap-3 p-4 rounded-2xl bg-white/90 backdrop-blur border border-emerald-200 shadow-sm hover:shadow-md hover:border-emerald-400 hover:-translate-y-0.5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Burial Records</div>
                            <div class="text-xs text-emerald-600/70">Search & manage</div>
                        </div>
                    </div>
                    <div class="group flex items-center gap-3 p-4 rounded-2xl bg-white/90 backdrop-blur border border-emerald-200 shadow-sm hover:shadow-md hover:border-emerald-400 hover:-translate-y-0.5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform"><i data-lucide="map" class="w-5 h-5"></i></div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Cemetery Map</div>
                            <div class="text-xs text-emerald-600/70">Interactive plots</div>
                        </div>
                    </div>
                    <div class="group flex items-center gap-3 p-4 rounded-2xl bg-white/90 backdrop-blur border border-emerald-200 shadow-sm hover:shadow-md hover:border-emerald-400 hover:-translate-y-0.5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform"><i data-lucide="calendar-check" class="w-5 h-5"></i></div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Reservations</div>
                            <div class="text-xs text-emerald-600/70">Track & approve</div>
                        </div>
                    </div>
                    <div class="group flex items-center gap-3 p-4 rounded-2xl bg-white/90 backdrop-blur border border-emerald-200 shadow-sm hover:shadow-md hover:border-emerald-400 hover:-translate-y-0.5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform"><i data-lucide="sparkles" class="w-5 h-5"></i></div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">AI Assistant</div>
                            <div class="text-xs text-emerald-600/70">Powered by Groq</div>
                        </div>
                    </div>
                </div>

                <!-- Stats strip -->
                <div class="flex items-center gap-8 pt-6 border-t border-emerald-200/70 max-w-lg">
                    <div>
                        <div class="text-2xl font-bold text-emerald-700">100%</div>
                        <div class="text-xs text-slate-600">Secure access</div>
                    </div>
                    <div class="w-px h-10 bg-emerald-200"></div>
                    <div>
                        <div class="text-2xl font-bold text-emerald-700">24/7</div>
                        <div class="text-xs text-slate-600">Always available</div>
                    </div>
                    <div class="w-px h-10 bg-emerald-200"></div>
                    <div>
                        <div class="text-2xl font-bold text-emerald-700">AI</div>
                        <div class="text-xs text-slate-600">Powered insights</div>
                    </div>
                </div>

                <!-- Footer note -->
                <p class="text-xs text-slate-600 mt-10 flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i> Protected with bcrypt password hashing · End-to-end encrypted session
                </p>
            </div>
        </div>

        <!-- RIGHT PANEL (30%) - Login form -->
        <div class="w-full lg:w-[30%] flex items-center justify-center p-6 sm:p-10 bg-white border-l border-slate-200">
            <div class="w-full max-w-sm animate-fade-up">
                <!-- Mobile logo (shown only on small screens) -->
                <div class="lg:hidden text-center mb-8">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg shadow-emerald-200 flex items-center justify-center">
                        <img src="assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-11 h-11 rounded-full object-cover">
                    </div>
                    <h1 class="text-xl font-bold text-slate-900">Matinao Memorial</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Cemetery Portal</p>
                </div>

                <!-- Heading -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900">Welcome back</h2>
                    <p class="text-sm text-slate-500 mt-1">Sign in to your account</p>
                </div>

                <!-- Role selector tabs -->
                <div class="grid grid-cols-2 gap-1 p-1 bg-slate-100 rounded-xl mb-6">
                    <button type="button" id="tab-admin" onclick="switchRole('admin')" class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition <?php echo $selected_role === 'admin' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
                        <i data-lucide="shield" class="w-4 h-4"></i> Admin
                    </button>
                    <button type="button" id="tab-visitor" onclick="switchRole('visitor')" class="flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition <?php echo $selected_role === 'visitor' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
                        <i data-lucide="user" class="w-4 h-4"></i> Visitor
                    </button>
                </div>

                <?php if ($error): ?>
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-2.5 animate-fade-in">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm" class="space-y-5">
                    <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($selected_role, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- Identifier (username or email) -->
                    <div>
                        <label for="identifier" id="identifierLabel" class="block text-sm font-medium text-slate-700 mb-1.5"><?php echo $selected_role === 'visitor' ? 'Email' : 'Username'; ?></label>
                        <div class="relative">
                            <i data-lucide="<?php echo $selected_role === 'visitor' ? 'mail' : 'user'; ?>" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" id="identifierIcon"></i>
                            <input
                                type="<?php echo $selected_role === 'visitor' ? 'email' : 'text'; ?>"
                                id="identifier"
                                name="identifier"
                                placeholder="<?php echo $selected_role === 'visitor' ? 'Enter your email' : 'Enter your username'; ?>"
                                required
                                autocomplete="username"
                                value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier'], ENT_QUOTES, 'UTF-8') : ''; ?>"
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
                    <a href="visitor/register.php" id="registerLink" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-sm font-semibold py-3 transition <?php echo $selected_role === 'admin' ? 'hidden' : ''; ?>">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Create Visitor Account
                    </a>
                    <a href="index.php" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-sm font-semibold py-3 transition">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
                    </a>
                </div>

                <!-- Footer -->
                <p class="text-center text-xs text-slate-400 mt-8">&copy; <?php echo date('Y'); ?> Matinao Memorial Cemetery</p>
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

        function switchRole(role) {
            const tabAdmin = document.getElementById('tab-admin');
            const tabVisitor = document.getElementById('tab-visitor');
            const roleInput = document.getElementById('roleInput');
            const identifier = document.getElementById('identifier');
            const identifierLabel = document.getElementById('identifierLabel');
            const identifierIcon = document.getElementById('identifierIcon');
            const registerLink = document.getElementById('registerLink');

            const activeClasses = 'bg-white text-emerald-700 shadow-sm';
            const inactiveClasses = 'text-slate-500 hover:text-slate-700';

            if (role === 'admin') {
                tabAdmin.className = 'flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition ' + activeClasses;
                tabVisitor.className = 'flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition ' + inactiveClasses;
                identifier.type = 'text';
                identifier.placeholder = 'Enter your username';
                identifierLabel.textContent = 'Username';
                identifierIcon.setAttribute('data-lucide', 'user');
                registerLink.classList.add('hidden');
            } else {
                tabVisitor.className = 'flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition ' + activeClasses;
                tabAdmin.className = 'flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition ' + inactiveClasses;
                identifier.type = 'email';
                identifier.placeholder = 'Enter your email';
                identifierLabel.textContent = 'Email';
                identifierIcon.setAttribute('data-lucide', 'mail');
                registerLink.classList.remove('hidden');
            }
            roleInput.value = role;
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
