<?php
/**
 * Template 5: Glassmorphism Theme
 * Modern glassmorphism design with blur effects and transparency
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $appName; ?> - Internet Service Provider</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo $appName; ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="assets/icons/icon-192x192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --secondary: #a855f7;
            --accent: #ec4899;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #94a3b8;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Outfit', sans-serif; 
            background: var(--dark);
            color: var(--light);
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Animated Background */
        .bg-animated {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            z-index: -2;
        }

        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 20s ease-in-out infinite;
            z-index: -1;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: var(--primary);
            top: -200px;
            left: -200px;
        }

        .orb-2 {
            width: 500px;
            height: 500px;
            background: var(--secondary);
            bottom: -250px;
            right: -250px;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: var(--accent);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        /* Glass Card */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
        }

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
        }

        .logo { 
            font-size: 1.5rem; 
            font-weight: 700; 
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
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
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--light);
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        
        /* Dropdown Menu */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            z-index: 1;
            border-radius: 15px;
            border: 1px solid var(--glass-border);
            top: 100%;
            right: 0;
            overflow: hidden;
            margin-top: 10px;
        }
        .dropdown-content a {
            color: var(--light);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            transition: 0.3s;
        }
        .dropdown-content a:hover {
            background: rgba(255,255,255,0.15);
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
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
        }

        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 5% 60px;
            text-align: center;
        }

        .hero-content { max-width: 900px; }
        .hero h1 { 
            font-size: 3.5rem; 
            line-height: 1.2; 
            margin-bottom: 30px;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p { font-size: 1.2rem; color: var(--gray); margin-bottom: 40px; line-height: 1.8; }
        .cta-buttons { display: flex; gap: 20px; justify-content: center; }
        .btn { 
            padding: 14px 32px; 
            border-radius: 50px; 
            text-decoration: none; 
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-primary { 
            background: var(--gradient); 
            color: #fff;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
        }
        .btn-outline { 
            border: 2px solid var(--glass-border);
            color: var(--light);
        }
        .btn-outline:hover {
            background: var(--glass-bg);
            border-color: var(--primary);
        }

        .features { padding: 100px 5%; }
        .section-title { 
            font-size: 2.5rem; 
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
            transition: 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
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
        .feature-card p { color: var(--gray); line-height: 1.6; }

        .packages { padding: 100px 5%; text-align: center; }
        .package-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 30px; 
            margin-top: 60px; 
        }
        .package-card { 
            padding: 50px; 
            transition: 0.3s;
        }
        .package-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
        }
        .package-card h3 { font-size: 1.5rem; margin-bottom: 20px; }
        .package-price { 
            font-size: 3rem; 
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .contact { padding: 100px 5%; text-align: center; }
        .contact-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 30px; 
            margin-top: 60px; 
        }
        .contact-item { padding: 30px; }
        .contact-item i { 
            font-size: 2.5rem; 
            margin-bottom: 15px;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .contact-item h3 { margin-bottom: 10px; }
        .contact-item p { color: var(--gray); }

        .footer { 
            padding: 40px 5%; 
            text-align: center;
            border-top: 1px solid var(--glass-border);
        }
        .social-links { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            margin-bottom: 20px; 
        }
        .social-links a { 
            color: var(--gray); 
            font-size: 1.5rem; 
            transition: 0.3s;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--glass-bg);
        }
        .social-links a:hover { 
            color: var(--light);
            background: var(--gradient);
            transform: translateY(-5px);
        }

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
                background: rgba(0, 0, 0, 0.35);
                border-bottom: 1px solid var(--glass-border);
                flex-direction: column;
                gap: 14px;
                align-items: stretch;
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
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
            .features, .packages, .contact { padding: 60px 20px; }
            .cta-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="bg-animated"></div>
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <div class="bg-orb orb-3"></div>

    <nav class="navbar glass">
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
            <h1><?php echo strip_tags($heroTitle); ?></h1>
            <p><?php echo $heroDesc; ?></p>
            <div class="cta-buttons">
                <a href="#packages" class="btn btn-primary">Mulai Sekarang</a>
                <a href="#contact" class="btn btn-outline">Contact Kami</a>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <h2 class="section-title">Kenapa Memilih Kami</h2>
        <div class="feature-grid">
            <div class="feature-card glass">
                <i class="fas fa-tachometer-alt feature-icon"></i>
                <h3><?php echo $f1_title; ?></h3>
                <p><?php echo $f1_desc; ?></p>
            </div>
            <div class="feature-card glass">
                <i class="fas fa-infinity feature-icon"></i>
                <h3><?php echo $f2_title; ?></h3>
                <p><?php echo $f2_desc; ?></p>
            </div>
            <div class="feature-card glass">
                <i class="fas fa-headset feature-icon"></i>
                <h3><?php echo $f3_title; ?></h3>
                <p><?php echo $f3_desc; ?></p>
            </div>
        </div>
    </section>

    <section class="packages" id="packages">
        <h2 class="section-title">Package Internet</h2>
        <div class="package-grid">
            <?php foreach ($packages as $pkg): ?>
            <div class="package-card glass">
                <h3><?php echo htmlspecialchars($pkg['name']); ?></h3>
                <div class="package-price"><?php echo formatCurrency($pkg['price']); ?></div>
                <p style="color: var(--gray);"><?php echo htmlspecialchars($pkg['description'] ?? ''); ?></p>
                <br>
                <a href="?reg=open&pkg=<?php echo rawurlencode((string) $pkg['name']); ?>#register" class="btn btn-primary" style="width: 100%;" onclick="try { window.__gembokOpenRegisterModalWithPackage && window.__gembokOpenRegisterModalWithPackage(<?php echo json_encode((string) $pkg['name']); ?>); return false; } catch (e) { return true; }">Select Package</a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="contact" id="contact">
        <h2 class="section-title">Contact Kami</h2>
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
            <div class="contact-item glass">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Alamat</h3>
                <p><?php echo $contactAddress; ?></p>
            </div>
        </div>
    </section>

    <footer class="footer glass">
        <div class="social-links">
            <a href="<?php echo $s_fb; ?>"><i class="fab fa-facebook"></i></a>
            <a href="<?php echo $s_ig; ?>"><i class="fab fa-instagram"></i></a>
            <a href="<?php echo $s_tw; ?>"><i class="fab fa-twitter"></i></a>
            <a href="<?php echo $s_yt; ?>"><i class="fab fa-youtube"></i></a>
        </div>
        <p><?php echo $footerAbout; ?></p>
    </footer>

    <script>
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
