<?php
require_once '../includes/auth.php';
requireTechnicianLogin();

$pageTitle = 'Dashboard Technician';
$tech = $_SESSION['technician'];

// Get Task Summary
// 1. Pending Trouble Tickets
$pendingTickets = fetchOne("SELECT COUNT(*) as total FROM trouble_tickets WHERE technician_id = ? AND status IN ('pending', 'in_progress')", [$tech['id']]);

// 2. Pending Installations (PSB)
$pendingInstalls = fetchOne("SELECT COUNT(*) as total FROM customers WHERE installed_by = ? AND status = 'registered'", [$tech['id']]);

// 3. Today's Completed Tasks
$todayCompleted = fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM trouble_tickets WHERE technician_id = ? AND status = 'resolved' AND DATE(resolved_at) = CURDATE()) +
        (SELECT COUNT(*) FROM customers WHERE installed_by = ? AND status = 'active' AND DATE(installation_date) = CURDATE()) as total
", [$tech['id'], $tech['id']]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($tech['name']); ?></title>
    <meta name="theme-color" content="#0a0a12">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../assets/icons/icon-192x192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00f5ff;
            --primary-dark: #00dbe3;
            --bg-dark: #0a0a12;
            --bg-card: #161628;
            --text-primary: #ffffff;
            --text-secondary: #b0b0c0;
            --success: #00ff88;
            --warning: #ffcc00;
            --danger: #ff4757;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            padding-bottom: 70px; /* Space for bottom nav */
        }
        
        .header {
            background: var(--bg-card);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .profile-info h2 { font-size: 1.1rem; }
        .profile-info p { font-size: 0.8rem; color: var(--text-secondary); }
        
        .btn-logout {
            color: var(--danger);
            text-decoration: none;
            font-size: 1.2rem;
        }
        
        .container {
            padding: 20px;
        }
        
        .section-title {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .menu-card {
            background: var(--bg-card);
            padding: 25px 20px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.2s;
        }
        
        .menu-card:active {
            transform: scale(0.98);
            background: rgba(255,255,255,0.05);
        }
        
        .menu-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .menu-title {
            font-size: 1rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="profile-info">
            <h2>Hi, <?php echo htmlspecialchars($tech['name']); ?> 👋</h2>
            <p>Field Technician</p>
        </div>
        <a href="logout.php" class="btn-logout" onclick="return confirm('Logout from application?');">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>

    <div class="container">
        <div class="section-title">Task Summary</div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: var(--warning)">
                    <?php echo $pendingTickets['total']; ?>
                </div>
                <div class="stat-label">Pending Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: var(--primary)">
                    <?php echo $pendingInstalls['total']; ?>
                </div>
                <div class="stat-label">New Installation</div>
            </div>
            <div class="stat-card" style="grid-column: span 2;">
                <div class="stat-number" style="color: var(--success)">
                    <?php echo $todayCompleted['total']; ?>
                </div>
                <div class="stat-label">Completed Today</div>
            </div>
        </div>

        <div class="section-title">Main Menu</div>
        
        <div class="menu-grid">
            <a href="tasks/index.php" class="menu-card">
                <i class="fas fa-clipboard-list menu-icon"></i>
                <div class="menu-title">Task List</div>
            </a>
            <a href="map/index.php" class="menu-card">
                <i class="fas fa-map-marked-alt menu-icon" style="color: #ff9f43"></i>
                <div class="menu-title">Location Map</div>
            </a>
            <a href="devices/search.php" class="menu-card">
                <i class="fas fa-satellite-dish menu-icon" style="color: #00d2d3"></i>
                <div class="menu-title">Check Device</div>
            </a>
            <a href="profile.php" class="menu-card">
                <i class="fas fa-user-cog menu-icon" style="color: #5f27cd"></i>
                <div class="menu-title">My Profile</div>
            </a>
        </div>
    </div>

    </div>

    <?php require_once 'includes/bottom_nav.php'; ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../sw.js');
            });
        }
    </script>
</body>
</html>
