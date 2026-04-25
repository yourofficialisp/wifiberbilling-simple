<?php
/**
 * Payment Page - Customer Portal
 */

require_once '../includes/auth.php';
requireCustomerLogin();

$pageTitle = 'Payment';

$customerSession = getCurrentCustomer();
$customerId = (int) ($customerSession['id'] ?? 0);

// Get invoice ID
$invoiceId = (int)($_GET['invoice_id'] ?? 0);

if ($invoiceId === 0) {
    setFlash('error', 'Invoice not found');
    redirect('dashboard.php');
}

// Get invoice details
$invoice = fetchOne("SELECT i.*, c.name as customer_name, c.phone as customer_phone, p.name as package_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id LEFT JOIN packages p ON c.package_id = p.id WHERE i.id = ? AND i.customer_id = ?", [$invoiceId, $customerId]);

if (!$invoice) {
    setFlash('error', 'Invoice not found');
    redirect('dashboard.php');
}

// Get default payment gateway from settings
$defaultGateway = getSetting('DEFAULT_PAYMENT_GATEWAY', 'tripay');
if (!in_array($defaultGateway, ['tripay', 'midtrans'], true)) {
    $defaultGateway = 'tripay';
}

// Get payment gateways
require_once '../includes/payment.php';
$gateways = getPaymentGateways();

// Get payment methods for selected gateway
$paymentMethods = [];
if ($defaultGateway === 'tripay') {
    $paymentMethods = [
        ['code' => 'QRIS', 'name' => 'QRIS', 'icon' => 'fa-qrcode', 'color' => '#00f5ff'],
        ['code' => 'BCAVA', 'name' => 'BCA Virtual Account', 'icon' => 'fa-building', 'color' => '#667eea'],
        ['code' => 'BRIVA', 'name' => 'BRI Virtual Account', 'icon' => 'fa-building', 'color' => '#667eea'],
        ['code' => 'MANDIRIVA', 'name' => 'Mandiri Virtual Account', 'icon' => 'fa-building', 'color' => '#667eea'],
        ['code' => 'BNIVA', 'name' => 'BNI Virtual Account', 'icon' => 'fa-building', 'color' => '#667eea'],
        ['code' => 'OVO', 'name' => 'OVO', 'icon' => 'fa-wallet', 'color' => '#bf00ff'],
        ['code' => 'DANA', 'name' => 'DANA', 'icon' => 'fa-wallet', 'color' => '#bf00ff'],
        ['code' => 'LINKAJA', 'name' => 'LinkAja', 'icon' => 'fa-wallet', 'color' => '#bf00ff'],
        ['code' => 'SHOPEEPAY', 'name' => 'ShopeePay', 'icon' => 'fa-wallet', 'color' => '#bf00ff'],
        ['code' => 'ALFAMART', 'name' => 'Alfamart', 'icon' => 'fa-store', 'color' => '#00ff00'],
        ['code' => 'INDOMARET', 'name' => 'Indomaret', 'icon' => 'fa-store', 'color' => '#ff0000']
    ];
} elseif ($defaultGateway === 'midtrans') {
    $paymentMethods = [
        ['code' => 'gopay', 'name' => 'GoPay', 'icon' => 'fa-wallet', 'color' => '#00f5ff'],
        ['code' => 'qris', 'name' => 'QRIS', 'icon' => 'fa-qrcode', 'color' => '#00f5ff'],
        ['code' => 'bca_va', 'name' => 'BCA Virtual Account', 'icon' => 'fa-building', 'color' => '#667eea'],
        ['code' => 'bri_va', 'name' => 'BRI Virtual Account', 'icon' => 'fa-building', 'color' => '#667eea'],
        ['code' => 'mandiri_va', 'name' => 'Mandiri Virtual Account', 'icon' => 'fa-building', 'color' => '#667eea'],
        ['code' => 'bni_va', 'name' => 'BNI Virtual Account', 'icon' => 'fa-building', 'color' => '#667eea'],
        ['code' => 'ovo', 'name' => 'OVO', 'icon' => 'fa-wallet', 'color' => '#bf00ff'],
        ['code' => 'dana', 'name' => 'DANA', 'icon' => 'fa-wallet', 'color' => '#bf00ff'],
        ['code' => 'linkaja', 'name' => 'LinkAja', 'icon' => 'fa-wallet', 'color' => '#bf00ff'],
        ['code' => 'shopeepay', 'name' => 'ShopeePay', 'icon' => 'fa-wallet', 'color' => '#bf00ff']
    ];
}

