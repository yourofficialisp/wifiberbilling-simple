<?php
/**
 * Admin Dashboard
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Dashboard';

// Get statistics
$stats = [
    'totalCustomers' => fetchOne("SELECT COUNT(*) as total FROM customers")['total'] ?? 0,
    'activeCustomers' => fetchOne("SELECT COUNT(*) as total FROM customers WHERE status = 'active'")['total'] ?? 0,
    'isolatedCustomers' => fetchOne("SELECT COUNT(*) as total FROM customers WHERE status = 'isolated'")['total'] ?? 0,
    'totalPackages' => fetchOne("SELECT COUNT(*) as total FROM packages")['total'] ?? 0,
    'totalInvoices' => fetchOne("SELECT COUNT(*) as total FROM invoices")['total'] ?? 0,
    'paidInvoices' => fetchOne("SELECT COUNT(*) as total FROM invoices WHERE status = 'paid'")['total'] ?? 0,
    'pendingInvoices' => fetchOne("SELECT COUNT(*) as total FROM invoices WHERE status = 'unpaid'")['total'] ?? 0,
    'totalRevenue' => fetchOne("
        SELECT SUM(amount) as total 
        FROM invoices 
        WHERE status = 'paid' 
        AND paid_at IS NOT NULL
        AND DATE_FORMAT(paid_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ")['total'] ?? 0,
];

// Get recent invoices
$recentInvoices = fetchAll("
    SELECT i.*, c.name as customer_name 
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.created_at DESC 
    LIMIT 10
");

// Get recent customers
$recentCustomers = fetchAll("
    SELECT c.*, p.name as package_name 
    FROM customers c 
    LEFT JOIN packages p ON c.package_id = p.id 
    ORDER BY c.created_at DESC 
    LIMIT 5
");

// Get monthly revenue for chart (last 6 months)
$monthlyRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $monthName = date('M Y', strtotime("-{$i} months"));

    $revenue = fetchOne("
        SELECT SUM(amount) as total 
        FROM invoices 
        WHERE status = 'paid' 
        AND DATE_FORMAT(paid_at, '%Y-%m') = ?
    ", [$month])['total'] ?? 0;

    $monthlyRevenue[] = [
        'month' => $monthName,
        'revenue' => (float) $revenue
    ];
}

// Get monthly customer growth (last 6 months)
$monthlyCustomers = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $monthName = date('M Y', strtotime("-{$i} months"));

    $count = fetchOne("
        SELECT COUNT(*) as total 
        FROM customers 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    ", [$month])['total'] ?? 0;

    $monthlyCustomers[] = [
        'month' => $monthName,
        'count' => (int) $count
    ];
}

// Get unpaid invoices count by due status
$overdueInvoices = fetchOne("
    SELECT COUNT(*) as total 
    FROM invoices 
    WHERE status = 'unpaid' 
    AND due_date < CURDATE()
")['total'] ?? 0;

$dueSoonInvoices = fetchOne("
    SELECT COUNT(*) as total 
    FROM invoices 
    WHERE status = 'unpaid' 
    AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")['total'] ?? 0;

// Get Hotspot data for Mikhmon v3 style dashboard
$hotspotUsers = mikrotikGetHotspotUsers();
$hotspotActive = mikrotikGetHotspotActive();
$routerResource = mikrotikGetSystemResource();
$hotspotTotalUsers = count($hotspotUsers);
$hotspotActiveCount = count($hotspotActive);
$pppoeActive = mikrotikGetActiveSessions();
$pppoeActiveCount = is_array($pppoeActive) ? count($pppoeActive) : 0;
$interfaces = mikrotikGetInterfaces();

// Sales Stats
$salesStats = [
    'totalSales' => fetchOne("SELECT COUNT(*) as total FROM sales_users")['total'] ?? 0,
    'todayVouchers' => fetchOne("SELECT COUNT(*) as total FROM hotspot_sales WHERE DATE(created_at) = CURDATE()")['total'] ?? 0,
    'monthVouchers' => fetchOne("SELECT COUNT(*) as total FROM hotspot_sales WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")['total'] ?? 0,
    'todayRevenue' => fetchOne("SELECT SUM(selling_price) as total FROM hotspot_sales WHERE DATE(created_at) = CURDATE()")['total'] ?? 0,
    'todayProfit' => fetchOne("SELECT SUM(selling_price - price) as total FROM hotspot_sales WHERE DATE(created_at) = CURDATE()")['total'] ?? 0,
];

ob_start();
?>

<!-- ==================== MIKHMON V3 DASHBOARD SECTION ==================== -->

<!-- Router Info Bar -->
<div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Date/Time & Uptime -->
    <div class="card" style="margin-bottom: 0;">
        <div style="padding: 10px; display: flex; align-items: center; gap: 18px;">
            <div
                style="width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, var(--neon-cyan), #0088cc); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 15px rgba(0, 245, 255, 0.2);">
                <i class="fas fa-calendar-alt" style="color: #fff; font-size: 1.4rem;"></i>
            </div>
            <div>
                <div style="color: var(--text-primary); font-weight: 700; font-size: 1.1rem;"><?php echo date('d M Y H:i:s'); ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 2px;">Uptime:
                    <span style="color: var(--neon-cyan); font-weight: 500;"><?php echo htmlspecialchars($routerResource['uptime']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Board Info -->
    <div class="card" style="margin-bottom: 0;">
        <div style="padding: 15px; display: flex; align-items: center; gap: 15px;">
            <div
                style="width: 45px; height: 45px; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-info-circle" style="color: #fff; font-size: 1.2rem;"></i>
            </div>
            <div>
                <div style="color: var(--text-primary); font-weight: 600;">
                    <?php echo htmlspecialchars($routerResource['board-name']); ?>
                </div>
                <div style="color: var(--text-muted); font-size: 0.85rem;">RouterOS
                    <?php echo htmlspecialchars($routerResource['version']); ?>
                    (<?php echo htmlspecialchars($routerResource['architecture-name']); ?>)
                </div>
            </div>
        </div>
    </div>

    <!-- CPU / Memory -->
    <div class="card" style="margin-bottom: 0;">
        <div style="padding: 15px; display: flex; align-items: center; gap: 15px;">
            <div
                style="width: 45px; height: 45px; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #ef4444); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-server" style="color: #fff; font-size: 1.2rem;"></i>
            </div>
            <div>
                <div style="color: var(--text-primary); font-weight: 600;">
                    CPU: <span
                        style="color: <?php echo $routerResource['cpu-load'] > 80 ? '#ef4444' : ($routerResource['cpu-load'] > 50 ? '#f59e0b' : 'var(--neon-green)'); ?>;"><?php echo $routerResource['cpu-load']; ?>%</span>
                </div>
                <div style="color: var(--text-muted); font-size: 0.85rem;">Free Memory:
                    <?php echo formatBytes($routerResource['free-memory']); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hotspot Stats (4 colored boxes like Mikhmon v3) -->
<div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <a href="hotspot-user.php" style="text-decoration: none;">
        <div style="background: linear-gradient(135deg, #00f5ff, #0088cc); border-radius: 16px; padding: 24px; text-align: center; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); cursor: pointer; box-shadow: 0 4px 15px rgba(0, 245, 255, 0.15);"
            onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 30px rgba(0, 245, 255, 0.4)';"
            onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 15px rgba(0, 245, 255, 0.15)';">
            <div style="font-size: 2.8rem; font-weight: 800; color: #fff; line-height: 1;"><?php echo $hotspotActiveCount; ?></div>
            <div style="color: rgba(255,255,255,0.9); font-size: 0.95rem; margin-top: 10px; font-weight: 500;"><i class="fas fa-laptop" style="margin-right: 5px;"></i> Hotspot Active</div>
        </div>
    </a>
    <a href="hotspot-user.php" style="text-decoration: none;">
        <div style="background: linear-gradient(135deg, #00ff88, #00a859); border-radius: 16px; padding: 24px; text-align: center; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); cursor: pointer; box-shadow: 0 4px 15px rgba(0, 255, 136, 0.15);"
            onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 30px rgba(0, 255, 136, 0.4)';"
            onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 15px rgba(0, 255, 136, 0.15)';">
            <div style="font-size: 2.8rem; font-weight: 800; color: #fff; line-height: 1;"><?php echo $hotspotTotalUsers; ?></div>
            <div style="color: rgba(255,255,255,0.9); font-size: 0.95rem; margin-top: 10px; font-weight: 500;"><i class="fas fa-users" style="margin-right: 5px;"></i> Hotspot Users</div>
        </div>
    </a>
    <a href="mikrotik.php" style="text-decoration: none;">
        <div style="background: linear-gradient(135deg, #bf00ff, #7a00a3); border-radius: 16px; padding: 24px; text-align: center; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); cursor: pointer; box-shadow: 0 4px 15px rgba(191, 0, 255, 0.15);"
            onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 30px rgba(191, 0, 255, 0.4)';"
            onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 15px rgba(191, 0, 255, 0.15)';">
            <div style="font-size: 2.8rem; font-weight: 800; color: #fff; line-height: 1;"><i class="fas fa-network-wired"></i></div>
            <div style="color: rgba(255,255,255,0.9); font-size: 0.95rem; margin-top: 10px; font-weight: 500;">Add PPPoE</div>
        </div>
    </a>
    <a href="voucher.php" style="text-decoration: none;">
        <div style="background: linear-gradient(135deg, #ff00aa, #b30077); border-radius: 16px; padding: 24px; text-align: center; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); cursor: pointer; box-shadow: 0 4px 15px rgba(255, 0, 170, 0.15);"
            onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 30px rgba(255, 0, 170, 0.4)';"
            onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 15px rgba(255, 0, 170, 0.15)';">
            <div style="font-size: 2.8rem; font-weight: 800; color: #fff; line-height: 1;"><i class="fas fa-ticket-alt"></i></div>
            <div style="color: rgba(255,255,255,0.9); font-size: 0.95rem; margin-top: 10px; font-weight: 500;">Generate Voucher</div>
        </div>
    </a>
</div>

<!-- ISP Stats Grid (Mikhmon Style) -->
<div class="isp-stats-grid">
    <!-- Total Customers -->
    <div class="isp-stat-card" style="background: linear-gradient(135deg, rgba(0, 245, 255, 0.1), rgba(0, 136, 204, 0.2)); border: 1px solid var(--neon-cyan);"
         onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 30px rgba(0, 245, 255, 0.15)';"
         onmouseout="this.style.transform=''; this.style.boxShadow='';">
        <div class="isp-stat-value" style="color: var(--neon-cyan);"><?php echo $stats['totalCustomers']; ?></div>
        <div class="isp-stat-label"><i class="fas fa-users" style="margin-right: 5px;"></i> Total Customers</div>
    </div>
    <!-- PPPoE Active -->
    <div class="isp-stat-card" style="background: linear-gradient(135deg, rgba(191, 0, 255, 0.1), rgba(122, 0, 163, 0.2)); border: 1px solid var(--neon-purple);"
         onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 30px rgba(191, 0, 255, 0.15)';"
         onmouseout="this.style.transform=''; this.style.boxShadow='';">
        <div class="isp-stat-value" style="color: var(--neon-purple);"><?php echo $pppoeActiveCount; ?></div>
        <div class="isp-stat-label"><i class="fas fa-network-wired" style="margin-right: 5px;"></i> PPPoE Active</div>
    </div>
    <!-- Isolated Customers -->
    <div class="isp-stat-card" style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(204, 85, 42, 0.2)); border: 1px solid var(--neon-orange);"
         onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 30px rgba(255, 107, 53, 0.15)';"
         onmouseout="this.style.transform=''; this.style.boxShadow='';">
        <div class="isp-stat-value" style="color: var(--neon-orange);"><?php echo $stats['isolatedCustomers']; ?></div>
        <div class="isp-stat-label"><i class="fas fa-user-lock" style="margin-right: 5px;"></i> Isolated Customers</div>
    </div>
    <!-- Total Revenue This Month -->
    <div class="isp-stat-card" style="background: linear-gradient(135deg, rgba(0, 255, 136, 0.1), rgba(0, 168, 89, 0.2)); border: 1px solid var(--neon-green);"
         onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 30px rgba(0, 255, 136, 0.15)';"
         onmouseout="this.style.transform=''; this.style.boxShadow='';">
        <div class="isp-stat-value isp-stat-value-currency" style="color: var(--neon-green);"><?php echo formatCurrency($stats['totalRevenue']); ?></div>
        <div class="isp-stat-label"><i class="fas fa-wallet" style="margin-right: 5px;"></i> Total Revenue This Month</div>
    </div>
</div>

<!-- Sales Portal Summary -->
<div class="card mb-4" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-wallet"></i> Sales Portal Summary</h3>
    </div>
    <div style="padding: 15px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border-left: 4px solid var(--neon-cyan);">
                <div style="color: var(--text-secondary); font-size: 0.9rem;">Total Sales User</div>
                <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $salesStats['totalSales']; ?></div>
            </div>
            <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border-left: 4px solid var(--neon-purple);">
                <div style="color: var(--text-secondary); font-size: 0.9rem;">Vouchers Sold (Today)</div>
                <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $salesStats['todayVouchers']; ?></div>
            </div>
            <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border-left: 4px solid var(--neon-green);">
                <div style="color: var(--text-secondary); font-size: 0.9rem;">Revenue (Today)</div>
                <div style="font-size: 1.5rem; font-weight: bold;"><?php echo formatCurrency($salesStats['todayRevenue']); ?></div>
            </div>
            <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border-left: 4px solid var(--neon-orange);">
                <div style="color: var(--text-secondary); font-size: 0.9rem;">Profit (Today)</div>
                <div style="font-size: 1.5rem; font-weight: bold;"><?php echo formatCurrency($salesStats['todayProfit']); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Traffic Monitor + Hotspot Log (2-column layout) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; margin-bottom: 24px;" id="mikhmon-main-grid">
    <!-- Traffic Monitor -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header"
            style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h3 class="card-title"><i class="fas fa-chart-area"></i> Traffic Monitor</h3>
            <select id="interfaceSelector" onchange="changeInterface(this.value)"
                class="form-control" style="width: auto; padding: 6px 12px;">
                <?php foreach ($interfaces as $iface): ?>
                    <option value="<?php echo htmlspecialchars($iface['name'] ?? ''); ?>">
                        <?php echo htmlspecialchars($iface['name'] ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <canvas id="trafficChart" height="250"></canvas>
        </div>
    </div>

    <!-- Hotspot Log -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-align-justify"></i> Hotspot Log</h3>
        </div>
        <div style="max-height: 290px; overflow-y: auto;" id="hotspotLogContainer">
            <table class="data-table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User (IP)</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody id="hotspotLogBody">
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 20px;">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- ==================== ISP BILLING SECTION ==================== -->

<!-- Alert for overdue invoices -->
<?php if ($overdueInvoices > 0 || $dueSoonInvoices > 0): ?>
    <div class="alert alert-warning" style="margin-bottom: 20px;">
        <i class="fas fa-exclamation-triangle"></i>
        <span>
            <?php if ($overdueInvoices > 0): ?>
                <strong><?php echo $overdueInvoices; ?></strong> invoices are overdue.
            <?php endif; ?>
            <?php if ($dueSoonInvoices > 0): ?>
                <strong><?php echo $dueSoonInvoices; ?></strong> invoices will be due in 7 days.
            <?php endif; ?>
            <a href="invoices.php" style="color: inherit; text-decoration: underline; margin-left: 10px;">View Invoice</a>
        </span>
    </div>
<?php endif; ?>

<!-- ISP Stats Grid Removed -->


<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Menu</h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="customers.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-users"></i> Customers
        </a>
        <a href="packages.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-box"></i> PPPoE Packages
        </a>
        <a href="invoices.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-file-invoice"></i> Invoice
        </a>
        <a href="mikrotik.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-network-wired"></i> PPPoE Data
        </a>
        <a href="genieacs.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-satellite-dish"></i> GenieACS
        </a>
        <a href="map.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-map-marked-alt"></i> Map
        </a>
        <a href="trouble.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-exclamation-triangle"></i> Issues
        </a>
        <a href="settings.php" class="btn btn-secondary" style="justify-content: center;">
            <i class="fas fa-cog"></i> Settings
        </a>
    </div>
</div>

<!-- Charts -->
<div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 20px;" id="charts-container">
    <!-- Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line"></i> Monthly Revenue</h3>
        </div>
        <canvas id="revenueChart" height="250"></canvas>
    </div>

    <!-- Customer Growth Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar"></i> New Customers</h3>
        </div>
        <canvas id="customerChart" height="250"></canvas>
    </div>
</div>

<!-- Recent Invoices -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> Recent Invoices</h3>
        <a href="invoices.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#Invoice</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentInvoices)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                        No invoices yet
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentInvoices as $invoice): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($invoice['invoice_number']); ?></code></td>
                        <td><?php echo htmlspecialchars($invoice['customer_name'] ?? '-'); ?></td>
                        <td><?php echo formatCurrency($invoice['amount']); ?></td>
                        <td>
                            <?php if ($invoice['status'] === 'paid'): ?>
                                <span class="badge badge-success">Paid</span>
                            <?php elseif ($invoice['status'] === 'unpaid'): ?>
                                <span class="badge badge-warning">Unpaid</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($invoice['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Recent Customers -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> Recent Customers</h3>
        <a href="customers.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>PPPoE</th>
                <th>Package</th>
                <th>Status</th>
                <th>Registered</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentCustomers)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                        No customers yet
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentCustomers as $customer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td><code><?php echo htmlspecialchars($customer['pppoe_username']); ?></code></td>
                        <td><?php echo htmlspecialchars($customer['package_name'] ?? '-'); ?></td>
                        <td>
                            <?php if ($customer['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Isolated</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($customer['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthlyRevenue, 'month')); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode(array_column($monthlyRevenue, 'revenue')); ?>,
                borderColor: '#00f5ff',
                backgroundColor: 'rgba(0, 245, 255, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        },
                        color: '#9ca3af'
                    },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                },
                x: {
                    ticks: { color: '#9ca3af' },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                }
            }
        }
    });

    // Customer Chart
    const customerCtx = document.getElementById('customerChart').getContext('2d');
    new Chart(customerCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($monthlyCustomers, 'month')); ?>,
            datasets: [{
                label: 'New Customers',
                data: <?php echo json_encode(array_column($monthlyCustomers, 'count')); ?>,
                backgroundColor: 'rgba(191, 0, 255, 0.5)',
                borderColor: '#bf00ff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#9ca3af', stepSize: 1 },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                },
                x: {
                    ticks: { color: '#9ca3af' },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                }
            }
        }
    });
</script>

<!-- Traffic Monitor & Hotspot Log Scripts -->
<script>
    // ==================== TRAFFIC MONITOR ====================
    const MAX_POINTS = 20;
    let trafficData = { labels: [], tx: [], rx: [] };
    let currentInterface = document.getElementById('interfaceSelector')?.value || 'ether1';
    let trafficChart;

    function formatBits(bits) {
        const sizes = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
        if (bits === 0) return '0 bps';
        const i = Math.floor(Math.log(bits) / Math.log(1024));
        return parseFloat((bits / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function initTrafficChart() {
        const ctx = document.getElementById('trafficChart');
        if (!ctx) return;
        trafficChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Tx (Upload)',
                    data: [],
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    borderWidth: 2,
                }, {
                    label: 'Rx (Download)',
                    data: [],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                animation: { duration: 300 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        labels: { color: '#9ca3af', usePointStyle: true, padding: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ' + formatBits(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (v) { return formatBits(v); },
                            color: '#9ca3af',
                            maxTicksLimit: 6
                        },
                        grid: { color: 'rgba(255,255,255,0.06)' }
                    },
                    x: {
                        ticks: { color: '#9ca3af', maxTicksLimit: 8, maxRotation: 0 },
                        grid: { color: 'rgba(255,255,255,0.06)' }
                    }
                }
            }
        });
    }

    function fetchTraffic() {
        fetch('../api/traffic.php?interface=' + encodeURIComponent(currentInterface))
            .then(r => r.json())
            .then(data => {
                if (!data || data.length < 2) return;
                const now = new Date();
                const label = now.getHours().toString().padStart(2, '0') + ':' +
                    now.getMinutes().toString().padStart(2, '0') + ':' +
                    now.getSeconds().toString().padStart(2, '0');

                const tx = parseInt(data[0].data) || 0;
                const rx = parseInt(data[1].data) || 0;

                trafficChart.data.labels.push(label);
                trafficChart.data.datasets[0].data.push(tx);
                trafficChart.data.datasets[1].data.push(rx);

                if (trafficChart.data.labels.length > MAX_POINTS) {
                    trafficChart.data.labels.shift();
                    trafficChart.data.datasets[0].data.shift();
                    trafficChart.data.datasets[1].data.shift();
                }

                trafficChart.update('none');
            })
            .catch(err => console.error('Traffic fetch error:', err));
    }

    function changeInterface(iface) {
        currentInterface = iface;
        // Reset chart data
        trafficChart.data.labels = [];
        trafficChart.data.datasets[0].data = [];
        trafficChart.data.datasets[1].data = [];
        trafficChart.update('none');
        fetchTraffic();
    }

    // ==================== HOTSPOT LOG ====================
    function loadHotspotLog() {
        fetch('../api/hotspot-log.php?limit=20')
            .then(r => r.json())
            .then(logs => {
                const tbody = document.getElementById('hotspotLogBody');
                if (!tbody) return;

                if (!logs || logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:var(--text-muted); padding:20px;"><i class="fas fa-info-circle"></i> No hotspot log entries</td></tr>';
                    return;
                }

                let html = '';
                logs.forEach(log => {
                    html += '<tr>' +
                        '<td style="white-space:nowrap;"><small>' + escapeHtml(log.time) + '</small></td>' +
                        '<td><small><strong>' + escapeHtml(log.user) + '</strong></small></td>' +
                        '<td><small>' + escapeHtml(log.message) + '</small></td>' +
                        '</tr>';
                });
                tbody.innerHTML = html;
            })
            .catch(err => {
                console.error('Hotspot log error:', err);
                const tbody = document.getElementById('hotspotLogBody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:var(--text-muted);"><i class="fas fa-exclamation-triangle"></i> Failed to load</td></tr>';
                }
            });
    }

    function escapeHtml(text) {
        if (!text) return '-';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ==================== INIT ====================
    document.addEventListener('DOMContentLoaded', function () {
        initTrafficChart();
        fetchTraffic();
        setInterval(fetchTraffic, 3000);

        loadHotspotLog();
        setInterval(loadHotspotLog, 10000);
    });
</script>

<style>
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-warning {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid var(--neon-orange);
        color: var(--neon-orange);
    }

    .isp-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .isp-stat-card {
        border-radius: 14px;
        padding: 16px 14px;
        text-align: center;
        color: white;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .isp-stat-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1.1;
        word-break: break-word;
    }

    .isp-stat-value-currency {
        font-size: 1.35rem;
    }

    .isp-stat-label {
        font-size: 0.9rem;
        margin-top: 8px;
        font-weight: 600;
    }

    /* Responsive: stack 2-column grid on mobile */
    @media (max-width: 768px) {
        #mikhmon-main-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
