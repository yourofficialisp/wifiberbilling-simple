<?php
/**
 * Hotspot Sales Report
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Hotspot Sales Report';

// Handle filters
$date_from = sanitize($_GET['date_from'] ?? date('Y-m-d'));
$date_to = sanitize($_GET['date_to'] ?? date('Y-m-d'));
$profile_filter = sanitize($_GET['profile'] ?? 'all');
$sales_user_filter = sanitize($_GET['sales_user'] ?? 'all');

$where = "DATE(h.created_at) BETWEEN ? AND ?";
$params = [$date_from, $date_to];

if ($profile_filter !== 'all') {
    $where .= " AND h.profile = ?";
    $params[] = $profile_filter;
}

if ($sales_user_filter !== 'all') {
    if ($sales_user_filter === 'admin') {
        $where .= " AND h.sales_user_id IS NULL";
    } else {
        $where .= " AND h.sales_user_id = ?";
        $params[] = $sales_user_filter;
    }
}

// Get Sales Data
$sql = "SELECT h.*, s.name as sales_name 
        FROM hotspot_sales h 
        LEFT JOIN sales_users s ON h.sales_user_id = s.id 
        WHERE $where 
        ORDER BY h.created_at DESC";

$sales = fetchAll($sql, $params);

// Get Profileeeeeeeeeeees for filter
$profiles = fetchAll("SELECT DISTINCT profile FROM hotspot_sales");

// Get Sales Users for filter
$salesUsers = fetchAll("SELECT id, name FROM sales_users ORDER BY name ASC");

// Statistics
$total_count = count($sales);
$total_income = array_sum(array_column($sales, 'price'));
$total_profit = array_sum(array_column($sales, 'selling_price'));

ob_start();
?>

<!-- Filter Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Report Filter</h3>
    </div>
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee</label>
                <select name="profile" class="form-control">
                    <option value="all" <?php echo $profile_filter === 'all' ? 'selected' : ''; ?>>All Profileeeeeeeeeeees</option>
                    <?php foreach ($profiles as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['profile']); ?>" <?php echo $profile_filter === $p['profile'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['profile']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Sales / Reseller</label>
                <select name="sales_user" class="form-control">
                    <option value="all" <?php echo $sales_user_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="admin" <?php echo $sales_user_filter === 'admin' ? 'selected' : ''; ?>>Admin (Direct)</option>
                    <?php foreach ($salesUsers as $su): ?>
                        <option value="<?php echo $su['id']; ?>" <?php echo $sales_user_filter == $su['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($su['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-search"></i>
                    Search</button>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <div class="card" style="margin-bottom: 0;">
        <div style="padding: 20px; text-align: center;">
            <div style="font-size: 2rem; color: var(--neon-purple); margin-bottom: 10px;"><i class="fas fa-ticket-alt"></i></div>
            <h3 style="margin-bottom: 5px;"><?php echo $total_count; ?></h3>
            <p style="color: var(--text-muted);">Vouchers Sold</p>
        </div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="padding: 20px; text-align: center;">
            <div style="font-size: 2rem; color: var(--neon-green); margin-bottom: 10px;"><i class="fas fa-money-bill-wave"></i></div>
            <h3 style="margin-bottom: 5px;"><?php echo formatCurrency($total_income); ?></h3>
            <p style="color: var(--text-muted);">Total Base Cost</p>
        </div>
    </div>
    <div class="card" style="margin-bottom: 0;">
        <div style="padding: 20px; text-align: center;">
            <div style="font-size: 2rem; color: var(--neon-cyan); margin-bottom: 10px;"><i class="fas fa-chart-line"></i></div>
            <h3 style="margin-bottom: 5px;"><?php echo formatCurrency($total_profit); ?></h3>
            <p style="color: var(--text-muted);">Total Revenue</p>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title"><i class="fas fa-list"></i> Sales Details</h3>
        <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 15%;">Time</th>
                        <th style="width: 15%;">Sales / Reseller</th>
                        <th style="width: 20%;">Username</th>
                        <th style="width: 15%;">Profileeeeeeeeeeee</th>
                        <th style="width: 15%; text-align: right;">Base Price</th>
                        <th style="width: 15%; text-align: right;">Selling Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">No sales data for this period</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                            <tr>
                                <td style="padding: 15px; vertical-align: middle;"><?php echo date('d/m/Y H:i', strtotime($s['created_at'])); ?></td>
                                <td style="padding: 15px; vertical-align: middle;">
                                    <?php if ($s['sales_name']): ?>
                                        <span class="badge badge-warning" style="padding: 8px 12px; font-size: 0.9em;"><?php echo htmlspecialchars($s['sales_name']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary" style="padding: 8px 12px; font-size: 0.9em;">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; vertical-align: middle;"><strong><?php echo htmlspecialchars($s['username']); ?></strong></td>
                                <td style="padding: 15px; vertical-align: middle;"><span class="badge badge-info" style="padding: 8px 12px; font-size: 0.9em;"><?php echo htmlspecialchars($s['profile']); ?></span></td>
                                <td style="padding: 15px; vertical-align: middle; text-align: right;"><?php echo formatCurrency($s['price']); ?></td>
                                <td style="padding: 15px; vertical-align: middle; text-align: right;"><strong class="text-success"><?php echo formatCurrency($s['selling_price']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>
