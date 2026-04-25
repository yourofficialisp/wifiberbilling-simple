<?php
/**
 * Sales Dashboard
 */

require_once '../includes/auth.php';
requireSalesLogin();

$pageTitle = 'Dashboard';

// Get Sales Info
$salesId = $_SESSION['sales']['id'];
$salesUser = getSalesUser($salesId);

// Update session balance
$_SESSION['sales']['deposit_balance'] = $salesUser['deposit_balance'];

// Get Stats
$today = date('Y-m-d');
$month = date('Y-m');

// Today's Sales
$todaySales = fetchOne("SELECT COUNT(*) as count, SUM(price) as capital, SUM(selling_price) as total 
    FROM hotspot_sales 
    WHERE sales_user_id = ? AND DATE(created_at) = ?", [$salesId, $today]);

// Month's Sales
$monthSales = fetchOne("SELECT COUNT(*) as count, SUM(price) as capital, SUM(selling_price) as total 
    FROM hotspot_sales 
    WHERE sales_user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?", [$salesId, $month]);

ob_start();
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <!-- Deposit Card -->
    <div class="card" style="background: linear-gradient(135deg, rgba(0, 245, 255, 0.1) 0%, rgba(191, 0, 255, 0.1) 100%); border-color: var(--neon-cyan);">
        <div class="card-body" style="padding: 25px;">
            <h5 style="color: var(--text-secondary); margin-bottom: 10px; font-size: 1rem;">Deposit Balance</h5>
            <h2 style="font-size: 2.5rem; color: var(--neon-cyan); margin-bottom: 5px;"><?php echo formatCurrency($salesUser['deposit_balance']); ?></h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Balance ready to use</p>
        </div>
    </div>
    
    <!-- Today Sales -->
    <div class="card">
        <div class="card-body" style="padding: 25px;">
            <h5 style="color: var(--text-secondary); margin-bottom: 15px; font-size: 1rem;">Today's Sales</h5>
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h3 style="font-size: 1.8rem; margin-bottom: 5px;"><?php echo (int)$todaySales['count']; ?> <span style="font-size: 1rem; color: var(--text-muted);">Voucher</span></h3>
                    <div style="color: var(--neon-green); font-weight: 600;">
                        + <?php echo formatCurrency($todaySales['total']); ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <small style="color: var(--text-muted); display: block;">Capital</small>
                    <span style="color: var(--text-primary);"><?php echo formatCurrency($todaySales['capital']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Month Sales -->
    <div class="card">
        <div class="card-body" style="padding: 25px;">
            <h5 style="color: var(--text-secondary); margin-bottom: 15px; font-size: 1rem;">This Month's Sales</h5>
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h3 style="font-size: 1.8rem; margin-bottom: 5px;"><?php echo (int)$monthSales['count']; ?> <span style="font-size: 1rem; color: var(--text-muted);">Voucher</span></h3>
                    <div style="color: var(--neon-green); font-weight: 600;">
                        + <?php echo formatCurrency($monthSales['total']); ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <small style="color: var(--text-muted); display: block;">Capital</small>
                    <span style="color: var(--text-primary);"><?php echo formatCurrency($monthSales['capital']); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-rocket"></i> Quick Actions</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="vouchers.php" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; font-size: 1.1rem;">
                <i class="fas fa-ticket-alt" style="font-size: 1.5rem;"></i> Create Voucher
            </a>
            <a href="pay.php" class="btn btn-success" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; font-size: 1.1rem; background: var(--neon-green); color: black;">
                <i class="fas fa-money-bill-wave" style="font-size: 1.5rem;"></i> Pay Bill
            </a>
            <a href="history.php" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; font-size: 1.1rem; background: var(--bg-input); border: 1px solid var(--border-color);">
                <i class="fas fa-history" style="font-size: 1.5rem;"></i> Transaction History
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/sales_layout.php';
?>
