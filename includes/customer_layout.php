<?php
/**
 * Customer Portal Layout Template
 * Base layout for customer portal pages
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get page title
$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? '';

// Check if customer is logged in
if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$customer = getCurrentCustomer();

// Capture the content from the including file
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo APP_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="theme-color" content="#7c3aed">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../assets/icons/icon-192x192.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* Dark Neon Theme */
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: rgba(20, 20, 35, 0.8);
            --bg-sidebar: #0d0d15;
            
            /* Neon Colors */
            --neon-cyan: #00f5ff;
            --neon-purple: #bf00ff;
            --neon-pink: #ff00aa;
            --neon-green: #00ff88;
            --neon-orange: #ff6b35;
            --neon-red: #ff4757;
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
            --gradient-success: linear-gradient(135deg, #00ff88 0%, #00d4aa 100%);
            --gradient-warning: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            
            /* Text */
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.6);
            --text-muted: rgba(255, 255, 255, 0.4);
            
            /* Border */
            --border-color: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(0, 245, 255, 0.3);
            
            /* Shadows */
            --shadow-neon: 0 0 20px rgba(0, 245, 255, 0.3);
            --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.4);
            
            /* Sidebar */
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
        }

        body.light-theme {
            --bg-primary: #f6f7fb;
            --bg-secondary: #ffffff;
            --bg-card: rgba(255, 255, 255, 0.9);
            --bg-sidebar: #ffffff;
            --neon-cyan: #0b5fff;
            --neon-purple: #6d28d9;
            --neon-pink: #db2777;
            --neon-green: #16a34a;
            --neon-orange: #ea580c;
            --neon-red: #dc2626;
            --gradient-primary: linear-gradient(135deg, #0b5fff 0%, #6d28d9 100%);
            --gradient-success: linear-gradient(135deg, #16a34a 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
            --text-primary: #0b1220;
            --text-secondary: rgba(11, 18, 32, 0.7);
            --text-muted: rgba(11, 18, 32, 0.5);
            --border-color: rgba(11, 18, 32, 0.12);
            --border-glow: rgba(11, 95, 255, 0.22);
            --shadow-neon: 0 0 18px rgba(11, 95, 255, 0.18);
            --shadow-card: 0 8px 28px rgba(17, 24, 39, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            color: var(--neon-cyan);
            text-decoration: none;
            transition: all 0.3s;
        }

        a:hover {
            color: var(--neon-purple);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-nav {
            padding: 20px 0;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text-secondary);
            transition: all 0.3s;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(0, 245, 255, 0.1);
            color: var(--neon-cyan);
            border-left-color: var(--neon-cyan);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .theme-toggle-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1rem;
            padding: 8px 10px;
            border-radius: 10px;
            transition: all 0.2s;
        }

        body.light-theme .theme-toggle-btn {
            background: rgba(11, 18, 32, 0.04);
        }

        .theme-toggle-btn:hover {
            color: var(--text-primary);
            border-color: var(--border-glow);
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.5rem;
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-card);
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--neon-cyan);
        }
        
        @media (max-width: 480px) {
            .card-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
            }
            
            .card-header input[type="text"] {
                width: 100% !important;
            }
        }
        
        /* Responsive grids */
        [style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
        }
        
        @media (max-width: 480px) {
            [style*="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr))"] {
                grid-template-columns: 1fr !important;
            }
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-neon);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.cyan { background: rgba(0, 245, 255, 0.2); color: var(--neon-cyan); }
        .stat-icon.purple { background: rgba(191, 0, 255, 0.2); color: var(--neon-purple); }
        .stat-icon.green { background: rgba(0, 255, 136, 0.2); color: var(--neon-green); }
        .stat-icon.orange { background: rgba(255, 107, 53, 0.2); color: var(--neon-orange); }
        .stat-icon.red { background: rgba(255, 71, 87, 0.2); color: var(--neon-red); }
        .stat-icon.yellow { background: rgba(255, 235, 59, 0.2); color: #ffeb3b; }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: #fff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-neon);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-success {
            background: var(--gradient-success);
            color: #fff;
        }

        .btn-danger {
            background: var(--gradient-warning);
            color: #fff;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--neon-cyan);
            background: rgba(255, 255, 255, 0.08);
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: var(--bg-secondary);
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        /* Responsive table for mobile */
        @media (max-width: 768px) {
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .data-table, 
            .data-table thead, 
            .data-table tbody, 
            .data-table th, 
            .data-table td, 
            .data-table tr {
                display: block !important;
            }
            
            .data-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .data-table tr {
                border: 1px solid var(--border-color);
                margin-bottom: 10px;
                padding: 10px;
                border-radius: 8px;
                position: relative;
            }
            
            .data-table td {
                border: none;
                position: relative;
                padding-left: calc(50% + 10px) !important;
                text-align: right;
                min-height: 40px;
            }
            
            .data-table td:before {
                content: attr(data-label) ": ";
                position: absolute;
                left: 10px;
                width: calc(50% - 10px);
                text-align: left;
                font-weight: 600;
                color: var(--text-secondary);
                top: 12px;
            }
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(0, 255, 136, 0.2);
            color: var(--neon-green);
            border: 1px solid var(--neon-green);
        }

        .badge-warning {
            background: rgba(255, 107, 53, 0.2);
            color: var(--neon-orange);
            border: 1px solid var(--neon-orange);
        }

        .badge-danger {
            background: rgba(255, 71, 87, 0.2);
            color: var(--neon-red);
            border: 1px solid var(--neon-red);
        }

        .badge-info {
            background: rgba(0, 245, 255, 0.2);
            color: var(--neon-cyan);
            border: 1px solid var(--neon-cyan);
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid var(--neon-red);
            color: var(--neon-red);
        }

        .alert-info {
            background: rgba(0, 245, 255, 0.1);
            border: 1px solid var(--neon-cyan);
            color: var(--neon-cyan);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 1000;
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .header {
                padding: 15px;
            }

            .header-title h1 {
                font-size: 1.3rem;
            }

            .header-actions {
                gap: 10px;
            }

            .card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .data-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            .btn-sm {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .header-title h1 {
                font-size: 1.1rem;
            }

            .form-control {
                padding: 10px;
            }

            .card-header {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(18, 18, 26, 0.85); /* background-secondary with transparency */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-top: 1px solid var(--border-color);
            z-index: 2000;
            padding-bottom: env(safe-area-inset-bottom, 0); /* Support for iPhone notch safe area */
        }

        .mobile-nav-inner {
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 65px;
        }

        .mobile-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            gap: 4px;
            height: 100%;
            transition: all 0.3s;
        }

        .mobile-nav-item i {
            font-size: 1.25rem;
            transition: transform 0.3s;
        }

        .mobile-nav-item span {
            font-size: 0.7rem;
            font-weight: 600;
        }

        .mobile-nav-item.active {
            color: var(--neon-cyan);
        }

        .mobile-nav-item.active i {
            transform: translateY(-2px);
            text-shadow: var(--shadow-neon);
        }

        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: block;
            }

            /* Hide the top hamburger menu since we use bottom nav now */
            .header-actions .menu-toggle {
                display: none !important;
            }

            /* Prevent content from hiding behind the bottom navbar */
            body {
                padding-bottom: 80px; 
            }
        }
    </style>
