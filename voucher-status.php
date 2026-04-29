<?php
if (!file_exists(__DIR__ . '/includes/installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ensurePublicVoucherTables();

$orderParam = $_GET['order'] ?? '';
$safeOrder = sanitizePublicVoucherOrderNumber($orderParam);
$appName = getSetting('app_name', 'GEMBOK');
$message = '';

if ($safeOrder === '') {
    $message = 'Invalid order number.';
    $order = null;
} else {
    if (isset($_GET['check']) && $_GET['check'] === '1') {
        syncPublicVoucherOrderPaymentStatus($safeOrder);
    }
    $order = getPublicVoucherOrderByNumber($safeOrder);
    if ($order && ($order['status'] ?? '') === 'paid' && (($order['fulfillment_status'] ?? '') !== 'success' || ($order['whatsapp_status'] ?? 'pending') !== 'sent')) {
        fulfillPublicVoucherOrder($safeOrder);
        $order = getPublicVoucherOrderByNumber($safeOrder);
    } elseif ($order && ($order['status'] ?? '') === 'pending') {
        $order = syncPublicVoucherOrderPaymentStatus($safeOrder);
    }
    if (!$order) {
        $message = 'Order not found.';
    }
}
$usePrettySetting = (string) getSetting('USE_PRETTY_URLS', '1') === '1';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$canPretty = $usePrettySetting && preg_match('~^/voucher(/|$)~', (string) $requestPath);
$orderFormUrl = rtrim(APP_URL, '/') . ($canPretty ? '/voucher' : '/voucher-order.php');
$statusOrderUrl = $safeOrder !== ''
    ? (rtrim(APP_URL, '/') . ($canPretty ? ('/voucher/status/' . rawurlencode($safeOrder)) : ('/voucher-status.php?order=' . rawurlencode($safeOrder))))
    : $orderFormUrl;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Voucher Status - <?php echo htmlspecialchars($appName); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; }
        .container { max-width: 860px; margin: 0 auto; padding: 24px; }
        .card { background: #111827; border: 1px solid #1f2937; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
        .title { font-size: 28px; margin: 0 0 8px; color: #67e8f9; }
        .meta { color: #94a3b8; margin: 0 0 16px; }
        .row { display: grid; grid-template-columns: 180px 1fr; gap: 10px; margin-bottom: 8px; }
        .row .k { color: #94a3b8; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .pending { background: #78350f; color: #fef3c7; }
        .paid { background: #14532d; color: #dcfce7; }
        .failed { background: #7f1d1d; color: #fecaca; }
        .btn { display: inline-block; text-decoration: none; border: 0; border-radius: 8px; padding: 11px 16px; font-size: 14px; font-weight: 600; cursor: pointer; margin-right: 8px; }
        .btn-primary { background: #67e8f9; color: #0f172a; }
        .btn-dark { background: #1e293b; color: #e2e8f0; border: 1px solid #334155; }
        .warn { background: #7c2d12; border: 1px solid #fb923c; color: #ffedd5; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .ok { background: #14532d; border: 1px solid #22c55e; color: #dcfce7; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .error { background: #7f1d1d; border: 1px solid #ef4444; color: #fecaca; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .voucher { background: #0b1220; border: 1px dashed #22d3ee; border-radius: 10px; padding: 14px; margin-top: 12px; }
        code { font-size: 16px; color: #67e8f9; }
        @media (max-width: 720px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1 class="title">Voucher Order Status</h1>
        <?php if ($message !== ''): ?>
            <div class="error"><?php echo htmlspecialchars($message); ?></div>
            <a class="btn btn-dark" href="<?php echo htmlspecialchars($orderFormUrl); ?>">Back to Order Form</a>
        <?php else: ?>
            <p class="meta">No Order: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
            <?php
                $statusClass = 'pending';
                if (($order['status'] ?? '') === 'paid') {
                    $statusClass = 'paid';
                } elseif (($order['status'] ?? '') === 'failed' || ($order['status'] ?? '') === 'expired') {
                    $statusClass = 'failed';
                }
            ?>
            <div class="row"><div class="k">Payment Status</div><div><span class="badge <?php echo $statusClass; ?>"><?php echo strtoupper(htmlspecialchars($order['status'])); ?></span></div></div>
            <div class="row"><div class="k">Name</div><div><?php echo htmlspecialchars($order['customer_name']); ?></div></div>
            <div class="row"><div class="k">WhatsApp Number</div><div><?php echo htmlspecialchars($order['customer_phone']); ?></div></div>
            <div class="row"><div class="k">Voucher Profile</div><div><?php echo htmlspecialchars($order['profile_name']); ?></div></div>
            <div class="row"><div class="k">Amount</div><div><?php echo htmlspecialchars(formatCurrency($order['amount'])); ?></div></div>
            <div class="row"><div class="k">Payment Gateway</div><div><?php echo strtoupper(htmlspecialchars($order['payment_gateway'])); ?></div></div>
            <?php if (!empty($order['payment_method'])): ?>
                <div class="row"><div class="k">Method</div><div><?php echo htmlspecialchars($order['payment_method']); ?></div></div>
            <?php endif; ?>
            <?php if (($order['status'] ?? '') === 'paid'): ?>
                <div class="row"><div class="k">Payment Date</div><div><?php echo htmlspecialchars($order['paid_at'] ?? '-'); ?></div></div>
            <?php endif; ?>

            <div style="margin-top: 14px;">
                <?php if (($order['status'] ?? '') === 'pending' && !empty($order['payment_link'])): ?>
                    <a class="btn btn-primary" href="<?php echo htmlspecialchars($order['payment_link']); ?>" target="_blank" rel="noopener noreferrer">Pay Now</a>
                    <a class="btn btn-dark" href="<?php echo htmlspecialchars($statusOrderUrl . '?check=1'); ?>">Check Payment Status</a>
                <?php elseif (($order['status'] ?? '') === 'paid' && ($order['whatsapp_status'] ?? 'pending') !== 'sent'): ?>
                    <a class="btn btn-dark" href="<?php echo htmlspecialchars($statusOrderUrl . '?check=1'); ?>">Resend WhatsApp</a>
                <?php elseif (($order['status'] ?? '') === 'failed' || ($order['status'] ?? '') === 'expired'): ?>
                    <div class="warn">Payment was not successful or expired. Please create a new order.</div>
                    <a class="btn btn-dark" href="<?php echo htmlspecialchars($orderFormUrl); ?>">Create New Order</a>
                <?php endif; ?>
            </div>

            <?php if (($order['status'] ?? '') === 'paid' && ($order['fulfillment_status'] ?? '') !== 'success'): ?>
                <div class="warn">Payment has been received, voucher is still being processed. Please refresh this page.</div>
            <?php endif; ?>

            <?php if (!empty($order['voucher_username']) && !empty($order['voucher_password'])): ?>
                <div class="voucher">
                    <div class="row"><div class="k">Voucher Username</div><div><code><?php echo htmlspecialchars($order['voucher_username']); ?></code></div></div>
                    <div class="row"><div class="k">Voucher Password</div><div><code><?php echo htmlspecialchars($order['voucher_password']); ?></code></div></div>
                    <div class="row"><div class="k">WhatsApp Status</div><div><?php echo strtoupper(htmlspecialchars($order['whatsapp_status'] ?? 'pending')); ?></div></div>
                </div>
                <div class="ok" style="margin-top: 12px;">Voucher successfully created and displayed on this page.</div>
            <?php endif; ?>

            <div style="margin-top: 12px;">
                <a class="btn btn-dark" href="<?php echo htmlspecialchars($orderFormUrl); ?>">Order Another Voucher</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($order) && ($order['status'] ?? '') === 'pending'): ?>
<script>
setTimeout(function () {
    window.location.href = '<?php echo htmlspecialchars($statusOrderUrl . '?check=1', ENT_QUOTES, 'UTF-8'); ?>';
}, 15000);
</script>
<?php endif; ?>
</body>
</html>
