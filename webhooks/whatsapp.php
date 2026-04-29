<?php
/**
 * Webhook Handler - WhatsApp
 */

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/payment.php';

header('Content-Type: application/json');

// Helper to get settings from database
// Redundant getSetting removed as it is already in includes/functions.php

try {
    // Get raw POST data
    $json = file_get_contents('php://input');
    
    // Debug: Log incoming payload
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    file_put_contents($logDir . 'whatsapp_webhook.log', "[" . date('Y-m-d H:i:s') . "] PAYLOAD: " . $json . "\n", FILE_APPEND);
    
    logActivity('WHATSAPP_WEBHOOK', "Received webhook");
    
    // Validate signature if configured
    if (!empty(WHATSAPP_TOKEN)) {
        $webhookToken = $_SERVER['HTTP_X_WHATSAPP_TOKEN'] ?? '';
        
        if (!hash_equals(WHATSAPP_TOKEN, $webhookToken)) {
            logError('WhatsApp webhook: Invalid token');
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
    }
    
    // Parse JSON data
    $data = json_decode($json, true);
    
    if (!$data) {
        logError('WhatsApp webhook: Invalid JSON');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    // Log webhook
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO webhook_logs (source, payload, status_code, response, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute(['whatsapp', $json, 200, 'Received']);
    
    // Handle webhook based on type
    $webhookType = $data['type'] ?? '';
    
    switch ($webhookType) {
        case 'message_status':
            handleMessageStatus($data);
            break;
            
        case 'message_sent':
            handleMessageSent($data);
            break;

        case 'message_received':
        case 'incoming_message':
        case 'message':
            handleMessageReceived($data);
            break;
            
        default:
            // Some providers send message without explicit type
            if (!handleMessageReceived($data)) {
                logActivity('WHATSAPP_WEBHOOK', "Unknown type: {$webhookType}");
            }
    }
    
    echo json_encode(['success' => true, 'message' => 'Webhook processed']);
    
} catch (Exception $e) {
    logError("WhatsApp webhook error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleMessageStatus($data) {
    $status = $data['status'] ?? '';
    $messageId = $data['message_id'] ?? '';
    $recipient = $data['recipient'] ?? '';
    
    logActivity('WHATSAPP_MESSAGE_STATUS', "Status: {$status}, Message ID: {$messageId}, Recipient: {$recipient}");
}

function handleMessageSent($data) {
    $recipient = $data['recipient'] ?? '';
    $message = $data['message'] ?? '';
    
    logActivity('WHATSAPP_MESSAGE_SENT', "To: {$recipient}, Message: " . substr($message, 0, 50));
}

function handleMessageReceived($data) {
    $payload = extractWhatsAppMessage($data);
    if (!$payload) {
        return false;
    }
    
    $from = $payload['from'];
    $text = $payload['text'];
    
    file_put_contents(__DIR__ . '/../logs/whatsapp_webhook.log', "[" . date('Y-m-d H:i:s') . "] PROCESSED SENDER: $from, TEXT: $text\n", FILE_APPEND);
    
    if ($from === '' || $text === '') {
        return false;
    }

    // Anti-Loopback: Ignore messages originating from the bot's own sender number
    $botSender = normalizeWhatsAppPhone(getSetting('MPWA_SENDER', ''));
    if ($botSender !== '' && $from === $botSender) {
        file_put_contents(__DIR__ . '/../logs/whatsapp_webhook.log', "[" . date('Y-m-d H:i:s') . "] LOOPBACK DETECTED: Ignoring message from bot's own number ($from)\n", FILE_APPEND);
        return true;
    }
    
    handleIncomingWhatsApp($from, $text);
    return true;
}

function extractWhatsAppMessage($data) {
    $candidates = [];
    if (is_array($data)) {
        $candidates[] = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $candidates[] = $data['data'];
        }
        if (isset($data['message']) && is_array($data['message'])) {
            $candidates[] = $data['message'];
        }
        if (isset($data['messages']) && is_array($data['messages']) && isset($data['messages'][0])) {
            $candidates[] = $data['messages'][0];
        }
    }
    
    $from = '';
    $text = '';
    $fromKeys = ['sender', 'from', 'phone', 'number', 'wa_id', 'participant', 'remoteJid'];
    $textKeys = ['message', 'text', 'body', 'content', 'caption'];
    
    foreach ($candidates as $c) {
        foreach ($fromKeys as $key) {
            if ($from === '' && isset($c[$key]) && is_string($c[$key])) {
                $from = $c[$key];
            }
        }
        foreach ($textKeys as $key) {
            if ($text === '' && isset($c[$key]) && is_string($c[$key])) {
                $text = $c[$key];
            }
        }
        if ($from === '' && isset($c['chat']['id']) && is_string($c['chat']['id'])) {
            $from = $c['chat']['id'];
        }
        if ($text === '' && isset($c['text']['body']) && is_string($c['text']['body'])) {
            $text = $c['text']['body'];
        }
    }
    
    $from = normalizeWhatsAppPhone($from);
    $text = trim((string)$text);
    
    if ($from === '' || $text === '') {
        return null;
    }
    
    return [
        'from' => $from,
        'text' => $text
    ];
}

function normalizeWhatsAppPhone($phone) {
    if (empty($phone)) return '';
    
    // Special handling for Baileys LID or other non-numeric ID formats
    // If it contains '@', ':', or non-numeric chars (other than leading '+'),
    // treat as a unique ID and do not aggressively clean it.
    if (preg_match('/[@:]/', (string)$phone)) {
        return trim((string)$phone);
    }

    $clean = preg_replace('/[^0-9]/', '', (string)$phone);
    if ($clean === '') {
        return '';
    }
    if (strpos($clean, '62') === 0) {
        return $clean;
    }
    if (strpos($clean, '0') === 0) {
        return '62' . substr($clean, 1);
    }
    return $clean;
}

function isWhatsAppAdmin($phone) {
    $admin = getSetting('WHATSAPP_ADMIN_NUMBER', '');
    if (empty($admin)) return false;

    $normalizedPhone = normalizeWhatsAppPhone($phone);
    
    // Check if admin is configured as LID/unique ID (contains non-numeric characters)
    $isAdminLid = preg_match('/[^0-9+]/', $admin);
    
    if ($isAdminLid) {
        // If admin is set as LID, compare exactly (case insensitive)
        $is_admin = (strtolower($normalizedPhone) === strtolower(trim($admin)));
    } else {
        // If admin is numeric, use standard normalization
        $is_admin = ($normalizedPhone === normalizeWhatsAppPhone($admin));
    }
    
    file_put_contents(__DIR__ . '/../logs/whatsapp_webhook.log', "[" . date('Y-m-d H:i:s') . "] VERIFY ADMIN: RequestID=$phone, NormalID=$normalizedPhone, AdminConfig=$admin, Result=" . ($is_admin ? 'YES' : 'NO') . "\n", FILE_APPEND);
    
    return $is_admin;
}

function sendWhatsAppResponse($phone, $message) {
    $success = sendWhatsApp($phone, $message);
    $status = $success ? "SUCCESS" : "FAILED";
    file_put_contents(__DIR__ . '/../logs/whatsapp_webhook.log', "[" . date('Y-m-d H:i:s') . "] SEND RESPONSE to $phone: $status\n", FILE_APPEND);
    return $success;
}

function handleIncomingWhatsApp($from, $text) {
    $line = trim(strtok($text, "\n"));
    $lower = strtolower($line);
    
    if ($lower === 'help') {
        $line = '/help';
    } elseif ($lower === 'menu') {
        $line = '/menu';
    }
    
    if ($line === '' || $line[0] !== '/') {
        handleWhatsAppRegularMessage($from);
        return;
    }
    
    $parts = explode(' ', $line, 2);
    $command = strtolower($parts[0]);
    $args = $parts[1] ?? '';
    
    switch ($command) {
        case '/help':
            handleWhatsAppHelp($from);
            break;
        case '/menu':
            handleWhatsAppMenu($from);
            break;
        case '/pay_invoice':
            $invoiceId = trim($args);
            if ($invoiceId === '') {
                sendWhatsAppResponse($from, "Format: /pay_invoice <invoice_id>");
                break;
            }
            handleWhatsAppPayInvoice($from, $invoiceId);
            break;
        case '/check_status':
            $phone = trim($args);
            if ($phone === '') {
                $phone = $from;
            }
            handleWhatsAppCheckStatus($from, $phone);
            break;
        case '/billing_cek':
            handleWhatsAppBillingCheck($from, $args);
            break;
        case '/billing_invoice':
            handleWhatsAppBillingInvoice($from, $args);
            break;
        case '/billing_isolir':
            handleWhatsAppBillingIsolir($from, $args);
            break;
        case '/billing_bukaisolir':
            handleWhatsAppBillingBukaIsolir($from, $args);
            break;
        case '/billing_paid':
            handleWhatsAppBillingPaid($from, $args);
            break;
        case '/invoice_create':
            handleWhatsAppInvoiceCreate($from, $args);
            break;
        case '/invoice_edit':
            handleWhatsAppInvoiceEdit($from, $args);
            break;
        case '/invoice_delete':
            handleWhatsAppInvoiceDelete($from, $args);
            break;
        case '/mt_setprofile':
            handleWhatsAppMikrotikSetProfileeeeeeeeeeee($from, $args);
            break;
        case '/mt_resource':
            handleWhatsAppMikrotikResource($from);
            break;
        case '/mt_online':
            handleWhatsAppMikrotikOnline($from);
            break;
        case '/mt_ping':
            handleWhatsAppMikrotikPing($from, $args);
            break;
        case '/pppoe_list':
            handleWhatsAppPppoeList($from);
            break;
        case '/pppoe_add':
            handleWhatsAppPppoeAdd($from, $args);
            break;
        case '/pppoe_edit':
            handleWhatsAppPppoeEdit($from, $args);
            break;
        case '/pppoe_del':
            handleWhatsAppPppoeDel($from, $args);
            break;
        case '/pppoe_disable':
            handleWhatsAppPppoeDisable($from, $args);
            break;
        case '/pppoe_enable':
            handleWhatsAppPppoeEnable($from, $args);
            break;
        case '/pppoe_profile_list':
            handleWhatsAppPppoeProfileeeeeeeeeeeeList($from);
            break;
        case '/hs_list':
            handleWhatsAppHotspotList($from);
            break;
        case '/hs_add':
            handleWhatsAppHotspotAdd($from, $args);
            break;
        case '/hs_del':
            handleWhatsAppHotspotDel($from, $args);
            break;
        default:
            handleWhatsAppRegularMessage($from);
    }
}

function handleWhatsAppRegularMessage($phone) {
    $message = "Thank you for your message.\n\nPlease use the available commands.\nType /help to see the list of commands.";
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppHelp($phone) {
    $message = "🤖 GEMBOK Bot Commands\n\n";
    $message .= "For customers:\n";
    $message .= "/pay_invoice <invoice_id> - Check and pay invoice\n";
    $message .= "/check_status <phone> - Check customer status\n";
    $message .= "/help - Show this help\n\n";
    
    if (isWhatsAppAdmin($phone)) {
        $message .= "For admin:\n";
        $message .= "/menu - Show main menu\n";
        $message .= "/billing_cek <pppoe_username> - Check customer billing\n";
        $message .= "/billing_invoice <pppoe_username> - List customer invoices\n";
        $message .= "/billing_isolir <pppoe_username> - Isolate customer\n";
        $message .= "/billing_bukaisolir <pppoe_username> - Remove customer isolation\n";
        $message .= "/billing_paid <invoice_no> - Mark invoice as paid\n";
        $message .= "/invoice_create <pppoe_username> <amount> <due_date> [desc]\n";
        $message .= "/invoice_edit <invoice_number> <amount> <due_date> <status>\n";
        $message .= "/invoice_delete <invoice_number>\n";
        $message .= "/mt_setprofile <pppoe_username> <profile>\n";
        $message .= "/mt_resource - Check MikroTik resources\n";
        $message .= "/mt_online - Check online PPPoE users\n";
        $message .= "/mt_ping <ip/host> - Ping from MikroTik\n";
        $message .= "/pppoe_list - List PPPoE users\n";
        $message .= "/pppoe_add <user> <pass> <profile>\n";
        $message .= "/pppoe_edit <user> <pass> <profile>\n";
        $message .= "/pppoe_del <user>\n";
        $message .= "/pppoe_disable <user>\n";
        $message .= "/pppoe_enable <user>\n";
        $message .= "/pppoe_profile_list\n";
        $message .= "/hs_list - List Hotspot users\n";
        $message .= "/hs_add <user> <pass> <profile>\n";
        $message .= "/hs_del <user>\n";
    }
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppMenu($phone) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Menu commands are for admin only.");
        return;
    }
    
    $message = "Admin Menu:\n";
    $message .= "1) Billing: /billing_cek, /billing_invoice, /billing_paid\n";
    $message .= "2) MikroTik: /pppoe_list, /pppoe_add, /hs_list, /hs_add\n";
    $message .= "Type /help for the full command list.";
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppPayInvoice($phone, $invoiceId) {
    $invoice = fetchOne("SELECT i.*, c.name as customer_name, c.phone as customer_phone FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = ?", [$invoiceId]);
    if (!$invoice) {
        sendWhatsAppResponse($phone, "Invoice not found.");
        return;
    }

    $payUrl = invoicePayUrl((string) $invoice['invoice_number']);
    $message = "Invoice #{$invoice['invoice_number']}\n";
    $message .= "Customer: {$invoice['customer_name']}\n";
    $message .= "Amount: " . formatCurrency($invoice['amount']) . "\n";
    $message .= "Due Date: " . formatDate($invoice['due_date']) . "\n\n";
    $message .= "Payment link:\n";
    $message .= $payUrl;
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppCheckStatus($phone, $targetPhone) {
    $targetPhone = normalizeWhatsAppPhone($targetPhone);
    $customer = fetchOne("SELECT * FROM customers WHERE phone = ?", [$targetPhone]);
    if (!$customer) {
        sendWhatsAppResponse($phone, "Customer not found with that phone number.");
        return;
    }
    
    $status = $customer['status'] === 'active' ? 'Active' : 'Isolated';
    $message = "Customer Status\n\n";
    $message .= "Name: {$customer['name']}\n";
    $message .= "Phone: {$customer['phone']}\n";
    $message .= "PPPoE Username: {$customer['pppoe_username']}\n";
    $message .= "Status: {$status}\n";
    if ($customer['status'] === 'isolated') {
        $message .= "\nConnection is currently isolated due to unpaid invoice.";
    }
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppBillingCheck($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Billing commands are for admin only.");
        return;
    }
    
    $username = trim($args);
    if ($username === '') {
        sendWhatsAppResponse($phone, "Format: /billing_cek <pppoe_username>");
        return;
    }
    
    $customer = fetchOne("SELECT c.*, p.name AS package_name, p.price AS package_price FROM customers c LEFT JOIN packages p ON c.package_id = p.id WHERE c.pppoe_username = ?", [$username]);
    if (!$customer) {
        sendWhatsAppResponse($phone, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    $invoice = fetchOne("SELECT * FROM invoices WHERE customer_id = ? ORDER BY due_date DESC LIMIT 1", [$customer['id']]);
    
    $message = "Customer Billing\n\n";
    $message .= "Name: {$customer['name']}\n";
    $message .= "PPPoE: {$customer['pppoe_username']}\n";
    $message .= "Package: " . ($customer['package_name'] ?? '-') . "\n";
    
    if ($invoice) {
        $status = $invoice['status'] === 'paid' ? 'Paid' : 'Unpaid';
        $message .= "Invoice: {$invoice['invoice_number']}\n";
        $message .= "Amount: " . formatCurrency($invoice['amount']) . "\n";
        $message .= "Due Date: " . formatDate($invoice['due_date']) . "\n";
        $message .= "Status: {$status}\n";
    } else {
        $message .= "No invoices found for this customer.\n";
    }
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppBillingInvoice($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Billing commands are for admin only.");
        return;
    }
    
    $username = trim($args);
    if ($username === '') {
        sendWhatsAppResponse($phone, "Format: /billing_invoice <pppoe_username>");
        return;
    }
    
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
    if (!$customer) {
        sendWhatsAppResponse($phone, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    $invoices = fetchAll("SELECT * FROM invoices WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5", [$customer['id']]);
    if (empty($invoices)) {
        sendWhatsAppResponse($phone, "No invoices found for customer {$customer['name']}.");
        return;
    }
    
    $message = "Invoice List - {$customer['name']}\n\n";
    foreach ($invoices as $inv) {
        $status = $inv['status'] === 'paid' ? 'Paid' : 'Unpaid';
        $message .= "#{$inv['invoice_number']} - " . formatCurrency($inv['amount']) . " - {$status}\n";
        $message .= "Due Date: " . formatDate($inv['due_date']) . "\n\n";
    }
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppBillingIsolir($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Billing commands are for admin only.");
        return;
    }
    
    $username = trim($args);
    if ($username === '') {
        sendWhatsAppResponse($phone, "Format: /billing_isolir <pppoe_username>");
        return;
    }
    
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
    if (!$customer) {
        sendWhatsAppResponse($phone, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    if (isCustomerIsolated($customer['id'])) {
        sendWhatsAppResponse($phone, "This customer is already isolated.");
        return;
    }
    
    if (isolateCustomer($customer['id'])) {
        sendWhatsAppResponse($phone, "Customer {$customer['name']} successfully isolated.");
    } else {
        sendWhatsAppResponse($phone, "Failed to isolate customer {$customer['name']}.")
    }
}

function handleWhatsAppBillingBukaIsolir($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Billing commands are for admin only.");
        return;
    }
    
    $username = trim($args);
    if ($username === '') {
        sendWhatsAppResponse($phone, "Format: /billing_bukaisolir <pppoe_username>");
        return;
    }
    
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
    if (!$customer) {
        sendWhatsAppResponse($phone, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    if (!isCustomerIsolated($customer['id'])) {
        sendWhatsAppResponse($phone, "This customer is not isolated.");
        return;
    }
    
    if (unisolateCustomer($customer['id'], ['send_whatsapp' => true])) {
        sendWhatsAppResponse($phone, "Customer {$customer['name']} has been un-isolated successfully.");
    } else {
        sendWhatsAppResponse($phone, "Failed to un-isolate customer {$customer['name']}.")
    }
}

function handleWhatsAppBillingPaid($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Billing commands are for admin only.");
        return;
    }
    
    $invoiceNumber = trim($args);
    if ($invoiceNumber === '') {
        sendWhatsAppResponse($phone, "Format: /billing_paid <invoice_number>");
        return;
    }
    
    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ?", [$invoiceNumber]);
    if (!$invoice) {
        sendWhatsAppResponse($phone, "Invoice {$invoiceNumber} not found.");
        return;
    }
    
    if ($invoice['status'] === 'paid') {
        sendWhatsAppResponse($phone, "Invoice {$invoiceNumber} is already paid.");
        return;
    }
    
    $updateData = [
        'status' => 'paid',
        'updated_at' => date('Y-m-d H:i:s'),
        'paid_at' => date('Y-m-d H:i:s'),
        'payment_method' => 'WhatsApp Bot'
    ];
    
    update('invoices', $updateData, 'id = ?', [$invoice['id']]);
    sendInvoicePaidWhatsapp($invoiceNumber, 'whatsapp', ['payment_method' => 'WhatsApp Bot']);
    
    if (isCustomerIsolated($invoice['customer_id'])) {
        unisolateCustomer($invoice['customer_id']);
    }
    
    logActivity('BOT_INVOICE_PAID', "Invoice: {$invoice['invoice_number']}");
    sendWhatsAppResponse($phone, "Invoice {$invoiceNumber} marked as paid. Customer isolation (if any) has been removed.");
}

function handleWhatsAppInvoiceCreate($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Invoice commands are for admin only.");
        return;
    }
    
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 3) {
        sendWhatsAppResponse($phone, "Format: /invoice_create <pppoe_username> <amount> <due_date> [desc]");
        return;
    }
    
    $username = $parts[0];
    $amount = (float)$parts[1];
    $dueDate = $parts[2];
    $description = '';
    if (count($parts) > 3) {
        $description = trim(implode(' ', array_slice($parts, 3)));
    }
    
    if (strtotime($dueDate) === false) {
        sendWhatsAppResponse($phone, "Invalid date format. Please use YYYY-MM-DD.");
        return;
    }
    
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
    if (!$customer) {
        sendWhatsAppResponse($phone, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    $invoiceData = [
        'invoice_number' => generateInvoiceNumber(),
        'customer_id' => $customer['id'],
        'amount' => $amount,
        'status' => 'unpaid',
        'due_date' => $dueDate,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    if ($description !== '') {
        $invoiceData['description'] = $description;
    }
    
    insert('invoices', $invoiceData);
    logActivity('CREATE_INVOICE', "Manual invoice via WhatsApp for customer: {$customer['name']}");
    
    sendWhatsAppResponse($phone, "Invoice created: {$invoiceData['invoice_number']} for {$customer['name']}.");
}

function handleWhatsAppInvoiceEdit($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Invoice commands are for admin only.");
        return;
    }
    
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 4) {
        sendWhatsAppResponse($phone, "Format: /invoice_edit <invoice_number> <amount> <due_date> <status>");
        return;
    }
    
    $invoiceNumber = $parts[0];
    $amount = (float)$parts[1];
    $dueDate = $parts[2];
    $status = strtolower($parts[3]);
    
    if (strtotime($dueDate) === false) {
        sendWhatsAppResponse($phone, "Invalid date format. Please use YYYY-MM-DD.");
        return;
    }
    
    if (!in_array($status, ['unpaid', 'paid', 'cancelled'], true)) {
        sendWhatsAppResponse($phone, "Invalid status. Use unpaid, paid, or cancelled.");
        return;
    }
    
    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ?", [$invoiceNumber]);
    if (!$invoice) {
        sendWhatsAppResponse($phone, "Invoice {$invoiceNumber} not found.");
        return;
    }
    
    $updateData = [
        'amount' => $amount,
        'due_date' => $dueDate,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($status === 'paid' && $invoice['status'] !== 'paid') {
        $updateData['paid_at'] = date('Y-m-d H:i:s');
        $updateData['payment_method'] = 'WhatsApp Bot';
        if (isCustomerIsolated($invoice['customer_id'])) {
            unisolateCustomer($invoice['customer_id']);
        }
    }
    
    update('invoices', $updateData, 'id = ?', [$invoice['id']]);
    if ($status === 'paid' && $invoice['status'] !== 'paid') {
        sendInvoicePaidWhatsapp((string) $invoice['invoice_number'], 'whatsapp', ['payment_method' => 'WhatsApp Bot']);
    }
    logActivity('EDIT_INVOICE', "Invoice: {$invoice['invoice_number']}");
    
    sendWhatsAppResponse($phone, "Invoice {$invoiceNumber} updated successfully.");
}

function handleWhatsAppInvoiceDelete($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "Invoice commands are for admin only.");
        return;
    }
    
    $invoiceNumber = trim($args);
    if ($invoiceNumber === '') {
        sendWhatsAppResponse($phone, "Format: /invoice_delete <invoice_number>");
        return;
    }
    
    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ?", [$invoiceNumber]);
    if (!$invoice) {
        sendWhatsAppResponse($phone, "Invoice {$invoiceNumber} not found.");
        return;
    }
    
    if ($invoice['status'] === 'paid') {
        sendWhatsAppResponse($phone, "Paid invoices cannot be deleted.");
        return;
    }
    
    delete('invoices', 'id = ?', [$invoice['id']]);
    logActivity('DELETE_INVOICE', "Invoice: {$invoice['invoice_number']}");
    
    sendWhatsAppResponse($phone, "Invoice {$invoiceNumber} deleted successfully.");
}

function handleWhatsAppPppoeProfileeeeeeeeeeeeList($phone) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $profiles = mikrotikGetProfileeeeeeeeeeees();
    if (empty($profiles)) {
        sendWhatsAppResponse($phone, "No PPPoE profiles found or failed to retrieve data.");
        return;
    }
    
    $message = "PPPoE Profileeeeeeeeeeees\n\n";
    foreach ($profiles as $p) {
        $id = $p['.id'] ?? '-';
        $name = $p['name'] ?? '-';
        $rate = $p['rate-limit'] ?? '-';
        $message .= "{$id} | {$name} | {$rate}\n";
    }
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppMikrotikSetProfileeeeeeeeeeee($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 2) {
        $msg = "Format: /mt_setprofile <pppoe_username> <profile>";
        sendWhatsAppResponse($phone, $msg);
        return;
    }
    
    $username = $parts[0];
    $profile = $parts[1];
    
    $ok = mikrotikSetProfileeeeeeeeeeee($username, $profile);
    if (!$ok) {
        sendWhatsAppResponse($phone, "Failed to change PPPoE profile for {$username} to {$profile}.");
        return;
    }
    
    mikrotikRemoveActiveSessionByName($username);
    sendWhatsAppResponse($phone, "PPPoE profile for {$username} changed to {$profile} and active session disconnected.");
}

function handleWhatsAppMikrotikResource($phone) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $res = mikrotikGetResource();
    if (!$res) {
        sendWhatsAppResponse($phone, "Failed to retrieve MikroTik resources. Check the Settings configuration.");
        return;
    }
    
    $cpu = $res['cpu-load'] ?? '-';
    $memTotal = $res['total-memory'] ?? '-';
    $memFree = $res['free-memory'] ?? '-';
    $hddTotal = $res['total-hdd-space'] ?? '-';
    $hddFree = $res['free-hdd-space'] ?? '-';
    $uptime = $res['uptime'] ?? '-';
    
    $message = "MikroTik Resources\n\n";
    $message .= "CPU Load: {$cpu}%\n";
    $message .= "Memory: {$memFree} / {$memTotal}\n";
    $message .= "HDD: {$hddFree} / {$hddTotal}\n";
    $message .= "Uptime: {$uptime}\n";
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppMikrotikOnline($phone) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $sessions = mikrotikGetActiveSessions();
    if (!is_array($sessions)) {
        sendWhatsAppResponse($phone, "Failed to retrieve active PPPoE sessions.");
        return;
    }
    
    $total = count($sessions);
    if ($total === 0) {
        sendWhatsAppResponse($phone, "No PPPoE sessions currently online.");
        return;
    }
    
    $message = "PPPoE Online: {$total}\n\n";
    $maxList = 30;
    $count = 0;
    foreach ($sessions as $s) {
        $name = $s['name'] ?? '-';
        $addr = $s['address'] ?? '-';
        $uptime = $s['uptime'] ?? '-';
        $message .= "- {$name} ({$addr}) up {$uptime}\n";
        $count++;
        if ($count >= $maxList) {
            break;
        }
    }
    if ($total > $maxList) {
        $message .= "\n...and " . ($total - $maxList) . " more users.";
    }
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppMikrotikPing($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $target = trim($args);
    if ($target === '') {
        sendWhatsAppResponse($phone, "Format: /mt_ping <ip/host>");
        return;
    }
    
    $result = mikrotikPing($target);
    if (!$result) {
        sendWhatsAppResponse($phone, "Failed to ping {$target} from MikroTik.");
        return;
    }
    
    $sent = $result['sent'];
    $recv = $result['received'];
    $loss = $result['loss'];
    $avg = $result['avg'] !== null ? round($result['avg'], 2) . " ms" : '-';
    
    $message = "Ping from MikroTik\n\n";
    $message .= "Target: {$target}\n";
    $message .= "Sent: {$sent}\n";
    $message .= "Received: {$recv}\n";
    $message .= "Loss: {$loss}%\n";
    $message .= "Average: {$avg}\n";
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppPppoeList($phone) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $users = mikrotikGetPppoeUsers();
    if (empty($users)) {
        sendWhatsAppResponse($phone, "No PPPoE users found or failed to retrieve data.");
        return;
    }
    
    $message = "PPPoE User List\n\n";
    $max = 50;
    $count = 0;
    foreach ($users as $u) {
        $name = $u['name'] ?? '-';
        $profile = $u['profile'] ?? '-';
        $disabled = $u['disabled'] ?? 'false';
        $status = $disabled === 'true' ? 'Disabled' : 'Active';
        $message .= "- {$name} ({$profile}) {$status}\n";
        $count++;
        if ($count >= $max) {
            break;
        }
    }
    if (count($users) > $max) {
        $message .= "\n...and " . (count($users) - $max) . " more users.";
    }
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppPppoeAdd($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 3) {
        $msg = "Format: /pppoe_add <user> <pass> <profile>";
        sendWhatsAppResponse($phone, $msg);
        return;
    }
    
    $user = $parts[0];
    $pass = $parts[1];
    $profile = $parts[2];
    
    $result = mikrotikAddSecret($user, $pass, $profile, 'pppoe');
    if ($result['success']) {
        sendWhatsAppResponse($phone, "PPPoE user {$user} added successfully with profile {$profile}.");
    } else {
        sendWhatsAppResponse($phone, "Failed to add PPPoE user {$user}: {$result['message']}");
    }
}

function handleWhatsAppPppoeEdit($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 3) {
        sendWhatsAppResponse($phone, "Format: /pppoe_edit <user> <pass> <profile>");
        return;
    }
    
    $user = $parts[0];
    $pass = $parts[1];
    $profile = $parts[2];
    
    $secret = mikrotikGetSecretByName($user);
    if (!$secret || empty($secret['.id'])) {
        sendWhatsAppResponse($phone, "PPPoE user {$user} not found.");
        return;
    }
    
    $result = mikrotikUpdateSecret($secret['.id'], ['password' => $pass, 'profile' => $profile]);
    if ($result['success']) {
        mikrotikRemoveActiveSessionByName($user);
        sendWhatsAppResponse($phone, "PPPoE user {$user} updated successfully.");
    } else {
        sendWhatsAppResponse($phone, "Failed to update PPPoE user {$user}: {$result['message']}");
    }
}

function handleWhatsAppPppoeDel($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $user = trim($args);
    if ($user === '') {
        sendWhatsAppResponse($phone, "Format: /pppoe_del <user>");
        return;
    }
    
    $secret = mikrotikGetSecretByName($user);
    if (!$secret || empty($secret['.id'])) {
        sendWhatsAppResponse($phone, "PPPoE user {$user} not found.");
        return;
    }
    
    $result = mikrotikDeleteSecret($secret['.id']);
    if ($result['success']) {
        sendWhatsAppResponse($phone, "PPPoE user {$user} deleted successfully.");
    } else {
        sendWhatsAppResponse($phone, "Failed to delete PPPoE user {$user}: {$result['message']}");
    }
}

function handleWhatsAppPppoeDisable($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $user = trim($args);
    if ($user === '') {
        sendWhatsAppResponse($phone, "Format: /pppoe_disable <user>");
        return;
    }
    
    $secret = mikrotikGetSecretByName($user);
    if (!$secret || empty($secret['.id'])) {
        sendWhatsAppResponse($phone, "PPPoE user {$user} not found.");
        return;
    }
    
    $result = mikrotikUpdateSecret($secret['.id'], ['disabled' => 'true']);
    if ($result['success']) {
        mikrotikRemoveActiveSessionByName($user);
        sendWhatsAppResponse($phone, "PPPoE user {$user} disabled successfully.");
    } else {
        sendWhatsAppResponse($phone, "Failed to disable PPPoE user {$user}: {$result['message']}");
    }
}

function handleWhatsAppPppoeEnable($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $user = trim($args);
    if ($user === '') {
        sendWhatsAppResponse($phone, "Format: /pppoe_enable <user>");
        return;
    }
    
    $secret = mikrotikGetSecretByName($user);
    if (!$secret || empty($secret['.id'])) {
        sendWhatsAppResponse($phone, "PPPoE user {$user} not found.");
        return;
    }
    
    $result = mikrotikUpdateSecret($secret['.id'], ['disabled' => 'false']);
    if ($result['success']) {
        sendWhatsAppResponse($phone, "PPPoE user {$user} enabled successfully.");
    } else {
        sendWhatsAppResponse($phone, "Failed to enable PPPoE user {$user}: {$result['message']}");
    }
}

function handleWhatsAppHotspotList($phone) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $users = mikrotikGetHotspotUsers();
    if (empty($users)) {
        sendWhatsAppResponse($phone, "No Hotspot users found or failed to retrieve data.");
        return;
    }
    
    $message = "Hotspot User List\n\n";
    $max = 50;
    $count = 0;
    foreach ($users as $u) {
        $name = $u['name'] ?? '-';
        $profile = $u['profile'] ?? '-';
        $message .= "- {$name} ({$profile})\n";
        $count++;
        if ($count >= $max) {
            break;
        }
    }
    if (count($users) > $max) {
        $message .= "\n...and " . (count($users) - $max) . " more users.";
    }
    
    sendWhatsAppResponse($phone, $message);
}

function handleWhatsAppHotspotAdd($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 3) {
        sendWhatsAppResponse($phone, "Format: /hs_add <user> <pass> <profile>");
        return;
    }
    
    $user = $parts[0];
    $pass = $parts[1];
    $profile = $parts[2];

    $phoneDigits = preg_replace('/\D+/', '', (string) $phone);
    $ts = date('YmdHis');
    $comment = "parent:{$profile} vc-wa-{$phoneDigits}-{$ts}";
    $ok = mikrotikAddHotspotUser($user, $pass, $profile, ['comment' => $comment]);
    if ($ok) {
        sendWhatsAppResponse($phone, "Hotspot user {$user} added successfully with profile {$profile}.");
    } else {
        sendWhatsAppResponse($phone, "Failed to add Hotspot user {$user}.");
    }
}

function handleWhatsAppHotspotDel($phone, $args) {
    if (!isWhatsAppAdmin($phone)) {
        sendWhatsAppResponse($phone, "MikroTik commands are for admin only.");
        return;
    }
    
    $user = trim($args);
    if ($user === '') {
        sendWhatsAppResponse($phone, "Format: /hs_del <user>");
        return;
    }
    
    $ok = mikrotikDeleteHotspotUser($user);
    if ($ok) {
        sendWhatsAppResponse($phone, "Hotspot user {$user} deleted successfully.");
    } else {
        sendWhatsAppResponse($phone, "Failed to delete Hotspot user {$user}.");
    }
}
