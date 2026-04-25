<?php
/**
 * Layout Template
 * Base layout for all pages
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
$appName = getSetting('app_name', 'WiFiber Billing');
$pageTitle = $pageTitle ?? $appName;
$pageDescription = $pageDescription ?? '';

// Phase 3: Multi-router support
$currentRouter = getMikrotikSettings();
$allRouters = getAllRouters();

// Handle global router switching via GET (optional but convenient)
if (isset($_GET['switch_router'])) {
    $swId = (int)$_GET['switch_router'];
    $_SESSION['active_router_id'] = $swId;
    $currentUrl = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $currentUrl);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars($appName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="theme-color" content="#7c3aed">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../assets/icons/icon-192x192.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">

    <style>
        :root {
            /* Dark Neon Theme (Premium) */
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: rgba(20, 20, 35, 0.6);
            --bg-sidebar: rgba(13, 13, 21, 0.85);
            --neon-cyan: #00f5ff;
            --neon-purple: #bf00ff;
            --neon-pink: #ff00aa;
            --neon-green: #00ff88;
            --neon-orange: #ff6b35;
            --neon-red: #ff4757;
            --gradient-primary: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
            --gradient-success: linear-gradient(135deg, #00ff88 0%, #00d4aa 100%);
            --gradient-warning: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.75);
            --text-muted: rgba(255, 255, 255, 0.4);
            --border-color: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(0, 245, 255, 0.4);
            --shadow-neon: 0 0 15px rgba(0, 245, 255, 0.2);
            --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.2);
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
            --bg-input: rgba(255, 255, 255, 0.05);
            --bg-submenu: rgba(0, 0, 0, 0.3);
            --backdrop-blur: blur(12px);
            --border-radius-md: 14px;
            --border-radius-lg: 18px;
        }

        body.light-theme {
            --bg-primary: #f8f9fa;
            --bg-secondary: #ffffff;
            --bg-card: rgba(255, 255, 255, 0.85);
            --bg-sidebar: rgba(255, 255, 255, 0.95);
            --neon-cyan: #0066ff;
            --neon-purple: #7b2cbf;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-muted: #9ca3af;
            --border-color: rgba(0, 0, 0, 0.08);
            --border-glow: rgba(0, 102, 255, 0.3);
            --shadow-neon: 0 4px 15px rgba(0, 102, 255, 0.15);
            --shadow-card: 0 4px 20px rgba(0, 0, 0, 0.05);
            --bg-input: #ffffff;
            --bg-submenu: rgba(243, 244, 246, 0.8);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.3);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.5);
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
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
            margin: 4px 12px;
            border-radius: 10px;
            color: var(--text-secondary);
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 3px solid transparent;
            font-weight: 500;
        }

        .menu-item:hover,
        .menu-item.active {
            background: rgba(0, 245, 255, 0.1);
            color: var(--neon-cyan);
            border-left-color: var(--neon-cyan);
            transform: translateX(4px);
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
            padding: 18px 24px;
            background: var(--bg-card);
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header-title h1 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-md);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-card);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05); /* Softer border */
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
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 15px;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                align-items: center;
            }

            .stat-info h3 {
                font-size: 1.5rem;
            }

            /* Chart Containers */
            #charts-container {
                display: grid !important;
                grid-template-columns: 1fr !important;
            }

            @media (min-width: 769px) {
                #charts-container {
                    grid-template-columns: 1fr 1fr !important;
                }
            }

            /* Quick Actions Grid */
            [style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
                gap: 15px !important;
            }

            @media (max-width: 480px) {
                [style*="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr))"] {
                    grid-template-columns: 1fr !important;
                }
            }
        }

        .stat-card {
            background: var(--bg-card);
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-md);
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-neon);
            border-color: rgba(0, 245, 255, 0.3);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        .stat-icon.cyan {
            background: rgba(0, 245, 255, 0.2);
            color: var(--neon-cyan);
        }

        .stat-icon.purple {
            background: rgba(191, 0, 255, 0.2);
            color: var(--neon-purple);
        }

        .stat-icon.green {
            background: rgba(0, 255, 136, 0.2);
            color: var(--neon-green);
        }

        .stat-icon.orange {
            background: rgba(255, 107, 53, 0.2);
            color: var(--neon-orange);
        }

        .stat-icon.red {
            background: rgba(255, 71, 87, 0.2);
            color: var(--neon-red);
        }

        .stat-icon.yellow {
            background: rgba(255, 235, 59, 0.2);
            color: #ffeb3b;
        }

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
            padding: 14px 16px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--neon-cyan);
            box-shadow: 0 0 0 4px rgba(0, 245, 255, 0.15);
            background: rgba(255, 255, 255, 0.08); /* slightly lighter on focus */
        }

        select option {
            background: var(--bg-secondary);
            color: var(--text-primary);
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
        /* Responsive table for mobile */
        @media (max-width: 768px) {
            .table-responsive {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .data-table {
                min-width: 600px;
            }
        }

        @media (max-width: 580px) {
            .data-table,
            .data-table thead,
            .data-table tbody,
            .data-table th,
            .data-table td,
            .data-table tr {
                display: block !important;
                min-width: auto !important;
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
                background: var(--bg-card);
            }

            .data-table td {
                border: none;
                position: relative;
                padding-left: 45% !important;
                text-align: right;
                min-height: 40px;
                border-bottom: 1px solid var(--border-color);
            }

            .data-table td:last-child {
                border-bottom: none;
            }

            .data-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 40%;
                text-align: left;
                font-weight: 600;
                color: var(--text-secondary);
                top: 12px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
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
            body {
                padding-bottom: 70px; /* Space for bottom nav */
            }

            .sidebar {
                display: none; /* Hide sidebar on mobile */
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
            
            /* Hide menu toggle since we use bottom nav */
            .menu-toggle {
                display: none !important;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
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
            
            /* Bottom Navigation */
            .bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background: var(--bg-sidebar);
                display: flex;
                justify-content: space-around;
                align-items: center;
                padding: 10px 0;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                z-index: 1000;
                border-top: 1px solid var(--border-color);
            }

            .nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-decoration: none;
                color: var(--text-secondary);
                font-size: 0.75rem;
                gap: 4px;
                transition: all 0.3s;
            }

            .nav-item i {
                font-size: 1.2rem;
                margin-bottom: 2px;
            }

            .nav-item.active {
                color: var(--neon-cyan);
            }
            
            .nav-item.active i {
                transform: translateY(-2px);
            }
        }

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

        /* Improved Mobile Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active { 
            display: block; 
            opacity: 1;
        }

        .theme-toggle-btn {
            background: none;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 15px;
        }
        .theme-toggle-btn:hover {
            border-color: var(--neon-cyan);
            color: var(--neon-cyan);
        }
        @media (max-width: 768px) {
            .bottom-nav {
                display: flex !important;
            }
        }

        /* ===== Modal Popup ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: 30px;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 30px rgba(0, 245, 255, 0.1);
            animation: modalSlideIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .modal-header .close {
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.3s ease;
            line-height: 1;
            border: none;
            background: none;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-header .close:hover {
            color: var(--neon-red);
            background: rgba(255, 71, 87, 0.15);
            transform: rotate(90deg);
        }

        /* Modal scrollbar */
        .modal-content::-webkit-scrollbar {
            width: 6px;
        }
        .modal-content::-webkit-scrollbar-thumb {
            background: rgba(0, 245, 255, 0.3);
            border-radius: 3px;
        }

        /* Modal responsive */
        @media (max-width: 768px) {
            .modal {
                padding: 15px;
                align-items: flex-end;
            }

            .modal-content {
                max-width: 100%;
                max-height: 85vh;
                border-radius: 18px 18px 0 0;
                padding: 24px 20px;
                animation: modalSlideUp 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            }

            @keyframes modalSlideUp {
                from { opacity: 0; transform: translateY(100%); }
                to { opacity: 1; transform: translateY(0); }
            }
        }
    </style>
    <script>
        // Apply theme immediately to prevent flash
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-theme');
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('light-theme');
            });
        }
    </script>
