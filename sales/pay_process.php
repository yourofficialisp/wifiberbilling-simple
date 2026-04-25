<?php
/**
 * Sales - Process Payment
 */

require_once '../includes/auth.php';
requireSalesLogin();

$salesId = $_SESSION['sales']['id'];
$salesUser = getSalesUser($salesId);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);

if (!$customer) {
    setFlash('error', 'Customer not found.');
    redirect('pay.php');
}

$pageTitle = 'Pay: ' . $customer['name'];

// Get Customer Invoices (Unpaid)
$invoices = fetchAll("SELECT * FROM invoices WHERE customer_id = ? AND status = 'unpaid' ORDER BY created_at ASC", [$id]);

// Get Package Info
$package = fetchOne("SELECT * FROM packages WHERE id = ?", [$customer['package_id']]);

// Handle Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid session.');
        redirect("pay_process.php?id=$id");
    }

    $selectedMonths = $_POST['selected_months'] ?? [];
    $selectedYear = (int) $_POST['year'];

    if (empty($selectedMonths)) {
        setFlash('error', 'Select at least 1 month.');
        redirect("pay_process.php?id=$id&year=$selectedYear");
    }

    $monthsCount = count($selectedMonths);
    $amountPerMonth = $package['price'];
    $billDiscount = $salesUser['bill_discount'] ?? 0;
    
    // Calculate cost for Sales (Package Price - Discount)
    $costPerMonth = $amountPerMonth - $billDiscount;
    if ($costPerMonth < 0) $costPerMonth = 0; // Prevent negative cost

    $totalCost = $costPerMonth * $monthsCount;
    $totalBill = $amountPerMonth * $monthsCount;

    // Check Balance
    if ($salesUser['deposit_balance'] < $totalCost) {
        setFlash('error', 'Insufficient deposit balance. Total Cost: ' . formatCurrency($totalCost));
        redirect("pay_process.php?id=$id&year=$selectedYear");
    }

    $pdo = getDB();
    try {
        $pdo->beginTransaction();

        // Deduct Sales Balance
        $newBalance = $salesUser['deposit_balance'] - $totalCost;
        update('sales_users', ['deposit_balance' => $newBalance], 'id = ?', [$salesId]);

        $generatedInvoiceIds = [];
        foreach ($selectedMonths as $monthNum) {
            // Create specific due date for selected month/year
            // Assuming billing date is based on isolation_date or default 20th
            $day = isset($customer['isolation_date']) ? (int) $customer['isolation_date'] : 20;
            if ($day < 1) $day = 1;
            if ($day > 28) $day = 28; // Avoid invalid dates
            
            $dueDate = date('Y-m-d', strtotime("$selectedYear-$monthNum-$day"));
            $monthName = date('F', mktime(0, 0, 0, $monthNum, 10)); // Get month name

            // Check if invoice already exists for this month/year (and unpaid)
            $existingInvoice = fetchOne("SELECT id FROM invoices 
                WHERE customer_id = ? 
                AND MONTH(due_date) = ? 
                AND YEAR(due_date) = ? 
                AND status = 'unpaid'", 
                [$id, $monthNum, $selectedYear]);

            if ($existingInvoice) {
                // Update existing invoice instead of creating new one
                update('invoices', [
                    'status' => 'paid',
                    'paid_at' => date('Y-m-d H:i:s'),
                    'payment_method' => 'sales_deposit',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existingInvoice['id']]);
                
                $generatedInvoiceIds[] = $existingInvoice['id'];
            } else {
                // Create new invoice if not exists
                $invData = [
                    'invoice_number' => 'INV-' . date('ymd') . rand(1000,9999),
                    'customer_id' => $id,
                    'amount' => $amountPerMonth,
                    'status' => 'paid',
                    'due_date' => $dueDate,
                    'paid_at' => date('Y-m-d H:i:s'),
                    'payment_method' => 'sales_deposit',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $invId = insert('invoices', $invData);
                $generatedInvoiceIds[] = $invId;
            }
            
            // Record Sales Transaction
            insert('sales_transactions', [
                'sales_user_id' => $salesId,
                'type' => 'bill_payment',
                'amount' => -$costPerMonth,
                'description' => "Bill Payment {$customer['name']} (Period $monthName $selectedYear)",
                'related_username' => $customer['pppoe_username'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $pdo->commit();
        
        // Send Notification (Optional)
        if (function_exists('sendWhatsApp') && !empty($customer['phone'])) {
            $msg = "Payment Received\n\n";
            $msg .= "Name: {$customer['name']}\n";
            $msg .= "Total: " . formatCurrency($totalBill) . "\n";
            $msg .= "Period: $monthsCount months\n";
            $msg .= "Via: Sales Agent ({$salesUser['name']})\n\n";
            $msg .= "Thank you.";
            if (function_exists('getWhatsAppFooter')) {
                $msg .= getWhatsAppFooter();
            }
            sendWhatsApp($customer['phone'], $msg);
        }

        setFlash('success', "Payment successful for $monthsCount months.");
        
        // Redirect to Print Page
        $ids = implode(',', $generatedInvoiceIds);
        redirect("print_invoice.php?ids=$ids");

    } catch (Exception $e) {
        $pdo->rollBack();
        logError("Payment Error: " . $e->getMessage());
        setFlash('error', 'Failed to process payment.');
        redirect("pay_process.php?id=$id&year=$selectedYear");
    }
}

// Prepare UI Data
$currentYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$monthsList = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Fetch paid invoices for selected year to mark as paid
$paidInvoices = fetchAll("SELECT MONTH(due_date) as month_num FROM invoices 
    WHERE customer_id = ? AND YEAR(due_date) = ? AND status = 'paid'", [$id, $currentYear]);
$paidMonths = array_column($paidInvoices, 'month_num');

ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user"></i> Customer Details</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td width="150">Nama</td>
                        <td>: <strong><?php echo htmlspecialchars($customer['name']); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Paket</td>
                        <td>: <?php echo htmlspecialchars($package['name']); ?> (<?php echo formatCurrency($package['price']); ?>/bln)</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>: <?php echo htmlspecialchars($customer['address']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title"><i class="fas fa-calendar-check"></i> Select Payment Month</h3>
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <select name="year" class="form-control" onchange="this.form.submit()" style="width: 100px;">
                        <?php 
                        $startYear = date('Y') - 1;
                        $endYear = date('Y') + 2;
                        for($y=$startYear; $y<=$endYear; $y++): 
                        ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $currentYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
            <div class="card-body">
                <form method="POST" id="payForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="year" value="<?php echo $currentYear; ?>">
                    
                    <div class="months-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <?php foreach ($monthsList as $num => $name): ?>
                            <?php 
                                $isPaid = in_array($num, $paidMonths);
                                $isPast = ($currentYear < date('Y')) || ($currentYear == date('Y') && $num < date('n'));
                            ?>
                            <div class="month-item" style="
                                border: 1px solid var(--border-color); 
                                border-radius: 8px; 
                                padding: 15px; 
                                text-align: center; 
                                cursor: pointer;
                                background: <?php echo $isPaid ? 'rgba(0, 255, 136, 0.1)' : 'var(--bg-input)'; ?>;
                                border-color: <?php echo $isPaid ? 'var(--neon-green)' : 'var(--border-color)'; ?>;
                                position: relative;
                            " onclick="<?php echo $isPaid ? '' : "toggleMonth($num)"; ?>">
                                
                                <?php if ($isPaid): ?>
                                    <div style="position: absolute; top: 5px; right: 5px; color: var(--neon-green);">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <h5 style="margin: 0; color: var(--text-muted); text-decoration: line-through;"><?php echo $name; ?></h5>
                                    <small style="color: var(--neon-green); font-weight: bold;">PAID</small>
                                <?php else: ?>
                                    <input type="checkbox" name="selected_months[]" value="<?php echo $num; ?>" id="month_<?php echo $num; ?>" style="display: none;" onchange="updateTotal()">
                                    <h5 style="margin: 0; color: var(--text-primary);" id="label_<?php echo $num; ?>"><?php echo $name; ?></h5>
                                    <small style="color: var(--text-secondary);">Unpaid</small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="alert alert-info">
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 1.1rem; font-weight: 500;">Total Customer Bill:</span>
                                <span id="totalBillDisplay" style="font-size: 1.5rem; font-weight: bold; color: var(--neon-cyan);">Rp 0</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.95rem; color: var(--text-secondary);">Sales Cost (After Discount):</span>
                                    <small style="color: var(--neon-green);">Keuntungan: <span id="profitDisplay">Rp 0</span></small>
                                </div>
                                <span id="totalCostDisplay" style="font-size: 1.2rem; font-weight: bold; color: var(--text-primary);">Rp 0</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                <small style="color: var(--text-muted);">Your Remaining Balance:</small>
                                <small style="color: var(--text-primary); font-weight: 600;"><?php echo formatCurrency($salesUser['deposit_balance']); ?></small>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success" onclick="confirmPayment()" style="width: 100%; padding: 15px; font-size: 1.2rem;">
                        <i class="fas fa-check-circle"></i> Pay Selected Months
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history"></i> Recent Payment History</h3>
            </div>
            <div class="card-body">
                <?php
                // Show last 5 payments for this customer
                $history = fetchAll("SELECT * FROM invoices WHERE customer_id = ? AND status = 'paid' ORDER BY paid_at DESC LIMIT 5", [$id]);
                ?>
                <?php if (empty($history)): ?>
                    <p class="text-muted">No payment history yet.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($history as $h): ?>
                            <li class="list-group-item" style="display: flex; justify-content: space-between;">
                                <span><?php echo date('d/m/Y', strtotime($h['paid_at'])); ?></span>
                                <span class="text-success"><?php echo formatCurrency($h['amount']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const pricePerMonth = <?php echo $package['price']; ?>;
    const discount = <?php echo $salesUser['bill_discount'] ?? 0; ?>;
    
    function toggleMonth(num) {
        const checkbox = document.getElementById('month_' + num);
        const div = checkbox.parentElement;
        const label = document.getElementById('label_' + num);
        
        checkbox.checked = !checkbox.checked;
        
        if (checkbox.checked) {
            div.style.backgroundColor = 'rgba(0, 245, 255, 0.2)';
            div.style.borderColor = 'var(--neon-cyan)';
            label.style.color = 'var(--neon-cyan)';
        } else {
            div.style.backgroundColor = 'var(--bg-input)';
            div.style.borderColor = 'var(--border-color)';
            label.style.color = 'var(--text-primary)';
        }
        
        updateTotal();
    }
    
    function updateTotal() {
        const checkboxes = document.querySelectorAll('input[name="selected_months[]"]:checked');
        const count = checkboxes.length;
        
        const totalBill = pricePerMonth * count;
        const totalCost = Math.max(0, (pricePerMonth - discount) * count);
        const totalProfit = totalBill - totalCost;
        
        // Simple currency formatter
        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
        
        document.getElementById('totalBillDisplay').innerText = formatter.format(totalBill);
        document.getElementById('totalCostDisplay').innerText = formatter.format(totalCost);
        document.getElementById('profitDisplay').innerText = formatter.format(totalProfit);
    }

    function confirmPayment() {
        const checkboxes = document.querySelectorAll('input[name="selected_months[]"]:checked');
        if (checkboxes.length === 0) {
            alert('Select at least 1 month to pay.');
            return;
        }
        
        if (confirm('Are you sure you want to process this payment? Your deposit balance will be deducted.')) {
            document.getElementById('payForm').submit();
        }
    }
</script>

<?php
$content = ob_get_clean();
require_once '../includes/sales_layout.php';
?>
