<?php
/**
 * Print Vouchers
 */

require_once '../includes/auth.php';
requireSalesLogin();

// Get generated vouchers from session if available (for redirect from vouchers.php)
$vouchers = $_SESSION['generated_vouchers'] ?? [];

// Or get from GET parameter (for reprint from history)
// Format: username|username2
if (isset($_GET['users'])) {
    $usernames = explode('|', $_GET['users']);
    // We need to fetch details from Mikrotik or local sales records?
    // Since we don't store passwords in plain text in local DB (only in mikrotik), 
    // and we can't easily retrieve passwords from Mikrotik (only reset),
    // Reprinting might only show username/price/profile if password is lost.
    // However, for recent sales (hotspot_sales table), we recorded username, price, profile.
    // But password is not in hotspot_sales.
    
    // Check if we can reconstruct the voucher data from sales records
    $placeholders = implode(',', array_fill(0, count($usernames), '?'));
    $salesRecords = fetchAll("SELECT * FROM hotspot_sales WHERE username IN ($placeholders)", $usernames);
    
    $vouchers = [];
    foreach ($salesRecords as $rec) {
        // If password is user=pass or predictable, we might show it. 
        // But for security, reprinting usually implies just the ticket info.
        // For this simple system, let's assume we just show username and info.
        // Or if we want to show password, we need to have stored it or be able to read it.
        // Mikrotik API user print might return password if allowed.
        
        // Try to get from Mikrotik to be sure?
        // $mikrotikUser = mikrotikGetUser($rec['username']);
        // $password = $mikrotikUser['password'] ?? '******';
        
        $vouchers[] = [
            'username' => $rec['username'],
            'password' => $rec['username'], // Fallback: Assume U=P or hide it. 
                                            // In a real scenario, you'd fetch from Mikrotik if possible.
            'profile' => $rec['profile'],
            'price' => formatCurrency($rec['selling_price']),
            'validity' => '-' // Validity not stored in local sales table
        ];
    }
}

if (empty($vouchers)) {
    setFlash('error', 'No voucher to print.');
    redirect('dashboard.php');
}

// Clear session after use to prevent double print on refresh? 
// Better keep it until new ones generated or explicitly cleared.
// unset($_SESSION['generated_vouchers']);

$appName = APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Voucher</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 20px;
            background: #eee;
        }
        .page-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        .voucher-card {
            background: #fff;
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            page-break-inside: avoid;
        }
        .voucher-header {
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .voucher-body {
            font-size: 14px;
        }
        .voucher-code {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            border: 1px solid #ccc;
            padding: 5px;
            background: #f9f9f9;
        }
        .voucher-footer {
            font-size: 10px;
            margin-top: 5px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            .page-container { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;">Print Voucher</button>
        <a href="vouchers.php" style="padding: 10px 20px; text-decoration: none; background: #6c757d; color: white; border-radius: 5px;">Back</a>
    </div>

    <div class="page-container">
        <div class="voucher-grid">
            <?php foreach ($vouchers as $v): ?>
            <div class="voucher-card">
                <div class="voucher-header">
                    <?php echo $appName; ?><br>
                    <?php echo $v['profile']; ?>
                </div>
                <div class="voucher-body">
                    <div>Username:</div>
                    <div class="voucher-code"><?php echo $v['username']; ?></div>
                    
                    <?php if ($v['username'] !== $v['password']): ?>
                        <div>Password:</div>
                        <div class="voucher-code"><?php echo $v['password']; ?></div>
                    <?php endif; ?>
                    
                    <div style="font-weight: bold; margin-top: 5px;">
                        <?php echo $v['price']; ?>
                    </div>
                </div>
                <div class="voucher-footer">
                    Login: http://mikrotik.local<br>
                    <i>Gunakan kode di atas untuk login</i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
