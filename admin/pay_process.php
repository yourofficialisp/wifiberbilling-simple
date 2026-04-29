<?php
/**
 * Admin - Process Payment
 */

require_once '../includes/auth.php';
requireAdminLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);

if (!$customer) {
    setFlash('error', 'Customer not found.');
    redirect('customers.php');
}

$pageTitle = 'Pay Bill: ' . $customer['name'];

// Get Package Info
$package = fetchOne("SELECT * FROM packages WHERE id = ?", [$customer['package_id']]);

// Handle Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Session is invalid.');
        redirect("pay_process.php?id=$id");
    }

    $selectedMonths = $_POST['selected_months'] ?? [];
    $selectedYear = (int) $_POST['year'];

    if (empty($selectedMonths)) {
        setFlash('error', 'Select minimum 1 month.');
        redirect("pay_process.php?id=$id&year=$selectedYear");
    }

    $monthsCount = count($selectedMonths);
    $amountPerMonth = $package['price'];
    $totalBill = $amountPerMonth * $monthsCount;

    $pdo = getDB();
    try {
        $pdo->beginTransaction();

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
                    'payment_method' => 'manual_admin',
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
                    'payment_method' => 'manual_admin',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $invId = insert('invoices', $invData);
                $generatedInvoiceIds[] = $invId;
            }
        }

        // Unisolate customer if they were isolated
        if ($customer['status'] === 'isolated') {
            unisolateCustomer($id);
        }

        $pdo->commit();
        
        // Send Notification (Optional)
        if (function_exists('sendWhatsApp') && !empty($customer['phone'])) {
            $msg = "Payment Received\n\n";
            $msg .= "Name: {$customer['name']}\n";
            $msg .= "Total: " . formatCurrency($totalBill) . "\n";
            $msg .= "Period: $monthsCount months\n";
            $msg .= "Via: Admin\n\n";
            $msg .= "Thank you.";
            if (function_exists('getWhatsAppFooter')) {
                $msg .= getWhatsAppFooter();
            }
            sendWhatsApp($customer['phone'], $msg);
        }

        setFlash('success', "Payment successful for $monthsCount months.");
        
        // Redirect to Print Page (Admin version?) or Invoice List
        // Admin might want to print too.
        // Let's create admin/print_invoice.php or reuse sales/print_invoice.php?
        // Better create admin/print_invoice.php to be safe with auth.
        
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
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user"></i> Customer Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="100">Name</td>
                        <td>: <strong><?php echo htmlspecialchars($customer['name']); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Package</td>
                        <td>: <?php echo htmlspecialchars($package['name']); ?></td>
                    </tr>
                    <tr>
                        <td>Price</td>
                        <td>: <?php echo formatCurrency($package['price']); ?>/mo</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>: <?php echo htmlspecialchars($customer['address']); ?></td>
                    </tr>
                </table>
                <hr>
                <div class="alert alert-info">
                    Total Bill: <strong id="totalBillDisplay" style="font-size: 1.2em;">Rp 0</strong>
                </div>
                <button type="button" class="btn btn-success btn-block" onclick="confirmPayment()" style="width: 100%;">
                    <i class="fas fa-check-circle"></i> Process Payment
                </button>
            </div>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history"></i> Recent History</h3>
            </div>
            <div class="card-body p-0">
                <?php
                $history = fetchAll("SELECT * FROM invoices WHERE customer_id = ? AND status = 'paid' ORDER BY paid_at DESC LIMIT 5", [$id]);
                ?>
                <?php if (empty($history)): ?>
                    <div style="padding: 15px;" class="text-muted">No history yet.</div>
                <?php else: ?>
                    <table class="table table-striped mb-0">
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?php echo date('d/m/y', strtotime($h['paid_at'])); ?></td>
                                <td class="text-right text-success"><?php echo formatCurrency($h['amount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Select Payment Period</h3>
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
                    
                    <div class="months-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px;">
                        <?php foreach ($monthsList as $num => $name): ?>
                            <?php 
                                $isPaid = in_array($num, $paidMonths);
                            ?>
                            <div class="month-item" style="
                                border: 1px solid var(--border-color); 
                                border-radius: 8px; 
                                padding: 20px; 
                                text-align: center; 
                                cursor: pointer;
                                background: <?php echo $isPaid ? 'rgba(0, 255, 136, 0.1)' : 'var(--bg-input)'; ?>;
                                border-color: <?php echo $isPaid ? 'var(--neon-green)' : 'var(--border-color)'; ?>;
                                transition: all 0.2s;
                            " onclick="<?php echo $isPaid ? '' : "toggleMonth($num)"; ?>">
                                
                                <?php if ($isPaid): ?>
                                    <div style="color: var(--neon-green); font-size: 1.5rem; margin-bottom: 5px;">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <h5 style="margin: 0; color: var(--text-muted); text-decoration: line-through;"><?php echo $name; ?></h5>
                                    <small style="color: var(--neon-green); font-weight: bold;">PAID</small>
                                <?php else: ?>
                                    <input type="checkbox" name="selected_months[]" value="<?php echo $num; ?>" id="month_<?php echo $num; ?>" style="display: none;" onchange="updateTotal()">
                                    <div id="icon_<?php echo $num; ?>" style="color: var(--text-secondary); font-size: 1.5rem; margin-bottom: 5px;">
                                        <i class="far fa-square"></i>
                                    </div>
                                    <h5 style="margin: 0; color: var(--text-primary);" id="label_<?php echo $num; ?>"><?php echo $name; ?></h5>
                                    <small style="color: var(--text-secondary);">Unpaid</small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const pricePerMonth = <?php echo $package['price']; ?>;
    
    function toggleMonth(num) {
        const checkbox = document.getElementById('month_' + num);
        const div = checkbox.parentElement;
        const label = document.getElementById('label_' + num);
        const icon = document.getElementById('icon_' + num);
        
        checkbox.checked = !checkbox.checked;
        
        if (checkbox.checked) {
            div.style.backgroundColor = 'rgba(0, 245, 255, 0.2)';
            div.style.borderColor = 'var(--neon-cyan)';
            div.style.transform = 'scale(1.02)';
            div.style.boxShadow = '0 0 10px rgba(0, 245, 255, 0.2)';
            label.style.color = 'var(--neon-cyan)';
            icon.innerHTML = '<i class="fas fa-check-square"></i>';
            icon.style.color = 'var(--neon-cyan)';
        } else {
            div.style.backgroundColor = 'var(--bg-input)';
            div.style.borderColor = 'var(--border-color)';
            div.style.transform = 'scale(1)';
            div.style.boxShadow = 'none';
            label.style.color = 'var(--text-primary)';
            icon.innerHTML = '<i class="far fa-square"></i>';
            icon.style.color = 'var(--text-secondary)';
        }
        
        updateTotal();
    }
    
    function updateTotal() {
        const checkboxes = document.querySelectorAll('input[name="selected_months[]"]:checked');
        const count = checkboxes.length;
        
        const totalBill = pricePerMonth * count;
        
        // Simple currency formatter
        const formatter = new Intl.NumberFormat('en-PK', {
            style: 'currency',
            currency: 'PKR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
        
        document.getElementById('totalBillDisplay').innerText = formatter.format(totalBill);
    }

    function confirmPayment() {
        const checkboxes = document.querySelectorAll('input[name="selected_months[]"]:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least 1 month to pay.');
            return;
        }
        
        if (confirm('Confirm processing this payment?')) {
            document.getElementById('payForm').submit();
        }
    }
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>