</head>
<body>
    <!-- Customer Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-user-circle" style="font-size: 1.5rem; color: var(--neon-cyan);"></i>
            <span class="sidebar-logo">PORTAL</span>
        </div>
        
        <div class="sidebar-nav">
            <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="invoices.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'invoices.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Bills</span>
            </a>
            
            <div style="margin-top: 20px; border-top: 1px solid var(--border-color);"></div>
            
            <a href="logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav">
        <div class="mobile-nav-inner">
            <a href="dashboard.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Home</span>
            </a>
            <a href="dashboard.php#wifi-settings" class="mobile-nav-item">
                <i class="fas fa-wifi"></i>
                <span>WiFi</span>
            </a>
            <a href="dashboard.php#lapor-gangguan" class="mobile-nav-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Report</span>
            </a>
            <a href="invoices.php" class="mobile-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'invoices.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>Bills</span>
            </a>
            <a href="logout.php" class="mobile-nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Exit</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>
            <div class="header-actions">
                <button class="menu-toggle" onclick="toggleSidebar()" style="display: none; background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem;">
                    <i class="fas fa-bars"></i>
                </button>
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Day/Night Mode">
                    <i class="fas fa-moon" id="themeToggleIcon"></i>
                </button>
                <span style="color: var(--text-secondary);">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($customer['name']); ?>
                </span>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <?php if (hasFlash('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars(getFlash('success')); ?>
            </div>
        <?php endif; ?>
        
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars(getFlash('error')); ?>
            </div>
        <?php endif; ?>
        
        <?php if (hasFlash('info')): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?php echo htmlspecialchars(getFlash('info')); ?>
            </div>
        <?php endif; ?>
        
        <!-- Page Content will be inserted here by the including file -->
        <?php echo $content; ?>
    </div>
    
    <!-- Scripts -->
    <script>
        function applyTheme(theme) {
            const body = document.body;
            const icon = document.getElementById('themeToggleIcon');
            if (theme === 'light') {
                body.classList.add('light-theme');
                if (icon) icon.className = 'fas fa-sun';
            } else {
                body.classList.remove('light-theme');
                if (icon) icon.className = 'fas fa-moon';
            }
        }

        function toggleTheme() {
            const isLight = document.body.classList.contains('light-theme');
            const next = isLight ? 'dark' : 'light';
            localStorage.setItem('theme', next);
            applyTheme(next);
        }

        (function initTheme() {
            const saved = localStorage.getItem('theme');
            const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
            const theme = saved ? saved : (prefersLight ? 'light' : 'dark');
            applyTheme(theme);
        })();

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // Show menu toggle button on mobile
        window.addEventListener('load', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            if (window.innerWidth <= 768) {
                menuToggle.style.display = 'block';
            }

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('../sw.js');
            }
            
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    menuToggle.style.display = 'block';
                } else {
                    menuToggle.style.display = 'none';
                    document.querySelector('.sidebar').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
