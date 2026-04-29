<?php
/**
 * Webhook Handler - Telegram Bot
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
    
    logActivity('TELEGRAM_WEBHOOK', "Received webhook");
    
    // Parse JSON data
    $data = json_decode($json, true);
    
    if (!$data) {
        logError('Telegram webhook: Invalid JSON');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    // Log webhook
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO webhook_logs (source, payload, status_code, response, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute(['telegram', $json, 200, 'Received']);
    
    $message = $data['message'] ?? null;
    $callbackQuery = $data['callback_query'] ?? null;
    
    if ($callbackQuery) {
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $callbackDataString = $callbackQuery['data'] ?? '';
        $callbackData = [];
        
        if ($callbackDataString !== '') {
            parse_str($callbackDataString, $callbackData);
        }
        
        $action = $callbackData['action'] ?? '';
        
        switch ($action) {
            case 'pay_invoice':
                handlePayInvoice($chatId, $callbackData);
                break;
                
            case 'check_status':
                handleCheckStatus($chatId, $callbackData);
                break;
                
            case 'help':
                handleHelp($chatId);
                break;
                
            case 'billing_menu':
                handleBillingMenu($chatId);
                break;

            case 'billing_picklist':
                handleBillingCustomerPicker($chatId, $callbackData);
                break;

            case 'billing_pick':
                handleBillingCustomerPickCallback($chatId, $callbackData, $callbackQuery);
                break;

            case 'billing_show_invoices':
                handleBillingInvoicesByCustomerCallback($chatId, $callbackData, $callbackQuery);
                break;
                
            case 'billing_help_cek':
                handleBillingHelpCek($chatId);
                break;
                
            case 'billing_help_isolir':
                handleBillingHelpIsolir($chatId);
                break;
                
            case 'billing_help_bukaisolir':
                handleBillingHelpBukaIsolir($chatId);
                break;
            
            case 'billing_help_invoice':
                handleBillingHelpInvoice($chatId);
                break;
            
            case 'billing_help_paid':
                handleBillingHelpPaid($chatId);
                break;
            
            case 'billing_mark_paid':
                handleBillingMarkPaidCallback($chatId, $callbackData, $callbackQuery);
                break;
            
            case 'mt_pppoe_kick':
                handlePppoeKickCallback($chatId, $callbackData);
                break;
            
            case 'mt_pppoe_disable':
                handlePppoeDisableCallback($chatId, $callbackData);
                break;
            
            case 'mt_pppoe_enable':
                handlePppoeEnableCallback($chatId, $callbackData);
                break;
            
            case 'mt_pppoe_del':
                handlePppoeDelCallback($chatId, $callbackData, $callbackQuery);
                break;
            
            case 'mt_hotspot_del':
                handleHotspotDelCallback($chatId, $callbackData, $callbackQuery);
                break;

            case 'mt_hotspot_add_menu':
                handleHotspotAddMenu($chatId, $callbackData);
                break;

            case 'mt_hotspot_add_pick':
                handleHotspotAddPickCallback($chatId, $callbackData, $callbackQuery);
                break;
            
            case 'mt_voucher_menu':
                handleHotspotVoucherMenu($chatId, $callbackData);
                break;
            
            case 'mt_voucher_gen':
                handleHotspotVoucherGenerateCallback($chatId, $callbackData, $callbackQuery);
                break;
            
            case 'mikrotik_menu':
                handleMikrotikMenu($chatId);
                break;
            
            case 'mt_resource':
                handleMikrotikResource($chatId);
                break;
            
            case 'mt_online':
                handleMikrotikOnline($chatId);
                break;
            
            case 'mt_ping_help':
                handleMikrotikPingHelp($chatId);
                break;
            
            case 'mt_pppoe_help':
                handleMikrotikPppoeHelp($chatId);
                break;
            
            case 'mt_hotspot_help':
                handleMikrotikHotspotHelp($chatId);
                break;
                
            default:
                handleHelp($chatId);
        }
    } elseif ($message) {
        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');
        
        if ($text !== '' && $text[0] === '/') {
            handleCommand($chatId, $text);
        } else {
            handleRegularMessage($chatId, $text);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Webhook processed']);
    
} catch (Exception $e) {
    logError("Telegram webhook error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handlePayInvoice($chatId, $data) {
    $invoiceId = $data['invoice_id'] ?? '';
    
    // Get invoice details
    $invoice = fetchOne("SELECT i.*, c.name as customer_name, c.phone as customer_phone FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = ?", [$invoiceId]);
    
    if (!$invoice) {
        sendMessage($chatId, "❌ Invoice not found.");
        return;
    }
    $paymentLink = invoicePayUrl((string) $invoice['invoice_number']);
    
    $message = "💳 *Invoice #{$invoice['invoice_number']}*\n\n";
    $message .= "Customer: {$invoice['customer_name']}\n";
    $message .= "Amount: " . formatCurrency($invoice['amount']) . "\n";
    $message .= "Due Date: " . formatDate($invoice['due_date']) . "\n\n";
    $message .= "Please pay via the following link:\n";
    $message .= $paymentLink;
    
    sendMessage($chatId, $message);
}

function handleCheckStatus($chatId, $data) {
    $phone = $data['phone'] ?? '';
    $phone = preg_replace('/[^0-9]/', '', (string)$phone);

    // Get customer by phone
    $customer = fetchOne("SELECT * FROM customers WHERE phone LIKE ?", ["%{$phone}"]);
    
    if (!$customer) {
        sendMessage($chatId, "❌ Customer not found with that phone number.");
        return;
    }
    
    // Get customer status
    $status = $customer['status'] === 'active' ? 'Active' : 'Isolated';
    
    $message = "📊 *Customer Status*\n\n";
    $message .= "Name: {$customer['name']}\n";
    $message .= "Phone: {$customer['phone']}\n";
    $message .= "PPPoE Username: {$customer['pppoe_username']}\n";
    $message .= "Status: {$status}\n";
    
    if ($customer['status'] === 'isolated') {
        $message .= "\n⚠️ Connection is currently isolated due to unpaid invoice.";
    }
    
    sendMessage($chatId, $message);
}

function handleHelp($chatId) {
    $message = "🤖 GEMBOK Bot Commands\n\n";
    $message .= "For customers:\n";
    $message .= "/pay_invoice &lt;invoice_id&gt; - Check and pay invoice\n";
    $message .= "/check_status &lt;phone&gt; - Check customer status\n";
    $message .= "/help - Show this help\n\n";
    
    if (isAdminChat($chatId)) {
        $message .= "For admin:\n";
        $message .= "/menu - Show main menu\n";
        $message .= "/billing_cek &lt;pppoe_username&gt; - Check customer billing\n";
        $message .= "/billing_invoice &lt;pppoe_username&gt; - List customer invoices\n";
        $message .= "/billing_isolir &lt;pppoe_username&gt; - Isolate customer\n";
        $message .= "/billing_bukaisolir &lt;pppoe_username&gt; - Remove customer isolation\n";
        $message .= "/billing_paid &lt;invoice_no&gt; - Mark invoice as paid\n";
        $message .= "/invoice_create &lt;pppoe_username&gt; &lt;amount&gt; &lt;due_date&gt; [desc]\n";
        $message .= "/invoice_edit &lt;invoice_number&gt; &lt;amount&gt; &lt;due_date&gt; &lt;status&gt;\n";
        $message .= "/invoice_delete &lt;invoice_number&gt;\n";
        $message .= "/mt_setprofile &lt;pppoe_username&gt; &lt;profile&gt; - Change PPPoE profile\n";
        $message .= "/mt_resource - Check MikroTik resources\n";
        $message .= "/mt_online - Check online PPPoE users\n";
        $message .= "/mt_ping &lt;ip/host&gt; - Ping from MikroTik\n";
        $message .= "/pppoe_list - List PPPoE users\n";
        $message .= "/pppoe_add &lt;user&gt; &lt;pass&gt; &lt;profile&gt; - Add PPPoE user\n";
        $message .= "/pppoe_edit &lt;user&gt; &lt;pass&gt; &lt;profile&gt; - Edit PPPoE user\n";
        $message .= "/pppoe_del &lt;user&gt; - Delete PPPoE user\n";
        $message .= "/pppoe_disable &lt;user&gt; - Disable PPPoE user\n";
        $message .= "/pppoe_enable &lt;user&gt; - Enable PPPoE user\n";
        $message .= "/pppoe_profile_list\n";
        $message .= "/hs_list - List Hotspot users\n";
        $message .= "/hs_addmenu - Add Hotspot user (menu)\n";
        $message .= "/hs_add &lt;user&gt; &lt;pass&gt; &lt;profile&gt; - Add Hotspot user\n";
        $message .= "/hs_del &lt;user&gt; - Delete Hotspot user\n";
    }
    
    sendMessage($chatId, $message);
}

function handleRegularMessage($chatId, $text) {
    $trimmedText = trim((string)$text);
    logActivity('TELEGRAM_MESSAGE', "From: {$chatId}, Len: " . strlen($trimmedText));

    $message = "Thank you for your message.\n\n";
    $message .= "To use this bot, please use the available commands.\n";
    $message .= "Type /help to see the list of commands.";
    
    sendMessage($chatId, $message);
}

function sendMessage($chatId, $text, $options = []) {
    $token = (string) getSetting('TELEGRAM_BOT_TOKEN', '');
    if ($token === '') {
        logError('Telegram bot token not configured');
        return false;
    }
    
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if (!empty($options)) {
        $data = array_merge($data, $options);
        if (isset($data['reply_markup']) && is_array($data['reply_markup'])) {
            $data['reply_markup'] = json_encode($data['reply_markup']);
        }
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    if ($response === false) {
        $curlError = curl_error($ch);
        logError("Telegram sendMessage curl error: {$curlError}");
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    
    logActivity('TELEGRAM_SEND', "To: {$chatId}, Status code: {$httpCode}");
    
    return $httpCode === 200;
}

function editMessageText($chatId, $messageId, $text, $replyMarkup = null) {
    $token = (string) getSetting('TELEGRAM_BOT_TOKEN', '');
    if ($token === '') {
        logError('Telegram bot token not configured');
        return false;
    }
    
    $url = "https://api.telegram.org/bot" . $token . "/editMessageText";
    
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($replyMarkup !== null) {
        $data['reply_markup'] = is_array($replyMarkup) ? json_encode($replyMarkup) : $replyMarkup;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    if ($response === false) {
        $curlError = curl_error($ch);
        logError("Telegram editMessageText curl error: {$curlError}");
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    
    logActivity('TELEGRAM_EDIT', "To: {$chatId}, Msg: {$messageId}, Status code: {$httpCode}");
    
    return $httpCode === 200;
}

function answerCallbackQuery($callbackQueryId, $text = null, $showAlert = false) {
    $token = (string) getSetting('TELEGRAM_BOT_TOKEN', '');
    if ($token === '') {
        logError('Telegram bot token not configured');
        return false;
    }

    if ($callbackQueryId === null || $callbackQueryId === '') {
        return false;
    }

    $url = "https://api.telegram.org/bot" . $token . "/answerCallbackQuery";

    $data = [
        'callback_query_id' => $callbackQueryId,
        'show_alert' => $showAlert ? 'true' : 'false'
    ];

    if ($text !== null && $text !== '') {
        $data['text'] = $text;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    if ($response === false) {
        $curlError = curl_error($ch);
        logError("Telegram answerCallbackQuery curl error: {$curlError}");
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    logActivity('TELEGRAM_ANSWER_CALLBACK', "Status code: {$httpCode}");

    return $httpCode === 200;
}

function isAdminChat($chatId) {
    $adminChatId = getSetting('TELEGRAM_ADMIN_CHAT_ID', '');
    if ($adminChatId === '') {
        return false;
    }
    return (string)$chatId === (string)$adminChatId;
}

function handleCommand($chatId, $text) {
    $parts = explode(' ', trim($text), 2);
    $command = strtolower($parts[0]);
    $args = $parts[1] ?? '';
    
    switch ($command) {
        case '/start':
        case '/menu':
            handleMenu($chatId);
            break;
            
        case '/help':
            handleHelp($chatId);
            break;
            
        case '/pay_invoice':
            $invoiceId = trim($args);
            if ($invoiceId === '') {
                sendMessage($chatId, "Format: /pay_invoice &lt;invoice_id&gt;");
                break;
            }
            handlePayInvoice($chatId, ['invoice_id' => $invoiceId]);
            break;
            
        case '/check_status':
            $phone = trim($args);
            if ($phone === '') {
                sendMessage($chatId, "Format: /check_status &lt;no_hp&gt;");
                break;
            }
            handleCheckStatus($chatId, ['phone' => $phone]);
            break;
            
        case '/billing_cek':
            handleBillingCheck($chatId, $args);
            break;
            
        case '/billing_invoice':
            handleBillingInvoice($chatId, $args);
            break;
            
        case '/billing_isolir':
            handleBillingIsolir($chatId, $args);
            break;
            
        case '/billing_bukaisolir':
            handleBillingBukaIsolir($chatId, $args);
            break;
        
        case '/billing_paid':
            handleBillingPaid($chatId, $args);
            break;
            
        case '/invoice_create':
            handleInvoiceCreate($chatId, $args);
            break;
            
        case '/invoice_edit':
            handleInvoiceEdit($chatId, $args);
            break;
            
        case '/invoice_delete':
            handleInvoiceDelete($chatId, $args);
            break;
            
        case '/mt_resource':
            handleMikrotikResource($chatId);
            break;
            
        case '/mt_online':
            handleMikrotikOnline($chatId);
            break;
            
        case '/mt_ping':
            handleMikrotikPing($chatId, $args);
            break;
        
        case '/mt_setprofile':
            handleMikrotikSetProfileeeeeeeeeeee($chatId, $args);
            break;
        
        case '/pppoe_list':
            handlePppoeList($chatId);
            break;
        
        case '/pppoe_add':
            handlePppoeAdd($chatId, $args);
            break;
        
        case '/pppoe_edit':
            handlePppoeEdit($chatId, $args);
            break;
        
        case '/pppoe_del':
            handlePppoeDel($chatId, $args);
            break;
        
        case '/pppoe_disable':
            handlePppoeDisable($chatId, $args);
            break;
        
        case '/pppoe_enable':
            handlePppoeEnable($chatId, $args);
            break;
        
        case '/pppoe_profile_list':
            handlePppoeProfileeeeeeeeeeeeList($chatId);
            break;
        
        case '/hs_list':
            handleHotspotList($chatId);
            break;
        
        case '/hs_add':
            handleHotspotAdd($chatId, $args);
            break;

        case '/hs_addmenu':
            handleHotspotAddMenu($chatId);
            break;
        
        case '/hs_del':
            handleHotspotDel($chatId, $args);
            break;
            
        default:
            handleRegularMessage($chatId, $text);
    }
}

function handleMenu($chatId) {
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📄 Billing', 'callback_data' => 'action=billing_menu'],
                ['text' => '📡 MikroTik', 'callback_data' => 'action=mikrotik_menu']
            ],
            [
                ['text' => '❓ Help', 'callback_data' => 'action=help']
            ]
        ]
    ];
    
    sendMessage($chatId, "Select menu:", ['reply_markup' => $keyboard]);
}

function handleBillingMenu($chatId) {
    if (!isAdminChat($chatId)) {
        sendMessage($chatId, "Billing commands are for admin only.");
        return;
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📄 Check Billing', 'callback_data' => 'action=billing_picklist&mode=cek&page=1'],
                ['text' => '📜 Invoice List', 'callback_data' => 'action=billing_picklist&mode=invoice&page=1']
            ],
            [
                ['text' => '🔒 Isolate Customer', 'callback_data' => 'action=billing_picklist&mode=isolir&page=1'],
                ['text' => '🔓 Remove Isolation', 'callback_data' => 'action=billing_picklist&mode=buka&page=1']
            ],
            [
                ['text' => '✅ Mark as Paid', 'callback_data' => 'action=billing_help_paid']
            ],
            [
                ['text' => '⬅️ Back', 'callback_data' => 'action=menu']
            ]
        ]
    ];
    
    sendMessage($chatId, "Admin Billing Menu:", ['reply_markup' => $keyboard]);
}

function handleBillingHelpCek($chatId) {
    if (!isAdminChat($chatId)) return;
    $message = "📄 Check Customer Billing\n\nUse the command:\n/billing_cek &lt;pppoe_username&gt;\n\nExample:\n/billing_cek customer001";
    sendMessage($chatId, $message);
}

function handleBillingHelpIsolir($chatId) {
    if (!isAdminChat($chatId)) return;
    $message = "🔒 Isolate Customer\n\nUse the command:\n/billing_isolir &lt;pppoe_username&gt;\n\nExample:\n/billing_isolir customer001";
    sendMessage($chatId, $message);
}

function handleBillingHelpBukaIsolir($chatId) {
    if (!isAdminChat($chatId)) return;
    $message = "🔓 Remove Customer Isolation\n\nUse the command:\n/billing_bukaisolir &lt;pppoe_username&gt;\n\nExample:\n/billing_bukaisolir customer001";
    sendMessage($chatId, $message);
}

function handleBillingHelpInvoice($chatId) {
    if (!isAdminChat($chatId)) return;
    $message = "📜 Customer Invoice List\n\nUse the command:\n/billing_invoice &lt;pppoe_username&gt;\n\nExample:\n/billing_invoice customer001";
    sendMessage($chatId, $message);
}

function handleBillingHelpPaid($chatId) {
    if (!isAdminChat($chatId)) return;
    $message = "✅ Mark Invoice as Paid\n\nUse the command:\n/billing_paid &lt;invoice_no&gt;\n\nExample:\n/billing_paid INV-2026-0001";
    sendMessage($chatId, $message);
}

function handleBillingCheck($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $username = trim($args);
    if ($username === '') {
        sendMessage($chatId, "Format: /billing_cek &lt;pppoe_username&gt;");
        return;
    }
    
    $customer = fetchOne("SELECT c.*, p.name AS package_name, p.price AS package_price FROM customers c LEFT JOIN packages p ON c.package_id = p.id WHERE c.pppoe_username = ?", [$username]);
    if (!$customer) {
        sendMessage($chatId, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    $invoice = fetchOne("SELECT * FROM invoices WHERE customer_id = ? ORDER BY due_date DESC LIMIT 1", [$customer['id']]);
    $message = "📄 Customer Billing\n\nName: {$customer['name']}\nPPPoE: {$customer['pppoe_username']}\nPackage: " . ($customer['package_name'] ?? '-') . "\n";
    
    if ($invoice) {
        $status = $invoice['status'] === 'paid' ? 'Paid' : 'Unpaid';
        $message .= "Invoice: {$invoice['invoice_number']}\nAmount: " . formatCurrency($invoice['amount']) . "\nDue Date: " . formatDate($invoice['due_date']) . "\nStatus: {$status}\n";
    } else {
        $message .= "No invoices found for this customer.\n";
    }
    
    $options = [];
    if ($invoice) {
        $buttons = [];
        $buttons[] = [['text' => '📜 Invoice List', 'callback_data' => 'action=billing_show_invoices&cid=' . urlencode((string) $customer['id'])]];
        if ($invoice['status'] !== 'paid') {
            $buttons[] = [['text' => '✅ Mark as Paid', 'callback_data' => 'action=billing_mark_paid&inv=' . urlencode($invoice['invoice_number'])]];
        }
        $buttons[] = [['text' => '⬅️ Billing Menu', 'callback_data' => 'action=billing_menu']];
        $options['reply_markup'] = ['inline_keyboard' => $buttons];
    }
    sendMessage($chatId, $message, $options);
}

function handleBillingInvoice($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $username = trim($args);
    if ($username === '') {
        sendMessage($chatId, "Format: /billing_invoice &lt;pppoe_username&gt;");
        return;
    }
    
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
    if (!$customer) {
        sendMessage($chatId, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    $invoices = fetchAll("SELECT * FROM invoices WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5", [$customer['id']]);
    if (empty($invoices)) {
        sendMessage($chatId, "No invoices found for customer {$customer['name']}.");
        return;
    }
    
    $message = "📜 Invoice List - {$customer['name']}\n\n";
    $keyboard = ['inline_keyboard' => []];
    
    foreach ($invoices as $inv) {
        $status = $inv['status'] === 'paid' ? 'Paid' : 'Unpaid';
        $message .= "#{$inv['invoice_number']} - " . formatCurrency($inv['amount']) . " - {$status}\nDue Date: " . formatDate($inv['due_date']) . "\n\n";
        
        if ($inv['status'] !== 'paid') {
            $keyboard['inline_keyboard'][] = [['text' => "✅ {$inv['invoice_number']}", 'callback_data' => 'action=billing_mark_paid&inv=' . urlencode($inv['invoice_number'])]];
        }
    }
    
    if (empty($keyboard['inline_keyboard'])) {
        sendMessage($chatId, $message);
    } else {
        sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
    }
}

function handleBillingCustomerPicker($chatId, $data) {
    if (!isAdminChat($chatId)) return;

    $mode = strtolower(trim((string) ($data['mode'] ?? 'cek')));
    $page = (int) ($data['page'] ?? 1);
    if ($page < 1) $page = 1;

    $where = [];
    $params = [];
    if ($mode === 'buka') {
        $where[] = "status = 'isolated'";
    } elseif ($mode === 'isolir') {
        $where[] = "status = 'active'";
    }

    $sql = "SELECT id, name, pppoe_username, status FROM customers";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY name ASC LIMIT 400";
    $customers = fetchAll($sql, $params);

    if (empty($customers)) {
        sendMessage($chatId, "No customers to display.", [
            'reply_markup' => ['inline_keyboard' => [[['text' => '⬅️ Menu Billing', 'callback_data' => 'action=billing_menu']]]]
        ]);
        return;
    }

    $perPage = 12;
    $total = count($customers);
    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages < 1) $totalPages = 1;
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;
    $items = array_slice($customers, $offset, $perPage);

    $title = 'Select Customer';
    if ($mode === 'cek') $title = 'Select Customer (Check Billing)';
    if ($mode === 'invoice') $title = 'Select Customer (Invoice List)';
    if ($mode === 'isolir') $title = 'Select Customer (Isolate)';
    if ($mode === 'buka') $title = 'Select Customer (Remove Isolation)';

    $message = "👤 *{$title}*\nPage {$page}/{$totalPages}";
    $keyboard = ['inline_keyboard' => []];
    $row = [];

    foreach ($items as $c) {
        $cid = (string) ($c['id'] ?? '');
        $name = (string) ($c['name'] ?? '');
        $pppoe = (string) ($c['pppoe_username'] ?? '');
        if ($cid === '' || $pppoe === '') {
            continue;
        }
        $label = $name !== '' ? $name : $pppoe;
        if (mb_strlen($label) > 18) {
            $label = mb_substr($label, 0, 18) . '…';
        }
        $row[] = ['text' => $label, 'callback_data' => 'action=billing_pick&mode=' . urlencode($mode) . '&cid=' . urlencode($cid)];
        if (count($row) === 2) {
            $keyboard['inline_keyboard'][] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard['inline_keyboard'][] = $row;
    }

    $nav = [];
    if ($page > 1) {
        $nav[] = ['text' => '⬅️ Prev', 'callback_data' => 'action=billing_picklist&mode=' . urlencode($mode) . '&page=' . ($page - 1)];
    }
    if ($page < $totalPages) {
        $nav[] = ['text' => 'Next ➡️', 'callback_data' => 'action=billing_picklist&mode=' . urlencode($mode) . '&page=' . ($page + 1)];
    }
    if (!empty($nav)) {
        $keyboard['inline_keyboard'][] = $nav;
    }
    $keyboard['inline_keyboard'][] = [['text' => '⬅️ Menu Billing', 'callback_data' => 'action=billing_menu']];

    sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
}

function handleBillingCustomerPickCallback($chatId, $data, $callbackQuery) {
    if (!isAdminChat($chatId)) return;

    $callbackQueryId = $callbackQuery['id'] ?? null;
    answerCallbackQuery($callbackQueryId);

    $mode = strtolower(trim((string) ($data['mode'] ?? 'cek')));
    $cid = (int) ($data['cid'] ?? 0);
    if ($cid <= 0) {
        sendMessage($chatId, "Invalid customer data.");
        return;
    }

    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$cid]);
    if (!$customer) {
        sendMessage($chatId, "Customer not found.");
        return;
    }

    $username = (string) ($customer['pppoe_username'] ?? '');
    if ($username === '') {
        sendMessage($chatId, "Customer PPPoE username is empty.");
        return;
    }

    if ($mode === 'invoice') {
        handleBillingInvoice($chatId, $username);
        return;
    }
    if ($mode === 'isolir') {
        handleBillingIsolir($chatId, $username);
        return;
    }
    if ($mode === 'buka') {
        handleBillingBukaIsolir($chatId, $username);
        return;
    }

    handleBillingCheck($chatId, $username);
}

function handleBillingInvoicesByCustomerCallback($chatId, $data, $callbackQuery) {
    if (!isAdminChat($chatId)) return;

    $callbackQueryId = $callbackQuery['id'] ?? null;
    answerCallbackQuery($callbackQueryId);

    $cid = (int) ($data['cid'] ?? 0);
    if ($cid <= 0) {
        sendMessage($chatId, "Invalid customer data.");
        return;
    }

    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$cid]);
    if (!$customer) {
        sendMessage($chatId, "Customer not found.");
        return;
    }
    $username = (string) ($customer['pppoe_username'] ?? '');
    if ($username === '') {
        sendMessage($chatId, "Customer PPPoE username is empty.");
        return;
    }
    handleBillingInvoice($chatId, $username);
}

function handleBillingIsolir($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $username = trim($args);
    if ($username === '') {
        sendMessage($chatId, "Format: /billing_isolir &lt;pppoe_username&gt;");
        return;
    }
    
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
    if (!$customer) {
        sendMessage($chatId, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    if (isCustomerIsolated($customer['id'])) {
        sendMessage($chatId, "This customer is already isolated.");
        return;
    }
    
    if (isolateCustomer($customer['id'])) {
        sendMessage($chatId, "Customer {$customer['name']} successfully isolated.");
    } else {
        sendMessage($chatId, "Failed to isolate customer {$customer['name']}.");
    }
}

function handleBillingBukaIsolir($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $username = trim($args);
    if ($username === '') {
        sendMessage($chatId, "Format: /billing_bukaisolir &lt;pppoe_username&gt;");
        return;
    }
    
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
    if (!$customer) {
        sendMessage($chatId, "Customer with PPPoE username {$username} not found.");
        return;
    }
    
    if (!isCustomerIsolated($customer['id'])) {
        sendMessage($chatId, "This customer is not isolated.");
        return;
    }
    
    if (unisolateCustomer($customer['id'], ['send_whatsapp' => true])) {
        sendMessage($chatId, "Customer {$customer['name']} has been un-isolated successfully.");
    } else {
        sendMessage($chatId, "Failed to un-isolate customer {$customer['name']}.");
    }
}

function handleBillingPaid($chatId, $args, $silent = false) {
    if (!isAdminChat($chatId)) return;
    $invoiceNumber = trim($args);
    if ($invoiceNumber === '') {
        sendMessage($chatId, "Format: /billing_paid &lt;invoice_no&gt;");
        return;
    }
    
    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ?", [$invoiceNumber]);
    if (!$invoice) {
        sendMessage($chatId, "Invoice {$invoiceNumber} not found.");
        return;
    }
    
    if ($invoice['status'] === 'paid') {
        sendMessage($chatId, "Invoice {$invoiceNumber} is already paid.");
        return;
    }
    
    $updateData = ['status' => 'paid', 'updated_at' => date('Y-m-d H:i:s'), 'paid_at' => date('Y-m-d H:i:s'), 'payment_method' => 'Telegram Bot'];
    update('invoices', $updateData, 'id = ?', [$invoice['id']]);
    sendInvoicePaidWhatsapp($invoiceNumber, 'telegram', ['payment_method' => 'Telegram Bot']);
    
    if (isCustomerIsolated($invoice['customer_id'])) {
        unisolateCustomer($invoice['customer_id']);
    }
    
    logActivity('BOT_INVOICE_PAID', "Invoice: {$invoice['invoice_number']}");
    if (!$silent) {
        sendMessage($chatId, "Invoice {$invoiceNumber} marked as paid successfully.");
    }
}

function handleInvoiceCreate($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 3) {
        sendMessage($chatId, "Format: /invoice_create <u_pppoe> <amount> <due_date> [desc]");
        return;
    }
    
    $username = $parts[0];
    $amount = (float)$parts[1];
    $dueDate = $parts[2];
    $description = (count($parts) > 3) ? trim(implode(' ', array_slice($parts, 3))) : '';
    
    if (strtotime($dueDate) === false) {
        sendMessage($chatId, "Invalid date format (YYYY-MM-DD).");
        return;
    }
    
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
    if (!$customer) {
        sendMessage($chatId, "Customer {$username} not found.");
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
    if ($description !== '') $invoiceData['description'] = $description;
    
    insert('invoices', $invoiceData);
    sendMessage($chatId, "Invoice {$invoiceData['invoice_number']} created.");
}

function handleInvoiceEdit($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 4) {
        sendMessage($chatId, "Format: /invoice_edit <inv> <amount> <due> <status>");
        return;
    }
    
    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ?", [$parts[0]]);
    if (!$invoice) {
        sendMessage($chatId, "Invoice {$parts[0]} not found.");
        return;
    }
    
    $status = strtolower($parts[3]);
    $updateData = [
        'amount' => (float)$parts[1],
        'due_date' => $parts[2],
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($status === 'paid' && $invoice['status'] !== 'paid') {
        $updateData['paid_at'] = date('Y-m-d H:i:s');
        $updateData['payment_method'] = 'Telegram Bot';
        if (isCustomerIsolated($invoice['customer_id'])) unisolateCustomer($invoice['customer_id']);
    }
    
    update('invoices', $updateData, 'id = ?', [$invoice['id']]);
    if ($status === 'paid' && $invoice['status'] !== 'paid') {
        sendInvoicePaidWhatsapp((string) $invoice['invoice_number'], 'telegram', ['payment_method' => 'Telegram Bot']);
    }
    sendMessage($chatId, "Invoice updated.");
}

function handleInvoiceDelete($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ?", [trim($args)]);
    if (!$invoice) {
        sendMessage($chatId, "Invoice not found.");
        return;
    }
    if ($invoice['status'] === 'paid') {
        sendMessage($chatId, "Cannot delete a paid invoice.");
        return;
    }
    delete('invoices', 'id = ?', [$invoice['id']]);
    sendMessage($chatId, "Invoice deleted.");
}

function handlePppoeProfileeeeeeeeeeeeList($chatId) {
    if (!isAdminChat($chatId)) return;
    $profiles = mikrotikGetProfileeeeeeeeeeees();
    if (empty($profiles)) {
        sendMessage($chatId, "Failed to retrieve profiles.");
        return;
    }
    $msg = "👤 *PPPoE Profileeeeeeeeeeees*\n\n";
    foreach ($profiles as $p) {
        $msg .= "- {$p['name']} | {$p['rate-limit']}\n";
    }
    sendMessage($chatId, $msg);
}

function handleBillingMarkPaidCallback($chatId, $data, $callbackQuery) {
    if (!isAdminChat($chatId)) return;
    $invoiceNumber = $data['inv'] ?? '';
    if ($invoiceNumber === '') {
        sendMessage($chatId, "Invalid invoice data.");
        return;
    }
    
    handleBillingPaid($chatId, $invoiceNumber, true);
    
    $messageId = $callbackQuery['message']['message_id'] ?? null;
    if ($messageId) {
        $invoice = fetchOne("SELECT * FROM invoices WHERE invoice_number = ?", [$invoiceNumber]);
        if ($invoice) {
            $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$invoice['customer_id']]);
            if ($customer) {
                // Refresh list
                handleBillingInvoice($chatId, $customer['pppoe_username']);
            }
        }
    }
}

function handleMikrotikMenu($chatId) {
    if (!isAdminChat($chatId)) return;
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '📊 Resource', 'callback_data' => 'action=mt_resource'], ['text' => '📡 Online PPPoE', 'callback_data' => 'action=mt_online']],
            [['text' => '📶 Ping IP/Host', 'callback_data' => 'action=mt_ping_help']],
            [['text' => '👤 PPPoE Commands', 'callback_data' => 'action=mt_pppoe_help'], ['text' => '🌐 Hotspot Commands', 'callback_data' => 'action=mt_hotspot_help']],
            [['text' => '➕ Add Hotspot User', 'callback_data' => 'action=mt_hotspot_add_menu']],
            [['text' => '🎫 Generate Voucher', 'callback_data' => 'action=mt_voucher_menu']]
        ]
    ];
    sendMessage($chatId, "Admin MikroTik Menu:", ['reply_markup' => $keyboard]);
}

function handleMikrotikResource($chatId) {
    if (!isAdminChat($chatId)) return;
    $res = mikrotikGetResource();
    if (!$res) {
        sendMessage($chatId, "Failed to retrieve MikroTik resources.");
        return;
    }
    
    $cpu = $res['cpu-load'] ?? '-';
    $memFree = $res['free-memory'] ?? '-';
    $memTotal = $res['total-memory'] ?? '-';
    $uptime = $res['uptime'] ?? '-';
    
    $message = "📊 *MikroTik Resources*\n\nCPU Load: {$cpu}%\nMemory: {$memFree} / {$memTotal} bytes\nUptime: {$uptime}";
    sendMessage($chatId, $message);
}

function handleMikrotikOnline($chatId) {
    if (!isAdminChat($chatId)) return;
    $sessions = mikrotikGetActiveSessions();
    if (!is_array($sessions)) {
        sendMessage($chatId, "Failed to retrieve active PPPoE sessions.");
        return;
    }
    
    $total = count($sessions);
    if ($total === 0) {
        sendMessage($chatId, "No PPPoE sessions currently online.");
        return;
    }
    
    $message = "📡 *PPPoE Online: {$total}*\n\n";
    $keyboard = ['inline_keyboard' => []];
    $count = 0;
    
    foreach ($sessions as $s) {
        $name = $s['name'] ?? '-';
        $message .= "- {$name} ({$s['address']}) up {$s['uptime']}\n";
        if ($count < 10) {
            $keyboard['inline_keyboard'][] = [['text' => "❌ Kick {$name}", 'callback_data' => 'action=mt_pppoe_kick&name=' . urlencode($name)]];
        }
        $count++;
        if ($count >= 20) break;
    }
    
    sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
}

function handleMikrotikPing($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $target = trim($args);
    if ($target === '') return;
    
    $result = mikrotikPing($target);
    if (!$result) {
        sendMessage($chatId, "Failed to execute ping.");
        return;
    }
    
    $avg = $result['avg'] !== null ? round($result['avg'], 2) . " ms" : '-';
    $message = "📶 *Ping Result*\nTarget: {$target}\nSent: {$result['sent']}\nRecv: {$result['received']}\nLoss: {$result['loss']}%\nAvg: {$avg}";
    sendMessage($chatId, $message);
}

function handleMikrotikPingHelp($chatId) {
    sendMessage($chatId, "📶 *Ping Help*\n/mt_ping &lt;ip/host&gt;");
}

function handleMikrotikSetProfileeeeeeeeeeee($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 2) {
        sendMessage($chatId, "Format: /mt_setprofile &lt;user&gt; &lt;profile&gt;");
        return;
    }
    
    if (mikrotikSetProfileeeeeeeeeeee($parts[0], $parts[1])) {
        mikrotikRemoveActiveSessionByName($parts[0]);
        sendMessage($chatId, "Profileeeeeeeeeeee for {$parts[0]} changed to {$parts[1]}.");
    } else {
        sendMessage($chatId, "Failed to change profile.");
    }
}

function handleMikrotikPppoeHelp($chatId) {
    $msg = "👤 *PPPoE Commands*\n/pppoe_list\n/pppoe_add &lt;u&gt; &lt;p&gt; &lt;prof&gt;\n/pppoe_edit &lt;u&gt; &lt;p&gt; &lt;prof&gt;\n/pppoe_del &lt;u&gt;\n/pppoe_disable &lt;u&gt;\n/pppoe_enable &lt;u&gt;";
    sendMessage($chatId, $msg);
}

function handleMikrotikHotspotHelp($chatId) {
    $msg = "🌐 *Hotspot Commands*\n/hs_list\n/hs_addmenu\n/hs_add &lt;u&gt; &lt;p&gt; &lt;prof&gt;\n/hs_del &lt;u&gt;\n\n🎫 Voucher:\nOpen MikroTik Menu → Generate Voucher";
    sendMessage($chatId, $msg);
}

function handleHotspotAddMenu($chatId, $data = []) {
    if (!isAdminChat($chatId)) return;

    $catalog = getPublicVoucherCatalog();
    if (empty($catalog)) {
        sendMessage($chatId, "No hotspot profiles with pricing found. Ensure Hotspot User Profileeeeeeeeeeees in MikroTik have an on-login script with price/selling_price.");
        return;
    }

    usort($catalog, function ($a, $b) {
        return ((int) ($a['price'] ?? 0)) <=> ((int) ($b['price'] ?? 0));
    });

    $page = (int) ($data['page'] ?? 1);
    if ($page < 1) $page = 1;
    $perPage = 12;
    $total = count($catalog);
    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages < 1) $totalPages = 1;
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;
    $items = array_slice($catalog, $offset, $perPage);

    $message = "➕ *Add Hotspot User*\n\nSelect a profile (only those with pricing):";
    $keyboard = ['inline_keyboard' => []];

    $row = [];
    foreach ($items as $item) {
        $profileName = (string) ($item['profile_name'] ?? '');
        if ($profileName === '') {
            continue;
        }
        $price = (int) ($item['price'] ?? 0);
        $label = $profileName;
        if ($price > 0) {
            $label .= ' - ' . formatCurrency($price);
        }
        $row[] = ['text' => $label, 'callback_data' => 'action=mt_hotspot_add_pick&profile=' . urlencode($profileName)];
        if (count($row) === 2) {
            $keyboard['inline_keyboard'][] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard['inline_keyboard'][] = $row;
    }

    $nav = [];
    if ($page > 1) {
        $nav[] = ['text' => '⬅️ Prev', 'callback_data' => 'action=mt_hotspot_add_menu&page=' . ($page - 1)];
    }
    if ($page < $totalPages) {
        $nav[] = ['text' => 'Next ➡️', 'callback_data' => 'action=mt_hotspot_add_menu&page=' . ($page + 1)];
    }
    if (!empty($nav)) {
        $keyboard['inline_keyboard'][] = $nav;
    }

    $keyboard['inline_keyboard'][] = [['text' => 'Cancel', 'callback_data' => 'action=mikrotik_menu']];

    sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
}

function handleHotspotAddPickCallback($chatId, $data, $callbackQuery) {
    if (!isAdminChat($chatId)) return;

    $callbackQueryId = $callbackQuery['id'] ?? null;
    answerCallbackQuery($callbackQueryId);

    $profile = trim((string) ($data['profile'] ?? ''));
    if ($profile === '') {
        sendMessage($chatId, "Invalid profile.");
        return;
    }

    $profileEscaped = htmlspecialchars($profile, ENT_QUOTES, 'UTF-8');
    $msg = "Selected profile: <b>{$profileEscaped}</b>\n\n";
    $msg .= "Send this command (replace USER/PASS):\n";
    $msg .= "<code>/hs_add USER PASS {$profileEscaped}</code>";
    sendMessage($chatId, $msg);
}

function handleHotspotVoucherMenu($chatId, $data = []) {
    if (!isAdminChat($chatId)) return;

    $catalog = getPublicVoucherCatalog();
    if (empty($catalog)) {
        sendMessage($chatId, "No hotspot profiles with pricing found. Ensure Hotspot User Profileeeeeeeeeeees in MikroTik have an on-login script with price/selling_price.");
        return;
    }

    usort($catalog, function ($a, $b) {
        return ((int) ($a['price'] ?? 0)) <=> ((int) ($b['price'] ?? 0));
    });

    $page = (int) ($data['page'] ?? 1);
    if ($page < 1) $page = 1;
    $perPage = 12;
    $total = count($catalog);
    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages < 1) $totalPages = 1;
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;
    $items = array_slice($catalog, $offset, $perPage);

    $message = "🎫 *Generate Hotspot Voucher*\n\nClick a profile to generate a voucher (numeric code only, username=password):\nPage {$page}/{$totalPages}";
    $keyboard = ['inline_keyboard' => []];

    $row = [];
    foreach ($items as $item) {
        $profileName = (string) ($item['profile_name'] ?? '');
        if ($profileName === '') {
            continue;
        }
        $price = (int) ($item['price'] ?? 0);
        $validity = (string) ($item['validity'] ?? '-');
        $label = $profileName;
        if ($price > 0) {
            $label .= ' - ' . formatCurrency($price);
        }
        if ($validity !== '' && $validity !== '-') {
            $label .= ' (' . $validity . ')';
        }
        $row[] = ['text' => $label, 'callback_data' => 'action=mt_voucher_gen&profile=' . urlencode($profileName)];
        if (count($row) === 2) {
            $keyboard['inline_keyboard'][] = $row;
            $row = [];
        }
    }
    if (!empty($row)) {
        $keyboard['inline_keyboard'][] = $row;
    }

    $nav = [];
    if ($page > 1) {
        $nav[] = ['text' => '⬅️ Prev', 'callback_data' => 'action=mt_voucher_menu&page=' . ($page - 1)];
    }
    if ($page < $totalPages) {
        $nav[] = ['text' => 'Next ➡️', 'callback_data' => 'action=mt_voucher_menu&page=' . ($page + 1)];
    }
    if (!empty($nav)) {
        $keyboard['inline_keyboard'][] = $nav;
    }

    $keyboard['inline_keyboard'][] = [['text' => 'Back', 'callback_data' => 'action=mikrotik_menu']];

    sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
}

function handleHotspotVoucherGenerateCallback($chatId, $data, $callbackQuery) {
    if (!isAdminChat($chatId)) return;

    $callbackQueryId = $callbackQuery['id'] ?? null;
    answerCallbackQuery($callbackQueryId);

    $profile = trim((string) ($data['profile'] ?? ''));
    if ($profile === '') {
        sendMessage($chatId, "Invalid profile.");
        return;
    }

    $catalog = getPublicVoucherCatalog();
    $selected = findPublicVoucherPackage($catalog, $profile);
    if (!$selected) {
        sendMessage($chatId, "Profileeeeeeeeeeee not found or has no price set.");
        return;
    }

    $length = (int) getSetting('PUBLIC_VOUCHER_LENGTH', 6);
    if ($length < 4) $length = 4;
    if ($length > 12) $length = 12;

    $prefix = trim((string) getSetting('PUBLIC_VOUCHER_PREFIX', ''));
    $prefix = preg_replace('/\D+/', '', (string) $prefix);

    $created = false;
    $username = '';
    $password = '';
    for ($i = 0; $i < 20; $i++) {
        $seed = generateRandomString($length, 'numeric');
        $username = $prefix . $seed;
        $password = $username;
        $ts = date('YmdHis');
        $comment = "parent:{$profile} vc-tg-voucher-{$chatId}-{$ts}";
        if (mikrotikAddHotspotUser($username, $password, $profile, ['comment' => $comment])) {
            $created = true;
            break;
        }
    }

    if (!$created) {
        sendMessage($chatId, "Failed to generate voucher. Please try again.");
        return;
    }

    $profileEscaped = htmlspecialchars($profile, ENT_QUOTES, 'UTF-8');
    $userEscaped = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $price = (int) ($selected['price'] ?? 0);
    $validity = (string) ($selected['validity'] ?? '-');

    $msg = "✅ Voucher created successfully\n\n";
    $msg .= "Profileeeeeeeeeeee: <b>{$profileEscaped}</b>\n";
    if ($price > 0) {
        $msg .= "Price: <b>" . htmlspecialchars(formatCurrency($price), ENT_QUOTES, 'UTF-8') . "</b>\n";
    }
    if ($validity !== '' && $validity !== '-') {
        $msg .= "Validity: <b>" . htmlspecialchars($validity, ENT_QUOTES, 'UTF-8') . "</b>\n";
    }
    $msg .= "\nUsername: <code>{$userEscaped}</code>\n";
    $msg .= "Password: <code>{$userEscaped}</code>";

    $keyboard = [
        'inline_keyboard' => [
            [['text' => '🎫 Generate Again', 'callback_data' => 'action=mt_voucher_menu']],
            [['text' => 'Back', 'callback_data' => 'action=mikrotik_menu']]
        ]
    ];
    sendMessage($chatId, $msg, ['reply_markup' => $keyboard]);
}

function handlePppoeList($chatId) {
    if (!isAdminChat($chatId)) return;
    $users = mikrotikGetPppoeUsers();
    if (empty($users)) {
        sendMessage($chatId, "No PPPoE users found.");
        return;
    }
    
    $message = "👤 *PPPoE User List*\n\n";
    $keyboard = ['inline_keyboard' => []];
    $count = 0;
    foreach ($users as $u) {
        $status = ($u['disabled'] ?? 'false') === 'true' ? '🚫' : '✅';
        $message .= "- {$u['name']} ({$u['profile']}) {$status}\n";
        if ($count < 10) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🚫 Kick", 'callback_data' => 'action=mt_pppoe_kick&name=' . urlencode($u['name'])],
                ['text' => "🗑 Del", 'callback_data' => 'action=mt_pppoe_del&name=' . urlencode($u['name'])]
            ];
        }
        $count++;
        if ($count >= 20) break;
    }
    sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
}

function handlePppoeAdd($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 3) {
        sendMessage($chatId, "Format: /pppoe_add &lt;u&gt; &lt;p&gt; &lt;prof&gt;");
        return;
    }
    
    $res = mikrotikAddSecret($parts[0], $parts[1], $parts[2]);
    sendMessage($chatId, $res['success'] ? "User added." : "Failed: " . $res['message']);
}

function handlePppoeEdit($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 3) {
        sendMessage($chatId, "Format: /pppoe_edit &lt;u&gt; &lt;p&gt; &lt;prof&gt;");
        return;
    }
    
    $secret = mikrotikGetSecretByName($parts[0]);
    if (!$secret) {
        sendMessage($chatId, "User not found.");
        return;
    }
    
    $res = mikrotikUpdateSecret($secret['.id'], ['password' => $parts[1], 'profile' => $parts[2]]);
    if ($res['success']) {
        mikrotikRemoveActiveSessionByName($parts[0]);
        sendMessage($chatId, "User updated.");
    } else {
        sendMessage($chatId, "Failed: " . $res['message']);
    }
}

function handlePppoeDel($chatId, $args, $silent = false) {
    if (!isAdminChat($chatId)) return;
    $user = trim($args);
    $secret = mikrotikGetSecretByName($user);
    if (!$secret) {
        if (!$silent) sendMessage($chatId, "User not found.");
        return;
    }
    
    $res = mikrotikDeleteSecret($secret['.id']);
    if (!$silent) sendMessage($chatId, $res['success'] ? "User deleted." : "Failed.");
}

function handlePppoeDisable($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $user = trim($args);
    $secret = mikrotikGetSecretByName($user);
    if (!$secret) {
        sendMessage($chatId, "User not found.");
        return;
    }
    
    $res = mikrotikUpdateSecret($secret['.id'], ['disabled' => 'true']);
    if ($res['success']) {
        mikrotikRemoveActiveSessionByName($user);
        sendMessage($chatId, "User disabled.");
    } else {
        sendMessage($chatId, "Failed.");
    }
}

function handlePppoeEnable($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $user = trim($args);
    $secret = mikrotikGetSecretByName($user);
    if (!$secret) {
        sendMessage($chatId, "User not found.");
        return;
    }
    
    $res = mikrotikUpdateSecret($secret['.id'], ['disabled' => 'false']);
    sendMessage($chatId, $res['success'] ? "User enabled." : "Failed.");
}

function handlePppoeKickCallback($chatId, $data) {
    if (!isAdminChat($chatId)) return;
    $user = $data['name'] ?? '';
    if (mikrotikRemoveActiveSessionByName($user)) {
        sendMessage($chatId, "Session {$user} disconnected.");
    } else {
        sendMessage($chatId, "Failed to disconnect session.");
    }
}

function handlePppoeDisableCallback($chatId, $data) {
    handlePppoeDisable($chatId, $data['name'] ?? '');
}

function handlePppoeEnableCallback($chatId, $data) {
    handlePppoeEnable($chatId, $data['name'] ?? '');
}

function handlePppoeDelCallback($chatId, $data, $callbackQuery) {
    $callbackQueryId = $callbackQuery['id'] ?? null;
    answerCallbackQuery($callbackQueryId);

    handlePppoeDel($chatId, $data['name'] ?? '', true);
    sendMessage($chatId, "User deleted.");
}

function handleHotspotList($chatId) {
    if (!isAdminChat($chatId)) return;
    $users = mikrotikGetHotspotUsers();
    if (empty($users)) {
        sendMessage($chatId, "No Hotspot users found.");
        return;
    }
    
    $message = "🌐 *Hotspot User List*\n\n";
    $keyboard = ['inline_keyboard' => []];
    $count = 0;
    foreach ($users as $u) {
        $message .= "- {$u['name']} ({$u['profile']})\n";
        if ($count < 10) {
            $keyboard['inline_keyboard'][] = [['text' => "🗑 Del {$u['name']}", 'callback_data' => 'action=mt_hotspot_del&name=' . urlencode($u['name'])]];
        }
        $count++;
        if ($count >= 20) break;
    }
    sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
}

function handleHotspotAdd($chatId, $args) {
    if (!isAdminChat($chatId)) return;
    $parts = preg_split('/\s+/', trim($args));
    if (count($parts) < 3) {
        sendMessage($chatId, "Format: /hs_add &lt;u&gt; &lt;p&gt; &lt;prof&gt;");
        return;
    }

    $profile = $parts[2];
    $ts = date('YmdHis');
    $comment = "parent:{$profile} vc-tg-{$chatId}-{$ts}";

    if (mikrotikAddHotspotUser($parts[0], $parts[1], $profile, ['comment' => $comment])) {
        sendMessage($chatId, "Hotspot user added.");
    } else {
        sendMessage($chatId, "Failed.");
    }
}

function handleHotspotDel($chatId, $args, $silent = false) {
    if (!isAdminChat($chatId)) return;
    if (mikrotikDeleteHotspotUser(trim($args))) {
        if (!$silent) sendMessage($chatId, "Hotspot user deleted.");
    } else {
        if (!$silent) sendMessage($chatId, "Failed.");
    }
}

function handleHotspotDelCallback($chatId, $data, $callbackQuery) {
    $callbackQueryId = $callbackQuery['id'] ?? null;
    answerCallbackQuery($callbackQueryId);

    handleHotspotDel($chatId, $data['name'] ?? '', true);
    sendMessage($chatId, "User deleted.");
}

function getHotspotUserByName($name) {
    $users = mikrotikGetHotspotUsers();
    foreach ($users as $u) {
        if (($u['name'] ?? '') === $name) return $u;
    }
    return null;
}
