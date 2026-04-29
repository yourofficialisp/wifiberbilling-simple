<?php
/**
 * Template 6: Neumorphism Theme
 * Soft UI design with subtle shadows and depth
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $appName; ?> - Internet Service Provider</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#e0e5ec">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo $appName; ?>">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="assets/icons/icon-192x192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #e0e5ec;
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --dark: #1e293b;
            --light: #ffffff;
            --gray: #64748b;
            --shadow-light: #ffffff;
            --shadow-dark: #a3b1c6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg);
            color: var(--dark);
            line-height: 1.6;
        }

        .neumorphic {
            background: var(--bg);
            box-shadow: 8px 8px 16px var(--shadow-dark),
                       -8px -8px 16px var(--shadow-light);
            border-radius: 20px;
            border: none;
        }

        .neumorphic-inset {
            background: var(--bg);
            box-shadow: inset 8px 8px 16px var(--shadow-dark),
                       inset -8px -8px 16px var(--shadow-light);
            border-radius: 20px;
            border: none;
        }

        .neumorphic-btn {
            background: var(--bg);
            box-shadow: 6px 6px 12px var(--shadow-dark),
                       -6px -6px 12px var(--shadow-light);
            border-radius: 50px;
            border: none;
            padding: 14px 32px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            color: var(--primary);
        }

        .neumorphic-btn:hover {
            box-shadow: 4px 4px 8px var(--shadow-dark),
                       -4px -4px 8px var(--shadow-light);
        }

        .neumorphic-btn:active {
            box-shadow: inset 4px 4px 8px var(--shadow-dark),
                       inset -4px -4px 8px var(--shadow-light);
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
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links { display: flex; gap: 30px; }
        .nav-links a { 
            color: var(--dark); 
            text-decoration: none; 
            font-weight: 500;
            transition: 0.3s;
        }
        .nav-links a:hover { color: var(--primary); }

        .nav-toggle {
            display: none;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            box-shadow: 6px 6px 12px var(--shadow-dark),
                       -6px -6px 12px var(--shadow-light);
            color: var(--dark);
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            border: none;
        }

        /* Dropdown Menu */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background: var(--bg);
            min-width: 200px;
            box-shadow: 8px 8px 16px var(--shadow-dark),
                       -8px -8px 16px var(--shadow-light);
            z-index: 1;
            border-radius: 15px;
            border: none;
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
            color: var(--primary);
        }
        .dropdown:hover .dropdown-content { display: block; }

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
            color: var(--dark);
        }
        .hero p { 
            font-size: 1.2rem; 
            color: var(--gray); 
            margin-bottom: 40px; 
        }
        .cta-buttons { display: flex; gap: 20px; justify-content: center; }

        .features { padding: 100px 5%; }
        .section-title { 
            font-size: 2.5rem; 
            margin-bottom: 60px; 
            text-align: center;
            color: var(--primary);
        }
        .feature-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 40px; 
        }
        .feature-card {
            padding: 50px;
            transition: 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon { 
            font-size: 3rem; 
            margin-bottom: 20px;
            color: var(--primary);
        }
        .feature-card h3 { font-size: 1.4rem; margin-bottom: 15px; color: var(--dark); }
        .feature-card p { color: var(--gray); }

        .packages { padding: 100px 5%; text-align: center; }
        .package-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 40px; 
            margin-top: 60px; 
        }
        .package-card { 
            padding: 50px;
            transition: 0.3s;
        }
        .package-card:hover {
            transform: translateY(-5px);
        }
        .package-card h3 { font-size: 1.5rem; margin-bottom: 20px; color: var(--dark); }
        .package-price { 
            font-size: 3rem; 
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .contact { padding: 100px 5%; text-align: center; }
        .contact-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 40px; 
            margin-top: 60px; 
        }
        .contact-item { padding: 40px; }
        .contact-item i { 
            font-size: 2.5rem; 
            margin-bottom: 15px;
            color: var(--primary);
        }
        .contact-item h3 { margin-bottom: 10px; color: var(--dark); }
        .contact-item p { color: var(--gray); }

        .footer { 
            padding: 40px 5%; 
            text-align: center;
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
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: 0.3s;
        }
        .social-links a:hover { 
            color: var(--primary);
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
                background: var(--bg);
                box-shadow: 6px 6px 12px var(--shadow-dark),
                           -6px -6px 12px var(--shadow-light);
                flex-direction: column;
                gap: 14px;
                border-radius: 0 0 18px 18px;
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
            .neumorphic-btn { width: 100%; text-align: center; display: inline-flex; justify-content: center; }
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
            <a href="#features">Features</a>
            <a href="#packages">Packages</a>
            <a href="voucher-order.php">Voucher</a>
            <a href="#" onclick="window.__gembokOpenRegisterModal && window.__gembokOpenRegisterModal(); return false;">List</a>
            <a href="#contact">Contact</a>
            <div class="dropdown">
                <a href="#" class="neumorphic-btn" onclick="window.__gembokToggleLogin && window.__gembokToggleLogin(event)">Login <i class="fas fa-chevron-down"></i></a>
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
                <a href="#packages" class="neumorphic-btn">Start Now</a>
                <a href="#contact" class="neumorphic-btn">Contact Us</a>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <h2 class="section-title">Why Choose Us</h2>
        <div class="feature-grid">
            <div class="feature-card neumorphic">
                <i class="fas fa-tachometer-alt feature-icon"></i>
                <h3><?php echo $f1_title; ?></h3>
                <p><?php echo $f1_desc; ?></p>
            </div>
            <div class="feature-card neumorphic">
                <i class="fas fa-infinity feature-icon"></i>
                <h3><?php echo $f2_title; ?></h3>
                <p><?php echo $f2_desc; ?></p>
            </div>
            <div class="feature-card neumorphic">
                <i class="fas fa-headset feature-icon"></i>
                <h3><?php echo $f3_title; ?></h3>
                <p><?php echo $f3_desc; ?></p>
            </div>
        </div>
    </section>

    <section class="packages" id="packages">
        <h2 class="section-title">Internet Package</h2>
        <div class="package-grid">
            <?php foreach ($packages as $pkg): ?>
            <div class="package-card neumorphic">
                <h3><?php echo htmlspecialchars($pkg['name']); ?></h3>
                <div class="package-price"><?php echo formatCurrency($pkg['price']); ?></div>
                <p style="color: var(--gray);"><?php echo htmlspecialchars($pkg['description'] ?? ''); ?></p>
                <br>
                <a href="?reg=open&pkg=<?php echo rawurlencode((string) $pkg['name']); ?>#register" class="neumorphic-btn" style="width: 100%;" onclick="try { window.__gembokOpenRegisterModalWithPackage && window.__gembokOpenRegisterModalWithPackage(<?php echo json_encode((string) $pkg['name']); ?>); return false; } catch (e) { return true; }">Select Package</a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="contact" id="contact">
        <h2 class="section-title">Contact Us</h2>
        <div class="contact-grid">
            <div class="contact-item neumorphic">
                <i class="fas fa-phone"></i>
                <h3>Phone</h3>
                <p><?php echo $contactPhone; ?></p>
            </div>
            <div class="contact-item neumorphic">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p><?php echo $contactEmail; ?></p>
            </div>
            <div class="contact-item neumorphic">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Address</h3>
                <p><?php echo $contactAddress; ?></p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="social-links">
            <a href="<?php echo $s_fb; ?>" class="neumorphic"><i class="fab fa-facebook"></i></a>
            <a href="<?php echo $s_ig; ?>" class="neumorphic"><i class="fab fa-instagram"></i></a>
            <a href="<?php echo $s_tw; ?>" class="neumorphic"><i class="fab fa-twitter"></i></a>
            <a href="<?php echo $s_yt; ?>" class="neumorphic"><i class="fab fa-youtube"></i></a>
        </div>
        <p style="color: var(--gray);"><?php echo $footerAbout; ?></p>
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
