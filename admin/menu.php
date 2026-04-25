<?php
/**
 * Admin Mobile Menu
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Menu';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bars"></i> Complete Menu</h3>
            </div>
            <div class="card-body">
                <div class="menu-grid">
                    <a href="dashboard.php" class="menu-grid-item">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="customers.php" class="menu-grid-item">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                    
                    <a href="packages.php" class="menu-grid-item">
                        <i class="fas fa-box"></i>
                        <span>Packages</span>
                    </a>
                    
                    <a href="invoices.php" class="menu-grid-item">
                        <i class="fas fa-file-invoice"></i>
                        <span>Invoice</span>
                    </a>
                    
                    <a href="trouble.php" class="menu-grid-item">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Trouble</span>
                    </a>
                    
                    <a href="technicians.php" class="menu-grid-item">
                        <i class="fas fa-tools"></i>
                        <span>Technicians</span>
                    </a>
                    
                    <hr style="width: 100%; border-color: var(--border-color); margin: 10px 0;">
                    
                    <h5 style="width: 100%; color: var(--text-secondary); margin: 10px 0; font-size: 0.9rem;">Sales / Agent</h5>
                    
                    <a href="sales-users.php" class="menu-grid-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Sales Data</span>
                    </a>
                    
                    <a href="sales-report.php" class="menu-grid-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Sales Report</span>
                    </a>

                    <a href="sales-history.php" class="menu-grid-item">
                        <i class="fas fa-history"></i>
                        <span>Transaction History</span>
                    </a>
                    
                    <hr style="width: 100%; border-color: var(--border-color); margin: 10px 0;">
                    
                    <h5 style="width: 100%; color: var(--text-secondary); margin: 10px 0; font-size: 0.9rem;">Technical & Hotspot</h5>
                    
                    <a href="mikrotik.php" class="menu-grid-item">
                        <i class="fas fa-server"></i>
                        <span>MikroTik</span>
                    </a>
                    
                    <a href="hotspot-user.php" class="menu-grid-item">
                        <i class="fas fa-wifi"></i>
                        <span>Hotspot Users</span>
                    </a>
                    
                    <a href="hotspot-active.php" class="menu-grid-item">
                        <i class="fas fa-signal"></i>
                        <span>Hotspot Active</span>
                    </a>
                    
                    <a href="hotspot-profile.php" class="menu-grid-item">
                        <i class="fas fa-id-card"></i>
                        <span>Hotspot Profileeeeeeeeeeees</span>
                    </a>

                    <a href="hotspot-login-export.php" class="menu-grid-item">
                        <i class="fas fa-right-to-bracket"></i>
                        <span>Login Template</span>
                    </a>
                    
                    <a href="voucher-editor.php" class="menu-grid-item">
                        <i class="fas fa-magic"></i>
                        <span>Template Voucher</span>
                    </a>

                    <a href="voucher-orders.php" class="menu-grid-item">
                        <i class="fas fa-receipt"></i>
                        <span>Order Voucher</span>
                    </a>
                    
                    <a href="map.php" class="menu-grid-item">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Map</span>
                    </a>
                    
                    <a href="trouble.php" class="menu-grid-item">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Trouble</span>
                    </a>
                    
                    <a href="genieacs.php" class="menu-grid-item">
                        <i class="fas fa-satellite-dish"></i>
                        <span>GenieACS</span>
                    </a>

                    <hr style="width: 100%; border-color: var(--border-color); margin: 10px 0;">
                    
                    <h5 style="width: 100%; color: var(--text-secondary); margin: 10px 0; font-size: 0.9rem;">System</h5>
                    
                    <a href="settings.php" class="menu-grid-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    
                    <a href="update.php" class="menu-grid-item">
                        <i class="fas fa-sync-alt"></i>
                        <span>Update</span>
                    </a>
                    
                    <a href="routers.php" class="menu-grid-item">
                        <i class="fas fa-network-wired"></i>
                        <span>Routers</span>
                    </a>
                    
                    <a href="logout.php" class="menu-grid-item" style="color: var(--neon-pink);">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .menu-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .menu-grid-item {
        flex: 1 0 30%; /* 3 items per row approx */
        min-width: 90px;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 15px 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: var(--text-primary);
        text-align: center;
        transition: all 0.2s;
        gap: 8px;
    }
    
    .menu-grid-item i {
        font-size: 1.5rem;
        margin-bottom: 5px;
        color: var(--neon-cyan);
    }
    
    .menu-grid-item span {
        font-size: 0.8rem;
        line-height: 1.2;
    }
    
    .menu-grid-item:active {
        background: rgba(0, 245, 255, 0.1);
        transform: scale(0.98);
    }
</style>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>
