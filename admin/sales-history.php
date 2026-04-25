<?php
/**
 * Sales Transaction History (Admin View)
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Sales Transaction History';

// Filters
$salesId = isset($_GET['sales_id']) ? (int)$_GET['sales_id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build Query
$where = "WHERE DATE(st.created_at) BETWEEN ? AND ?";
$params = [$startDate, $endDate];

if ($salesId > 0) {
    $where .= " AND st.sales_user_id = ?";
    $params[] = $salesId;
}

if (!empty($type)) {
    $where .= " AND st.type = ?";
    $params[] = $type;
}

// Get Transactions
$query = "SELECT st.*, s.name as sales_name, s.username as sales_username 
          FROM sales_transactions st 
          JOIN sales_users s ON st.sales_user_id = s.id 
          $where 
          ORDER BY st.created_at DESC";

$transactions = fetchAll($query, $params);

// Get Sales Users for Filter
$salesUsers = fetchAll("SELECT id, name FROM sales_users ORDER BY name");

// Calculate Totals
$totalDeposit = 0;
$totalSales = 0;

foreach ($transactions as $t) {
    if ($t['type'] === 'deposit') {
        $totalDeposit += $t['amount'];
    } elseif ($t['amount'] < 0) {
        $totalSales += abs($t['amount']);
    }
}

ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filter Data</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label class="form-label">Sales Agent</label>
                <select name="sales_id" class="form-control">
                    <option value="">-- All Sales Agents --</option>
                    <?php foreach ($salesUsers as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $salesId == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label class="form-label">Transaction Type</label>
                <select name="type" class="form-control">
                    <option value="">-- All Types --</option>
                    <option value="deposit" <?php echo $type === 'deposit' ? 'selected' : ''; ?>>Deposit (Topup)</option>
                    <option value="voucher_sale" <?php echo $type === 'voucher_sale' ? 'selected' : ''; ?>>Voucher Sale</option>
                    <option value="bill_payment" <?php echo $type === 'bill_payment' ? 'selected' : ''; ?>>Bill Payment</option>
                    <option value="adjustment" <?php echo $type === 'adjustment' ? 'selected' : ''; ?>>Balance Adjustment</option>
                </select>
            </div>

            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label class="form-label">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
            </div>

            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label class="form-label">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="sales-history.php" class="btn btn-secondary" title="Reset Filter">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
    <div class="col-md-6" style="flex: 1; min-width: 300px;">
        <div class="stat-card" style="background: rgba(0, 255, 136, 0.1); border: 1px solid var(--neon-green); padding: 20px; border-radius: 10px; display: flex; align-items: center; gap: 15px;">
            <div style="font-size: 2rem; color: var(--neon-green);"><i class="fas fa-wallet"></i></div>
            <div>
                <h4 style="margin: 0; color: var(--text-secondary);">Total Deposits Received</h4>
                <h2 style="margin: 0; color: var(--text-primary);"><?php echo formatCurrency($totalDeposit); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6" style="flex: 1; min-width: 300px;">
        <div class="stat-card" style="background: rgba(0, 245, 255, 0.1); border: 1px solid var(--neon-cyan); padding: 20px; border-radius: 10px; display: flex; align-items: center; gap: 15px;">
            <div style="font-size: 2rem; color: var(--neon-cyan);"><i class="fas fa-shopping-cart"></i></div>
            <div>
                <h4 style="margin: 0; color: var(--text-secondary);">Total Sales Transactions</h4>
                <h2 style="margin: 0; color: var(--text-primary);"><?php echo formatCurrency($totalSales); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> Transaction Data</h3>
        <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Sales Agent</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                No transaction data for this period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($t['sales_name']); ?></strong><br>
                                <small class="text-muted">@<?php echo htmlspecialchars($t['sales_username']); ?></small>
                            </td>
                            <td>
                                <?php 
                                    $badges = [
                                        'deposit' => 'badge-success',
                                        'voucher_sale' => 'badge-info',
                                        'bill_payment' => 'badge-warning',
                                        'adjustment' => 'badge-danger'
                                    ];
                                    $labels = [
                                        'deposit' => 'Deposit Topup',
                                        'voucher_sale' => 'Voucher Sale',
                                        'bill_payment' => 'Bill Payment',
                                        'adjustment' => 'Adjustment'
                                    ];
                                    $typeKey = $t['type'] ?? 'adjustment';
                                ?>
                                <span class="badge <?php echo $badges[$typeKey] ?? 'badge-secondary'; ?>">
                                    <?php echo $labels[$typeKey] ?? ucfirst($typeKey); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($t['description']); ?>
                                <?php if ($t['related_username']): ?>
                                    <br><small class="text-muted">Ref: <?php echo htmlspecialchars($t['related_username']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: bold; color: <?php echo $t['amount'] >= 0 ? 'var(--neon-green)' : 'var(--neon-red)'; ?>;">
                                <?php echo ($t['amount'] > 0 ? '+' : '') . formatCurrency($t['amount']); ?>
                            </td>
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
