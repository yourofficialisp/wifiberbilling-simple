<?php
/**
 * Sales History
 */

require_once '../includes/auth.php';
requireSalesLogin();

$pageTitle = 'Transaction History';
$salesId = $_SESSION['sales']['id'];

// Get Transactions
$transactions = fetchAll("SELECT * FROM sales_transactions WHERE sales_user_id = ? ORDER BY created_at DESC LIMIT 50", [$salesId]);

// Get Voucher Sales
$voucherSales = fetchAll("SELECT * FROM hotspot_sales WHERE sales_user_id = ? ORDER BY created_at DESC LIMIT 50", [$salesId]);

// Filters for bill payment history
$dateFrom = sanitize($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = sanitize($_GET['date_to'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $dateFrom = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $dateTo = date('Y-m-d');
}

// Get Bill Payments (Invoices paid by this sales)
// Since we don't store sales_user_id in invoices directly (we only used payment_method='sales_deposit'),
// we rely on the sales_transactions table to find related payments.
// However, to make it easier to display, let's query invoices linked to sales transactions.
// Or better, let's fetch from sales_transactions where type='bill_payment' and join with invoices if possible?
// Actually, sales_transactions doesn't have invoice_id.
// But we can query invoices that were created by this sales logic.
// For now, let's use a workaround:
// We'll add a new column `sales_user_id` to `invoices` table to track who processed the payment?
// Or we can just list the transactions that are type='bill_payment' in a separate table below.

// Let's create a dedicated section for "Payment History Bill" using sales_transactions
$billPayments = fetchAll("SELECT st.*, c.id as customer_id, c.name as customer_name, c.pppoe_username,
    (
        SELECT i.id
        FROM invoices i
        WHERE i.customer_id = c.id
        AND ABS(TIMESTAMPDIFF(SECOND, i.created_at, st.created_at)) < 300
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, i.created_at, st.created_at)) ASC
        LIMIT 1
    ) as invoice_id
    FROM sales_transactions st 
    LEFT JOIN customers c ON st.related_username = c.pppoe_username
    WHERE st.sales_user_id = ? AND st.type = 'bill_payment'
    AND DATE(st.created_at) BETWEEN ? AND ?
    ORDER BY st.created_at DESC LIMIT 200", [$salesId, $dateFrom, $dateTo]);

$billSummary = fetchOne("
    SELECT COUNT(*) as total_count, COALESCE(SUM(ABS(amount)), 0) as total_amount
    FROM sales_transactions
    WHERE sales_user_id = ?
    AND type = 'bill_payment'
    AND DATE(created_at) BETWEEN ? AND ?
", [$salesId, $dateFrom, $dateTo]);
$billPaymentCount = (int) ($billSummary['total_count'] ?? 0);
$billPaymentTotal = (float) ($billSummary['total_amount'] ?? 0);

ob_start();
?>

<div class="row" style="display: flex; flex-direction: column; gap: 20px;">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Filter Bill Payment</h3>
            </div>
            <div class="card-body">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-search"></i> Show</button>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <a href="history.php" class="btn btn-secondary" style="width: 100%;"><i class="fas fa-undo"></i> Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
            <div class="card" style="margin-bottom: 0;">
                <div style="padding: 18px;">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">Bill Payment</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--neon-cyan);"><?php echo $billPaymentCount; ?></div>
                    <div style="color: var(--text-muted); font-size: 0.85rem;">Period <?php echo htmlspecialchars($dateFrom); ?> to <?php echo htmlspecialchars($dateTo); ?></div>
                </div>
            </div>
            <div class="card" style="margin-bottom: 0;">
                <div style="padding: 18px;">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">Total Payment</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--neon-green);"><?php echo formatCurrency($billPaymentTotal); ?></div>
                    <div style="color: var(--text-muted); font-size: 0.85rem;">Excluding voucher sales</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill Payment History -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Payment History Bill</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover history-table" id="billTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Description</th>
                                <th>Total Pay</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($billPayments)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; color: var(--text-muted);">No bill payment history.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($billPayments as $b): ?>
                                    <tr>
                                        <td>
                                            <div class="history-date"><?php echo formatDate($b['created_at'], 'd M Y'); ?></div>
                                            <small class="text-muted"><?php echo formatDate($b['created_at'], 'H:i'); ?></small>
                                        </td>
                                        <td>
                                            <div class="history-customer"><?php echo htmlspecialchars($b['customer_name'] ?? $b['related_username']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($b['related_username']); ?></small>
                                        </td>
                                        <td>
                                            <div class="history-desc"><?php echo htmlspecialchars($b['description']); ?></div>
                                        </td>
                                        <td>
                                            <span class="history-amount"><?php echo formatCurrency(abs($b['amount'])); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($b['invoice_id'])): ?>
                                                <a href="print_invoice.php?ids=<?php echo (int) $b['invoice_id']; ?>" target="_blank" class="btn btn-sm btn-info" title="Print Struk">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Voucher History -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ticket-alt"></i> History Penjualan Voucher</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="voucherTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Username</th>
                                <th>Profileeeeeeeeeeee</th>
                                <th>Selling Price</th>
                                <th>Modal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($voucherSales as $v): ?>
                                <tr>
                                    <td><?php echo $v['created_at']; ?></td>
                                    <td><?php echo htmlspecialchars($v['username']); ?></td>
                                    <td><?php echo htmlspecialchars($v['profile']); ?></td>
                                    <td><?php echo formatCurrency($v['selling_price']); ?></td>
                                    <td><?php echo formatCurrency($v['price']); ?></td>
                                    <td>
                                        <a href="print_voucher.php?users=<?php echo $v['username']; ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.history-table th,
.history-table td {
    vertical-align: middle;
}

.history-date {
    font-weight: 600;
}

.history-customer {
    font-weight: 700;
    color: var(--text-primary);
}

.history-desc {
    color: var(--text-secondary);
    max-width: 360px;
    white-space: normal;
    line-height: 1.4;
}

.history-amount {
    display: inline-block;
    color: #10b981;
    font-weight: 700;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.25);
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .history-desc {
        min-width: 180px;
    }
}
</style>

<script>
    // Initialize SimpleDatatables if available
    document.addEventListener('DOMContentLoaded', () => {
        const billTable = document.getElementById('billTable');
        if (billTable) {
            new simpleDatatables.DataTable(billTable);
        }
        const voucherTable = document.getElementById('voucherTable');
        if (voucherTable) {
            new simpleDatatables.DataTable(voucherTable);
        }
    });
</script>

<?php
$content = ob_get_clean();
require_once '../includes/sales_layout.php';
?>
