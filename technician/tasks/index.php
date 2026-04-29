<?php
require_once '../../includes/auth.php';
requireTechnicianLogin();

$pageTitle = 'Task List';
$tech = $_SESSION['technician'];

$type = $_GET['type'] ?? 'ticket'; // ticket | install

if ($type === 'ticket') {
    // Get Tickets
    $status = $_GET['status'] ?? 'all';
    $where = "technician_id = ?";
    $params = [$tech['id']];

    if ($status === 'pending') {
        $where .= " AND status IN ('pending', 'in_progress')";
    } elseif ($status === 'resolved') {
        $where .= " AND status = 'resolved'";
    }

    $tickets = fetchAll("
        SELECT t.*, c.name as customer_name, c.address 
        FROM trouble_tickets t 
        LEFT JOIN customers c ON t.customer_id = c.id 
        WHERE $where 
        ORDER BY FIELD(t.status, 'in_progress', 'pending', 'resolved'), t.created_at DESC
    ", $params);
} else {
    // Get Installations
    $status = $_GET['status'] ?? 'pending'; // pending (registered) | resolved (active)
    $where = "installed_by = ?";
    $params = [$tech['id']];

    if ($status === 'pending') {
        $where .= " AND status = 'registered'";
    } elseif ($status === 'resolved') {
        $where .= " AND status = 'active'";
    } else {
        $where .= " AND status IN ('registered', 'active')";
    }

    $installs = fetchAll("
        SELECT * FROM customers 
        WHERE $where 
        ORDER BY created_at DESC
    ", $params);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task List - Technician</title>
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
        
        .header h2 { font-size: 1.1rem; }
        
        .type-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--bg-card);
            padding: 10px 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .type-tab {
            text-align: center;
            padding: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            border-bottom: 2px solid transparent;
        }
        
        .type-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: bold;
        }
        
        .filter-tabs {
            display: flex;
            padding: 15px 20px;
            gap: 10px;
            overflow-x: auto;
        }
        
        .filter-tab {
            background: var(--bg-card);
            padding: 8px 16px;
            border-radius: 20px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            white-space: nowrap;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .filter-tab.active {
            background: rgba(0, 245, 255, 0.1);
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .task-list {
            padding: 0 20px;
        }
        
        .task-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.05);
            display: block;
            text-decoration: none;
            color: inherit;
            position: relative;
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .task-id {
            font-size: 0.8rem;
            color: var(--text-secondary);
            background: rgba(255,255,255,0.05);
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .status-pending { background: rgba(255, 204, 0, 0.15); color: #ffcc00; }
        .status-in_progress { background: rgba(0, 245, 255, 0.15); color: #00f5ff; }
        .status-resolved { background: rgba(0, 255, 136, 0.15); color: #00ff88; }
        .status-registered { background: rgba(0, 245, 255, 0.15); color: #00f5ff; }
        .status-active { background: rgba(0, 255, 136, 0.15); color: #00ff88; }
        
        .customer-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        
        .task-desc {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: var(--text-secondary);
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="../dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h2>Task List</h2>
    </div>

    <div class="type-tabs">
        <a href="?type=ticket" class="type-tab <?php echo $type === 'ticket' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Trouble
        </a>
        <a href="?type=install" class="type-tab <?php echo $type === 'install' ? 'active' : ''; ?>">
            <i class="fas fa-satellite-dish"></i> New Installation
        </a>
    </div>

    <div class="filter-tabs">
        <a href="?type=<?php echo $type; ?>&status=all" class="filter-tab <?php echo $status === 'all' ? 'active' : ''; ?>">All</a>
        <a href="?type=<?php echo $type; ?>&status=pending" class="filter-tab <?php echo $status === 'pending' ? 'active' : ''; ?>">
            <?php echo $type === 'ticket' ? 'Needs Action' : 'Not yet Installed'; ?>
        </a>
        <a href="?type=<?php echo $type; ?>&status=resolved" class="filter-tab <?php echo $status === 'resolved' ? 'active' : ''; ?>">Completed</a>
    </div>

    <div class="task-list">
        <?php if ($type === 'ticket'): ?>
            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <p>No trouble tickets at this time.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <a href="view_ticket.php?id=<?php echo $t['id']; ?>" class="task-card">
                        <div class="task-header">
                            <span class="task-id">#<?php echo $t['id']; ?></span>
                            <span class="status-badge status-<?php echo $t['status']; ?>">
                                <?php 
                                switch($t['status']) {
                                    case 'pending': echo 'Pending'; break;
                                    case 'in_progress': echo 'In Progress'; break;
                                    case 'resolved': echo 'Completed'; break;
                                }
                                ?>
                            </span>
                        </div>
                        <div class="customer-name"><?php echo htmlspecialchars($t['customer_name']); ?></div>
                        <div class="task-desc">
                            <i class="fas fa-exclamation-circle" style="color: #ff4757; margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($t['description']); ?>
                        </div>
                        <div class="task-footer">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(substr($t['address'] ?? '-', 0, 25)) . '...'; ?></span>
                            <span><?php echo date('d M H:i', strtotime($t['created_at'])); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <?php if (empty($installs)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No new installation schedules.</p>
                </div>
            <?php else: ?>
                <?php foreach ($installs as $i): ?>
                    <a href="view_install.php?id=<?php echo $i['id']; ?>" class="task-card">
                        <div class="task-header">
                            <span class="task-id">#C<?php echo $i['id']; ?></span>
                            <span class="status-badge status-<?php echo $i['status']; ?>">
                                <?php echo $i['status'] === 'registered' ? 'Not yet Installed' : 'Active'; ?>
                            </span>
                        </div>
                        <div class="customer-name"><?php echo htmlspecialchars($i['name']); ?></div>
                        <div class="task-desc">
                            <i class="fas fa-box" style="color: var(--primary); margin-right: 5px;"></i>
                            New Internet Package
                        </div>
                        <div class="task-footer">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(substr($i['address'] ?? '-', 0, 25)) . '...'; ?></span>
                            <span><?php echo date('d M H:i', strtotime($i['created_at'])); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
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
