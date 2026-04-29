<?php
/**
 * API: Payment Gateway Integration
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/payment.php';

requireAdminLogin();

try {
    $action = $_GET['action'] ?? '';
    $gateway = $_GET['gateway'] ?? '';

    if ($action === 'create_transaction') {
        // Create payment transaction
        $invoiceId = (int) ($_GET['invoice_id'] ?? 0);
        $paymentMethod = trim((string) ($_GET['payment_method'] ?? ''));

        if ($invoiceId === 0) {
            echo json_encode(['success' => false, 'message' => 'Invoice ID required']);
            exit;
        }

        $invoice = fetchOne("SELECT i.*, c.name as customer_name, c.phone as customer_phone FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = ?", [$invoiceId]);

        if (!$invoice) {
            echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            exit;
        }

        // Generate payment link based on gateway
        $selectedGateway = $gateway !== '' ? $gateway : getSetting('DEFAULT_PAYMENT_GATEWAY', 'tripay');
        $paymentLink = generatePaymentLink(
            $invoice['invoice_number'],
            $invoice['amount'],
            $invoice['customer_name'] ?? '-',
            $invoice['customer_phone'] ?? '',
            $invoice['due_date'],
            $selectedGateway,
            $paymentMethod
        );

        echo json_encode([
            'success' => (bool) ($paymentLink['success'] ?? false),
            'data' => [
                'payment_link' => $paymentLink,
                'invoice' => [
                    'number' => $invoice['invoice_number'],
                    'amount' => $invoice['amount'],
                    'customer' => $invoice['customer_name'],
                    'due_date' => $invoice['due_date']
                ]
            ]
        ]);

    } elseif ($action === 'get_gateways') {
        // Get list of supported payment gateways
        $gateways = [
            [
                'id' => 'tripay',
                'name' => 'Tripay',
                'icon' => 'fa-credit-card',
                'color' => '#00f5ff',
                'description' => 'Popular Pakistani payment gateway',
                'features' => ['QRIS', 'Virtual Account', 'VA']
            ],
            [
                'id' => 'midtrans',
                'name' => 'Midtrans',
                'icon' => 'fa-credit-card',
                'color' => '#667eea',
                'description' => 'Popular Pakistani payment gateway',
                'features' => ['QRIS', 'Virtual Account', 'VA', 'Bank Transfer']
            ]
        ];

        echo json_encode([
            'success' => true,
            'data' => [
                'gateways' => $gateways
            ]
        ]);
    }

} catch (Exception $e) {
    logError("Payment API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
