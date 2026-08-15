<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['visitor_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle registration form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';

    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM visitors WHERE email = ?");
            $checkStmt->execute([$email]);

            if ($checkStmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                // Hash password using bcrypt
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new visitor
                $insertStmt = $pdo->prepare("INSERT INTO visitors (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
                $insertStmt->execute([$full_name, $email, $phone, $hashed_password]);

                $success = 'Registration successful! You can now log in.';
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred during registration. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Matinao Memorial Cemetery</title>

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
        @keyframes pulseSlow { 0%, 100% { opacity: 0.5; transform: scale(1); } 50% { opacity: 0.7; transform: scale(1.08); } }
        @keyframes pulseDot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.4); } }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-12px); } }
        .animate-slide-up { animation: slideUp 0.6s ease both; }
        .animate-fade-in { animation: fadeIn 0.8s ease both; }
        .animate-fade-up { animation: fadeUp 0.8s ease both; }
        .animate-pulse-slow { animation: pulseSlow 7s ease-in-out infinite; }
        .animate-pulse-dot { animation: pulseDot 1.8s ease-in-out infinite; }
        .animate-float { animation: float 5s ease-in-out infinite; }
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
        button svg, a svg, button i, a i { pointer-events: none; }
        .nav-link { position: relative; }
        .nav-link::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: #10b981; border-radius: 2px; transition: width 0.3s ease; }
        .nav-link:hover::after { width: 100%; }
        .password-strength { height: 6px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; margin-top: 8px; }
        .password-strength-bar { height: 100%; width: 0; border-radius: 9999px; transition: width 0.3s ease, background 0.3s ease; }
        .password-strength-bar.weak { width: 33%; background: #ef4444; }
        .password-strength-bar.medium { width: 66%; background: #f59e0b; }
        .password-strength-bar.strong { width: 100%; background: #10b981; }
    </style>
</head>
<body class="min-h-screen">

    <!-- ===================== NAVBAR ===================== -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/70">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 h-16 flex items-center justify-between">
            <a href="../index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white shadow-md border border-emerald-200 flex items-center justify-center">
                    <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-8 h-8 rounded-full object-cover">
                </div>
                <div class="hidden sm:block">
                    <div class="text-sm font-bold text-slate-900 leading-tight">Matinao Memorial</div>
                    <div class="text-[11px] text-emerald-600 font-medium leading-tight">Cemetery System</div>
                </div>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="../index.php#home" class="nav-link hover:text-emerald-600 transition">Home</a>
                <a href="../index.php#features" class="nav-link hover:text-emerald-600 transition">Features</a>
                <a href="../index.php#services" class="nav-link hover:text-emerald-600 transition">Services</a>
                <a href="../index.php#about" class="nav-link hover:text-emerald-600 transition">About</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="../login.php?role=visitor" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700 hover:text-emerald-600 transition px-3 py-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Sign In
                </a>
                <a href="../index.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg px-4 py-2 transition shadow-sm shadow-emerald-200">
                    <i data-lucide="home" class="w-4 h-4"></i> Home
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
                        <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-12 h-12 rounded-full object-cover">
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Matinao Memorial</h1>
                        <p class="text-sm text-emerald-700 font-medium">Cemetery Mapping & Management System</p>
                    </div>
                </div>

                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/80 backdrop-blur text-emerald-700 text-xs font-semibold mb-6 border border-emerald-200 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse-dot"></span>
                    Visitor Registration · Free Account
                </div>

                <!-- Headline -->
                <h2 class="text-5xl font-bold text-slate-900 leading-[1.1] mb-5 tracking-tight">
                    Join the <span class="text-emerald-700 relative inline-block">community<svg class="absolute -bottom-2 left-0 w-full" height="8" viewBox="0 0 200 8" preserveAspectRatio="none"><path d="M0,6 Q100,0 200,6" stroke="#10b981" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.6"/></svg></span> of remembrance.
                </h2>
                <p class="text-slate-600 text-base leading-relaxed mb-10 max-w-lg">
                    Create a free visitor account to search burial records, explore the interactive cemetery map, reserve plots, and chat with our AI assistant.
                </p>

                <!-- Benefits -->
                <div class="space-y-3 max-w-lg mb-10 reveal-stagger reveal reveal-up">
                    <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-white/80 backdrop-blur border border-emerald-200 shadow-sm">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="search" class="w-4 h-4"></i></div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Search Burial Records</div>
                            <div class="text-xs text-emerald-600/70">Find loved ones by name or plot number</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-white/80 backdrop-blur border border-emerald-200 shadow-sm">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="map" class="w-4 h-4"></i></div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Explore Cemetery Map</div>
                            <div class="text-xs text-emerald-600/70">GPS-enabled interactive mapping</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-white/80 backdrop-blur border border-emerald-200 shadow-sm">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center"><i data-lucide="calendar-check" class="w-4 h-4"></i></div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Reserve Plots Online</div>
                            <div class="text-xs text-emerald-600/70">Track status & payments</div>
                        </div>
                    </div>
                </div>

                <!-- Footer note -->
                <p class="text-xs text-slate-600 flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i> Your data is protected with bcrypt password hashing
                </p>
            </div>
        </div>

        <!-- RIGHT PANEL (30%) - Register form -->
        <div class="w-full lg:w-[30%] flex items-center justify-center p-6 sm:p-10 bg-white border-l border-slate-200">
            <div class="w-full max-w-sm animate-fade-up">
                <!-- Mobile logo (shown only on small screens) -->
                <div class="lg:hidden text-center mb-8">
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg shadow-emerald-200 flex items-center justify-center">
                        <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-11 h-11 rounded-full object-cover">
                    </div>
                    <h1 class="text-xl font-bold text-slate-900">Matinao Memorial</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Visitor Registration</p>
                </div>

                <!-- Heading -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900">Create account</h2>
                    <p class="text-sm text-slate-500 mt-1">Join Matinao Memorial Cemetery</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-2.5 animate-fade-in">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm animate-fade-in">
                        <div class="flex items-center gap-2.5 mb-2">
                            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <a href="../login.php?role=visitor" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:text-emerald-800">Go to Login <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm" class="space-y-4">
                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                placeholder="Juan Dela Cruz"
                                required
                                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                            >
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="you@example.com"
                                required
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                            >
                        </div>
                        <small id="email-status" class="text-xs mt-1 block"></small>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number <span class="text-slate-400 text-xs font-normal">(optional)</span></label>
                        <div class="relative">
                            <i data-lucide="phone" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="+63 9XX XXX XXXX"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Min 8 characters"
                                required
                                minlength="8"
                                oninput="updateStrength(this)"
                                class="w-full rounded-xl border border-slate-300 pl-10 pr-11 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                            >
                            <button type="button" onclick="togglePassword('password', 'eyeIcon1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <i data-lucide="eye" class="w-4 h-4" id="eyeIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength"><div class="password-strength-bar" id="strength-bar"></div></div>
                        <small id="strength-text" class="text-xs text-slate-400 mt-1 block"></small>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Re-enter password"
                                required
                                minlength="8"
                                oninput="checkMatch()"
                                class="w-full rounded-xl border border-slate-300 pl-10 pr-11 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                            >
                            <button type="button" onclick="togglePassword('confirm_password', 'eyeIcon2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <i data-lucide="eye" class="w-4 h-4" id="eyeIcon2"></i>
                            </button>
                        </div>
                        <small id="match-status" class="text-xs mt-1 block"></small>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-3.5 transition shadow-lg shadow-emerald-200 hover:shadow-emerald-300 mt-2">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Create Account
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
                    <a href="../login.php?role=visitor" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-sm font-semibold py-3 transition">
                        <i data-lucide="log-in" class="w-4 h-4"></i> Already have an account? Sign in
                    </a>
                    <a href="../index.php" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-sm font-semibold py-3 transition">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
                    </a>
                </div>

                <!-- Footer -->
                <p class="text-center text-xs text-slate-400 mt-8">&copy; <?php echo date('Y'); ?> Matinao Memorial Cemetery</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function updateStrength(input) {
            const bar = document.getElementById('strength-bar');
            const text = document.getElementById('strength-text');
            const v = input.value;
            if (!v) { bar.className = 'password-strength-bar'; text.textContent = ''; return; }
            let score = 0;
            if (v.length >= 8) score++;
            if (v.length >= 12) score++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^a-zA-Z\d]/.test(v)) score++;
            if (score <= 2) { bar.className = 'password-strength-bar weak'; text.textContent = 'Weak password'; text.style.color = '#ef4444'; }
            else if (score <= 4) { bar.className = 'password-strength-bar medium'; text.textContent = 'Medium password'; text.style.color = '#f59e0b'; }
            else { bar.className = 'password-strength-bar strong'; text.textContent = 'Strong password'; text.style.color = '#10b981'; }
            checkMatch();
        }

        function checkMatch() {
            const newP = document.getElementById('password').value;
            const conf = document.getElementById('confirm_password').value;
            const t = document.getElementById('match-status');
            if (!conf) { t.textContent = ''; return; }
            if (newP === conf) { t.textContent = 'Passwords match'; t.style.color = '#10b981'; }
            else { t.textContent = 'Passwords do not match'; t.style.color = '#ef4444'; }
        }

        // Email uniqueness check (async)
        const emailInput = document.getElementById('email');
        const emailStatus = document.getElementById('email-status');
        let emailCheckTimeout;

        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        emailInput.addEventListener('input', () => {
            clearTimeout(emailCheckTimeout);
            const email = emailInput.value;

            if (!validateEmail(email)) {
                emailStatus.textContent = '';
                return;
            }

            emailStatus.textContent = 'Checking...';
            emailStatus.style.color = '#94a3b8';

            emailCheckTimeout = setTimeout(() => {
                fetch('../api/check_email.php?email=' + encodeURIComponent(email))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            emailStatus.textContent = 'Email already registered';
                            emailStatus.style.color = '#ef4444';
                        } else {
                            emailStatus.textContent = 'Email available';
                            emailStatus.style.color = '#10b981';
                        }
                    })
                    .catch(() => {
                        emailStatus.textContent = '';
                    });
            }, 500);
        });

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
        });
    </script>
</body>
</html>