// Handle payment method selection
$selectedPaymentMethod = $_POST['payment_method'] ?? '';
$paymentLink = null;

$supportPhone = (string) getSiteSetting('contact_phone', '+62 812-3456-7890');
$supportEmail = (string) getSiteSetting('contact_email', 'your.official.isp@gmail.com');
$waDigits = preg_replace('/\D+/', '', $supportPhone);
if ($waDigits !== '') {
    if (strpos($waDigits, '0') === 0) {
        $waDigits = '62' . substr($waDigits, 1);
    } elseif (strpos($waDigits, '62') !== 0) {
        $waDigits = '62' . $waDigits;
    }
}
$supportWaUrl = $waDigits !== '' ? ('https://wa.me/' . $waDigits) : '#';
$supportMailto = $supportEmail !== '' ? ('mailto:' . $supportEmail) : '#';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Session is invalid or has expired. Please try again.');
        redirect('payment.php?invoice_id=' . $invoiceId);
    }

    $selectedPaymentMethod = $_POST['payment_method'] ?? '';
    
    if (empty($selectedPaymentMethod)) {
        setFlash('error', 'Please select a payment method');
    } else {
        // Generate payment link with payment method
        $result = generatePaymentLink(
            $invoice['invoice_number'],
            $invoice['amount'],
            $invoice['customer_name'],
            $invoice['customer_phone'],
            $invoice['due_date'],
            $defaultGateway,
            $selectedPaymentMethod
        );
        
        if ($result['success']) {
            logActivity('PAYMENT_LINK_GENERATED', "Invoice: {$invoice['invoice_number']}, Gateway: {$defaultGateway}, Method: {$selectedPaymentMethod}");
            $paymentLink = $result['link'] ?? '';
            if ($paymentLink !== '') {
                redirect($paymentLink);
            }
            setFlash('error', 'Failed to open payment gateway');
        } else {
            setFlash('error', $result['message'] ?? 'Failed to generate payment link');
        }
    }
}

ob_start();
?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-credit-card"></i> Invoice Payment</h3>
        </div>
        
        <div style="margin-bottom: 30px;">
            <h4 style="color: var(--neon-cyan);">Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h4>
            <p style="color: var(--text-secondary);">Package: <?php echo htmlspecialchars($invoice['package_name']); ?></p>
            <p style="color: var(--text-secondary);">Due Date: <?php echo formatDate($invoice['due_date']); ?></p>
            <p style="font-size: 1.5rem; font-weight: bold; color: var(--neon-cyan);">
                Total: <?php echo formatCurrency($invoice['amount']); ?>
            </p>
        </div>
        
        <?php if ($invoice['status'] === 'paid'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> This invoice has already been paid
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <p style="color: var(--text-secondary); margin-bottom: 15px; font-size: 0.9rem;">
                        Select a payment method for this invoice:
                    </p>
                    <div class="payment-method-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <?php foreach ($paymentMethods as $method): ?>
                            <div class="payment-method-option" 
                                 style="border: 2px solid var(--border-color); 
                                        border-radius: 8px; 
                                        padding: 15px; 
                                        cursor: pointer; 
                                        transition: all 0.3s;
                                        text-align: center;"
                                 onclick="selectPaymentMethod('<?php echo $method['code']; ?>')">
                                <input type="radio" 
                                       name="payment_method" 
                                       value="<?php echo $method['code']; ?>"
                                       id="method_<?php echo $method['code']; ?>"
                                       style="display: none;">
                                <div style="color: <?php echo $method['color']; ?>; font-size: 1.5rem; margin-bottom: 8px;">
                                    <i class="fas <?php echo $method['icon']; ?>"></i>
                                </div>
                                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary);">
                                    <?php echo $method['name']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-credit-card"></i> Proceed to Payment
                </button>
                
                <div style="margin-top: 20px; text-align: center; font-size: 0.8rem; color: var(--text-secondary);">
                    By continuing with payment, you agree to the 
                    <a href="#" onclick="openModal('tosModal'); return false;" style="color: var(--neon-cyan);">Terms & Conditions</a> 
                    dan 
                    <a href="#" onclick="openModal('refundModal'); return false;" style="color: var(--neon-cyan);">Refund Policy</a>.
                </div>
            </form>
            
            <?php if ($paymentLink): ?>
                <div style="margin-top: 30px; padding: 20px; background: rgba(0, 245, 255, 0.1); border: 1px solid var(--neon-cyan); border-radius: 8px;">
                    <h4 style="color: var(--neon-cyan); margin-bottom: 15px;">
                        <i class="fas fa-external-link-alt"></i> Payment Link
                    </h4>
                    <p style="color: var(--text-secondary); margin-bottom: 15px;">
                        Please click the link below to proceed with payment:
                    </p>
                    <a href="<?php echo htmlspecialchars($paymentLink); ?>" 
                       target="_blank" 
                       class="btn btn-primary" 
                       style="display: inline-block; text-decoration: none; text-align: center;">
                        <i class="fas fa-external-link-alt"></i> Open Payment Gateway
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Contact Support -->
    <div style="margin-top: 30px; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
        <p>Need help? Contact our Customer Service:</p>
        <p>
            <i class="fab fa-whatsapp" style="color: #25D366;"></i> 
            <a href="<?php echo htmlspecialchars($supportWaUrl); ?>" style="color: var(--text-primary); text-decoration: none;"><?php echo htmlspecialchars($supportPhone); ?></a>
            &nbsp;|&nbsp; 
            <i class="fas fa-envelope" style="color: var(--neon-cyan);"></i> 
            <a href="<?php echo htmlspecialchars($supportMailto); ?>" style="color: var(--text-primary); text-decoration: none;"><?php echo htmlspecialchars($supportEmail); ?></a>
        </p>
    </div>