</head>

<body>
    <?php if (isAdminLoggedIn()): ?>
        <!-- Mobile Bottom Navigation -->
        <div class="bottom-nav d-md-none" style="display: none;">
            <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            
            <a href="<?php echo APP_URL; ?>/admin/customers.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Customer</span>
            </a>
            
            <a href="<?php echo APP_URL; ?>/admin/pay.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'pay.php' || basename($_SERVER['PHP_SELF']) === 'pay_process.php') ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Pay</span>
            </a>
            
            <a href="<?php echo APP_URL; ?>/admin/menu.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : ''; ?>">
                <i class="fas fa-bars"></i>
                <span>Menu</span>
            </a>
        </div>

        <!-- Sidebar Overlay for mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <!-- Admin Sidebar -->
        <div class="sidebar" id="mainSidebar">
            <div class="sidebar-header">
                <i class="fas fa-network-wired" style="font-size: 1.5rem; color: var(--neon-cyan);"></i>
                <span class="sidebar-logo"><?php echo htmlspecialchars($appName); ?></span>
            </div>

            <div class="sidebar-nav">
                <a href="<?php echo APP_URL; ?>/admin/dashboard.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/customers.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Customer</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/packages.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'packages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>Package Layanan</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/invoices.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'invoices.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Invoice</span>
                </a>

                <div class="menu-item <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'sales') !== false) ? 'active' : ''; ?>"
                    onclick="toggleSubmenu(this)">
                    <i class="fas fa-user-tie"></i>
                    <span>Sales / Agen</span>
                    <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 0.7rem;"></i>
                </div>
                <div class="submenu"
                    style="<?php echo (strpos(basename($_SERVER['PHP_SELF']), 'sales') !== false) ? 'display: block;' : 'display: none;'; ?> background: var(--bg-submenu);">
                    <a href="<?php echo APP_URL; ?>/admin/sales-users.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'sales-users.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-users"></i> <span>Data Sales</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/sales-report.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'sales-report.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-chart-line"></i> <span>Laporan Penjualan</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/sales-history.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'sales-history.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-history"></i> <span>History Transaksi</span>
                    </a>
                </div>

                <div class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'mikrotik.php' || basename($_SERVER['PHP_SELF']) === 'pppoe-profile.php') ? 'active' : ''; ?>"
                    onclick="toggleSubmenu(this)">
                    <i class="fas fa-network-wired"></i>
                    <span>PPPOE</span>
                    <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 0.7rem;"></i>
                </div>
                <div class="submenu"
                    style="<?php echo (basename($_SERVER['PHP_SELF']) === 'mikrotik.php' || basename($_SERVER['PHP_SELF']) === 'pppoe-profile.php') ? 'display: block;' : 'display: none;'; ?> background: var(--bg-submenu);">
                    <a href="<?php echo APP_URL; ?>/admin/mikrotik.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'mikrotik.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-server"></i> <span>Data MikroTik</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/pppoe-profile.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'pppoe-profile.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-id-card"></i> <span>PPPoE Profileeeeeeeeeeees</span>
                    </a>
                </div>

                <div class="menu-item <?php echo strpos(basename($_SERVER['PHP_SELF']), 'hotspot') !== false ? 'active' : ''; ?>"
                    onclick="toggleSubmenu(this)">
                    <i class="fas fa-wifi"></i>
                    <span>Hotspot</span>
                    <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 0.7rem;"></i>
                </div>
                <div class="submenu"
                    style="<?php echo strpos(basename($_SERVER['PHP_SELF']), 'hotspot') !== false ? 'display: block;' : 'display: none;'; ?> background: var(--bg-submenu);">
                    <a href="<?php echo APP_URL; ?>/admin/hotspot-user.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'hotspot-user.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-users"></i> <span>Hotspot Users</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/hotspot-active.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'hotspot-active.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-signal"></i> <span>Hotspot Active</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/hotspot-profile.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'hotspot-profile.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-id-card"></i> <span>Hotspot Profileeeeeeeeeeees</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/hotspot-cookies.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'hotspot-cookies.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-cookie-bite"></i> <span>Hotspot Cookies</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/hotspot-scheduler.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'hotspot-scheduler.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-clock"></i> <span>Schedulers</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/hotspot-login-export.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'hotspot-login-export.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-right-to-bracket"></i> <span>Login Template</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/export-rsc.php"
                        class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'export-rsc.php' ? 'active' : ''; ?>"
                        style="padding-left: 45px; font-size: 0.9rem;">
                        <i class="fas fa-file-export"></i> <span>Export RSC</span>
                    </a>
                </div>

                <a href="<?php echo APP_URL; ?>/admin/genieacs.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'genieacs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-satellite-dish"></i>
                    <span>GenieACS</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/hioso.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'hioso.php' ? 'active' : ''; ?>">
                    <i class="fas fa-project-diagram"></i>
                    <span>Hioso OLT</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/map.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'map.php' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Map</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/voucher-editor.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'voucher-editor.php' ? 'active' : ''; ?>">
                    <i class="fas fa-magic"></i>
                    <span>Voucher Template</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/voucher-orders.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'voucher-orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-receipt"></i>
                    <span>Order Voucher</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/trouble.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'trouble.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Trouble</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/technicians.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'technicians.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span>Technician</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/settings.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/update.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'update.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sync-alt"></i>
                    <span>Update</span>
                </a>

                <a href="<?php echo APP_URL; ?>/admin/routers.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'routers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-server"></i>
                    <span>Router Management</span>
                </a>

                <div style="margin-top: 20px; border-top: 1px solid var(--border-color);"></div>

                <a href="<?php echo APP_URL; ?>/admin/logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isAdminLoggedIn()): ?>
            <div class="header">
                <div class="header-title">
                    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                </div>
                <div class="header-actions">
                    <!-- Theme Toggle -->
                    <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Light/Dark Mode">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>

                    <!-- Router Switcher -->
                    <?php if (count($allRouters) > 1): ?>
                    <div class="router-switcher" style="margin-right: 15px;">
                        <select onchange="window.location.href='?switch_router=' + this.value" 
                                style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--neon-cyan); padding: 5px 10px; border-radius: 6px; cursor: pointer;">
                            <?php foreach ($allRouters as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo $currentRouter['id'] == $r['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <button class="menu-toggle" onclick="toggleSidebar()"
                        style="display: none; background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5rem;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span style="color: var(--text-secondary);">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars(getCurrentAdmin()['username']); ?>
                    </span>
                    <span class="badge badge-info" style="margin-left: 10px;">
                        <i class="fas fa-server"></i> <?php echo htmlspecialchars($currentRouter['name'] ?? 'Default'); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

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

        <!-- Page Content -->
        <?php echo $content; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/umd/simple-datatables.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function toggleTheme() {
            document.body.classList.toggle('light-theme');
            const isLight = document.body.classList.contains('light-theme');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const icon = document.getElementById('themeIcon');
            const isLight = document.body.classList.contains('light-theme');
            icon.className = isLight ? 'fas fa-sun' : 'fas fa-moon';
        }

        function toggleSubmenu(el) {
            const submenu = el.nextElementSibling;
            const icon = el.querySelector('.fa-chevron-down');
            if (submenu.style.display === 'none') {
                submenu.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                submenu.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Apply saved theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
            }
            updateThemeIcon();
            
            // Show mobile menu toggle button
            const menuToggle = document.querySelector('.menu-toggle');
            if (window.innerWidth <= 768) {
                menuToggle.style.display = 'block';
            }

            window.addEventListener('resize', function () {
                if (window.innerWidth <= 768) {
                    menuToggle.style.display = 'block';
                } else {
                    menuToggle.style.display = 'none';
                    document.querySelector('.sidebar').classList.remove('active');
                }
            });
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../sw.js');
            });
        }
    </script>
</body>

</html>
