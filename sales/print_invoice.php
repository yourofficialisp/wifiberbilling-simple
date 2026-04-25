<?php
/**
 * Print Invoice
 */

require_once '../includes/auth.php';
requireSalesLogin();

$salesId = $_SESSION['sales']['id'];
$salesUser = getSalesUser($salesId);

// Get Invoice IDs from URL (comma separated)
$invoiceIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
$invoiceIds = array_map('intval', $invoiceIds);

if (empty($invoiceIds)) {
    die("Invoice ID invalid.");
}

// Fetch Invoices
$placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
$invoices = fetchAll("SELECT i.*, c.name as customer_name, c.address, c.pppoe_username, p.name as package_name 
    FROM invoices i 
    JOIN customers c ON i.customer_id = c.id 
    JOIN packages p ON c.package_id = p.id 
    WHERE i.id IN ($placeholders)", $invoiceIds);

if (empty($invoices)) {
    die("Invoice not found.");
}

$customer = [
    'name' => $invoices[0]['customer_name'],
    'address' => $invoices[0]['address'],
    'pppoe_username' => $invoices[0]['pppoe_username']
];

$totalAmount = 0;
foreach ($invoices as $inv) {
    $totalAmount += $inv['amount'];
}

// App Settings
$appName = APP_NAME;
$appUrl = APP_URL;
$qrUrl = rtrim((string) $appUrl, '/') . '/sales/print_invoice.php?ids=' . rawurlencode(implode(',', $invoiceIds));
$managerName = trim((string) getSetting('invoice_manager_name', ''));
$isThermal = isset($_GET['thermal']) && (string) $_GET['thermal'] === '1';
$basePrintUrl = 'print_invoice.php?ids=' . rawurlencode(implode(',', $invoiceIds));
$thermalUrl = $basePrintUrl . '&thermal=1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Invoice - <?php echo $customer['name']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: <?php echo $isThermal ? '12px' : '14px'; ?>;
            margin: 0;
            padding: 20px;
            background: #fff;
            color: #000;
        }
        .invoice-box {
            max-width: <?php echo $isThermal ? '58mm' : '800px'; ?>;
            margin: auto;
            border: 1px solid #eee;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .header h2 { margin: 0; }
        .details {
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
        }
        .signatures {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
        }
        .sign-box {
            width: 220px;
            text-align: center;
            font-size: 12px;
        }
        .sign-space {
            height: 12px;
        }
        .sign-qr img {
            width: 90px;
            height: 90px;
            border: 1px solid #eee;
            padding: 4px;
            display: inline-block;
        }
        .qr-wrap {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 10px;
        }
        .qr-box {
            text-align: center;
            min-width: 140px;
        }
        .qr-box img {
            width: 140px;
            height: 140px;
            border: 1px solid #eee;
            padding: 6px;
        }
        .qr-caption {
            font-size: 11px;
            margin-top: 6px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .invoice-box { border: none; }
        }
        <?php if ($isThermal): ?>
        @page { size: 58mm auto; margin: 6mm; }
        body { padding: 0; }
        .invoice-box { padding: 10px; }
        .qr-wrap { flex-direction: column; gap: 10px; }
        .qr-box { min-width: 0; text-align: left; }
        .qr-box img { width: 120px; height: 120px; }
        .details table { width: 100%; }
        .table, .table thead { display: none; }
        .thermal-lines { display: block; }
        .line { display: flex; justify-content: space-between; gap: 10px; margin: 4px 0; }
        .line strong { font-weight: 700; }
        .muted { color: #444; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        .signatures { justify-content: center; }
        .sign-box { width: 100%; }
        <?php else: ?>
        .thermal-lines { display: none; }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Invoice</button>
        <a href="<?php echo htmlspecialchars($basePrintUrl); ?>" style="display: inline-block; padding: 10px 20px; cursor: pointer; text-decoration: none; color: #000; border: 1px solid #ccc; background: #f3f3f3;">Mode Normal</a>
        <a href="<?php echo htmlspecialchars($thermalUrl); ?>" style="display: inline-block; padding: 10px 20px; cursor: pointer; text-decoration: none; color: #000; border: 1px solid #ccc; background: #f3f3f3;">Mode Thermal 58mm</a>
        <button onclick="window.history.back()" style="padding: 10px 20px; cursor: pointer;">Back</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <h2><?php echo $appName; ?></h2>
            <p>Bukti Bill Payment Internet</p>
        </div>

        <div class="qr-wrap">
            <div class="details" style="flex: 1;">
            <table>
                <tr>
                    <td width="100">Nama</td>
                    <td>: <?php echo htmlspecialchars($customer['name']); ?></td>
                </tr>
                <tr>
                    <td>ID Customer</td>
                    <td>: <?php echo htmlspecialchars($customer['pppoe_username']); ?></td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>: <?php echo htmlspecialchars($customer['address']); ?></td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td>: <?php echo htmlspecialchars($salesUser['name']); ?></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td>: <?php echo date('d/m/Y H:i'); ?></td>
                </tr>
            </table>
        </div>
            <div class="qr-box">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?php echo rawurlencode($qrUrl); ?>" alt="QR Invoice">
                <div class="qr-caption">Scan untuk cek/print cepat</div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Description</th>
                    <th>Period</th>
                    <th style="text-align: right;">Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><?php echo $inv['invoice_number']; ?></td>
                    <td><?php echo $inv['package_name']; ?></td>
                    <td><?php echo formatDate($inv['due_date'], 'M Y'); ?></td>
                    <td style="text-align: right;"><?php echo formatCurrency($inv['amount']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="thermal-lines">
            <div class="divider"></div>
            <?php foreach ($invoices as $inv): ?>
                <div class="line"><span class="muted">Invoice</span><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></div>
                <div class="line"><span class="muted">Paket</span><span><?php echo htmlspecialchars($inv['package_name']); ?></span></div>
                <div class="line"><span class="muted">Period</span><span><?php echo htmlspecialchars(formatDate($inv['due_date'], 'M Y')); ?></span></div>
                <div class="line"><span class="muted">Quantity</span><strong><?php echo htmlspecialchars(formatCurrency($inv['amount'])); ?></strong></div>
                <div class="divider"></div>
            <?php endforeach; ?>
        </div>

        <div class="total">
            Total Pay: <?php echo formatCurrency($totalAmount); ?>
        </div>

        <?php if ($managerName !== ''): ?>
        <div class="signatures">
            <div class="sign-box">
                <div>Mengetahui,</div>
                <div class="sign-space"></div>
                <div class="sign-qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo rawurlencode('Manager: ' . $managerName); ?>" alt="QR Manager">
                </div>
                <div><strong><?php echo htmlspecialchars($managerName); ?></strong></div>
                <div>Manager</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Thank you atas pembayaran You.</p>
            <p>Save struk ini sebagai bukti pembayaran yang sah.</p>
        </div>
    </div>
    
    <script>
        // Auto print on load
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