</div>

<!-- TOS Modal -->
<div id="tosModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); width: 600px; max-width: 90%; max-height: 80vh; overflow-y: auto; padding: 25px; border-radius: 12px; border: 1px solid var(--border-color);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <h3 style="color: var(--neon-cyan);">Terms & Conditions</h3>
            <button onclick="closeModal('tosModal')" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="color: var(--text-primary); line-height: 1.6;">
            <p>1. Internet service bill payments must be made before the due date each month.</p>
            <p>2. Late payment may result in temporary service suspension automatically by the system.</p>
            <p>3. Payment gateway administrative fees are borne by the customer (unless there is a specific promotion).</p>
            <p>4. Save payment proof if the transaction is successful but the status has not changed in the system.</p>
            <p>5. We guarantee the security of your transaction data through industry standard encryption.</p>
        </div>
    </div>
</div>

<!-- Refund Policy Modal -->
<div id="refundModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); width: 600px; max-width: 90%; max-height: 80vh; overflow-y: auto; padding: 25px; border-radius: 12px; border: 1px solid var(--border-color);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <h3 style="color: var(--neon-cyan);">Refund Policy</h3>
            <button onclick="closeModal('refundModal')" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="color: var(--text-primary); line-height: 1.6;">
            <p>1. Payments that have been verified by the system <strong>cannot be cancelled or refunded (non-refundable)</strong>.</p>
            <p>2. If overpayment occurs (double payment), funds will be credited as deposit balance for next month's bill payment.</p>
            <p>3. If service cannot be used due to technical disruption on our side for more than 3x24 hours, customer is entitled to apply for bill reduction compensation (prorated).</p>
            <p>4. Payment complaints must be accompanied by valid transfer proof within 7 days after the transaction.</p>
        </div>
    </div>
</div>

<style>
.card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}
.card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--neon-cyan);
}
.form-group { margin-bottom: 20px; }
.form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-secondary); }
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
    transition: all 0.3s;
    display: inline-block;
    text-decoration: none;
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,245,255,0.3); }
.btn-secondary {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}
.btn-secondary:hover { background: rgba(255, 255,255,0.05); }
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-success { background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; color: #00ff00; }
.alert-error { background: rgba(255, 0, 0, 0.1); border: 1px solid #ff0000; color: #ff0000; }
.gateway-option:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,245,255,0.2); }

@media (max-width: 520px) {
    .payment-method-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    .payment-method-option {
        padding: 12px !important;
    }
}
</style>

<script>
function selectPaymentMethod(methodCode) {
    document.querySelectorAll('input[name="payment_method"]').forEach(input => {
        input.checked = false;
    });
    document.getElementById('method_' + methodCode).checked = true;
    
    // Highlight selected method
    document.querySelectorAll('.payment-method-option').forEach(el => {
        el.style.borderColor = 'var(--border-color)';
    });
    event.currentTarget.style.borderColor = 'var(--neon-cyan)';
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.id === 'tosModal') {
        closeModal('tosModal');
    }
    if (event.target.id === 'refundModal') {
        closeModal('refundModal');
    }
}
</script>

<?php
$content = ob_get_clean();
require_once '../includes/customer_layout.php';
