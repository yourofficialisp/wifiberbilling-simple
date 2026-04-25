<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$apiKey = trim((string) getSetting('DUITKU_API_KEY', ''));
$merchantCodeCfg = trim((string) getSetting('DUITKU_MERCHANT_CODE', ''));

try {
    $pdo = getDB();
    $pdo->exec("CREATE TABLE IF NOT EXISTS webhook_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(50) NOT NULL,
        payload LONGTEXT NOT NULL,
        status_code INT NOT NULL,
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
}

try {
    $post = $_POST;
    $merchantCode = isset($post['merchantCode']) ? (string) $post['merchantCode'] : '';
    $amount = isset($post['amount']) ? (string) $post['amount'] : '';
    $merchantOrderId = isset($post['merchantOrderId']) ? (string) $post['merchantOrderId'] : '';
    $resultCode = isset($post['resultCode']) ? (string) $post['resultCode'] : '';
    $signature = isset($post['signature']) ? (string) $post['signature'] : '';
    $reference = isset($post['reference']) ? (string) $post['reference'] : '';
    $paymentCode = isset($post['paymentCode']) ? (string) $post['paymentCode'] : '';

    $payloadLog = json_encode($post);
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO webhook_logs (source, payload, status_code, response, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['duitku', $payloadLog ?: '{}', 200, 'Received']);
    } catch (Exception $e) {
    }

    if ($apiKey === '' || $merchantCodeCfg === '') {
        echo json_encode(['success' => false, 'message' => 'Duitku not configured']);
        exit;
    }

    if ($merchantCode === '' || $amount === '' || $merchantOrderId === '' || $signature === '') {
        echo json_encode(['success' => false, 'message' => 'Bad parameter']);
        exit;
    }

    $calc = md5($merchantCode . $amount . $merchantOrderId . $apiKey);
    if (!hash_equals($calc, $signature)) {
        echo json_encode(['success' => false, 'message' => 'Invalid signature']);
        exit;
    }

    if ($resultCode !== '00') {
        echo json_encode(['success' => true, 'message' => 'Ignored']);
        exit;
    }

    $paymentData = [
        'payment_method' => $paymentCode,
        'reference' => $reference,
        'amount' => $amount,
        'merchantOrderId' => $merchantOrderId
    ];

    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ? LIMIT 1", [$merchantOrderId]);
    if (!$invoice) {
        $invoice = fetchOne("SELECT * FROM invoices WHERE payment_order_id = ? LIMIT 1", [$merchantOrderId]);
    }

    if (!$invoice) {
        if (markPublicVoucherOrderPaid($merchantOrderId, 'duitku', $paymentData)) {
            logActivity('PUBLIC_VOUCHER_PAID', "Order: {$merchantOrderId}");
            echo json_encode(['success' => true, 'message' => 'SUCCESS']);
            exit;
        }
        logError("Invoice/order not found: {$merchantOrderId}");
        echo json_encode(['success' => true, 'message' => 'SUCCESS']);
        exit;
    }

    update('invoices', [
        'status' => 'paid',
        'paid_at' => date('Y-m-d H:i:s'),
        'payment_method' => $paymentCode !== '' ? $paymentCode : 'Duitku',
        'payment_ref' => $reference !== '' ? $reference : $merchantOrderId,
        'payment_gateway' => 'duitku'
    ], 'id = ?', [(int) $invoice['id']]);

    logActivity('INVOICE_PAID', "Invoice: {$invoice['invoice_number']}");
    sendInvoicePaidWhatsapp((string) $invoice['invoice_number'], 'duitku', $paymentData);

    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$invoice['customer_id']]);
    if ($customer && ($customer['status'] ?? '') === 'isolated') {
        $unpaidCount = fetchOne("
            SELECT COUNT(*) as total
            FROM invoices
            WHERE customer_id = ?
            AND status = 'unpaid'
            AND due_date < CURDATE()
        ", [$customer['id']])['total'] ?? 0;

        if ((int) $unpaidCount === 0) {
            if (unisolateCustomer($invoice['customer_id'])) {
                logActivity('AUTO_UNISOLATE', "Customer ID: {$invoice['customer_id']}");
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'SUCCESS']);
} catch (Exception $e) {
    logError('Duitku webhook error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal error']);
}

