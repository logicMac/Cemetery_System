<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matinao Memorial Cemetery - Home</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/smooth-scroll.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
    
    <style>
        .hero-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: url('https://sa.kapamilya.com/absnews/abscbnnews/media/2020/news/10/29/20201017-early-undas-visit-gc-8793.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        
        .hero-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(10, 10, 20, 0.92) 0%, 
                rgba(20, 20, 40, 0.88) 50%, 
                rgba(30, 20, 40, 0.92) 100%);
            backdrop-filter: blur(2px);
        }
        
        .hero-section {
            position: relative;
            z-index: 1;
        }
        
        .floating-badge {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .feature-icon-wrapper {
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        
        .glass-card:hover .feature-icon-wrapper {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary, .btn-secondary {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before, .btn-secondary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-primary:hover::before, .btn-secondary:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 768px) {
            .hero-background {
                background-attachment: scroll;
            }
        }
        
        /* Additional enhancements */
        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
            cursor: pointer;
            z-index: 10;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-10px); }
            60% { transform: translateX(-50%) translateY(-5px); }
        }
        
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .gradient-text {
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 6s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        footer {
            background: rgba(10, 10, 20, 0.95);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 60px 20px 30px;
            margin-top: 80px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-section h4 {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .footer-section p, .footer-section a {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            line-height: 1.8;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }
        
        .footer-section a:hover {
            color: #667eea;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="hero-background"></div>
    <div class="hero-section">
        <div class="hero-content fade-in-up">
            <!-- Floating Badge -->
            <div class="floating-badge" style="display: inline-block; background: rgba(102, 126, 234, 0.15); border: 1px solid rgba(102, 126, 234, 0.3); padding: 8px 20px; border-radius: 30px; margin-bottom: 24px; font-size: 0.9rem; font-weight: 500;">
                <svg style="display: inline-block; width: 16px; height: 16px; margin-right: 6px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
                Modern Cemetery Management System
            </div>
            
            <h1 class="hero-title" style="font-size: 3.5rem; margin-bottom: 20px; text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);">
                Matinao Memorial Cemetery
            </h1>
            <p class="hero-subtitle" style="font-size: 1.25rem; line-height: 1.8; max-width: 700px; margin: 0 auto 40px;">
                A dignified resting place with modern mapping technology.<br>
                Find and honor your loved ones with ease and convenience.
            </p>
            
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-bottom: 40px;">
                <a href="visitor/login.php" class="btn-primary" style="display: inline-flex; align-items: center; padding: 16px 32px; font-size: 1.05rem; font-weight: 600;">
                    <svg style="width: 22px; height: 22px; margin-right: 10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Visitor Portal
                </a>
                
                <a href="admin/login.php" class="btn-secondary" style="display: inline-flex; align-items: center; padding: 16px 32px; font-size: 1.05rem; font-weight: 600;">
                    <svg style="width: 22px; height: 22px; margin-right: 10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Admin Panel
                </a>
            </div>
            
            <!-- Trust Indicators -->
            <div style="display: flex; gap: 30px; justify-content: center; flex-wrap: wrap; margin-top: 50px; padding-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="text-align: center; opacity: 0.8;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 4px;">24/7</div>
                    <div style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7);">Available Online</div>
                </div>
                <div style="text-align: center; opacity: 0.8;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 4px;">GPS</div>
                    <div style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7);">Enabled Mapping</div>
                </div>
                <div style="text-align: center; opacity: 0.8;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 4px;">AI</div>
                    <div style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7);">Powered Support</div>
                </div>
            </div>
        </div>
        
        <!-- Decorative background elements -->
        <div style="position: absolute; top: 10%; left: 10%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%); border-radius: 50%; filter: blur(60px);" data-parallax="0.3"></div>
        <div style="position: absolute; bottom: 10%; right: 10%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(118, 75, 162, 0.1) 0%, transparent 70%); border-radius: 50%; filter: blur(80px);" data-parallax="0.5"></div>
        
        <!-- Scroll Indicator -->
        <div class="scroll-indicator" onclick="document.querySelector('.features-section').scrollIntoView({behavior: 'smooth'})">
            <svg style="width: 32px; height: 32px; color: rgba(255, 255, 255, 0.6);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
    </div>
    
    <!-- Features Section -->
    <section class="features-section" style="padding: 100px 20px; max-width: 1200px; margin: 0 auto; position: relative; z-index: 1;">
        <div style="text-align: center; margin-bottom: 70px;">
            <h2 style="font-size: 2.8rem; margin-bottom: 16px;">Powerful Features</h2>
            <p style="color: rgba(255, 255, 255, 0.7); font-size: 1.1rem; max-width: 600px; margin: 0 auto;">
                Experience modern cemetery management with cutting-edge technology
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 35px;">
            <div class="glass-card fade-in-up" style="text-align: center;">
                <div class="feature-icon-wrapper" style="width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);">
                    <svg style="width: 36px; height: 36px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                </div>
                <h3 style="margin-bottom: 14px; font-size: 1.4rem;">Interactive Mapping</h3>
                <p style="color: rgba(255, 255, 255, 0.7); line-height: 1.7;">Navigate the cemetery with precision using our advanced GPS-enabled mapping system for easy location finding.</p>
            </div>
            
            <div class="glass-card fade-in-up" style="animation-delay: 0.1s; text-align: center;">
                <div class="feature-icon-wrapper" style="width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);">
                    <svg style="width: 36px; height: 36px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h3 style="margin-bottom: 14px; font-size: 1.4rem;">Quick Search</h3>
                <p style="color: rgba(255, 255, 255, 0.7); line-height: 1.7;">Find burial records instantly by name, plot number, or family name with our powerful search engine.</p>
            </div>
            
            <div class="glass-card fade-in-up" style="animation-delay: 0.2s; text-align: center;">
                <div class="feature-icon-wrapper" style="width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);">
                    <svg style="width: 36px; height: 36px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                </div>
                <h3 style="margin-bottom: 14px; font-size: 1.4rem;">AI Assistant</h3>
                <p style="color: rgba(255, 255, 255, 0.7); line-height: 1.7;">Get instant help and guidance from our intelligent AI assistant available 24/7 to answer your questions.</p>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4>About Us</h4>
                    <p>Matinao Memorial Cemetery provides a peaceful and dignified resting place with modern cemetery management technology.</p>
                    <p style="margin-top: 20px;">
                        <strong style="color: rgba(255, 255, 255, 0.8);">Location:</strong><br>
                        Matinao, Philippines
                    </p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <a href="visitor/login.php">Visitor Portal</a>
                    <a href="admin/login.php">Admin Login</a>
                    <a href="visitor/register.php">Register Account</a>
                </div>
                
                <div class="footer-section">
                    <h4>Services</h4>
                    <a href="#">Cemetery Mapping</a>
                    <a href="#">Burial Records</a>
                    <a href="#">Plot Reservations</a>
                    <a href="#">AI Assistant</a>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>
                        <svg style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        info@matinaocemetery.com
                    </p>
                    <p>
                        <svg style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        +63 XXX XXX XXXX
                    </p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Matinao Memorial Cemetery. All rights reserved. | Powered by Modern Cemetery Management System</p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/smooth-scroll.js"></script>
</body>
</html>
