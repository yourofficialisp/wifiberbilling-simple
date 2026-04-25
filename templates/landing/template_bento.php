<?php
/**
 * Template 7: Bento Grid Theme
 * Modern bento grid layout with smooth animations and micro-interactions
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7c3aed;
            --primary-light: #8b5cf6;
            --accent: #f97316;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;
            --card-bg: rgba(255, 255, 255, 0.05);
            --card-hover: rgba(255, 255, 255, 0.1);
            --gradient: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 50%, #f97316 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--dark);
            color: var(--light);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated Background */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            z-index: -2;
        }

        .bg-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(124, 58, 237, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(124, 58, 237, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
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
            box-shadow:0 8px 32px rgba(0,0,0,0.3);
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
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
        }

        /* Bento Grid Layout */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
            padding: 100px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .bento-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .bento-card:hover {
            background: var(--card-hover);
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .card-hero {
            grid-column: span 12;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            background: var(--gradient);
            position: relative;
            overflow: hidden;
        }

        .card-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWRpZD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAxMDAgMTAwIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2VuT2RkIj48L3N2ZnPg==');
            background-size: cover;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .card-features {
            grid-column: span 8;
        }

        .card-cta {
            grid-column: span 4;
        }

        .card-packages {
            grid-column: span 12;
        }

        .card-contact {
            grid-column: span 6;
        }

        .card-testimonial {
            grid-column: span 6;
        }

        /* Hero Section */
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.1;
        }

        .hero-desc {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            max-width: 600px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--light);
            color: var(--primary);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
        }

        .btn-outline {
            border: 2px solid var(--gradient);
            color: var(--light);
        }

        .btn-outline:hover {
            background: var(--gradient);
            color: #fff;
        }

        /* Feature Cards */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .feature-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 15px;
        }

        .feature-icon {
            font-size: 2.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        /* Package Cards */
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .package-card {
            text-align: center;
        }

        .package-price {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 20px 0;
        }

        /* Contact Section */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .contact-item {
            text-align: center;
        }

        .contact-icon {
            font-size: 2rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        /* Footer */
        .footer {
            padding: 40px 5%;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .social-links a {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--light);
            font-size: 1.2rem;
            transition: 0.3s;
        }

        .social-links a:hover {
            background: var(--gradient);
            transform: translateY(-3px);
        }

        /* Scroll Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: 0.6s ease-out;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }
            .card-hero,
            .card-features,
            .card-cta,
            .card-packages,
            .card-contact,
            .card-testimonial {
                grid-column: span 12;
            }
            .hero-title {
                font-size: 2.5rem;
            }
            .navbar {
                padding: 15px 20px;
            }
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
            .nav-toggle { display: inline-flex; }
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
    <div class="bg-grid"></div>

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

    <div class="bento-grid">
        <!-- Hero Card -->
        <div class="bento-card card-hero fade-in">
            <h1 class="hero-title"><?php echo strip_tags($heroTitle); ?></h1>
            <p class="hero-desc"><?php echo $heroDesc; ?></p>
            <div class="hero-buttons">
                <a href="#packages" class="btn btn-primary">Mulai Sekarang</a>
                <a href="#contact" class="btn btn-outline">Contact Kami</a>
            </div>
        </div>

        <!-- Features Card -->
        <div class="bento-card card-features fade-in">
            <h2 style="font-size: 1.8rem; margin-bottom: 30px; background: var(--gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">Kenapa Memilih Kami</h2>
            <div class="feature-grid">
                <div class="feature-item">
                    <i class="fas fa-tachometer-alt feature-icon"></i>
                    <h3><?php echo $f1_title; ?></h3>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><?php echo $f1_desc; ?></p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-infinity feature-icon"></i>
                    <h3><?php echo $f2_title; ?></h3>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><?php echo $f2_desc; ?></p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-headset feature-icon"></i>
                    <h3><?php echo $f3_title; ?></h3>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><?php echo $f3_desc; ?></p>
                </div>
            </div>
        </div>

        <!-- CTA Card -->
        <div class="bento-card card-cta fade-in">
            <div style="text-align: center;">
                <i class="fas fa-rocket" style="font-size: 3rem; background: var(--gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 20px;"></i>
                <h3 style="font-size: 1.3rem; margin-bottom: 10px;">Siap Memulai?</h3>
                <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px;">List sekarang dan nikmati internet super cepat!</p>
                <a href="#packages" class="btn btn-primary" style="width: 100%;">View Paket</a>
            </div>
        </div>

        <!-- Packages Card -->
        <div class="bento-card card-packages fade-in">
            <h2 style="font-size: 1.8rem; margin-bottom: 30px; background: var(--gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">Package Internet</h2>
            <div class="package-grid">
                <?php foreach ($packages as $pkg): ?>
                <div class="package-card">
                    <h3><?php echo htmlspecialchars($pkg['name']); ?></h3>
                    <div class="package-price"><?php echo formatCurrency($pkg['price']); ?></div>
                    <p style="color: rgba(255,255,255,0.7);"><?php echo htmlspecialchars($pkg['description'] ?? ''); ?></p>
                    <br>
                    <a href="?reg=open&pkg=<?php echo rawurlencode((string) $pkg['name']); ?>#register" class="btn btn-primary" onclick="try { window.__gembokOpenRegisterModalWithPackage && window.__gembokOpenRegisterModalWithPackage(<?php echo json_encode((string) $pkg['name']); ?>); return false; } catch (e) { return true; }">Select Package</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contact Card -->
        <div class="bento-card card-contact fade-in">
            <h2 style="font-size: 1.8rem; margin-bottom: 30px; background: var(--gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">Contact Kami</h2>
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fas fa-phone contact-icon"></i>
                    <h3>Phone</h3>
                    <p style="color: rgba(255,255,255,0.7);"><?php echo $contactPhone; ?></p>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope contact-icon"></i>
                    <h3>Email</h3>
                    <p style="color: rgba(255,255,255,0.7);"><?php echo $contactEmail; ?></p>
                </div>
            </div>
        </div>

        <!-- Testimonial Card -->
        <div class="bento-card card-testimonial fade-in">
            <div style="text-align: center;">
                <i class="fas fa-quote-left" style="font-size: 2rem; background: var(--gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 20px;"></i>
                <h3 style="font-size: 1.3rem; margin-bottom: 10px;">Kata Mereka</h3>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; line-height: 1.6;">"Internet super cepat dan stabil, support 24/7 yang sangat membantu!"</p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="social-links">
            <a href="<?php echo $s_fb; ?>"><i class="fab fa-facebook"></i></a>
            <a href="<?php echo $s_ig; ?>"><i class="fab fa-instagram"></i></a>
            <a href="<?php echo $s_tw; ?>"><i class="fab fa-twitter"></i></a>
            <a href="<?php echo $s_yt; ?>"><i class="fab fa-youtube"></i></a>
        </div>
        <p style="color: rgba(255,255,255,0.5); font-size: 0.85rem;"><?php echo $footerAbout; ?></p>
    </footer>

    <script>
        // Scroll Animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

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
