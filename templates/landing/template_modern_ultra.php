<?php
/**
 * Template 8: Modern Ultra Theme
 * Ultra-modern design with 3D effects, particles, and smooth animations
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $appName; ?> - Internet Service Provider</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo $appName; ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="assets/icons/icon-192x192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-light: #a78bfa;
            --secondary: #ec4899;
            --accent: #f97316;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;
            --gradient: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--dark);
            color: var(--light);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated Gradient Background */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            z-index: -2;
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-100px) rotate(90deg); }
            50% { transform: translateY(0) rotate(180deg); }
            75% { transform: translateY(100px) rotate(270deg); }
        }

        /* Glassmorphism Cards */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
        }

        /* 3D Card Effect */
        .card-3d {
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .card-3d-inner {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(236, 72, 153, 0.1));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            transition: transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card-3d:hover .card-3d-inner {
            transform: rotateY(5deg) rotateX(5deg) translateZ(20px);
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.3);
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            backdrop-filter: blur(20px);
            background: rgba(15, 23, 42, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .logo { 
            font-size: 1.5rem; 
            font-weight: 700; 
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links { display: flex; gap: 30px; }
        .nav-links a { 
            color: var(--light); 
            text-decoration: none; 
            font-weight: 500;
            transition: 0.3s;
            position: relative;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gradient);
            transition: 0.3s;
        }
        .nav-links a:hover::after { width: 100%; }

        .nav-toggle {
            display: none;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--light);
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            backdrop-filter: blur(20px);
        }

        /* Dropdown Menu */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            min-width: 220px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            top: 100%;
            right: 0;
            overflow: hidden;
            margin-top: 10px;
        }
        .dropdown-content a {
            color: var(--light);
            padding: 14px 18px;
            text-decoration: none;
            display: block;
            text-align: left;
            transition: 0.3s;
            font-size: 0.9rem;
        }
        .dropdown-content a:hover {
            background: rgba(255,255,255,0.05);
        }
        .dropdown:hover .dropdown-content { display: block; }

        .login-btn {
            background: var(--gradient);
            padding: 12px 28px;
            border-radius: 50px;
            color: #fff !important;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 5% 60px;
            text-align: center;
            position: relative;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 { 
            font-size: 4rem; 
            font-weight: 900; 
            line-height: 1.1; 
            margin-bottom: 30px;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -2px;
        }
        .hero p { 
            font-size: 1.2rem; 
            color: rgba(255, 255, 255, 0.7); 
            margin-bottom: 40px; 
            line-height: 1.8;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons { display: flex; gap: 20px; justify-content: center; }
        .btn { 
            padding: 16px 36px; 
            border-radius: 50px; 
            text-decoration: none; 
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-primary { 
            background: var(--gradient); 
            color: #fff;
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(139, 92, 246, 0.4);
        }
        .btn-outline { 
            border: 2px solid var(--gradient);
            color: var(--light);
        }
        .btn-outline:hover {
            background: var(--gradient);
            color: #fff;
        }

        /* Features Section */
        .features { padding: 100px 5%; }
        .section-title { 
            font-size: 3rem; 
            margin-bottom: 60px; 
            text-align: center;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 30px;
        }
        .feature-card {
            padding: 40px;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-icon { 
            font-size: 3rem; 
            margin-bottom: 20px;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .feature-card h3 { font-size: 1.4rem; margin-bottom: 15px; }
        .feature-card p { color: rgba(255,255,255,0.7); }

        /* Packages Section */
        .packages { padding: 100px 5%; text-align: center; }
        .package-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 30px; 
            margin-top: 60px; 
        }
        .package-card {
            padding: 50px;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .package-card:hover {
            transform: translateY(-10px);
        }
        .package-card h3 { font-size: 1.5rem; margin-bottom: 20px; }
        .package-price { 
            font-size: 3rem; 
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 20px 0;
        }

        /* Contact Section */
        .contact { padding: 100px 5%; text-align: center; }
        .contact-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 30px; 
            margin-top: 60px; 
        }
        .contact-item { padding: 40px; }
        .contact-item i { 
            font-size: 2.5rem; 
            margin-bottom: 15px;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .contact-item h3 { margin-bottom: 10px; }
        .contact-item p { color: rgba(255,255,255,0.7); }

        /* Footer */
        .footer { 
            padding: 40px 5%; 
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .social-links { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            margin-bottom: 20px; 
        }
        .social-links a { 
            color: var(--light); 
            font-size: 1.5rem; 
            transition: 0.3s;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .social-links a:hover { 
            background: var(--gradient);
            transform: translateY(-5px);
        }

        /* Scroll Animations */
        .fade-up {
            opacity: 0;
            transform: translateY(50px);
            transition: 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Staggered Animation */
        .stagger-1 { transition-delay: 0.1s; }
        .stagger-2 { transition-delay: 0.2s; }
        .stagger-3 { transition-delay: 0.3s; }
        .stagger-4 { transition-delay: 0.4s; }
        .stagger-5 { transition-delay: 0.5s; }
        .stagger-6 { transition-delay: 0.6s; }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .navbar { padding: 15px 20px; }
            .nav-toggle { display: inline-flex; }
            .nav-links {
                display: none;
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                padding: 14px 20px 18px;
                background: rgba(15, 23, 42, 0.92);
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                flex-direction: column;
                gap: 14px;
                align-items: stretch;
                backdrop-filter: blur(20px);
            }
            .nav-links.open { display: flex; }
            .dropdown:hover .dropdown-content { display: none; }
            .dropdown.open .dropdown-content {
                display: block;
                position: static;
                margin-top: 10px;
                width: 100%;
            }
            .nav-links a { width: 100%; }
            .nav-links .dropdown { width: 100%; }
            .nav-links .dropdown-content { width: 100%; box-sizing: border-box; }
            .login-btn { width: 100%; text-align: center; display: inline-flex; justify-content: center; }
            .hero-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="particles" id="particles"></div>

    <nav class="navbar">
        <a href="#" class="logo"><i class="fas fa-wifi"></i> <?php echo $appName; ?></a>
        <button class="nav-toggle" type="button" onclick="window.__gembokToggleNav && window.__gembokToggleNav()"><i class="fas fa-bars"></i></button>
        <div class="nav-links">
            <a href="#features">Fitur</a>
            <a href="#packages">Paket</a>
            <a href="voucher-order.php">Voucher</a>
            <a href="#" onclick="window.__gembokOpenRegisterModal && window.__gembokOpenRegisterModal(); return false;">List</a>
            <a href="#contact">Kontak</a>
            <div class="dropdown">
                <a href="#" class="login-btn" onclick="window.__gembokToggleLogin && window.__gembokToggleLogin(event)">Login <i class="fas fa-chevron-down"></i></a>
                <div class="dropdown-content">
                    <a href="portal/login.php"><i class="fas fa-user"></i> Customer</a>
                    <a href="sales/login.php"><i class="fas fa-user-tie"></i> Sales / Agen</a>
                    <a href="technician/login.php"><i class="fas fa-tools"></i> Technician</a>
                    <a href="admin/login.php"><i class="fas fa-user-shield"></i> Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1 class="fade-up"><?php echo strip_tags($heroTitle); ?></h1>
            <p class="fade-up stagger-2"><?php echo $heroDesc; ?></p>
            <div class="cta-buttons fade-up stagger-3">
                <a href="#packages" class="btn btn-primary">Mulai Sekarang</a>
                <a href="#contact" class="btn btn-outline">Contact Kami</a>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <h2 class="section-title fade-up">Kenapa Memilih Kami</h2>
        <div class="feature-grid">
            <div class="feature-card glass fade-up stagger-1">
                <i class="fas fa-tachometer-alt feature-icon"></i>
                <h3><?php echo $f1_title; ?></h3>
                <p><?php echo $f1_desc; ?></p>
            </div>
            <div class="feature-card glass fade-up stagger-2">
                <i class="fas fa-infinity feature-icon"></i>
                <h3><?php echo $f2_title; ?></h3>
                <p><?php echo $f2_desc; ?></p>
            </div>
            <div class="feature-card glass fade-up stagger-3">
                <i class="fas fa-headset feature-icon"></i>
                <h3><?php echo $f3_title; ?></h3>
                <p><?php echo $f3_desc; ?></p>
            </div>
        </div>
    </section>

    <section class="packages" id="packages">
        <h2 class="section-title fade-up">Package Internet</h2>
        <div class="package-grid">
            <?php foreach ($packages as $pkg): ?>
            <div class="package-card glass fade-up">
                <h3><?php echo htmlspecialchars($pkg['name']); ?></h3>
                <div class="package-price"><?php echo formatCurrency($pkg['price']); ?></div>
                <p style="color: rgba(255,255,255,0.7);"><?php echo htmlspecialchars($pkg['description'] ?? ''); ?></p>
                <br>
                <a href="?reg=open&pkg=<?php echo rawurlencode((string) $pkg['name']); ?>#register" class="btn btn-primary" onclick="try { window.__gembokOpenRegisterModalWithPackage && window.__gembokOpenRegisterModalWithPackage(<?php echo json_encode((string) $pkg['name']); ?>); return false; } catch (e) { return true; }">Select Package</a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="contact" id="contact">
        <h2 class="section-title fade-up">Contact Kami</h2>
        <div class="contact-grid">
            <div class="contact-item glass">
                <i class="fas fa-phone"></i>
                <h3>Phone</h3>
                <p><?php echo $contactPhone; ?></p>
            </div>
            <div class="contact-item glass">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p><?php echo $contactEmail; ?></p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="social-links">
            <a href="<?php echo $s_fb; ?>"><i class="fab fa-facebook"></i></a>
            <a href="<?php echo $s_ig; ?>"><i class="fab fa-instagram"></i></a>
            <a href="<?php echo $s_tw; ?>"><i class="fab fa-twitter"></i></a>
            <a href="<?php echo $s_yt; ?>"><i class="fab fa-youtube"></i></a>
        </div>
        <p><?php echo $footerAbout; ?></p>
    </footer>

    <script>
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        // Scroll animations
        function initScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -100px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
            initScrollAnimations();
        });

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }

        (function() {
            const navLinks = document.querySelector('.nav-links');
            const dropdown = document.querySelector('.dropdown');

            window.__gembokToggleNav = function() {
                if (!navLinks) return;
                navLinks.classList.toggle('open');
                if (!navLinks.classList.contains('open') && dropdown) {
                    dropdown.classList.remove('open');
                }
            };

            window.__gembokToggleLogin = function(e) {
                if (e) e.preventDefault();
                if (!dropdown) return;
                dropdown.classList.toggle('open');
                if (navLinks && !navLinks.classList.contains('open')) {
                    navLinks.classList.add('open');
                }
            };

            document.addEventListener('click', function(e) {
                const target = e.target;
                if (!target) return;
                const inNav = navLinks && navLinks.contains(target);
                const inDropdown = dropdown && dropdown.contains(target);
                const isToggle = target.closest && target.closest('.nav-toggle');
                if (!inNav && !inDropdown && !isToggle) {
                    if (navLinks) navLinks.classList.remove('open');
                    if (dropdown) dropdown.classList.remove('open');
                }
            });

            if (navLinks) {
                navLinks.addEventListener('click', function(e) {
                    const a = e.target && e.target.closest ? e.target.closest('a') : null;
                    const href = a && a.getAttribute ? a.getAttribute('href') : null;
                    if (a && href && href.startsWith('#') && href !== '#' && !a.closest('.dropdown')) {
                        navLinks.classList.remove('open');
                        if (dropdown) dropdown.classList.remove('open');
                    }
                });
            }
        })();
    </script>
</body>
</html>
