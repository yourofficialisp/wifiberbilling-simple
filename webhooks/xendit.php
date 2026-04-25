<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$token = trim((string) getSetting('XENDIT_CALLBACK_TOKEN', ''));

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
    $raw = file_get_contents('php://input');
    $payload = json_decode((string) $raw, true);

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO webhook_logs (source, payload, status_code, response, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['xendit', $raw ?: '{}', 200, 'Received']);
    } catch (Exception $e) {
    }

    if ($token !== '') {
        $headerToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? ($_SERVER['HTTP_XENDIT_CALLBACK_TOKEN'] ?? '');
        if (!hash_equals($token, (string) $headerToken)) {
            echo json_encode(['success' => false, 'message' => 'Invalid callback token']);
            exit;
        }
    }

    if (!is_array($payload)) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    $externalId = (string) ($payload['external_id'] ?? '');
    $status = strtoupper((string) ($payload['status'] ?? ''));
    $xenditId = (string) ($payload['id'] ?? '');

    if ($externalId === '' || $status === '') {
        echo json_encode(['success' => false, 'message' => 'Bad parameter']);
        exit;
    }

    if (!in_array($status, ['PAID', 'SETTLED'], true)) {
        echo json_encode(['success' => true, 'message' => 'Ignored']);
        exit;
    }

    $paymentData = [
        'payment_type' => 'xendit',
        'transaction_id' => $xenditId,
        'external_id' => $externalId,
        'status' => $status
    ];

    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ? LIMIT 1", [$externalId]);
    if (!$invoice) {
        $invoice = fetchOne("SELECT * FROM invoices WHERE payment_order_id = ? LIMIT 1", [$externalId]);
    }

    if (!$invoice) {
        if (markPublicVoucherOrderPaid($externalId, 'xendit', $paymentData)) {
            logActivity('PUBLIC_VOUCHER_PAID', "Order: {$externalId}");
            echo json_encode(['success' => true, 'message' => 'OK']);
            exit;
        }
        logError("Invoice/order not found: {$externalId}");
        echo json_encode(['success' => true, 'message' => 'OK']);
        exit;
    }

    update('invoices', [
        'status' => 'paid',
        'paid_at' => date('Y-m-d H:i:s'),
        'payment_method' => 'Xendit',
        'payment_ref' => $xenditId !== '' ? $xenditId : $externalId,
        'payment_gateway' => 'xendit'
    ], 'id = ?', [(int) $invoice['id']]);

    logActivity('INVOICE_PAID', "Invoice: {$invoice['invoice_number']}");
    sendInvoicePaidWhatsapp((string) $invoice['invoice_number'], 'xendit', $paymentData);

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

    echo json_encode(['success' => true, 'message' => 'OK']);
} catch (Exception $e) {
    logError('Xendit webhook error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal error']);
}

