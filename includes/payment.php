<?php
/**
 * Payment Gateway Integration
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function paymentLog($event, $data)
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $line = json_encode([
        'ts' => date('Y-m-d H:i:s'),
        'event' => (string) $event,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    @file_put_contents($logDir . '/payment_gateway.log', $line . PHP_EOL, FILE_APPEND);
}

function paymentGetConfig($key, $default = '')
{
    if (function_exists('getSetting')) {
        return getSetting($key, $default);
    }

    if (defined($key)) {
        $value = constant($key);
        if ($value !== '') {
            return $value;
        }
    }

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && ($row['setting_value'] ?? '') !== '') {
            return $row['setting_value'];
        }
    } catch (Exception $_) {
    }

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && ($row['setting_value'] ?? '') !== '') {
            return $row['setting_value'];
        }
    } catch (Exception $_) {
    }

    return $default;
}

function paymentGetTripayBaseUrl()
{
    $candidates = paymentGetTripayCandidateBaseUrls();
    return $candidates[0] ?? 'https://tripay.co.id/api';
}

function paymentGetTripayCandidateBaseUrls()
{
    $mode = strtolower(trim((string) paymentGetConfig('TRIPAY_MODE', '')));
    if (strpos($mode, 'sandbox') !== false) {
        return ['https://tripay.co.id/api-sandbox', 'https://payment.tripay.co.id/api-sandbox'];
    }
    if ($mode !== '' && strpos($mode, 'production') !== false) {
        return ['https://tripay.co.id/api', 'https://payment.tripay.co.id/api'];
    }
    return ['https://tripay.co.id/api', 'https://tripay.co.id/api-sandbox', 'https://payment.tripay.co.id/api', 'https://payment.tripay.co.id/api-sandbox'];
}

function paymentNormalizeTripayMethod($code)
{
    $value = strtoupper(trim((string) $code));
    if ($value === '') {
        return 'QRIS';
    }

    $legacyMap = [
        'VIRTUAL_ACCOUNT_BCA' => 'BCAVA',
        'VIRTUAL_ACCOUNT_BRI' => 'BRIVA',
        'VIRTUAL_ACCOUNT_MANDIRI' => 'MANDIRIVA',
        'VIRTUAL_ACCOUNT_BNI' => 'BNIVA',
        'EWALLET_OVO' => 'OVO',
        'EWALLET_DANA' => 'DANA',
        'EWALLET_LINKAJA' => 'LINKAJA',
        'EWALLET_SHOPEEPAY' => 'SHOPEEPAY',
        'QRIS' => 'QRIS',
        'ALFAMART' => 'ALFAMART',
        'INDOMARET' => 'INDOMARET'
    ];

    return $legacyMap[$value] ?? $value;
}

function paymentFallbackEmailFromPhone($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    $host = parse_url(APP_URL, PHP_URL_HOST);
    if (!$host) {
        $host = 'example.local';
    }
    if ($digits !== '') {
        return 'cust' . $digits . '@' . $host;
    }
    return 'customer@' . $host;
}

function paymentTripayRequest($path, $method, $apiKey, $payload = null, $baseUrl = null)
{
    $base = $baseUrl !== null ? (string) $baseUrl : paymentGetTripayBaseUrl();
    $url = rtrim($base, '/') . '/' . ltrim($path, '/');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    if (defined('CURLOPT_POSTREDIR')) {
        $postRedir = defined('CURL_REDIR_POST_ALL') ? CURL_REDIR_POST_ALL : 7;
        curl_setopt($ch, CURLOPT_POSTREDIR, $postRedir);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json',
        'User-Agent: GEMBOK/2.x (Tripay Client)'
    ]);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($payload) ? http_build_query($payload) : (string) $payload);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $redirectCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    unset($ch);

    return [
        'url' => $url,
        'effective_url' => $effectiveUrl,
        'http_code' => $httpCode,
        'redirect_count' => $redirectCount,
        'content_type' => $contentType,
        'error' => $error,
        'raw' => $response,
        'json' => json_decode((string) $response, true)
    ];
}

function paymentMidtransSnapBaseUrl()
{
    $mode = strtolower(trim((string) paymentGetConfig('MIDTRANS_MODE', paymentGetConfig('MIDTRANS_ENV', 'production'))));
    $isSandbox = strpos($mode, 'sandbox') !== false;
    return $isSandbox ? 'https://app.sandbox.midtrans.com' : 'https://app.midtrans.com';
}

function paymentGetDuitkuCreateInvoiceUrl()
{
    $mode = strtolower(trim((string) paymentGetConfig('DUITKU_MODE', 'production')));
    $isSandbox = strpos($mode, 'sandbox') !== false;
    return $isSandbox
        ? 'https://api-sandbox.duitku.com/api/merchant/createInvoice'
        : 'https://api-prod.duitku.com/api/merchant/createInvoice';
}

function paymentGetXenditApiBaseUrl()
{
    $base = trim((string) paymentGetConfig('XENDIT_BASE_URL', 'https://api.xendit.co'));
    if ($base === '') {
        $base = 'https://api.xendit.co';
    }
    return rtrim($base, '/');
}

// Generate payment link based on gateway
function generatePaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $gateway = 'tripay', $paymentMethod = '', $orderIdOverride = '') {
    switch ($gateway) {
        case 'tripay':
            return generateTripayPaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $paymentMethod, $orderIdOverride);
            
        case 'midtrans':
            return generateMidtransPaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $paymentMethod, $orderIdOverride);

        case 'duitku':
            return generateDuitkuPaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $paymentMethod, $orderIdOverride);

        case 'xendit':
            return generateXenditPaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $paymentMethod, $orderIdOverride);
            
        default:
            return [
                'success' => false,
                'message' => 'Payment gateway not supported',
                'link' => null
            ];
    }
}

function paymentCurlJson($url, $method, $headers, $payload = null, $timeout = 20)
{
    $ch = curl_init((string) $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper((string) $method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, (array) $headers);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($payload) ? $payload : json_encode($payload));
    }
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    unset($ch);
    return [
        'http_code' => (int) $httpCode,
        'error' => (string) $error,
        'raw' => $raw,
        'json' => json_decode((string) $raw, true)
    ];
}

function generateDuitkuPaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $paymentMethod = '', $orderIdOverride = '')
{
    $merchantCode = trim((string) paymentGetConfig('DUITKU_MERCHANT_CODE', ''));
    $apiKey = trim((string) paymentGetConfig('DUITKU_API_KEY', ''));
    if ($merchantCode === '' || $apiKey === '') {
        return ['success' => false, 'message' => 'Payment gateway not configured', 'link' => null];
    }
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'cURL unavailable di server ini', 'link' => null];
    }

    $merchantOrderId = $orderIdOverride !== '' ? (string) $orderIdOverride : (string) $invoiceNumber;
    $amountInt = (int) $amount;
    $timestamp = (int) round(microtime(true) * 1000);
    $signature = hash('sha256', $merchantCode . $timestamp . $apiKey);

    $expiryMinutes = (int) paymentGetConfig('DUITKU_EXPIRY_MINUTES', '60');
    if ($expiryMinutes < 5) {
        $expiryMinutes = 5;
    }
    $dueTs = strtotime((string) $dueDate);
    if ($dueTs !== false) {
        $minsToDue = (int) floor(($dueTs - time()) / 60);
        if ($minsToDue > 0) {
            $expiryMinutes = min($expiryMinutes, $minsToDue);
        }
    }

    $usePretty = (string) paymentGetConfig('USE_PRETTY_URLS', '1') === '1';
    $isVoucher = preg_match('/^VCR/i', (string) $merchantOrderId) === 1;
    $returnUrl = $isVoucher
        ? rtrim(APP_URL, '/') . ($usePretty ? ('/voucher/status/' . rawurlencode($merchantOrderId)) : ('/voucher-status.php?order=' . rawurlencode($merchantOrderId)))
        : rtrim(APP_URL, '/') . '/portal/dashboard.php';

    $payload = [
        'paymentAmount' => $amountInt,
        'merchantOrderId' => $merchantOrderId,
        'productDetails' => 'Payment ' . $merchantOrderId,
        'email' => paymentFallbackEmailFromPhone($customerPhone),
        'phoneNumber' => (string) $customerPhone,
        'customerVaName' => (string) $customerName,
        'callbackUrl' => rtrim(APP_URL, '/') . '/webhooks/duitku.php',
        'returnUrl' => $returnUrl,
        'expiryPeriod' => $expiryMinutes
    ];
    $methodCode = strtoupper(trim((string) $paymentMethod));
    if ($methodCode !== '' && $methodCode !== 'AUTO') {
        $payload['paymentMethod'] = $methodCode;
    }

    $url = paymentGetDuitkuCreateInvoiceUrl();
    $res = paymentCurlJson($url, 'POST', [
        'Content-Type: application/json',
        'x-duitku-signature: ' . $signature,
        'x-duitku-timestamp: ' . $timestamp,
        'x-duitku-merchantcode: ' . $merchantCode
    ], $payload, 30);

    $json = $res['json'];
    if ((int) $res['http_code'] !== 200 || !is_array($json)) {
        paymentLog('DUITKU_CREATE_INVOICE_FAILED', ['http' => $res['http_code'], 'error' => $res['error'], 'raw' => $res['raw']]);
        return ['success' => false, 'message' => 'Failed to create Duitku payment', 'link' => null];
    }

    $statusCode = (string) ($json['statusCode'] ?? '');
    if ($statusCode !== '' && $statusCode !== '00') {
        $msg = (string) ($json['statusMessage'] ?? 'Duitku error');
        paymentLog('DUITKU_CREATE_INVOICE_ERROR', $json);
        return ['success' => false, 'message' => $msg, 'link' => null];
    }

    $paymentUrl = (string) ($json['paymentUrl'] ?? '');
    if ($paymentUrl === '') {
        paymentLog('DUITKU_CREATE_INVOICE_EMPTY_URL', $json);
        return ['success' => false, 'message' => 'Duitku did not return paymentUrl', 'link' => null];
    }

    return ['success' => true, 'link' => $paymentUrl, 'data' => $json];
}

function generateXenditPaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $paymentMethod = '', $orderIdOverride = '')
{
    $secretKey = trim((string) paymentGetConfig('XENDIT_SECRET_KEY', ''));
    if ($secretKey === '') {
        return ['success' => false, 'message' => 'Payment gateway not configured', 'link' => null];
    }
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'cURL unavailable di server ini', 'link' => null];
    }

    $externalId = $orderIdOverride !== '' ? (string) $orderIdOverride : (string) $invoiceNumber;
    $amountFloat = (float) $amount;

    $usePretty = (string) paymentGetConfig('USE_PRETTY_URLS', '1') === '1';
    $isVoucher = preg_match('/^VCR/i', (string) $externalId) === 1;
    $returnUrl = $isVoucher
        ? rtrim(APP_URL, '/') . ($usePretty ? ('/voucher/status/' . rawurlencode($externalId)) : ('/voucher-status.php?order=' . rawurlencode($externalId)))
        : rtrim(APP_URL, '/') . '/portal/dashboard.php';

    $payload = [
        'external_id' => $externalId,
        'amount' => $amountFloat,
        'payer_email' => paymentFallbackEmailFromPhone($customerPhone),
        'description' => 'Payment ' . $externalId,
        'success_redirect_url' => $returnUrl,
        'failure_redirect_url' => $returnUrl
    ];

    $pm = trim((string) $paymentMethod);
    if ($pm !== '' && strtoupper($pm) !== 'AUTO') {
        $payload['payment_methods'] = array_values(array_filter(array_map('trim', explode(',', $pm))));
    }

    $invoiceDuration = (int) paymentGetConfig('XENDIT_INVOICE_DURATION', '3600');
    if ($invoiceDuration > 0) {
        $dueTs = strtotime((string) $dueDate);
        if ($dueTs !== false && $dueTs > time()) {
            $invoiceDuration = min($invoiceDuration, (int) ($dueTs - time()));
        }
        $payload['invoice_duration'] = $invoiceDuration;
    }

    $auth = base64_encode($secretKey . ':');
    $url = paymentGetXenditApiBaseUrl() . '/v2/invoices';
    $res = paymentCurlJson($url, 'POST', [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/json'
    ], $payload, 30);

    $json = $res['json'];
    if ((int) $res['http_code'] < 200 || (int) $res['http_code'] >= 300 || !is_array($json)) {
        paymentLog('XENDIT_CREATE_INVOICE_FAILED', ['http' => $res['http_code'], 'error' => $res['error'], 'raw' => $res['raw']]);
        return ['success' => false, 'message' => 'Failed to create Xendit payment', 'link' => null];
    }

    $paymentUrl = (string) ($json['invoice_url'] ?? '');
    if ($paymentUrl === '') {
        $paymentUrl = (string) ($json['url'] ?? '');
    }
    if ($paymentUrl === '') {
        paymentLog('XENDIT_CREATE_INVOICE_EMPTY_URL', $json);
        return ['success' => false, 'message' => 'Xendit did not return invoice_url', 'link' => null];
    }

    return ['success' => true, 'link' => $paymentUrl, 'data' => $json];
}

// Tripay Payment Link Generator
function generateTripayPaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $paymentMethod = '', $orderIdOverride = '') {
    $apiKey = trim((string) paymentGetConfig('TRIPAY_API_KEY', ''));
    $merchantCode = trim((string) paymentGetConfig('TRIPAY_MERCHANT_CODE', ''));
    $privateKey = trim((string) paymentGetConfig('TRIPAY_PRIVATE_KEY', ''));
    if ($apiKey === '' || $merchantCode === '' || $privateKey === '') {
        return [
            'success' => false,
            'message' => 'Payment gateway not configured',
            'link' => null
        ];
    }

    $merchantRef = $orderIdOverride !== '' ? (string) $orderIdOverride : (string) $invoiceNumber;
    $amountInt = (int) $amount;
    $method = paymentNormalizeTripayMethod($paymentMethod);
    $expiredTime = time() + (24 * 60 * 60);
    $dueTs = strtotime((string) $dueDate);
    if ($dueTs !== false && $dueTs > time()) {
        $expiredTime = min($expiredTime, (int) $dueTs);
    }
    $signature = hash_hmac('sha256', $merchantCode . $merchantRef . $amountInt, $privateKey);

    $payload = [
        'method' => $method,
        'merchant_ref' => $merchantRef,
        'amount' => $amountInt,
        'customer_name' => (string) $customerName,
        'customer_email' => paymentFallbackEmailFromPhone($customerPhone),
        'customer_phone' => (string) $customerPhone,
        'order_items' => [
            [
                'sku' => $merchantRef,
                'name' => 'Payment ' . $merchantRef,
                'price' => $amountInt,
                'quantity' => 1
            ]
        ],
        'expired_time' => $expiredTime,
        'callback_url' => rtrim(APP_URL, '/') . '/webhooks/tripay.php',
        'signature' => $signature
    ];

    $usePretty = (string) paymentGetConfig('USE_PRETTY_URLS', '1') === '1';
    if (preg_match('/^VCR/i', $merchantRef)) {
        $payload['return_url'] = rtrim(APP_URL, '/') . ($usePretty
            ? ('/voucher/status/' . rawurlencode($merchantRef))
            : ('/voucher-status.php?order=' . rawurlencode($merchantRef))
        );
    } else {
        $payload['return_url'] = rtrim(APP_URL, '/') . '/portal/dashboard.php';
    }

    $result = paymentTripayRequest('/transaction/create', 'POST', $apiKey, $payload);
    $json = $result['json'] ?? null;
    if (!is_array($json) || !($json['success'] ?? false)) {
        $bases = paymentGetTripayCandidateBaseUrls();
        $lastMessage = is_array($json) ? (string) ($json['message'] ?? '') : '';
        $lastData = is_array($json) ? $json : null;
        $lastHttpCode = (int) ($result['http_code'] ?? 0);
        $lastError = (string) ($result['error'] ?? '');
        $lastUrl = (string) ($result['effective_url'] ?? ($result['url'] ?? ''));
        $lastRaw = (string) ($result['raw'] ?? '');
        $lastRedirects = (int) ($result['redirect_count'] ?? 0);
        $lastContentType = (string) ($result['content_type'] ?? '');
        foreach ($bases as $base) {
            foreach ([$payload, $method !== 'QRIS' ? array_merge($payload, ['method' => 'QRIS']) : null] as $candidatePayload) {
                if ($candidatePayload === null) {
                    continue;
                }
                $res = paymentTripayRequest('/transaction/create', 'POST', $apiKey, $candidatePayload, $base);
                $js = $res['json'] ?? null;
                if (is_array($js) && ($js['success'] ?? false) && ($js['data']['checkout_url'] ?? '') !== '') {
                    $data = $js['data'];
                    return ['success' => true, 'link' => $data['checkout_url'], 'data' => $data];
                }
                $lastHttpCode = (int) ($res['http_code'] ?? 0);
                $lastError = (string) ($res['error'] ?? '');
                $lastUrl = (string) ($res['effective_url'] ?? ($res['url'] ?? ''));
                $lastRaw = (string) ($res['raw'] ?? '');
                $lastRedirects = (int) ($res['redirect_count'] ?? 0);
                $lastContentType = (string) ($res['content_type'] ?? '');
                if (is_array($js)) {
                    $lastData = $js;
                    if (!empty($js['message'])) {
                        $lastMessage = (string) $js['message'];
                    }
                }
            }
        }
        $message = $lastMessage;
        if ($message === '') {
            if ($lastHttpCode !== 0) {
                $message = 'Tripay error HTTP ' . $lastHttpCode;
            } else {
                $message = 'Failed membuat transaksi Tripay';
            }
            if ($lastError !== '') {
                $message .= ': ' . $lastError;
            }
        }
        paymentLog('tripay_create_failed', [
            'order' => $merchantRef,
            'mode' => (string) paymentGetConfig('TRIPAY_MODE', ''),
            'method' => $method,
            'url' => $lastUrl,
            'http_code' => $lastHttpCode,
            'redirects' => $lastRedirects,
            'content_type' => $lastContentType,
            'error' => $lastError,
            'message' => $lastMessage,
            'raw' => mb_substr($lastRaw, 0, 800),
            'data' => $lastData
        ]);
        return ['success' => false, 'message' => $message, 'link' => null, 'data' => $lastData];
    }
    $data = $json['data'] ?? [];
    $checkoutUrl = $data['checkout_url'] ?? '';
    if ($checkoutUrl === '') {
        return ['success' => false, 'message' => 'Tripay tidak mengembalikan checkout_url', 'link' => null];
    }

    return ['success' => true, 'link' => $checkoutUrl, 'data' => $data];
}

// Midtrans Payment Link Generator
function generateMidtransPaymentLink($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate, $paymentMethod = '', $orderIdOverride = '') {
    $serverKey = trim((string) paymentGetConfig('MIDTRANS_API_KEY', ''));
    if ($serverKey === '') {
        return [
            'success' => false,
            'message' => 'Payment gateway not configured',
            'link' => null
        ];
    }

    $baseUrl = rtrim(paymentMidtransSnapBaseUrl(), '/');
    $url = $baseUrl . '/snap/v1/transactions';

    $orderId = $orderIdOverride !== '' ? (string) $orderIdOverride : (string) $invoiceNumber;
    $amountInt = (int) $amount;

    $durationHours = 24;
    $dueTs = strtotime((string) $dueDate);
    if ($dueTs !== false && $dueTs > time()) {
        $diffHours = (int) ceil(((int) $dueTs - time()) / 3600);
        if ($diffHours > 0 && $diffHours < $durationHours) {
            $durationHours = $diffHours;
        }
    }

    $payload = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => $amountInt
        ],
        'customer_details' => [
            'first_name' => (string) $customerName,
            'email' => paymentFallbackEmailFromPhone($customerPhone),
            'phone' => (string) $customerPhone
        ],
        'item_details' => [
            [
                'id' => $orderId,
                'price' => $amountInt,
                'quantity' => 1,
                'name' => 'Payment ' . $orderId
            ]
        ],
        'expiry' => [
            'start_time' => date('Y-m-d H:i:s O'),
            'unit' => 'hour',
            'duration' => $durationHours
        ]
    ];

    if ($paymentMethod !== '') {
        $payload['enabled_payments'] = [(string) $paymentMethod];
    }

    $usePretty = (string) paymentGetConfig('USE_PRETTY_URLS', '1') === '1';
    if (preg_match('/^VCR/i', $orderId)) {
        $payload['callbacks'] = ['finish' => rtrim(APP_URL, '/') . ($usePretty
            ? ('/voucher/status/' . rawurlencode($orderId))
            : ('/voucher-status.php?order=' . rawurlencode($orderId))
        )];
    } else {
        $payload['callbacks'] = ['finish' => rtrim(APP_URL, '/') . '/portal/dashboard.php'];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    if ($httpCode !== 201 && $httpCode !== 200) {
        return ['success' => false, 'message' => 'Failed membuat transaksi Midtrans', 'link' => null];
    }
    $json = json_decode((string) $response, true);
    $redirectUrl = $json['redirect_url'] ?? '';
    if ($redirectUrl === '') {
        return ['success' => false, 'message' => 'Midtrans tidak mengembalikan redirect_url', 'link' => null];
    }

    return ['success' => true, 'link' => $redirectUrl, 'data' => $json];
}

// Get supported payment gateways
function getPaymentGateways() {
    return [
        [
            'id' => 'tripay',
            'name' => 'Tripay',
            'icon' => 'fa-credit-card',
            'color' => '#00f5ff',
            'description' => 'Payment gateway populer Indonesia',
            'features' => ['QRIS', 'Virtual Account', 'VA'],
            'supported_channels' => ['QRIS', 'VA', 'Bank Transfer']
        ],
        [
            'id' => 'midtrans',
            'name' => 'Midtrans',
            'icon' => 'fa-credit-card',
            'color' => '#667eea',
            'description' => 'Payment gateway populer Indonesia',
            'features' => ['QRIS', 'Virtual Account', 'VA', 'Bank Transfer'],
            'supported_channels' => ['QRIS', 'VA', 'Bank Transfer']
        ],
        [
            'id' => 'duitku',
            'name' => 'Duitku',
            'icon' => 'fa-credit-card',
            'color' => '#0ea5e9',
            'description' => 'Payment gateway Indonesia',
            'features' => ['QRIS', 'Virtual Account', 'E-Wallet'],
            'supported_channels' => ['QRIS', 'VA', 'E-Wallet']
        ],
        [
            'id' => 'xendit',
            'name' => 'Xendit',
            'icon' => 'fa-credit-card',
            'color' => '#2563eb',
            'description' => 'Payment gateway Indonesia',
            'features' => ['QRIS', 'Virtual Account', 'E-Wallet', 'Retail'],
            'supported_channels' => ['QRIS', 'VA', 'E-Wallet', 'Retail']
        ]
    ];
}

// Send payment reminder via WhatsApp
function sendPaymentReminder($invoiceNumber, $amount, $customerName, $customerPhone, $dueDate) {
    $message = "Hello {$customerName},\n\n";
    $message .= "No Invoice: {$invoiceNumber}\n";
    $message .= "Your internet bill is due on " . formatDate($dueDate) . "\n\n";
    $message .= "Nominal: " . formatCurrency($amount) . "\n\n";
    $message .= "Please make payment immediately to reactivate your internet connection.\n\n";
    $message .= "Thank you.";
    if (function_exists('getWhatsAppFooter')) {
        $message .= getWhatsAppFooter();
    }
    
    return sendWhatsApp($customerPhone, $message);
}

// Get payment status from Tripay
function getTripayPaymentStatus($merchantRef) {
    $apiKey = trim((string) paymentGetConfig('TRIPAY_API_KEY', ''));
    if ($apiKey === '') {
        return ['success' => false, 'message' => 'API Key not configured'];
    }

    $query = http_build_query([
        'merchant_ref' => (string) $merchantRef,
        'sort' => 'desc',
        'per_page' => 1
    ]);
    $result = paymentTripayRequest('/merchant/transactions?' . $query, 'GET', $apiKey);
    $json = $result['json'] ?? null;
    if (!is_array($json) || !($json['success'] ?? false)) {
        return ['success' => false, 'message' => is_array($json) ? ($json['message'] ?? 'Failed to get payment status') : 'Failed to get payment status'];
    }

    $transaction = null;
    if (isset($json['data']['data'][0]) && is_array($json['data']['data'][0])) {
        $transaction = $json['data']['data'][0];
    } elseif (isset($json['data'][0]) && is_array($json['data'][0])) {
        $transaction = $json['data'][0];
    }

    if (!$transaction) {
        return ['success' => false, 'message' => 'Transaction not found'];
    }

    return ['success' => true, 'data' => ['data' => $transaction]];
}

// Get payment status from Midtrans
function getMidtransPaymentStatus($orderId) {
    // Note: MIDTRANS_API_KEY should contain your Server Key
    $serverKey = trim((string) paymentGetConfig('MIDTRANS_API_KEY', ''));
    if ($serverKey === '') {
        return ['success' => false, 'message' => 'API Key not configured'];
    }
    
    // Correct Midtrans status endpoint does not include merchant code
    $url = "https://api.midtrans.com/v2/{$orderId}/status";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        // Use Server Key for basic auth
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'message' => 'Failed to get payment status'];
    }
    
    return ['success' => true, 'data' => json_decode($response, true)];
}
