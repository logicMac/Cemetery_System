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

    $full_name = strip_tags((string)filter_input(INPUT_POST, 'full_name'));
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = strip_tags((string)filter_input(INPUT_POST, 'phone'));
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

    <!-- ===================== REGISTER LAYOUT ===================== -->
    <div class="min-h-screen flex items-stretch">
        <!-- Left: Photo panel (desktop only) -->
        <div class="hidden lg:flex w-[70%] relative overflow-hidden">
            <!-- Background photo -->
            <img src="../assets/images/cemetery-banner.jpg" alt="Matinao Memorial Cemetery" class="absolute inset-0 w-full h-full object-cover">
            <!-- Subtle dark gradient for text readability at top/bottom -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/50"></div>

            <div class="relative z-10 flex flex-col justify-between p-12 xl:p-20 text-white w-full animate-fade-left">
                <!-- Top: logo + badge -->
                <div>
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-full bg-white/90 shadow-lg flex items-center justify-center ring-4 ring-white/10">
                                <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-10 h-10 rounded-full object-cover">
                            </div>
                            <div>
                                <div class="text-lg font-bold leading-tight">Matinao Memorial</div>
                                <div class="text-sm text-emerald-100 font-medium leading-tight">Cemetery System</div>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur border border-white/15 text-emerald-50 text-xs font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse-dot"></span>
                            Free Registration
                        </div>
                    </div>

                    <h2 class="text-4xl xl:text-5xl font-bold leading-[1.1] mb-6 max-w-2xl drop-shadow-lg">
                        Create an account to <span class="relative inline-block">stay connected<svg class="absolute -bottom-2 left-0 w-full" height="8" viewBox="0 0 200 8" preserveAspectRatio="none"><path d="M0,6 Q100,0 200,6" stroke="#6ee7b7" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.8"/></svg></span>.
                    </h2>
                    <p class="text-white/80 text-lg leading-relaxed max-w-xl drop-shadow-md">
                        Register as a visitor to search burial records, explore the interactive cemetery map, reserve plots, and get AI-assisted directions to your loved ones.
                    </p>
                </div>

                <!-- Bottom: quote + location -->
                <div>
                    <div class="flex items-start gap-3 mb-6 max-w-xl">
                        <i data-lucide="quote" class="w-8 h-8 text-white/30 flex-shrink-0"></i>
                        <p class="text-white/70 text-sm italic leading-relaxed">
                            "Finding my grandmother's plot used to take hours. Now I can locate it in seconds with the interactive map."
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-white/60 text-sm pt-6 border-t border-white/10">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                        <span>Polomolok, South Cotabato, Philippines</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Register form -->
        <div class="w-full lg:w-[30%] flex items-center justify-center p-6 sm:p-10 bg-gradient-to-b from-slate-50 to-white relative overflow-y-auto">
            <!-- Subtle decorative accent -->
            <div class="absolute top-0 right-0 w-40 h-40 bg-emerald-100/40 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-emerald-50/50 rounded-full blur-3xl"></div>

        <div class="w-full max-w-md animate-fade-up relative z-10">
            <!-- Mobile logo (visible on small screens) -->
            <div class="lg:hidden text-center mb-8">
                <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-white shadow-lg shadow-emerald-100 border border-emerald-200 flex items-center justify-center">
                    <img src="../assets/images/matinao-logo.png" alt="Matinao Memorial Logo" class="w-12 h-12 rounded-full object-cover">
                </div>
                <div class="text-sm font-bold text-slate-900">Matinao Memorial</div>
                <div class="text-xs text-emerald-600 font-medium">Cemetery System</div>
            </div>

            <!-- Heading -->
            <div class="mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                    <i data-lucide="user-plus" class="w-3 h-3"></i> Free Visitor Account
                </div>
                <h2 class="text-2xl font-bold text-slate-900">Create account</h2>
                <p class="text-sm text-slate-500 mt-1.5">Register to search records and reserve plots</p>
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
                    <a href="../login.php" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:text-emerald-800">Go to Login <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm" class="space-y-4">
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Full Name <span class="text-rose-500 normal-case">*</span></label>
                    <div class="relative group">
                        <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition"></i>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            placeholder="Juan Dela Cruz"
                            required
                            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                        >
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Email Address <span class="text-rose-500 normal-case">*</span></label>
                    <div class="relative group">
                        <i data-lucide="mail" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                        >
                    </div>
                    <small id="email-status" class="text-xs mt-1 block"></small>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Phone Number <span class="text-slate-400 text-xs font-normal normal-case">(optional)</span></label>
                    <div class="relative group">
                        <i data-lucide="phone" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition"></i>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="+63 9XX XXX XXXX"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Password <span class="text-rose-500 normal-case">*</span></label>
                    <div class="relative group">
                        <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Min 8 characters"
                            required
                            minlength="8"
                            oninput="updateStrength(this)"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-11 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                        >
                        <button type="button" onclick="togglePassword('password', 'eyeIcon1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition">
                            <i data-lucide="eye" class="w-4 h-4" id="eyeIcon1"></i>
                        </button>
                    </div>
                    <div class="password-strength"><div class="password-strength-bar" id="strength-bar"></div></div>
                    <small id="strength-text" class="text-xs text-slate-400 mt-1 block"></small>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Confirm Password <span class="text-rose-500 normal-case">*</span></label>
                    <div class="relative group">
                        <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition"></i>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Re-enter password"
                            required
                            minlength="8"
                            oninput="checkMatch()"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-11 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition"
                        >
                        <button type="button" onclick="togglePassword('confirm_password', 'eyeIcon2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition">
                            <i data-lucide="eye" class="w-4 h-4" id="eyeIcon2"></i>
                        </button>
                    </div>
                    <small id="match-status" class="text-xs mt-1 block"></small>
                </div>

                <!-- Submit -->
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-3.5 transition shadow-lg shadow-emerald-200 hover:shadow-emerald-300 hover:-translate-y-0.5 active:translate-y-0 mt-2">
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
                <a href="../login.php" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-sm font-semibold py-3 transition hover:-translate-y-0.5">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Already have an account? Sign in
                </a>
                <a href="../index.php" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-sm font-semibold py-3 transition">
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
        });
    </script>
</body>
</html>
