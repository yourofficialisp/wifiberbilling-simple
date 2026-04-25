<?php
require_once '../../includes/auth.php';
requireTechnicianLogin();

$pageTitle = 'Search Device';
$tech = $_SESSION['technician'];
$results = [];
$search = $_GET['q'] ?? '';

if (!empty($search)) {
    // Search in local DB first to get PPPoE username
    $results = fetchAll("
        SELECT id, name, pppoe_username, address, phone 
        FROM customers 
        WHERE name LIKE ? OR pppoe_username LIKE ? OR phone LIKE ?
        LIMIT 10
    ", ["%$search%", "%$search%", "%$search%"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Device - Technician</title>
    <meta name="theme-color" content="#0a0a12">
    <link rel="manifest" href="../../manifest.json">
    <link rel="apple-touch-icon" href="../../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../../assets/icons/icon-192x192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00f5ff;
            --bg-dark: #0a0a12;
            --bg-card: #161628;
            --text-primary: #ffffff;
            --text-secondary: #b0b0c0;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            padding-bottom: 80px;
        }
        
        .header {
            background: var(--bg-card);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .back-btn {
            color: var(--text-primary);
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .container { padding: 20px; }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .form-control {
            flex: 1;
            padding: 12px;
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-primary);
        }
        
        .btn-search {
            padding: 0 20px;
            background: var(--primary);
            border: none;
            border-radius: 8px;
            color: #000;
            cursor: pointer;
        }
        
        .result-card {
            background: var(--bg-card);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .result-info h3 { font-size: 1rem; margin-bottom: 5px; }
        .result-info p { font-size: 0.8rem; color: var(--text-secondary); }
        
        .btn-manage {
            background: rgba(0, 245, 255, 0.1);
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="../dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h2>Check Device</h2>
    </div>

    <div class="container">
        <form method="GET" class="search-box">
            <input type="text" name="q" class="form-control" placeholder="Name, PPPoE, or Mobile..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
        </form>
        
        <?php if (!empty($search) && empty($results)): ?>
            <div style="text-align: center; color: var(--text-secondary); margin-top: 30px;">
                <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Not found.</p>
            </div>
        <?php endif; ?>
        
        <?php foreach ($results as $r): ?>
            <a href="manage.php?username=<?php echo urlencode($r['pppoe_username']); ?>" class="result-card">
                <div class="result-info">
                    <h3><?php echo htmlspecialchars($r['name']); ?></h3>
                    <p><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($r['pppoe_username']); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(substr($r['address'] ?? '-', 0, 25)); ?></p>
                </div>
                <div class="btn-manage">
                    <i class="fas fa-cog"></i> Manage
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php require_once '../includes/bottom_nav.php'; ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../../sw.js');
            });
        }
    </script>
</body>
</html>
