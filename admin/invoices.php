<?php
/**
 * Invoices Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Invoice';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('invoices.php');
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate':
                // Generate invoices for all active customers
                $customers = fetchAll("SELECT * FROM customers WHERE status = 'active'");
                $generatedCount = 0;
                $currentMonth = date('Y-m');
                
                foreach ($customers as $customer) {
                    // Check if invoice already exists for this month
                    $existingInvoice = fetchOne("
                        SELECT id FROM invoices 
                        WHERE customer_id = ? 
                        AND DATE_FORMAT(created_at, '%Y-%m') = ?",
                        [$customer['id'], $currentMonth]
                    );
                    
                    if (!$existingInvoice) {
                        $package = fetchOne("SELECT * FROM packages WHERE id = ?", [$customer['package_id']]);
                        
                        if ($package) {
                            $dueDate = getCustomerDueDate($customer, $currentMonth . '-01');
                            $invoiceData = [
                                'invoice_number' => generateInvoiceNumber(),
                                'customer_id' => $customer['id'],
                                'amount' => $package['price'],
                                'status' => 'unpaid',
                                'due_date' => $dueDate,
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            
                            insert('invoices', $invoiceData);
                            $generatedCount++;
                        }
                    }
                }
                
                setFlash('success', "Invoice successfully generated for {$generatedCount} active customers");
                logActivity('GENERATE_INVOICES', "Generated {$generatedCount} invoices for " . date('F Y'));
                redirect('invoices.php');
                break;
                
            case 'pay':
                $invoiceId = (int)$_POST['invoice_id'];
                $invoice = fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
                
                if ($invoice) {
                    // Update invoice status
                    update('invoices', [
                        'status' => 'paid',
                        'paid_at' => date('Y-m-d H:i:s'),
                        'payment_method' => sanitize($_POST['payment_method'] ?? 'Manual'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$invoiceId]);
                    
                    // Unisolate customer
                    if (isCustomerIsolated($invoice['customer_id'])) {
                        unisolateCustomer($invoice['customer_id']);
                    }
                    
                    setFlash('success', 'Invoice successfully paid');
                    logActivity('PAY_INVOICE', "Invoice: {$invoice['invoice_number']}");
                } else {
                    setFlash('error', 'Invoice not found');
                }
                redirect('invoices.php');
                break;
                
            case 'unisolate_only':
                $invoiceId = (int)$_POST['invoice_id'];
                $invoice = fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
                
                if ($invoice && $invoice['status'] === 'unpaid') {
                    if (unisolateCustomer($invoice['customer_id'])) {
                        setFlash('success', 'Customer successfully unisolated (bill still unpaid)');
                    } else {
                        setFlash('error', 'Failed to unisolate customer');
                    }
                } else {
                    setFlash('error', 'Invoice not found or already paid');
                }
                redirect('invoices.php');
                break;
            
            case 'defer_next_month':
                $invoiceId = (int)$_POST['invoice_id'];
                $invoice = fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
                
                if ($invoice && $invoice['status'] === 'unpaid') {
                    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$invoice['customer_id']]);
                    
                    if ($customer) {
                        $nextMonthBase = date('Y-m-01', strtotime('+1 month'));
                        $newDueDate = getCustomerDueDate($customer, $nextMonthBase);
                        
                        $description = $invoice['description'] ?? '';
                        $note = 'Deferred to next month from due date ' . $invoice['due_date'];
                        if ($description) {
                            $description .= ' | ' . $note;
                        } else {
                            $description = $note;
                        }
                        
                        update('invoices', [
                            'due_date' => $newDueDate,
                            'description' => $description,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$invoiceId]);
                        
                        if (isCustomerIsolated($invoice['customer_id'])) {
                            unisolateCustomer($invoice['customer_id']);
                        }
                        
                        setFlash('success', 'Invoice deferred to next month and customer isolation opened (if previously isolated).');
                        logActivity('DEFER_INVOICE', "Invoice: {$invoice['invoice_number']} deferred to {$newDueDate}");
                    } else {
                        setFlash('error', 'Customer not found for this invoice');
                    }
                } else {
                    setFlash('error', 'Invoice not found or already paid');
                }
                redirect('invoices.php');
                break;
                
            case 'edit':
                $invoiceId = (int)$_POST['invoice_id'];
                $amount = (float)$_POST['amount'];
                $dueDate = sanitize($_POST['due_date']);
                $status = sanitize($_POST['status']);
                
                $invoice = fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
                
                if ($invoice) {
                    $updateData = [
                        'amount' => $amount,
                        'due_date' => $dueDate,
                        'status' => $status,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // If status changed to paid, set paid_at
                    if ($status === 'paid' && $invoice['status'] !== 'paid') {
                        $updateData['paid_at'] = date('Y-m-d H:i:s');
                        $updateData['payment_method'] = 'Manual';
                        
                        // Unisolate customer if was isolated
                        if (isCustomerIsolated($invoice['customer_id'])) {
                            unisolateCustomer($invoice['customer_id']);
                        }
                    }
                    
                    update('invoices', $updateData, 'id = ?', [$invoiceId]);
                    setFlash('success', 'Invoice successfully updated');
                    logActivity('EDIT_INVOICE', "Invoice: {$invoice['invoice_number']}");
                } else {
                    setFlash('error', 'Invoice not found');
                }
                redirect('invoices.php');
                break;
                
            case 'delete':
                $invoiceId = (int)$_POST['invoice_id'];
                $invoice = fetchOne("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);
                
                if ($invoice) {
                    if ($invoice['status'] === 'paid') {
                        setFlash('error', 'Paid invoices cannot be deleted');
                    } else {
                        delete('invoices', 'id = ?', [$invoiceId]);
                        setFlash('success', 'Invoice successfully deleted');
                        logActivity('DELETE_INVOICE', "Invoice: {$invoice['invoice_number']}");
                    }
                } else {
                    setFlash('error', 'Invoice not found');
                }
                redirect('invoices.php');
                break;
                
            case 'create_manual':
                // Create manual invoice for specific customer
                $customerId = (int)$_POST['customer_id'];
                $amount = (float)$_POST['manual_amount'];
                $dueDate = sanitize($_POST['manual_due_date']);
                $description = sanitize($_POST['manual_description'] ?? '');
                
                $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$customerId]);
                
                if ($customer) {
                    $invoiceData = [
                        'invoice_number' => generateInvoiceNumber(),
                        'customer_id' => $customerId,
                        'amount' => $amount,
                        'status' => 'unpaid',
                        'due_date' => $dueDate,
                        'description' => $description,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    insert('invoices', $invoiceData);
                    setFlash('success', 'Manual invoice successfully created');
                    logActivity('CREATE_INVOICE', "Manual invoice for customer: {$customer['name']}");
                } else {
                    setFlash('error', 'Customer not found');
                }
                redirect('invoices.php');
                break;
        }
    }
}

// Get data
$invoices = fetchAll("
    SELECT i.*, c.name as customer_name, c.pppoe_username, c.phone 
    FROM invoices i 
    LEFT JOIN customers c ON i.customer_id = c.id 
    ORDER BY i.created_at DESC
");

// Get active customers for manual invoice
$customers = fetchAll("SELECT id, name, pppoe_username, package_id FROM customers WHERE status = 'active' ORDER BY name");

$totalInvoices = count($invoices);
$paidInvoices = count(array_filter($invoices, fn($i) => $i['status'] === 'paid'));
$unpaidInvoices = $totalInvoices - $paidInvoices;
$currentMonthKey = date('Y-m');
$paidThisMonth = array_filter($invoices, fn($i) => $i['status'] === 'paid' && !empty($i['paid_at']) && date('Y-m', strtotime($i['paid_at'])) === $currentMonthKey);
$monthRevenue = array_sum(array_column($paidThisMonth, 'amount'));

ob_start();
?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $totalInvoices; ?></h3>
            <p>Total Invoices</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $paidInvoices; ?></h3>
            <p>Paid</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $unpaidInvoices; ?></h3>
            <p>Unpaid</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-money-bill"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo formatCurrency($monthRevenue); ?></h3>
            <p>Total Revenue This Month</p>
        </div>
    </div>
</div>

<!-- Generate Invoice & Manual Invoice -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-magic"></i> Generate Invoice</h3>
        <span style="color: var(--text-muted); font-size: 0.85rem;">
            <?php 
            $currentMonth = date('F Y');
            $existingThisMonth = fetchOne("SELECT COUNT(*) as count FROM invoices WHERE DATE_FORMAT(created_at, '%Y-%m') = ?", [date('Y-m')]);
            echo "This month: {$existingThisMonth['count']} invoice";
            ?>
        </span>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Auto Generate -->
        <div>
            <p style="margin-bottom: 10px; color: var(--text-secondary);">Auto generate for all active customers this month:</p>
            <form method="POST" onsubmit="return confirm('Generate invoice for all active customers this month?');">
                <input type="hidden" name="action" value="generate">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-magic"></i> Generate This Month Invoice
                </button>
            </form>
        </div>
        
        <!-- Manual Invoice -->
        <div>
            <p style="margin-bottom: 10px; color: var(--text-secondary);">Create manual invoice for specific customer:</p>
            <button type="button" class="btn btn-secondary" onclick="openManualInvoiceModal()">
                <i class="fas fa-plus"></i> Create Manual Invoice
            </button>
        </div>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> Bill History</h3>
        <input type="text" id="searchInvoice" class="form-control" placeholder="Search invoice..." style="width: 250px;">
    </div>
    
    <table class="data-table" id="invoiceTable">
        <thead>
            <tr>
                <th>#Invoice</th>
                <th>Customer</th>
                <th>Period</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;" data-label="Data">
                        No invoice data yet
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td data-label="#Invoice">
                        <code style="color: var(--neon-cyan);">
                            <?php echo htmlspecialchars($inv['invoice_number']); ?>
                        </code>
                    </td>
                    <td data-label="Customer">
                        <strong><?php echo htmlspecialchars($inv['customer_name']); ?></strong><br>
                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($inv['pppoe_username']); ?></small>
                    </td>
                    <td data-label="Period"><?php echo date('F Y', strtotime($inv['created_at'])); ?></td>
                    <td data-label="Amount">
                        <strong style="color: var(--neon-green);">
                            <?php echo formatCurrency($inv['amount']); ?>
                        </strong>
                    </td>
                    <td data-label="Status">
                        <?php if ($inv['status'] === 'paid'): ?>
                            <span class="badge badge-success">Paid</span>
                            <?php if ($inv['paid_at']): ?>
                                <br><small style="color: var(--text-muted);"><?php echo date('d/m/Y H:i', strtotime($inv['paid_at'])); ?></small>
                            <?php endif; ?>
                        <?php elseif ($inv['status'] === 'cancelled'): ?>
                            <span class="badge badge-secondary">Cancelled</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Unpaid</span>
                            <?php if (strtotime($inv['due_date']) < time()): ?>
                                <span class="badge badge-danger" style="margin-left: 5px;">Overdue</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td data-label="Due Date"><?php echo formatDate($inv['due_date']); ?></td>
                    <td data-label="Action">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php if ($inv['status'] === 'unpaid'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="pay">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                    <input type="hidden" name="payment_method" value="Manual">
                                    <button type="submit" class="btn btn-primary btn-sm" title="Mark as Paid">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="unisolate_only">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Remove Isolation" style="background: var(--neon-purple); border-color: var(--neon-purple);">
                                        <i class="fas fa-unlock"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Defer this invoice due date to next month and remove customer isolation (if any)?');">
                                    <input type="hidden" name="action" value="defer_next_month">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Defer to Next Month" style="background: var(--neon-cyan); border-color: var(--neon-cyan);">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <button class="btn btn-secondary btn-sm" onclick="editInvoice(<?php echo htmlspecialchars(json_encode($inv)); ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <?php if ($inv['status'] !== 'paid'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this invoice?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($inv['status'] === 'paid'): ?>
                                <a class="btn btn-secondary btn-sm" href="print_invoice.php?ids=<?php echo (int) $inv['id']; ?>" target="_blank" title="Print Invoice">
                                    <i class="fas fa-print"></i>
                                </a>
                                <span class="badge badge-success" style="align-self: center;">Paid</span>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" onclick="sendWhatsApp('<?php echo htmlspecialchars($inv['phone']); ?>', '<?php echo htmlspecialchars($inv['invoice_number']); ?>', '<?php echo htmlspecialchars(formatCurrency($inv['amount'])); ?>', '<?php echo htmlspecialchars(invoicePayUrl((string) $inv['invoice_number'])); ?>')" title="Send via WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Invoice Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Edit Invoice</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="invoice_id" id="edit_invoice_id">
            
            <div class="form-group">
                <label class="form-label">Invoice Number</label>
                <input type="text" id="edit_invoice_number" class="form-control" readonly style="background: rgba(255,255,255,0.05);">
            </div>
            
            <div class="form-group">
                <label class="form-label">Amount (Rp)</label>
                <input type="number" name="amount" id="edit_amount" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" id="edit_due_date" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="edit_status" class="form-control" required>
                    <option value="unpaid">Unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Manual Invoice Modal -->
<div id="manualModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus"></i> Manual Invoice</h3>
            <button onclick="closeManualModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create_manual">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-control" required>
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['pppoe_username']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Amount (Rp)</label>
                <input type="number" name="manual_amount" class="form-control" required placeholder="Example: 150000">
            </div>
            
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" name="manual_due_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Description (Optional)</label>
                <input type="text" name="manual_description" class="form-control" placeholder="Example: Additional charge">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="button" class="btn btn-secondary" onclick="closeManualModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInvoice').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#invoiceTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// Edit modal functions
function editInvoice(invoice) {
    document.getElementById('edit_invoice_id').value = invoice.id;
    document.getElementById('edit_invoice_number').value = invoice.invoice_number;
    document.getElementById('edit_amount').value = invoice.amount;
    document.getElementById('edit_due_date').value = invoice.due_date;
    document.getElementById('edit_status').value = invoice.status;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Manual invoice modal
function openManualInvoiceModal() {
    document.getElementById('manualModal').style.display = 'flex';
}

function closeManualModal() {
    document.getElementById('manualModal').style.display = 'none';
}

// Send WhatsApp
function sendWhatsApp(phone, invoiceNumber, amount, payUrl) {
    if (!phone) {
        alert('Customer phone number not available');
        return;
    }
    
    // Clean phone number
    phone = phone.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    
    const message = `Hello,\n\nHere is your internet billing information:\n\nInvoice: ${invoiceNumber}\nAmount: ${amount}\n\nPay online:\n${payUrl}\n\nThank you.`;
    
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank');
}

const searchInput = document.getElementById('searchInvoice');
if (searchInput) {
    const rows = document.querySelectorAll('#invoiceTable tbody tr');
    const applyFilter = () => {
        const q = searchInput.value.toLowerCase();
        rows.forEach(row => {
            if (!q) {
                row.style.display = '';
                return;
            }
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    };
    searchInput.addEventListener('input', applyFilter);

    const params = new URLSearchParams(window.location.search);
    const preset = params.get('q') || params.get('invoice') || '';
    if (preset) {
        searchInput.value = preset;
        applyFilter();
    }
}

// Close modals on outside click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

document.getElementById('manualModal').addEventListener('click', function(e) {
    if (e.target === this) closeManualModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
        closeManualModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
