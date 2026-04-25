<?php
/**
 * Webhook Handler - Tripay Payment Gateway
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Get raw POST data
    $json = file_get_contents('php://input');
    $callbackSignature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
    
    logActivity('TRIPAY_WEBHOOK', "Received webhook");
    
    // Validate signature
    $privateKey = trim((string) getSetting('TRIPAY_PRIVATE_KEY', ''));
    if ($privateKey === '') {
        logError('Tripay webhook: Private key not configured');
        echo json_encode(['success' => false, 'message' => 'Private key not configured']);
        exit;
    }
    
    // Generate expected signature
    $expectedSignature = hash_hmac('sha256', $json, $privateKey);
    
    if (!hash_equals($expectedSignature, $callbackSignature)) {
        logError('Tripay webhook: Invalid signature');
        echo json_encode(['success' => false, 'message' => 'Invalid signature']);
        exit;
    }
    
    // Parse JSON data
    $data = json_decode($json, true);
    
    if (!$data) {
        logError('Tripay webhook: Invalid JSON');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    $merchantRef = $data['merchant_ref'] ?? '';
    $status = $data['status'] ?? '';
    
    // Log webhook
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO webhook_logs (source, payload, status_code, response, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute(['tripay', $json, 200, 'Received']);
    
    // Handle payment status
    if ($status === 'PAID') {
        handlePaidInvoice($merchantRef, $data);
    } elseif ($status === 'EXPIRED' || $status === 'FAILED') {
        handleFailedInvoice($merchantRef, $status);
    }
    
    echo json_encode(['success' => true, 'message' => 'Webhook processed']);
    
} catch (Exception $e) {
    logError("Tripay webhook error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handlePaidInvoice($invoiceNumber, $paymentData) {
    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ?", [$invoiceNumber]);
    if (!$invoice) {
        $invoice = fetchOne("SELECT * FROM invoices WHERE payment_order_id = ? LIMIT 1", [$invoiceNumber]);
    }
    
    if (!$invoice) {
        if (markPublicVoucherOrderPaid($invoiceNumber, 'tripay', $paymentData)) {
            logActivity('PUBLIC_VOUCHER_PAID', "Order: {$invoiceNumber}");
            return;
        }
        logError("Invoice/order not found: {$invoiceNumber}");
        return;
    }
    
    // Update invoice status
    update('invoices', [
        'status' => 'paid',
        'paid_at' => date('Y-m-d H:i:s'),
        'payment_method' => $paymentData['payment_method'] ?? 'Tripay',
        'payment_ref' => $paymentData['reference'] ?? ''
    ], 'id = ?', [(int) $invoice['id']]);
    
    logActivity('INVOICE_PAID', "Invoice: {$invoice['invoice_number']}");

    sendInvoicePaidWhatsapp((string) $invoice['invoice_number'], 'tripay', $paymentData);
    
    // Check if customer should be unisolated
    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$invoice['customer_id']]);
    
    if ($customer && $customer['status'] === 'isolated') {
        // Check if all invoices are paid
        $unpaidCount = fetchOne("
            SELECT COUNT(*) as total 
            FROM invoices 
            WHERE customer_id = ? 
            AND status = 'unpaid' 
            AND due_date < CURDATE()
        ", [$customer['id']])['total'] ?? 0;
        
        if ($unpaidCount === 0) {
            // Unisolate customer
            if (unisolateCustomer($invoice['customer_id'])) {
                logActivity('AUTO_UNISOLATE', "Customer ID: {$invoice['customer_id']}");
            }
        }
    }
}

function handleFailedInvoice($invoiceNumber, $status) {
    if (markPublicVoucherOrderFailed($invoiceNumber, $status, ['status' => $status])) {
        logActivity('PUBLIC_VOUCHER_FAILED', "Order: {$invoiceNumber}, Status: {$status}");
        return;
    }
    logActivity('INVOICE_FAILED', "Invoice: {$invoiceNumber}, Status: {$status}");
}
