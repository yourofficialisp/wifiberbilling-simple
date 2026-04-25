<?php
require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Public Voucher Orders';

ensurePublicVoucherTables();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('voucher-orders.php');
    }

    $action = $_POST['action'] ?? '';
    $orderNumber = sanitizePublicVoucherOrderNumber($_POST['order_number'] ?? '');

    if ($orderNumber === '') {
        setFlash('error', 'Invalid order number');
        redirect('voucher-orders.php');
    }

    if ($action === 'sync_payment') {
        $order = syncPublicVoucherOrderPaymentStatus($orderNumber);
        if ($order) {
            setFlash('success', 'Payment status synchronised successfully');
        } else {
            setFlash('error', 'Failed to synchronise payment status');
        }
        redirect('voucher-orders.php');
    }

    if ($action === 'process_voucher') {
        $result = fulfillPublicVoucherOrder($orderNumber);
        if ($result['success'] ?? false) {
            setFlash('success', $result['message'] ?? 'Voucher processed successfully');
            logActivity('PUBLIC_VOUCHER_PROCESS', 'Order: ' . $orderNumber);
        } else {
            setFlash('error', $result['message'] ?? 'Voucher processing failed');
        }
        redirect('voucher-orders.php');
    }

    if ($action === 'resend_whatsapp') {
        $order = getPublicVoucherOrderByNumber($orderNumber);
        if (!$order) {
            setFlash('error', 'Order not found');
            redirect('voucher-orders.php');
        }
        if (empty($order['voucher_username']) || empty($order['voucher_password'])) {
            setFlash('error', 'Voucher not yet available for this order');
            redirect('voucher-orders.php');
        }
        $sent = sendPublicVoucherWhatsapp($order);
        update('hotspot_voucher_orders', [
            'whatsapp_status' => $sent ? 'sent' : 'failed',
            'whatsapp_sent_at' => $sent ? date('Y-m-d H:i:s') : null
        ], 'order_number = ?', [$orderNumber]);
        if ($sent) {
            setFlash('success', 'Voucher resent to WhatsApp successfully');
            logActivity('PUBLIC_VOUCHER_RESEND_WA', 'Order: ' . $orderNumber);
        } else {
            setFlash('error', 'Failed to resend voucher to WhatsApp');
        }
        redirect('voucher-orders.php');
    }
}

$statusFilter = strtolower(trim((string) ($_GET['status'] ?? '')));
$keyword = trim((string) ($_GET['q'] ?? ''));
$allowedStatus = ['pending', 'paid', 'failed', 'expired'];
$where = [];
$params = [];

if (in_array($statusFilter, $allowedStatus, true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
if ($keyword !== '') {
    $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR voucher_username LIKE ?)';
    $like = '%' . $keyword . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = "SELECT * FROM hotspot_voucher_orders";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY id DESC LIMIT 300";
$orders = fetchAll($sql, $params);

$statsRows = fetchAll("SELECT status, COUNT(*) total FROM hotspot_voucher_orders GROUP BY status");
$stats = ['pending' => 0, 'paid' => 0, 'failed' => 0, 'expired' => 0];
foreach ($statsRows as $row) {
    $key = strtolower((string) ($row['status'] ?? ''));
    if (isset($stats[$key])) {
        $stats[$key] = (int) ($row['total'] ?? 0);
    }
}

ob_start();
?>

<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
        <div class="stat-info"><h3><?php echo $stats['pending']; ?></h3><p>Pending</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><h3><?php echo $stats['paid']; ?></h3><p>Paid</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
        <div class="stat-info"><h3><?php echo $stats['failed']; ?></h3><p>Failed</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-hourglass-end"></i></div>
        <div class="stat-info"><h3><?php echo $stats['expired']; ?></h3><p>Expired</p></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Filter</h3>
    </div>
    <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 180px; margin-bottom: 0;">
            <label class="form-label">Status</label>
            <select class="form-control" name="status">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
            </select>
        </div>
        <div class="form-group" style="flex: 2; min-width: 220px; margin-bottom: 0;">
            <label class="form-label">Search</label>
            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Order, name, WA number, voucher">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
        <a href="voucher-orders.php" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</a>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Public Voucher Order List</h3>
    </div>
    <?php if (empty($orders)): ?>
        <p style="color: var(--text-muted);">No public voucher orders found.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Voucher</th>
                        <th>Payment</th>
                        <th>WhatsApp</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <div><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></div>
                                <div style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($order['profile_name']); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($order['voucher_username'] ?: '-'); ?></div>
                                <div style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($order['voucher_password'] ?: '-'); ?></div>
                            </td>
                            <td>
                                <div><?php echo strtoupper(htmlspecialchars($order['status'])); ?></div>
                                <div style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars(formatCurrency($order['amount'])); ?></div>
                            </td>
                            <td>
                                <div><?php echo strtoupper(htmlspecialchars($order['whatsapp_status'])); ?></div>
                                <div style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($order['whatsapp_sent_at'] ?: '-'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <a href="<?php echo APP_URL; ?>/voucher-status.php?order=<?php echo urlencode($order['order_number']); ?>" target="_blank" class="btn btn-secondary btn-sm">Detail</a>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="sync_payment">
                                        <input type="hidden" name="order_number" value="<?php echo htmlspecialchars($order['order_number']); ?>">
                                        <button class="btn btn-primary btn-sm" type="submit">Sync</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="process_voucher">
                                        <input type="hidden" name="order_number" value="<?php echo htmlspecialchars($order['order_number']); ?>">
                                        <button class="btn btn-warning btn-sm" type="submit">Process</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="resend_whatsapp">
                                        <input type="hidden" name="order_number" value="<?php echo htmlspecialchars($order['order_number']); ?>">
                                        <button class="btn btn-success btn-sm" type="submit">Resend WA</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
