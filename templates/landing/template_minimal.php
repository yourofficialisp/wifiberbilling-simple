<?php
/**
 * Template 4: Minimum Dark Theme
 * Minimalist dark design with clean typography
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $appName; ?> - Internet Service Provider</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo $appName; ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="assets/icons/icon-192x192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ffffff;
            --secondary: #888888;
            --dark: #000000;
            --light: #ffffff;
            --gray: #888888;
            --bg-dark: #0a0a0a;
            --bg-card: #111111;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Space Grotesk', sans-serif; background: var(--bg-dark); color: var(--light); line-height: 1.6; }

        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 30px 5%; background: var(--bg-dark);
            position: fixed; top: 0; left: 0; width: 100%; z-index: 1000;
        }

        .logo { font-size: 1.5rem; font-weight: 700; color: var(--light); text-decoration: none; letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 40px; }
        .nav-links a { color: var(--gray); text-decoration: none; font-size: 0.9rem; font-weight: 400; transition: 0.3s; }
        .nav-links a:hover { color: var(--light); }

        .nav-toggle {
            display: none;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--light);
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
        }
        
        /* Dropdown Menu */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background: var(--bg-card);
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.5);
            z-index: 1;
            border-radius: 2px;
            border: 1px solid #1a1a1a;
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
            font-size: 0.9rem;
        }
        .dropdown-content a:hover {
            background: #1a1a1a;
            color: var(--light);
        }
        .dropdown:hover .dropdown-content { display: block; }
        
        .login-btn {
            background: var(--light); padding: 10px 24px; border-radius: 2px;
            color: var(--dark) !important; font-weight: 500; border: none; cursor: pointer;
            transition: 0.3s; letter-spacing: 0.5px;
        }
        .login-btn:hover { background: #e0e0e0; }

        .hero {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 120px 5% 60px; background: var(--bg-dark);
            text-align: center;
        }

        .hero-content { max-width: 900px; }
        .hero h1 { font-size: 4rem; line-height: 1.1; margin-bottom: 30px; font-weight: 700; letter-spacing: -2px; }
        .hero p { font-size: 1.1rem; color: var(--gray); margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto; }
        .cta-buttons { display: flex; gap: 20px; justify-content: center; }
        .btn { padding: 14px 32px; border-radius: 2px; text-decoration: none; font-weight: 500; transition: 0.3s; letter-spacing: 0.5px; }
        .btn-primary { background: var(--light); color: var(--dark); }
        .btn-primary:hover { background: #e0e0e0; }
        .btn-outline { border: 1px solid var(--light); color: var(--light); }
        .btn-outline:hover { background: var(--light); color: var(--dark); }

        .features { padding: 100px 5%; background: var(--bg-dark); border-top: 1px solid #1a1a1a; }
        .section-title { font-size: 2rem; margin-bottom: 60px; text-align: center; font-weight: 300; letter-spacing: -1px; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 60px; }
        .feature-card { text-align: left; }
        .feature-number { font-size: 4rem; color: #1a1a1a; font-weight: 700; margin-bottom: -20px; }
        .feature-icon { font-size: 1.5rem; color: var(--light); margin-bottom: 20px; }
        .feature-card h3 { font-size: 1.2rem; margin-bottom: 15px; font-weight: 500; }
        .feature-card p { color: var(--gray); font-size: 0.95rem; }

        .packages { padding: 100px 5%; background: var(--bg-dark); border-top: 1px solid #1a1a1a; text-align: center; }
        .package-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 60px; }
        .package-card {
            background: var(--bg-card); padding: 50px; text-align: left;
            border: 1px solid #1a1a1a; transition: 0.3s;
        }
        .package-card:hover { border-color: var(--light); }
        .package-card h3 { font-size: 1.1rem; margin-bottom: 20px; font-weight: 500; }
        .package-price { font-size: 2rem; color: var(--light); font-weight: 700; margin-bottom: 20px; }

        .contact { padding: 100px 5%; background: var(--bg-dark); border-top: 1px solid #1a1a1a; text-align: center; }
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 60px; margin-top: 60px; }
        .contact-item { text-align: left; }
        .contact-item i { font-size: 1.5rem; color: var(--light); margin-bottom: 15px; }
        .contact-item h3 { margin-bottom: 10px; font-size: 0.9rem; font-weight: 500; }
        .contact-item p { color: var(--gray); font-size: 0.9rem; }

        .footer { padding: 40px 5%; background: var(--bg-dark); border-top: 1px solid #1a1a1a; text-align: center; }
        .social-links { display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; }
        .social-links a { color: var(--gray); font-size: 1.2rem; transition: 0.3s; }
        .social-links a:hover { color: var(--light); }
        .footer p { color: var(--gray); font-size: 0.85rem; }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .navbar { padding: 20px; }
            .nav-toggle { display: inline-flex; }
            .nav-links {
                display: none;
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                padding: 14px 20px 18px;
                background: rgba(10, 10, 10, 0.98);
                border-bottom: 1px solid #1a1a1a;
                flex-direction: column;
                gap: 14px;
                align-items: stretch;
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
            .login-btn { width: 100%; text-align: center; }
            .features, .packages, .contact { padding: 60px 20px; }
            .cta-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="#" class="logo"><?php echo $appName; ?></a>
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
        <h2 class="section-title">Fitur Unggulan</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-number">01</div>
                <i class="fas fa-tachometer-alt feature-icon"></i>
                <h3><?php echo $f1_title; ?></h3>
                <p><?php echo $f1_desc; ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-number">02</div>
                <i class="fas fa-infinity feature-icon"></i>
                <h3><?php echo $f2_title; ?></h3>
                <p><?php echo $f2_desc; ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-number">03</div>
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
            <div class="package-card">
                <h3><?php echo htmlspecialchars($pkg['name']); ?></h3>
                <div class="package-price"><?php echo formatCurrency($pkg['price']); ?></div>
                <p style="color: var(--gray); font-size: 0.9rem;"><?php echo htmlspecialchars($pkg['description'] ?? ''); ?></p>
                <br>
                <a href="?reg=open&pkg=<?php echo rawurlencode((string) $pkg['name']); ?>#register" class="btn btn-primary" style="width: 100%;" onclick="try { window.__gembokOpenRegisterModalWithPackage && window.__gembokOpenRegisterModalWithPackage(<?php echo json_encode((string) $pkg['name']); ?>); return false; } catch (e) { return true; }">Select Package</a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="contact" id="contact">
        <h2 class="section-title">Kontak</h2>
        <div class="contact-grid">
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <h3>TELEPON</h3>
                <p><?php echo $contactPhone; ?></p>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <h3>EMAIL</h3>
                <p><?php echo $contactEmail; ?></p>
            </div>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <h3>ADDRESS</h3>
                <p><?php echo $contactAddress; ?></p>
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
