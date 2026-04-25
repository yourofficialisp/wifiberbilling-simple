<?php
require_once '../includes/auth.php';
requireCustomerLogin();

$pageTitle = 'History Bill';

$customerSession = getCurrentCustomer();
$customerId = (int) ($customerSession['id'] ?? 0);

if ($customerId === 0) {
    setFlash('error', 'Akun customer invalid.');
    redirect('login.php');
}

$invoices = fetchAll("
    SELECT *
    FROM invoices
    WHERE customer_id = ?
    ORDER BY created_at DESC
    LIMIT 50
", [$customerId]);

ob_start();
?>

<div style="max-width: 1000px; margin: 0 auto; padding: 20px;">
    <div class="card">
        <div class="card-header" style="gap: 12px;">
            <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> History Bill</h3>
            <input type="text" id="searchInvoice" class="form-control" placeholder="Search invoice..." style="max-width: 260px;">
        </div>

        <?php if (empty($invoices)): ?>
            <div style="color: var(--text-muted); padding: 20px; text-align: center;">
                No invoice history.
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="data-table" id="invoiceTable">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Period</th>
                            <th>Due Date</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <?php
                            $invId = (int) ($inv['id'] ?? 0);
                            $invNo = (string) ($inv['invoice_number'] ?? '');
                            $createdAt = (string) ($inv['created_at'] ?? '');
                            $dueDate = (string) ($inv['due_date'] ?? '');
                            $status = (string) ($inv['status'] ?? '');
                            ?>
                            <tr>
                                <td data-label="Invoice"><code><?php echo htmlspecialchars($invNo !== '' ? $invNo : ('#' . $invId)); ?></code></td>
                                <td data-label="Period"><?php echo htmlspecialchars($createdAt !== '' ? date('F Y', strtotime($createdAt)) : '-'); ?></td>
                                <td data-label="Due Date"><?php echo htmlspecialchars(formatDate($dueDate)); ?></td>
                                <td data-label="Quantity"><?php echo htmlspecialchars(formatCurrency($inv['amount'] ?? 0)); ?></td>
                                <td data-label="Status">
                                    <?php if ($status === 'paid'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($status === 'unpaid'): ?>
                                        <span class="badge badge-warning">Unpaid</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?php echo htmlspecialchars(strtoupper($status !== '' ? $status : '-')); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Action">
                                    <?php if ($status === 'unpaid' && $invId > 0): ?>
                                        <a class="btn btn-primary" href="payment.php?invoice_id=<?php echo $invId; ?>" style="padding: 8px 14px; font-size: 0.85rem;">
                                            <i class="fas fa-credit-card"></i> Pay
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchInvoice');
    const table = document.getElementById('invoiceTable');
    if (!input || !table) return;

    input.addEventListener('input', (e) => {
        const q = (e.target.value || '').toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require_once '../includes/customer_layout.php';

