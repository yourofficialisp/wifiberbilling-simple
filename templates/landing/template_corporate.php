<?php
/**
 * Template 3: Corporate Blue Theme
 * Professional corporate design with blue color scheme
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $appName; ?> - Internet Service Provider</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0056b3">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo $appName; ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="assets/icons/icon-192x192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0056b3;
            --primary-dark: #004494;
            --secondary: #0d6efd;
            --light: #ffffff;
            --dark: #212529;
            --gray: #6c757d;
            --bg-light: #f8f9fa;
            --bg-white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; background: var(--bg-light); color: var(--dark); line-height: 1.6; }

        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 5%; background: var(--bg-white);
            position: fixed; top: 0; left: 0; width: 100%; z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo { font-size: 1.8rem; font-weight: 700; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { display: flex; gap: 25px; }
        .nav-links a { color: var(--dark); text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: 0.3s; }
        .nav-links a:hover { color: var(--primary); }

        .nav-toggle {
            display: none;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            background: var(--bg-light);
            border: 1px solid #dee2e6;
            color: var(--dark);
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
        }
        
        /* Dropdown Menu */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background: var(--bg-white);
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            top: 100%;
            right: 0;
            overflow: hidden;
            margin-top: 10px;
        }
        .dropdown-content a {
            color: var(--dark);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            transition: 0.3s;
        }
        .dropdown-content a:hover {
            background: var(--bg-light);
            color: var(--primary);
        }
        .dropdown:hover .dropdown-content { display: block; }
        
        .login-btn {
            background: var(--primary); padding: 10px 24px; border-radius: 4px;
            color: #fff !important; font-weight: 500; border: none; cursor: pointer;
            transition: 0.3s;
        }
        .login-btn:hover { background: var(--primary-dark); }

        .hero {
            min-height: 100vh; display: flex; align-items: center; justify-content: space-between;
            padding: 100px 5% 60px; background: linear-gradient(135deg, #0056b3 0%, #0d6efd 100%);
            color: var(--light);
        }

        .hero-content { max-width: 600px; }
        .hero h1 { font-size: 3rem; line-height: 1.2; margin-bottom: 20px; font-weight: 700; }
        .hero p { font-size: 1.1rem; margin-bottom: 30px; opacity: 0.9; }
        .cta-buttons { display: flex; gap: 15px; }
        .btn { padding: 12px 28px; border-radius: 4px; text-decoration: none; font-weight: 500; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--light); color: var(--primary); }
        .btn-primary:hover { background: #e9ecef; }
        .btn-outline { border: 2px solid var(--light); color: var(--light); }
        .btn-outline:hover { background: var(--light); color: var(--primary); }

        .features { padding: 80px 5%; background: var(--bg-white); }
        .section-title { font-size: 2.2rem; margin-bottom: 15px; text-align: center; color: var(--primary); }
        .section-subtitle { color: var(--gray); text-align: center; margin-bottom: 50px; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
        .feature-card {
            background: var(--bg-light); padding: 35px; border-radius: 8px;
            border-left: 4px solid var(--primary); transition: 0.3s;
        }
        .feature-card:hover { transform: translateX(10px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .feature-icon { font-size: 2.5rem; color: var(--primary); margin-bottom: 20px; }
        .feature-card h3 { font-size: 1.3rem; margin-bottom: 12px; }
        .feature-card p { color: var(--gray); }

        .packages { padding: 80px 5%; background: var(--bg-light); text-align: center; }
        .package-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 50px; }
        .package-card {
            background: var(--bg-white); padding: 40px; border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: 0.3s;
        }
        .package-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15); }
        .package-card h3 { font-size: 1.4rem; margin-bottom: 15px; color: var(--primary); }
        .package-price { font-size: 2.5rem; color: var(--dark); font-weight: 700; margin-bottom: 20px; }

        .contact { padding: 80px 5%; background: var(--primary); color: var(--light); text-align: center; }
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 50px; }
        .contact-item { background: rgba(255, 255, 255, 0.1); padding: 30px; border-radius: 8px; }
        .contact-item i { font-size: 2rem; margin-bottom: 15px; }
        .contact-item h3 { margin-bottom: 10px; }
        .contact-item p { opacity: 0.9; }

        .footer { padding: 30px 5%; background: var(--dark); color: var(--light); text-align: center; }
        .social-links { display: flex; justify-content: center; gap: 20px; margin-bottom: 20px; }
        .social-links a { color: var(--light); font-size: 1.5rem; transition: 0.3s; }
        .social-links a:hover { color: var(--primary); }

        @media (max-width: 768px) {
            .hero { flex-direction: column; text-align: center; padding: 100px 20px; }
            .hero h1 { font-size: 2rem; }
            .navbar { padding: 15px 20px; }
            .nav-toggle { display: inline-flex; }
            .nav-links {
                display: none;
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                padding: 14px 20px 18px;
                background: var(--bg-white);
                border-bottom: 1px solid #dee2e6;
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
            <h1><?php echo $heroTitle; ?></h1>
            <p><?php echo $heroDesc; ?></p>
            <div class="cta-buttons">
                <a href="#packages" class="btn btn-primary"><i class="fas fa-rocket"></i> Mulai Sekarang</a>
                <a href="#contact" class="btn btn-outline"><i class="fas fa-phone"></i> Contact Kami</a>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <h2 class="section-title">Keunggulan Kami</h2>
        <p class="section-subtitle">Mengapa memilih <?php echo $appName; ?> sebagai partner internet You</p>
        <div class="feature-grid">
            <div class="feature-card">
                <i class="fas fa-tachometer-alt feature-icon"></i>
                <h3><?php echo $f1_title; ?></h3>
                <p><?php echo $f1_desc; ?></p>
            </div>
            <div class="feature-card">
                <i class="fas fa-infinity feature-icon"></i>
                <h3><?php echo $f2_title; ?></h3>
                <p><?php echo $f2_desc; ?></p>
            </div>
            <div class="feature-card">
                <i class="fas fa-headset feature-icon"></i>
                <h3><?php echo $f3_title; ?></h3>
                <p><?php echo $f3_desc; ?></p>
            </div>
        </div>
    </section>

    <section class="packages" id="packages">
        <h2 class="section-title">Package Layanan</h2>
        <p class="section-subtitle">Select paket yang sesuai dengan kebutuhan bisnis You</p>
        <div class="package-grid">
            <?php foreach ($packages as $pkg): ?>
            <div class="package-card">
                <h3><?php echo htmlspecialchars($pkg['name']); ?></h3>
                <div class="package-price"><?php echo formatCurrency($pkg['price']); ?></div>
                <p><?php echo htmlspecialchars($pkg['description'] ?? ''); ?></p>
                <br>
                <a href="?reg=open&pkg=<?php echo rawurlencode((string) $pkg['name']); ?>#register" class="btn btn-primary" style="background: var(--primary); color: #fff;" onclick="try { window.__gembokOpenRegisterModalWithPackage && window.__gembokOpenRegisterModalWithPackage(<?php echo json_encode((string) $pkg['name']); ?>); return false; } catch (e) { return true; }">Select Package</a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="contact" id="contact">
        <h2 class="section-title">Contact Kami</h2>
        <p class="section-subtitle" style="color: rgba(255,255,255,0.8);">Tim kami siap melayani You 24/7</p>
        <div class="contact-grid">
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <h3>Phone</h3>
                <p><?php echo $contactPhone; ?></p>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p><?php echo $contactEmail; ?></p>
            </div>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Alamat</h3>
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
