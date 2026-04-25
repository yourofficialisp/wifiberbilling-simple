<?php
/**
 * Helper Functions
 */

// Global settings cache
$global_settings_cache = null;
$site_settings_cache = null;

// Get setting from database with fallback to config constant
function getSetting($key, $default = '') {
    global $global_settings_cache;
    
    if ($global_settings_cache === null) {
        $global_settings_cache = [];
        $data = fetchAll("SELECT setting_key, setting_value FROM settings");
        foreach ($data as $row) {
            $global_settings_cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    if (isset($global_settings_cache[$key]) && $global_settings_cache[$key] !== '') {
        return $global_settings_cache[$key];
    }
    
    if (defined($key)) {
        return constant($key);
    }
    
    return $default;
}

// Get site setting from site_settings table
function getSiteSetting($key, $default = '') {
    global $site_settings_cache;
    
    if ($site_settings_cache === null) {
        $site_settings_cache = [];
        try {
            $data = fetchAll("SELECT setting_key, setting_value FROM site_settings");
            if (is_array($data)) {
                foreach ($data as $row) {
                    $site_settings_cache[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            // Table might not exist yet
        }
    }
    
    return $site_settings_cache[$key] ?? $default;
}

// Get Mikrotik settings from database (supports multi-router)
require_once __DIR__ . '/mikrotik_api.php';

// Format currency
function formatCurrency($amount)
{
    $amount = is_numeric($amount) ? $amount : 0;
    $symbol = getSetting('CURRENCY_SYMBOL', 'Rp');
    return $symbol . ' ' . number_format((float) $amount, 0, ',', '.');
}

// Format date
function formatDate($date, $format = 'd M Y')
{
    if (!$date)
        return '-';
    $time = strtotime($date);
    return $time ? date($format, $time) : '-';
}

// Generate invoice number
function generateInvoiceNumber()
{
    $prefix = INVOICE_PREFIX;
    $start = INVOICE_START;

    $lastInvoice = fetchOne("SELECT invoice_number FROM invoices ORDER BY id DESC LIMIT 1");

    if ($lastInvoice) {
        $lastNum = (int) str_replace($prefix, '', $lastInvoice['invoice_number']);
        $newNum = $lastNum + 1;
    } else {
        $newNum = $start;
    }

    return $prefix . str_pad($newNum, 6, '0', STR_PAD_LEFT);
}

function sendWhatsApp($phone, $message)
{
    require_once __DIR__ . '/whatsapp.php';

    // Get default WhatsApp gateway from settings
    $defaultGateway = fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", ['DEFAULT_WHATSAPP_GATEWAY'])['setting_value'] ?? 'fonnte';
    $defaultGateway = trim((string) $defaultGateway);
    if ($defaultGateway === '') {
        $defaultGateway = 'fonnte';
    }

    // Format phone number (62 format)
    if (substr($phone, 0, 2) === '08') {
        $phone = '62' . substr($phone, 1);
    }

    // Send using selected gateway
    $result = sendWhatsAppMessage($phone, $message, $defaultGateway);

    $success = $result['success'] ?? false;
    if (!$success) {
        $err = (string) ($result['message'] ?? 'Unknown error');
        if (function_exists('logWhatsAppError')) {
            logWhatsAppError("SEND_FAILED: gateway={$defaultGateway} to={$phone} msg=" . $err);
        }
    }
    return $success;
}

function getWhatsAppFooter()
{
    $appName = trim((string) getSetting('app_name', defined('APP_NAME') ? APP_NAME : ''));
    $admin = trim((string) getSetting('WHATSAPP_ADMIN_NUMBER', ''));
    $adminDigits = preg_replace('/\D+/', '', $admin);
    if ($adminDigits !== '') {
        if (strpos($adminDigits, '0') === 0) {
            $adminDigits = '62' . substr($adminDigits, 1);
        } elseif (strpos($adminDigits, '62') !== 0) {
            $adminDigits = '62' . $adminDigits;
        }
    }

    $lines = [];
    if ($appName !== '') {
        $lines[] = $appName;
    }
    if ($adminDigits !== '') {
        $lines[] = 'CS/WA: ' . $adminDigits;
    }
    if (empty($lines)) {
        return '';
    }
    return "\n\n" . implode("\n", $lines);
}

function getCustomerDueDate($customer, $baseDate = null)
{
    $baseTimestamp = $baseDate ? strtotime($baseDate) : time();
    $year = date('Y', $baseTimestamp);
    $month = date('m', $baseTimestamp);
    $day = isset($customer['isolation_date']) ? (int) $customer['isolation_date'] : 20;
    if ($day < 1) {
        $day = 1;
    }
    if ($day > 28) {
        $day = 28;
    }
    $lastDay = (int) date('t', strtotime($year . '-' . $month . '-01'));
    if ($day > $lastDay) {
        $day = $lastDay;
    }
    return date('Y-m-d', strtotime($year . '-' . $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT)));
}

function logError($message)
{
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] ERROR: {$message}\n";

    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Log activity
function logActivity($action, $details = '')
{
    $logFile = __DIR__ . '/../logs/activity.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $user = $_SESSION['admin']['username'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $logMessage = "[{$timestamp}] [{$user}] [{$ip}] {$action} - {$details}\n";

    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Redirect
function redirect($url)
{
    header("Location: {$url}");
    exit;
}

// Flash message
function setFlash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type)
{
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}

function hasFlash($type)
{
    return isset($_SESSION['flash'][$type]);
}

// Sanitize input
function sanitize($input)
{
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate random string with charset options
function generateRandomString($length = 10, $type = 'mixed')
{
    switch ($type) {
        case 'numeric':
        case 'num':
            $x = '0123456789';
            break;
        case 'alpha':
        case 'low':
            $x = 'abcdefghijklmnopqrstuvwxyz';
            break;
        case 'up':
            $x = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'mixed':
            $x = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
            break; // Avoid ambiguous chars
        case 'alphanumeric':
        default:
            $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
    }
    
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $x[mt_rand(0, strlen($x) - 1)];
    }
    return $str;
}

// Mikhmon Metadata Helpers
function formatMikhmonComment($price, $validity, $profile)
{
    // Format: vc-user-dd-mm-yy (Price: Rp 5.000, Validity: 1d)
    // Note: Mikhmon often uses specific patterns like uct-ddmmyy-price
    $date = date('d/m/y');
    return "price:{$price},validity:{$validity},profile:{$profile},date:{$date}";
}

function parseMikhmonComment($comment)
{
    $data = [
        'price' => 0,
        'validity' => '-',
        'profile' => '-',
        'date' => '-',
        'raw' => $comment
    ];

    if (empty($comment))
        return $data;

    // 1. Try existing key:value format (e.g. price:5000,validity:1d,date=...)
    // Note: Mikhmon uses both : and =
    if (strpos($comment, 'price:') !== false || strpos($comment, 'price=') !== false) {
        $parts = preg_split('/[, ]+/', $comment);
        foreach ($parts as $part) {
            $kv = preg_split('/[:=]/', $part, 2);
            if (count($kv) === 2) {
                $itemKey = trim($kv[0]);
                $itemVal = trim($kv[1]);
                if (isset($data[$itemKey])) {
                    $data[$itemKey] = $itemVal;
                }
            }
        }
        return $data;
    }

    // 2. Try Standard Mikhmon Format: Date - Code - Price - Profileeeeeeeeeeee - Validity
    $parts = array_map('trim', explode('-', $comment));
    if (count($parts) >= 5) {
        $data['date'] = $parts[0];
        $data['price'] = preg_replace('/[^0-9]/', '', $parts[2]);
        $data['profile'] = $parts[3];
        $data['validity'] = $parts[4];
        return $data;
    }

    // 3. Fallback search using Regex - BE STRICTER
    // Prioritize Rp or price: prefixes. If none, only accept numeric strings if they are reasonable (< 1,000,000)
    // and not too long (vouchers rarely cost billions)

    $foundPrice = 0;

    // Pattern A: Explicit Price Prefix (Rp, price:, parent:)
    if (preg_match('/(?:price[:=]|Rp\.?\s?|rp\.?\s?|parent[:=])\s?(\d{1,3}(?:\.\d{3})*|\d{3,})/i', $comment, $matches)) {
        $foundPrice = str_replace('.', '', $matches[1]);
    }
    // Pattern B: Bare number at the end or surrounded by spaces (only if Pattern A failed)
    elseif (preg_match('/(?:\s|^)(\d{3,7})(?:\s|$|,)/', $comment, $matches)) {
        $tempPrice = $matches[1];
        // Sanity check: Mikhmon voucher prices are usually under 1,000,000
        if ((int) $tempPrice < 1000000) {
            $foundPrice = $tempPrice;
        }
    }

    if ($foundPrice) {
        $data['price'] = (int) $foundPrice;
    }

    // Date - Be careful not to pick up the same big number
    if (preg_match('/(?:date[:=]|^|\s)([a-z]{3}\/\d{2}\/\d{4}\s\d{2}:\d{2}:\d{2})/i', $comment, $matches)) {
        $data['date'] = $matches[1];
    } elseif (preg_match('/(\d{2}[-\/\.]\d{2}[-\/\.]\d{2,4})/', $comment, $matches)) {
        $data['date'] = $matches[1];
    }

    return $data;
}

function parseHotspotProfileeeeeeeeeeeeComment($comment)
{
    $price = 0;

    if (empty($comment)) {
        return 0;
    }

    // 1. Try 'parent:PRICE' format (used by this app)
    if (strpos($comment, 'parent:') !== false) {
        // Extract everything after parent:
        $parts = explode('parent:', $comment);
        if (isset($parts[1])) {
            // Take the number immediately following parent:
            $val = trim($parts[1]);
            // If comma separated like parent:5000,other:value
            $valParts = explode(',', $val);
            $price = preg_replace('/[^0-9]/', '', $valParts[0]);
            return (int) $price;
        }
    }

    // 2. Try explicit 'price:' format
    if (preg_match('/price[:=]\s?(\d+)/i', $comment, $matches)) {
        return (int) $matches[1];
    }

    // 3. Try formatted currency format (Rp 5.000)
    if (preg_match('/Rp\.?\s?(\d{1,3}(?:\.\d{3})*|\d{3,})/i', $comment, $matches)) {
        $clean = str_replace('.', '', $matches[1]);
        return (int) $clean;
    }

    // 4. Try bare numeric price (with sanity check)
    // Mikhmon sometimes just puts the price. But we must ignore timestamps (YYYYMMDD...)
    if (preg_match('/(?:\s|^)(\d{3,7})(?:\s|$|,)/', $comment, $matches)) {
        $val = (int) $matches[1];
        // Sanity check: if it looks like a date/timestamp 
        // (e.g. starts with 202, 201 or has 8+ digits), ignore it
        if ($val < 1000000 && strlen($matches[1]) <= 7) {
            return $val;
        }
    }

    return 0;
}

// Check if customer is isolated
function isCustomerIsolated($customerId)
{
    $customer = fetchOne("SELECT status FROM customers WHERE id = ?", [$customerId]);
    return $customer && $customer['status'] === 'isolated';
}

// Isolate customer
function isolateCustomer($customerId, $options = [])
{
    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$customerId]);
    if (!$customer) {
        return false;
    }

    if (($customer['status'] ?? '') === 'isolated') {
        return true;
    }

    // Update status
    update('customers', ['status' => 'isolated'], 'id = ?', [$customerId]);

    // Update MikroTik profile
    $package = fetchOne("SELECT * FROM packages WHERE id = ?", [$customer['package_id']]);
    $sendWhatsapp = true;
    if (is_array($options) && array_key_exists('send_whatsapp', $options)) {
        $sendWhatsapp = (bool) $options['send_whatsapp'];
    }

    if ($package && !empty($customer['pppoe_username']) && !empty($package['profile_isolir'])) {
        // Call MikroTik API to change profile on assigned router
        mikrotikSetProfileeeeeeeeeeee($customer['pppoe_username'], $package['profile_isolir'], $customer['router_id']);

        // Send WhatsApp notification
        if ($sendWhatsapp) {
            $message = "Hello {$customer['name']},\n\nYour internet payment has passed the due date.\n\nPlease make payment immediately to reactivate your internet connection.\n\nThank you.";
            $message .= getWhatsAppFooter();
            sendWhatsApp($customer['phone'], $message);
        }
    } elseif ($package && !empty($customer['pppoe_username']) && empty($package['profile_isolir'])) {
        logError('Isolation skipped MikroTik profile update (profile_isolir empty). Customer ID: ' . $customerId);
    }

    logActivity('ISOLATE_CUSTOMER', "Customer ID: {$customerId}");

    return true;
}

// Unisolate customer
function unisolateCustomer($customerId, $options = [])
{
    $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$customerId]);
    if (!$customer) {
        return false;
    }

    if (($customer['status'] ?? '') !== 'isolated') {
        return true;
    }

    // Update status
    update('customers', ['status' => 'active'], 'id = ?', [$customerId]);

    // Update MikroTik profile
    $package = fetchOne("SELECT * FROM packages WHERE id = ?", [$customer['package_id']]);
    if ($package && !empty($customer['pppoe_username'])) {
        // Call MikroTik API to change profile on assigned router
        mikrotikSetProfileeeeeeeeeeee($customer['pppoe_username'], $package['profile_normal'], $customer['router_id']);
    }

    $sendWhatsapp = false;
    if (is_array($options) && array_key_exists('send_whatsapp', $options)) {
        $sendWhatsapp = (bool) $options['send_whatsapp'];
    }
    if ($sendWhatsapp && !empty($customer['phone'])) {
        $message = "Hello {$customer['name']},\n\nYour internet service has been reactivated.\n\nThank you.";
        $message .= getWhatsAppFooter();
        sendWhatsApp($customer['phone'], $message);
    }

    logActivity('UNISOLATE_CUSTOMER', "Customer ID: {$customerId}");

    return true;
}

// Get GenieACS settings from database (override config.php)
function getGenieacsSettings()
{
    static $settings = null;
    if ($settings === null) {
        $settings = [
            'url' => defined('GENIEACS_URL') ? GENIEACS_URL : '',
            'username' => defined('GENIEACS_USERNAME') ? GENIEACS_USERNAME : '',
            'password' => defined('GENIEACS_PASSWORD') ? GENIEACS_PASSWORD : ''
        ];

        // Try to get from database
        $dbSettings = fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('GENIEACS_URL', 'GENIEACS_USERNAME', 'GENIEACS_PASSWORD')");
        foreach ($dbSettings as $s) {
            switch ($s['setting_key']) {
                case 'GENIEACS_URL':
                    $settings['url'] = $s['setting_value'];
                    break;
                case 'GENIEACS_USERNAME':
                    $settings['username'] = $s['setting_value'];
                    break;
                case 'GENIEACS_PASSWORD':
                    $settings['password'] = $s['setting_value'];
                    break;
            }
        }
    }
    return $settings;
}

// GenieACS functions
function genieacsGetDevices()
{
    $genieacs = getGenieacsSettings();
    if (empty($genieacs['url'])) {
        return [];
    }

    $projection = [
        '_id',
        '_lastInform',
        '_deviceId',
        'DeviceID',
        'VirtualParameters.pppoeUsername',
        'VirtualParameters.pppoeUsername2',
        'VirtualParameters.gettemp',
        'VirtualParameters.RXPower',
        'VirtualParameters.pppoeIP',
        'VirtualParameters.IPTR069',
        'VirtualParameters.pppoeMac',
        'VirtualParameters.getponmode',
        'VirtualParameters.PonMac',
        'VirtualParameters.getSerialNumber',
        'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
        'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase',
        'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations',
        'VirtualParameters.activedevices',
        'VirtualParameters.getdeviceuptime'
    ];

    $query = json_encode(['_id' => ['$regex' => '']]);
    $projectionStr = implode(',', $projection);
    
    $url = rtrim($genieacs['url'], '/') . '/devices/?query=' . urlencode($query) . '&projection=' . $projectionStr;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Increased timeout for larger datasets

    // Add authentication if credentials are set
    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close($ch); // Deprecated in PHP 8.0+

    if ($httpCode === 200) {
        $devices = json_decode($response, true);
        return is_array($devices) ? $devices : [];
    }

    return [];
}

function genieacsGetDevice($serial)
{
    $genieacs = getGenieacsSettings();
    if (empty($genieacs['url'])) {
        return null;
    }

    // Attempt 1: Search by Serial Number
    $query1 = json_encode(['_deviceId._SerialNumber' => $serial]);
    $url1 = rtrim($genieacs['url'], '/') . '/devices/?query=' . urlencode($query1);

    $ch1 = curl_init($url1);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch1, CURLOPT_TIMEOUT, 10);
    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch1, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    $response1 = curl_exec($ch1);
    $httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);

    if ($httpCode1 === 200) {
        $devices = json_decode($response1, true);
        if (is_array($devices) && count($devices) > 0) {
            return $devices[0];
        }
    }

    // Attempt 2: Search by _id (Exact match)
    // Using query parameter is safer than direct URL access for special chars
    $query2 = json_encode(['_id' => $serial]);
    $url2 = rtrim($genieacs['url'], '/') . '/devices/?query=' . urlencode($query2);

    $ch2 = curl_init($url2);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch2, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    $response2 = curl_exec($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

    if ($httpCode2 === 200) {
        $devices = json_decode($response2, true);
        if (is_array($devices) && count($devices) > 0) {
            return $devices[0];
        }
    }

    // Attempt 3: Search by _id (Decoded)
    // Handles cases where ID was passed encoded (e.g. %2D instead of -)
    $decodedSerial = urldecode($serial);
    if ($decodedSerial !== $serial) {
        $query3 = json_encode(['_id' => $decodedSerial]);
        $url3 = rtrim($genieacs['url'], '/') . '/devices/?query=' . urlencode($query3);

        $ch3 = curl_init($url3);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch3, CURLOPT_TIMEOUT, 10);
        if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
            curl_setopt($ch3, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
        }

        $response3 = curl_exec($ch3);
        $httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);

        if ($httpCode3 === 200) {
            $devices = json_decode($response3, true);
            if (is_array($devices) && count($devices) > 0) {
                return $devices[0];
            }
        }
    }

    // Attempt 4: Search by PPPoE Username (VirtualParameters.pppoeUsername)
    // Since `customers.php` maps PPPoE Username to the `serial_number` column in the database,
    // this acts as a vital fallback for finding online status on the map.
    $query4 = json_encode(['VirtualParameters.pppoeUsername' => $serial]);
    $url4 = rtrim($genieacs['url'], '/') . '/devices/?query=' . urlencode($query4);

    $ch4 = curl_init($url4);
    curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch4, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch4, CURLOPT_TIMEOUT, 10);
    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch4, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    $response4 = curl_exec($ch4);
    $httpCode4 = curl_getinfo($ch4, CURLINFO_HTTP_CODE);

    if ($httpCode4 === 200) {
        $devices = json_decode($response4, true);
        if (is_array($devices) && count($devices) > 0) {
            return $devices[0];
        }
    }

    return null;
}

// Helper function to extract value from GenieACS parameter structure
function genieacsGetValue($device, $path)
{
    // Navigate through nested structure
    $keys = explode('.', $path);
    $current = $device;

    foreach ($keys as $key) {
        if (!is_array($current)) {
            return null;
        }

        // Try direct key access
        if (isset($current[$key])) {
            $current = $current[$key];
        } else {
            // Try numeric index pattern (e.g., LANDevice.1 -> LANDevice["1"])
            $found = false;
            foreach ($current as $k => $v) {
                if (strpos($k, $key) === 0) {
                    $current = $v;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return null;
            }
        }
    }

    // Extract value - GenieACS stores values in different formats
    if (is_array($current)) {
        // Try common value keys
        if (isset($current['_value'])) {
            return $current['_value'];
        }
        if (isset($current['value'])) {
            return $current['value'];
        }
        if (isset($current[0]) && is_string($current[0])) {
            return $current[0];
        }
    }

    return is_string($current) ? $current : null;
}

// Get device info summary from GenieACS
function genieacsGetDeviceInfo($serial)
{
    $device = genieacsGetDevice($serial);

    if (!$device) {
        return null;
    }

    $info = [
        'id' => $device['_id'] ?? $serial,
        'serial_number' => $serial,
        'last_inform' => $device['_lastInform'] ?? null,
        'status' => 'unknown',
        'uptime' => null,
        'manufacturer' => null,
        'model' => null,
        'software_version' => null,
        'rx_power' => null,
        'tx_power' => null,
        'ssid' => null,
        'ssid_5g' => null,
        'wifi_password' => null,
        'wifi_password_5g' => null,
        'has_5g' => false,
        'ip_address' => null,
        'mac_address' => null,
        'total_associations' => null,
        'total_associations_5g' => null
    ];

    // Determine online status (last inform within 5 minutes)
    if ($info['last_inform']) {
        $lastInform = strtotime($info['last_inform']);
        $info['status'] = (time() - $lastInform) < 300 ? 'online' : 'offline';
    }

    // Extract common parameters using different possible paths
    // Device Manufacturer
    $info['manufacturer'] =
        genieacsGetValue($device, 'InternetGatewayDevice.DeviceInfo.Manufacturer') ??
        genieacsGetValue($device, 'Device.DeviceInfo.Manufacturer') ??
        genieacsGetValue($device, 'DeviceID.Manufacturer');

    // Device Model
    $info['model'] =
        genieacsGetValue($device, 'InternetGatewayDevice.DeviceInfo.ModelName') ??
        genieacsGetValue($device, 'Device.DeviceInfo.ModelName') ??
        genieacsGetValue($device, 'DeviceID.ProductClass');

    // Software Version
    $info['software_version'] =
        genieacsGetValue($device, 'InternetGatewayDevice.DeviceInfo.SoftwareVersion') ??
        genieacsGetValue($device, 'Device.DeviceInfo.SoftwareVersion');

    // Uptime
    $info['uptime'] =
        genieacsGetValue($device, 'InternetGatewayDevice.DeviceInfo.UpTime') ??
        genieacsGetValue($device, 'Device.DeviceInfo.UpTime');

    // WAN IP Address
    $info['ip_address'] =
        genieacsGetValue($device, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress') ??
        genieacsGetValue($device, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ExternalIPAddress');

    // MAC Address
    $info['mac_address'] =
        genieacsGetValue($device, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.MACAddress') ??
        genieacsGetValue($device, 'Device.Ethernet.Interface.1.MACAddress');

    // WiFi SSID - try multiple paths
    $info['ssid'] =
        genieacsGetValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID') ??
        genieacsGetValue($device, 'InternetGatewayDevice.LANDevice.1.WiFi.Radio.1.SSID') ??
        genieacsGetValue($device, 'Device.WiFi.SSID.1.SSID');

    $ssid5g = null;
    foreach ([5, 6, 7, 8, 9] as $idx) {
        $ssid5g = genieacsGetValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.' . $idx . '.SSID');
        if ($ssid5g !== null) {
            break;
        }
    }
    if ($ssid5g === null) {
        $ssid5g = genieacsGetValue($device, 'Device.WiFi.SSID.2.SSID');
    }
    $info['ssid_5g'] = $ssid5g;

    // WiFi Password
    $info['wifi_password'] =
        genieacsGetValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase') ??
        genieacsGetValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase') ??
        genieacsGetValue($device, 'Device.WiFi.AccessPoint.1.Security.KeyPassphrase');

    $pass5g = null;
    foreach ([5, 6, 7, 8, 9] as $idx) {
        $pass5g = genieacsGetValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.' . $idx . '.PreSharedKey.1.KeyPassphrase');
        if ($pass5g !== null) {
            break;
        }
    }
    if ($pass5g === null) {
        $pass5g = genieacsGetValue($device, 'Device.WiFi.AccessPoint.2.Security.KeyPassphrase');
    }
    $info['wifi_password_5g'] = $pass5g;

    // PON Optical Power (for GPON/EPON ONUs)
    $info['rx_power'] =
        genieacsGetValue($device, 'VirtualParameters.RXPower') ??
        genieacsGetValue($device, 'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.RxPower') ??
        genieacsGetValue($device, 'Device.Optical.Interface.1.RXPower');

    $info['tx_power'] =
        genieacsGetValue($device, 'VirtualParameters.TXPower') ??
        genieacsGetValue($device, 'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.TxPower') ??
        genieacsGetValue($device, 'Device.Optical.Interface.1.TXPower');

    // Connected Devices / Total Associations (SSID 1 Only)
    $info['total_associations'] = genieacsGetValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations');

    $assoc5g = null;
    foreach ([5, 6, 7, 8, 9] as $idx) {
        $assoc5g = genieacsGetValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.' . $idx . '.TotalAssociations');
        if ($assoc5g !== null) {
            break;
        }
    }
    $info['total_associations_5g'] = $assoc5g;

    $info['has_5g'] = $info['ssid_5g'] !== null || $info['wifi_password_5g'] !== null || $info['total_associations_5g'] !== null;

    return $info;
}

function genieacsSetParameter($serial, $parameter, $value)
{
    $genieacs = getGenieacsSettings();
    if (empty($genieacs['url'])) {
        return ['success' => false, 'message' => 'GenieACS URL not configured'];
    }

    // Get device first to find the actual device ID
    $device = genieacsGetDevice($serial);
    if (!$device) {
        // If device lookup fails, return specific error
        return ['success' => false, 'message' => "Device lookup failed for: $serial"];
    }

    $deviceId = $device['_id'] ?? $serial;
    // Use rawurlencode and add timeout parameter (3000ms) to avoid hanging
    // This matches GACS implementation reference
    $encodedId = rawurlencode($deviceId);
    $url = rtrim($genieacs['url'], '/') . "/devices/{$encodedId}/tasks?timeout=3000&connection_request";

    $data = [
        'name' => 'setParameterValues', // Note: GACS uses setParameterValues, check if different from setParameter
        'parameterValues' => [
            [$parameter, (string)$value, 'xsd:string']
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10s > 3s GenieACS timeout

    // Add authentication if credentials are set
    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    // curl_close() is deprecated in PHP 8.0+ - CurlHandle auto-destroys

    if ($httpCode === 200 || $httpCode === 201 || $httpCode === 202) {
        return ['success' => true, 'message' => 'Task created successfully'];
    }

    if ($curlError) {
        return ['success' => false, 'message' => "Curl Error: $curlError"];
    }

    return ['success' => false, 'message' => "GenieACS Error ($httpCode): " . ($response ?: 'Unknown error')];
}

function genieacsSetParameterValues($serial, $params)
{
    $genieacs = getGenieacsSettings();
    if (empty($genieacs['url'])) {
        return false;
    }

    // Get device first to find the actual device ID
    $device = genieacsGetDevice($serial);
    if (!$device) {
        return false;
    }

    $deviceId = $device['_id'] ?? $serial;
    $encodedId = rawurlencode($deviceId);
    $url = rtrim($genieacs['url'], '/') . "/devices/{$encodedId}/tasks?timeout=3000&connection_request";

    $parameterValues = [];
    foreach ($params as $key => $value) {
        $parameterValues[] = [$key, (string)$value, 'xsd:string'];
    }

    $data = [
        'name' => 'setParameterValues',
        'parameterValues' => $parameterValues
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return $httpCode === 200 || $httpCode === 201 || $httpCode === 202;
}

// Find device by PPPoE username in GenieACS
function genieacsFindDeviceByPppoe($pppoeUsername)
{
    $genieacs = getGenieacsSettings();
    if (empty($genieacs['url'])) {
        return null;
    }

    // First, try to find device using VirtualParameters.pppoeUsername which is the most reliable approach
    $query = json_encode(['VirtualParameters.pppoeUsername' => $pppoeUsername]);
    $url = rtrim($genieacs['url'], '/') . '/devices/?query=' . urlencode($query);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Add authentication if credentials are set
    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close() is deprecated in PHP 8.0+ - CurlHandle auto-destroys

    if ($httpCode === 200) {
        $devices = json_decode($response, true);
        if (is_array($devices) && count($devices) > 0) {
            return $devices[0]; // Return first matching device
        }
    }

    // If not found via VirtualParameters, try alternative approaches
    // Try searching for devices with PPPoE username in various possible locations
    $possibleQueries = [
        // Alternative VirtualParameters that might contain the username
        ['VirtualParameters.pppoeUsername2' => $pppoeUsername],
        // Common paths where username might be stored in standard parameters
        ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.Username' => $pppoeUsername],
        ['InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username' => $pppoeUsername],
        ['Device.PPP.Interface.1.Credentials.Username' => $pppoeUsername],
        ['InternetGatewayDevice.PPPPEngine.PPPoE.UnicastDiscovery.Username' => $pppoeUsername],
        // If PPPoE username is stored as part of device name or description
        ['Device.DeviceInfo.Description' => $pppoeUsername],
        ['Device.DeviceInfo.FriendlyName' => $pppoeUsername]
    ];

    foreach ($possibleQueries as $query) {
        $encodedQuery = json_encode($query);
        $url = rtrim($genieacs['url'], '/') . '/devices/?query=' . urlencode($encodedQuery);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Add authentication if credentials are set
        if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close() is deprecated in PHP 8.0+ - CurlHandle auto-destroys

        if ($httpCode === 200) {
            $devices = json_decode($response, true);
            if (is_array($devices) && count($devices) > 0) {
                return $devices[0]; // Return first matching device
            }
        }
    }

    // If no device found by searching parameters, try a more general search
    // Sometimes the PPPoE username might be stored in custom fields
    $generalQuery = urlencode('"' . $pppoeUsername . '"');
    $url = rtrim($genieacs['url'], '/') . '/devices/?query=' . $generalQuery;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Add authentication if credentials are set
    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close() is deprecated in PHP 8.0+ - CurlHandle auto-destroys

    if ($httpCode === 200) {
        $devices = json_decode($response, true);
        if (is_array($devices) && count($devices) > 0) {
            return $devices[0]; // Return first matching device
        }
    }

    return null;
}

// Reboot device via GenieACS
function genieacsReboot($serial)
{
    $genieacs = getGenieacsSettings();
    if (empty($genieacs['url'])) {
        return false;
    }

    // Get device first to find the actual device ID
    $device = genieacsGetDevice($serial);
    if (!$device) {
        return false;
    }

    $deviceId = $device['_id'] ?? $serial;
    $url = rtrim($genieacs['url'], '/') . '/devices/' . urlencode($deviceId) . '/tasks?connection_request';

    $data = [
        'name' => 'reboot'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Add authentication if credentials are set
    if (!empty($genieacs['username']) && !empty($genieacs['password'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $genieacs['username'] . ':' . $genieacs['password']);
    }

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close() is deprecated in PHP 8.0+ - CurlHandle auto-destroys

    return $httpCode === 200 || $httpCode === 201;
}
// Pagination
function paginate($table, $page = 1, $perPage = ITEMS_PER_PAGE, $where = '', $params = [])
{
    $offset = ($page - 1) * $perPage;

    // Get total
    $countSql = "SELECT COUNT(*) as total FROM {$table}";
    if ($where) {
        $countSql .= " WHERE {$where}";
    }
    $totalResult = fetchOne($countSql, $params);
    $total = $totalResult['total'] ?? 0;

    // Get data
    $dataSql = "SELECT * FROM {$table}";
    if ($where) {
        $dataSql .= " WHERE {$where}";
    }
    $perPage = (int) $perPage;
    $offset = (int) $offset;
    $dataSql .= " ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";

    $data = fetchAll($dataSql, $params);

    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => ceil($total / $perPage)
    ];
}

// Generate CSRF token
function generateCsrfToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getApiCsrfToken($input = null)
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($token === '' && is_array($input)) {
        $token = $input['csrf_token'] ?? '';
    }
    return is_string($token) ? $token : '';
}

function verifyApiCsrfToken($input = null)
{
    return verifyCsrfToken(getApiCsrfToken($input));
}

function requireApiCsrfToken($input = null)
{
    if (!verifyApiCsrfToken($input)) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 419);
    }
}

function getClientIpAddress()
{
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $value = trim((string) $_SERVER[$key]);
            if ($key === 'HTTP_X_FORWARDED_FOR' && strpos($value, ',') !== false) {
                $parts = explode(',', $value);
                $value = trim($parts[0]);
            }
            if ($value !== '') {
                return $value;
            }
        }
    }
    return 'unknown';
}

function getLoginThrottleStorePath()
{
    return __DIR__ . '/../logs/login_throttle.json';
}

function readLoginThrottleData()
{
    $file = getLoginThrottleStorePath();
    if (!file_exists($file)) {
        return [];
    }
    $raw = @file_get_contents($file);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function writeLoginThrottleData($data)
{
    $file = getLoginThrottleStorePath();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

function buildLoginThrottleKey($scope, $identifier, $ip)
{
    $scopeValue = strtolower(trim((string) $scope));
    $identifierValue = strtolower(trim((string) $identifier));
    $ipValue = strtolower(trim((string) $ip));
    return hash('sha256', $scopeValue . '|' . $identifierValue . '|' . $ipValue);
}

function getLoginThrottleStatus($scope, $identifier, $maxAttempts = 5, $windowSeconds = 900, $blockSeconds = 900)
{
    $now = time();
    $ip = getClientIpAddress();
    $key = buildLoginThrottleKey($scope, $identifier, $ip);
    $data = readLoginThrottleData();
    $record = $data[$key] ?? ['attempts' => [], 'blocked_until' => 0];
    $attempts = array_values(array_filter($record['attempts'] ?? [], function ($ts) use ($now, $windowSeconds) {
        return is_numeric($ts) && (int) $ts > ($now - $windowSeconds);
    }));
    $blockedUntil = (int) ($record['blocked_until'] ?? 0);
    if (count($attempts) >= $maxAttempts && $blockedUntil < $now) {
        $blockedUntil = $now + $blockSeconds;
    }
    $record['attempts'] = $attempts;
    $record['blocked_until'] = $blockedUntil;
    $data[$key] = $record;
    writeLoginThrottleData($data);
    return [
        'blocked' => $blockedUntil > $now,
        'retry_after' => max(0, $blockedUntil - $now),
        'attempts' => count($attempts)
    ];
}

function addLoginFailure($scope, $identifier, $maxAttempts = 5, $windowSeconds = 900, $blockSeconds = 900)
{
    $now = time();
    $ip = getClientIpAddress();
    $key = buildLoginThrottleKey($scope, $identifier, $ip);
    $data = readLoginThrottleData();
    $record = $data[$key] ?? ['attempts' => [], 'blocked_until' => 0];
    $attempts = array_values(array_filter($record['attempts'] ?? [], function ($ts) use ($now, $windowSeconds) {
        return is_numeric($ts) && (int) $ts > ($now - $windowSeconds);
    }));
    $attempts[] = $now;
    $blockedUntil = (int) ($record['blocked_until'] ?? 0);
    if (count($attempts) >= $maxAttempts) {
        $blockedUntil = max($blockedUntil, $now + $blockSeconds);
    }
    $record['attempts'] = $attempts;
    $record['blocked_until'] = $blockedUntil;
    $data[$key] = $record;
    writeLoginThrottleData($data);
}

function clearLoginFailures($scope, $identifier)
{
    $ip = getClientIpAddress();
    $key = buildLoginThrottleKey($scope, $identifier, $ip);
    $data = readLoginThrottleData();
    if (isset($data[$key])) {
        unset($data[$key]);
        writeLoginThrottleData($data);
    }
}

// Check if admin is logged in
function isAdminLoggedIn()
{
    if (!isset($_SESSION['admin']['logged_in']) || $_SESSION['admin']['logged_in'] !== true) {
        return false;
    }
    $loginTime = $_SESSION['admin']['login_time'] ?? null;
    if (is_numeric($loginTime) && (time() - (int) $loginTime) > 43200) {
        unset($_SESSION['admin']);
        return false;
    }
    return true;
}

// Check if customer is logged in
function isCustomerLoggedIn()
{
    if (!isset($_SESSION['customer']['logged_in']) || $_SESSION['customer']['logged_in'] !== true) {
        return false;
    }
    $loginTime = $_SESSION['customer']['login_time'] ?? null;
    if (is_numeric($loginTime) && (time() - (int) $loginTime) > 43200) {
        unset($_SESSION['customer']);
        return false;
    }
    return true;
}

// Get current admin
function getCurrentAdmin()
{
    return $_SESSION['admin'] ?? null;
}

// Get current customer
function getCurrentCustomer()
{
    return $_SESSION['customer'] ?? null;
}

// JSON response
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Check if request is AJAX
function isAjax()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Get current URL
function getCurrentUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Format bytes to human readable format
function formatBytes($bytes, $precision = 2)
{
    $bytes = is_numeric($bytes) ? (float) $bytes : 0;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getBackupDirectory()
{
    return __DIR__ . '/../backups/';
}

function ensureBackupDirectory()
{
    $backupDir = getBackupDirectory();
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0777, true);
    }
    return is_dir($backupDir);
}

function ensureCustomersAutoIsolateColumn()
{
    static $checked = false;
    if ($checked) {
        return true;
    }
    if (!tableExists('customers')) {
        return false;
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'auto_isolate'");
        $exists = $stmt && $stmt->rowCount() > 0;
        if (!$exists) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN auto_isolate TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
        }
        $checked = true;
        return true;
    } catch (Exception $e) {
        logError('Ensure customers.auto_isolate failed: ' . $e->getMessage());
        return false;
    }
}

function ensureInvoicesPaymentLinkColumns()
{
    static $checked = false;
    if ($checked) {
        return true;
    }
    if (!tableExists('invoices')) {
        return false;
    }
    try {
        $pdo = getDB();
        $cols = [
            'payment_gateway' => "ALTER TABLE invoices ADD COLUMN payment_gateway VARCHAR(20) NULL AFTER payment_ref",
            'payment_link' => "ALTER TABLE invoices ADD COLUMN payment_link TEXT NULL AFTER payment_method",
            'payment_reference' => "ALTER TABLE invoices ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_link",
            'payment_payload' => "ALTER TABLE invoices ADD COLUMN payment_payload TEXT NULL AFTER payment_reference",
            'payment_link_created_at' => "ALTER TABLE invoices ADD COLUMN payment_link_created_at DATETIME NULL AFTER payment_payload",
            'payment_link_expires_at' => "ALTER TABLE invoices ADD COLUMN payment_link_expires_at DATETIME NULL AFTER payment_link_created_at",
            'payment_order_id' => "ALTER TABLE invoices ADD COLUMN payment_order_id VARCHAR(100) NULL AFTER payment_gateway"
        ];
        foreach ($cols as $name => $ddl) {
            $stmt = $pdo->query("SHOW COLUMNS FROM invoices LIKE '{$name}'");
            $exists = $stmt && $stmt->rowCount() > 0;
            if (!$exists) {
                $pdo->exec($ddl);
            }
        }
        $checked = true;
        return true;
    } catch (Exception $e) {
        logError('Ensure invoices payment columns failed: ' . $e->getMessage());
        return false;
    }
}

function getInvoicePayTokenSecret()
{
    $row = fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'INVOICE_PAY_TOKEN' LIMIT 1");
    if ($row && trim((string) ($row['setting_value'] ?? '')) !== '') {
        return (string) $row['setting_value'];
    }
    $token = bin2hex(random_bytes(16));
    if ($row) {
        update('settings', ['setting_value' => $token], "setting_key = 'INVOICE_PAY_TOKEN'");
    } else {
        insert('settings', ['setting_key' => 'INVOICE_PAY_TOKEN', 'setting_value' => $token]);
    }
    return $token;
}

function invoicePayToken($invoiceNumber)
{
    $secret = getInvoicePayTokenSecret();
    return hash_hmac('sha256', (string) $invoiceNumber, $secret);
}

function invoicePayUrl($invoiceNumber)
{
    $token = invoicePayToken($invoiceNumber);
    return rtrim((string) APP_URL, '/') . '/pay_invoice.php?inv=' . rawurlencode((string) $invoiceNumber) . '&token=' . rawurlencode($token);
}

function sanitizeBackupFilename($filename)
{
    $name = basename((string) $filename);
    return preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $name) ? $name : '';
}

function listDatabaseBackups()
{
    if (!ensureBackupDirectory()) {
        return [];
    }
    $files = glob(getBackupDirectory() . 'backup_*.sql');
    if (!is_array($files)) {
        return [];
    }
    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });
    $result = [];
    foreach ($files as $file) {
        $result[] = [
            'name' => basename($file),
            'path' => $file,
            'size' => is_file($file) ? filesize($file) : 0,
            'modified_at' => is_file($file) ? date('Y-m-d H:i:s', filemtime($file)) : null,
            'timestamp' => is_file($file) ? filemtime($file) : 0
        ];
    }
    return $result;
}

function applyBackupRetention($retentionDays = 7)
{
    $days = (int) $retentionDays;
    if ($days < 1) {
        $days = 1;
    }
    $deleted = [];
    if (!ensureBackupDirectory()) {
        return $deleted;
    }
    $threshold = strtotime("-{$days} days");
    $files = glob(getBackupDirectory() . 'backup_*.sql');
    if (!is_array($files)) {
        return $deleted;
    }
    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }
        if (filemtime($file) < $threshold) {
            if (@unlink($file)) {
                $deleted[] = basename($file);
            }
        }
    }
    return $deleted;
}

function createDatabaseBackup($retentionDays = 7)
{
    if (!ensureBackupDirectory()) {
        return ['success' => false, 'message' => 'Backup folder could not be created'];
    }
    $backupFile = getBackupDirectory() . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $command = sprintf(
        "mysqldump -h %s -u %s -p%s %s > %s",
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($backupFile)
    );
    exec($command, $output, $returnCode);
    if ($returnCode !== 0 || !file_exists($backupFile)) {
        return ['success' => false, 'message' => 'Failed to create database backup'];
    }
    $deletedFiles = applyBackupRetention($retentionDays);
    return [
        'success' => true,
        'message' => 'Database backup successfully created',
        'file_path' => $backupFile,
        'file_name' => basename($backupFile),
        'file_size' => filesize($backupFile),
        'deleted_files' => $deletedFiles
    ];
}

function restoreDatabaseBackup($filename)
{
    $safeName = sanitizeBackupFilename($filename);
    if ($safeName === '') {
        return ['success' => false, 'message' => 'Invalid backup file name'];
    }
    if (!ensureBackupDirectory()) {
        return ['success' => false, 'message' => 'Backup folder not found'];
    }
    $backupFile = getBackupDirectory() . $safeName;
    if (!is_file($backupFile)) {
        return ['success' => false, 'message' => 'Backup file not found'];
    }
    $command = sprintf(
        "mysql -h %s -u %s -p%s %s < %s",
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($backupFile)
    );
    exec($command, $output, $returnCode);
    if ($returnCode !== 0) {
        return ['success' => false, 'message' => 'Backup restore failed to execute'];
    }
    return ['success' => true, 'message' => 'Backup restore successful', 'file_name' => $safeName];
}

function ensurePublicVoucherTables()
{
    static $checked = false;
    if ($checked) {
        return true;
    }
    $pdo = getDB();
    $sql = "CREATE TABLE IF NOT EXISTS hotspot_voucher_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        profile_name VARCHAR(100) NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        payment_gateway VARCHAR(20) NOT NULL DEFAULT 'tripay',
        payment_method VARCHAR(100) DEFAULT NULL,
        payment_link TEXT,
        payment_reference VARCHAR(100) DEFAULT NULL,
        payment_payload LONGTEXT,
        status ENUM('pending','paid','failed','expired') DEFAULT 'pending',
        paid_at DATETIME DEFAULT NULL,
        voucher_username VARCHAR(100) DEFAULT NULL,
        voucher_password VARCHAR(100) DEFAULT NULL,
        voucher_generated_at DATETIME DEFAULT NULL,
        fulfillment_status ENUM('pending','success','failed') DEFAULT 'pending',
        fulfillment_error TEXT,
        whatsapp_status ENUM('pending','sent','failed') DEFAULT 'pending',
        whatsapp_sent_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try {
        $pdo->exec($sql);
        $checked = true;
        return true;
    } catch (Exception $e) {
        logError('Ensure hotspot_voucher_orders failed: ' . $e->getMessage());
        return false;
    }
}

function ensureInvoiceNotificationTables()
{
    static $checked = false;
    if ($checked) {
        return true;
    }
    $pdo = getDB();
    $sql = "CREATE TABLE IF NOT EXISTS invoice_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number VARCHAR(50) NOT NULL,
        event VARCHAR(30) NOT NULL,
        whatsapp_status ENUM('pending','sent','failed') DEFAULT 'pending',
        whatsapp_sent_at DATETIME DEFAULT NULL,
        last_error TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_invoice_event (invoice_number, event)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try {
        $pdo->exec($sql);
        $checked = true;
        return true;
    } catch (Exception $e) {
        logError('Ensure invoice_notifications failed: ' . $e->getMessage());
        return false;
    }
}

function sendInvoicePaidWhatsapp($invoiceNumber, $gateway = '', $paymentData = [])
{
    if (!ensureInvoiceNotificationTables()) {
        return false;
    }
    $invoiceNumber = trim((string) $invoiceNumber);
    if ($invoiceNumber === '') {
        return false;
    }
    $existing = fetchOne("SELECT * FROM invoice_notifications WHERE invoice_number = ? AND event = ?", [$invoiceNumber, 'paid']);
    if ($existing && ($existing['whatsapp_status'] ?? '') === 'sent') {
        return true;
    }

    $invoice = fetchOne("SELECT i.*, c.name as customer_name, c.phone as customer_phone, p.name as package_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id LEFT JOIN packages p ON i.package_id = p.id WHERE i.invoice_number = ?", [$invoiceNumber]);
    if (!$invoice) {
        return false;
    }

    $phone = trim((string) ($invoice['customer_phone'] ?? ''));
    if ($phone === '') {
        return false;
    }

    $method = '';
    if (is_array($paymentData)) {
        $method = (string) ($paymentData['payment_method'] ?? ($paymentData['payment_type'] ?? ''));
    }
    if ($method === '') {
        $method = (string) ($invoice['payment_method'] ?? '');
    }
    if ($gateway === '') {
        $gateway = (string) ($invoice['payment_gateway'] ?? '');
    }

    $message = "Payment Received\n\n";
    $message .= "Invoice: {$invoiceNumber}\n";
    $message .= "Name: " . ($invoice['customer_name'] ?? '-') . "\n";
    if (!empty($invoice['package_name'])) {
        $message .= "Package: " . $invoice['package_name'] . "\n";
    }
    $message .= "Total: " . formatCurrency($invoice['amount'] ?? 0) . "\n";
    $message .= "Method: " . ($method !== '' ? $method : '-') . "\n";
    $message .= "Gateway: " . ($gateway !== '' ? strtoupper((string) $gateway) : '-') . "\n";
    $paidAt = $invoice['paid_at'] ?? '';
    if ($paidAt) {
        $message .= "Time: {$paidAt}\n";
    }
    $message .= "\nThank you. If service was temporarily isolated, the system will reactivate automatically.";
    $message .= getWhatsAppFooter();

    $sent = sendWhatsApp($phone, $message);

    $data = [
        'invoice_number' => $invoiceNumber,
        'event' => 'paid',
        'whatsapp_status' => $sent ? 'sent' : 'failed',
        'whatsapp_sent_at' => $sent ? date('Y-m-d H:i:s') : null,
        'last_error' => $sent ? null : 'sendWhatsApp failed'
    ];

    if ($existing) {
        update('invoice_notifications', [
            'whatsapp_status' => $data['whatsapp_status'],
            'whatsapp_sent_at' => $data['whatsapp_sent_at'],
            'last_error' => $data['last_error']
        ], 'id = ?', [(int) $existing['id']]);
    } else {
        insert('invoice_notifications', $data);
    }

    return $sent;
}

function getPublicVoucherCatalog()
{
    $profiles = mikrotikGetHotspotProfileeeeeeeeeeees();
    $catalog = [];
    foreach ($profiles as $profile) {
        $name = trim((string) ($profile['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $onLogin = parseMikhmonOnLogin($profile['on-login'] ?? '');
        $price = (int) ($onLogin['selling_price'] ?? 0);
        if ($price <= 0) {
            $price = (int) ($onLogin['price'] ?? 0);
        }
        if ($price <= 0) {
            continue;
        }
        $catalog[] = [
            'profile_name' => $name,
            'display_name' => $name,
            'price' => $price,
            'validity' => $onLogin['validity'] ?? '-'
        ];
    }
    usort($catalog, function ($a, $b) {
        return ((int) $a['price']) <=> ((int) $b['price']);
    });
    return $catalog;
}

function findPublicVoucherPackage($catalog, $profileName)
{
    $target = trim((string) $profileName);
    foreach ($catalog as $item) {
        if (($item['profile_name'] ?? '') === $target) {
            return $item;
        }
    }
    return null;
}

function normalizePublicVoucherPhone($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        return '';
    }
    if (strpos($digits, '0') === 0) {
        return '62' . substr($digits, 1);
    }
    if (strpos($digits, '62') === 0) {
        return $digits;
    }
    return $digits;
}

function generatePublicVoucherOrderNumber()
{
    return 'VCR' . date('YmdHis') . strtoupper(generateRandomString(4, 'mixed'));
}

function createPublicVoucherOrder($payload)
{
    if (!ensurePublicVoucherTables()) {
        return ['success' => false, 'message' => 'Failed to prepare public voucher tables'];
    }
    require_once __DIR__ . '/payment.php';
    $name = trim((string) ($payload['customer_name'] ?? ''));
    $phone = normalizePublicVoucherPhone($payload['customer_phone'] ?? '');
    $profileName = trim((string) ($payload['profile_name'] ?? ''));
    $amount = (int) ($payload['amount'] ?? 0);
    $gateway = strtolower(trim((string) ($payload['payment_gateway'] ?? 'tripay')));
    $paymentMethod = trim((string) ($payload['payment_method'] ?? ''));
    if ($name === '' || $phone === '' || $profileName === '' || $amount <= 0) {
        return ['success' => false, 'message' => 'Invalid voucher order data'];
    }
    if (!in_array($gateway, ['tripay', 'midtrans'], true)) {
        $gateway = 'tripay';
    }
    $orderNumber = '';
    for ($i = 0; $i < 5; $i++) {
        $candidate = generatePublicVoucherOrderNumber();
        $exists = fetchOne("SELECT id FROM hotspot_voucher_orders WHERE order_number = ?", [$candidate]);
        if (!$exists) {
            $orderNumber = $candidate;
            break;
        }
    }
    if ($orderNumber === '') {
        return ['success' => false, 'message' => 'Failed to create voucher order number'];
    }
    $payment = generatePaymentLink(
        $orderNumber,
        $amount,
        $name,
        $phone,
        date('Y-m-d', strtotime('+1 day')),
        $gateway,
        $paymentMethod
    );
    if (!($payment['success'] ?? false)) {
        return ['success' => false, 'message' => $payment['message'] ?? 'Failed to create payment link'];
    }
    $paymentData = $payment['data'] ?? [];
    $paymentReference = null;
    if (is_array($paymentData)) {
        if ($gateway === 'tripay' && isset($paymentData['reference'])) {
            $paymentReference = (string) $paymentData['reference'];
        } elseif ($gateway === 'midtrans' && isset($paymentData['token'])) {
            $paymentReference = (string) $paymentData['token'];
        }
    }
    $insertId = insert('hotspot_voucher_orders', [
        'order_number' => $orderNumber,
        'customer_name' => $name,
        'customer_phone' => $phone,
        'profile_name' => $profileName,
        'amount' => $amount,
        'payment_gateway' => $gateway,
        'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
        'payment_link' => $payment['link'] ?? '',
        'payment_reference' => $paymentReference,
        'payment_payload' => is_array($paymentData) ? json_encode($paymentData) : null,
        'status' => 'pending',
        'fulfillment_status' => 'pending',
        'whatsapp_status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    if (!$insertId) {
        return ['success' => false, 'message' => 'Failed to save voucher order'];
    }
    return [
        'success' => true,
        'order_number' => $orderNumber,
        'payment_link' => $payment['link'] ?? '',
        'id' => $insertId
    ];
}

function sanitizePublicVoucherOrderNumber($orderNumber)
{
    $value = trim((string) $orderNumber);
    return preg_match('/^VCR[0-9]{14}[A-Za-z0-9]{4}$/', $value) ? strtoupper($value) : '';
}

function getPublicVoucherOrderByNumber($orderNumber)
{
    if (!ensurePublicVoucherTables()) {
        return null;
    }
    $safe = sanitizePublicVoucherOrderNumber($orderNumber);
    if ($safe === '') {
        return null;
    }
    return fetchOne("SELECT * FROM hotspot_voucher_orders WHERE order_number = ?", [$safe]);
}

function buildPublicVoucherMessage($order)
{
    $message = "Hotspot voucher payment successful.\n\n";
    $message .= "Order No: " . ($order['order_number'] ?? '-') . "\n";
    $message .= "Profileeeeeeeeeeee: " . ($order['profile_name'] ?? '-') . "\n";
    $username = (string) ($order['voucher_username'] ?? '-');
    $password = (string) ($order['voucher_password'] ?? '-');
    if ($username !== '-' && $password !== '-' && $username === $password) {
        $message .= "Voucher Code: " . $username . "\n";
        $message .= "Password: same as voucher code\n";
    } else {
        $message .= "Username: " . $username . "\n";
        $message .= "Password: " . $password . "\n";
    }
    $message .= "Amount: " . formatCurrency($order['amount'] ?? 0) . "\n\n";
    $message .= "Keep this voucher code safe.";
    return $message;
}

function sendPublicVoucherWhatsapp($order)
{
    $phone = $order['customer_phone'] ?? '';
    if ($phone === '') {
        return false;
    }
    $message = buildPublicVoucherMessage($order);
    return sendWhatsApp($phone, $message);
}

function fulfillPublicVoucherOrder($orderNumber)
{
    if (!ensurePublicVoucherTables()) {
        return ['success' => false, 'message' => 'Voucher order table not ready'];
    }
    $safe = sanitizePublicVoucherOrderNumber($orderNumber);
    if ($safe === '') {
        return ['success' => false, 'message' => 'Invalid voucher order number'];
    }
    $order = fetchOne("SELECT * FROM hotspot_voucher_orders WHERE order_number = ?", [$safe]);
    if (!$order) {
        return ['success' => false, 'message' => 'Voucher order not found'];
    }
    if (($order['status'] ?? '') !== 'paid') {
        return ['success' => false, 'message' => 'Voucher order not yet paid'];
    }
    if (!empty($order['voucher_username']) && !empty($order['voucher_password'])) {
        if (($order['whatsapp_status'] ?? 'pending') !== 'sent') {
            $waSent = sendPublicVoucherWhatsapp($order);
            update('hotspot_voucher_orders', [
                'whatsapp_status' => $waSent ? 'sent' : 'failed',
                'whatsapp_sent_at' => $waSent ? date('Y-m-d H:i:s') : null
            ], 'order_number = ?', [$safe]);
        }
        return ['success' => true, 'message' => 'Voucher already available', 'order' => getPublicVoucherOrderByNumber($safe)];
    }
    $prefix = trim((string) getSetting('PUBLIC_VOUCHER_PREFIX', 'VCH-'));
    $numericOnly = (string) getSetting('PUBLIC_VOUCHER_CODE_TYPE', 'numeric') === 'numeric'
        || (int) getSetting('PUBLIC_VOUCHER_NUMERIC_ONLY', 0) === 1;
    $passwordSame = (string) getSetting('PUBLIC_VOUCHER_PASSWORD_MODE', 'same') === 'same'
        || (int) getSetting('PUBLIC_VOUCHER_PASSWORD_SAME', 0) === 1;
    if ($numericOnly) {
        $prefix = preg_replace('/\D+/', '', $prefix);
    }
    $length = (int) getSetting('PUBLIC_VOUCHER_LENGTH', 6);
    if ($length < 4) {
        $length = 4;
    }
    if ($length > 12) {
        $length = 12;
    }
    $profileName = trim((string) ($order['profile_name'] ?? ''));
    if ($profileName === '') {
        return ['success' => false, 'message' => 'Invalid voucher profile'];
    }
    $created = false;
    $username = '';
    $password = '';
    $errorMessage = '';
    for ($i = 0; $i < 20; $i++) {
        $seed = generateRandomString($length, $numericOnly ? 'numeric' : 'mixed');
        $username = $prefix . ($numericOnly ? $seed : strtoupper($seed));
        $password = $passwordSame ? $username : ($numericOnly ? $seed : strtoupper($seed));
        $comment = 'vc-public-voucher-' . $safe;
        if (mikrotikAddHotspotUser($username, $password, $profileName, ['comment' => $comment])) {
            $created = true;
            break;
        }
    }
    if (!$created) {
        $errorMessage = 'Failed to create voucher in MikroTik';
        update('hotspot_voucher_orders', [
            'fulfillment_status' => 'failed',
            'fulfillment_error' => $errorMessage
        ], 'order_number = ?', [$safe]);
        logError('Fulfill public voucher failed: ' . $safe);
        return ['success' => false, 'message' => $errorMessage];
    }
    update('hotspot_voucher_orders', [
        'voucher_username' => $username,
        'voucher_password' => $password,
        'voucher_generated_at' => date('Y-m-d H:i:s'),
        'fulfillment_status' => 'success',
        'fulfillment_error' => null
    ], 'order_number = ?', [$safe]);
    recordHotspotSale($username, $profileName, (int) $order['amount'], (int) $order['amount'], $prefix);
    $updatedOrder = getPublicVoucherOrderByNumber($safe);
    $waSent = false;
    if ($updatedOrder) {
        $waSent = sendPublicVoucherWhatsapp($updatedOrder);
    }
    update('hotspot_voucher_orders', [
        'whatsapp_status' => $waSent ? 'sent' : 'failed',
        'whatsapp_sent_at' => $waSent ? date('Y-m-d H:i:s') : null
    ], 'order_number = ?', [$safe]);
    return [
        'success' => true,
        'message' => $waSent ? 'Voucher successfully created and sent to WhatsApp' : 'Voucher successfully created, WhatsApp delivery failed',
        'order' => getPublicVoucherOrderByNumber($safe)
    ];
}

function markPublicVoucherOrderPaid($orderNumber, $gateway, $paymentData = [])
{
    if (!ensurePublicVoucherTables()) {
        return false;
    }
    $safe = sanitizePublicVoucherOrderNumber($orderNumber);
    if ($safe === '') {
        return false;
    }
    $order = fetchOne("SELECT * FROM hotspot_voucher_orders WHERE order_number = ?", [$safe]);
    if (!$order) {
        return false;
    }
    $paymentMethod = $paymentData['payment_method'] ?? ($paymentData['payment_type'] ?? null);
    $paymentRef = $paymentData['reference'] ?? ($paymentData['transaction_id'] ?? null);
    update('hotspot_voucher_orders', [
        'status' => 'paid',
        'paid_at' => $order['paid_at'] ?: date('Y-m-d H:i:s'),
        'payment_gateway' => $gateway,
        'payment_method' => $paymentMethod ?: ($order['payment_method'] ?? null),
        'payment_reference' => $paymentRef ?: ($order['payment_reference'] ?? null),
        'payment_payload' => json_encode($paymentData)
    ], 'order_number = ?', [$safe]);
    $result = fulfillPublicVoucherOrder($safe);
    return $result['success'] ?? false;
}

function markPublicVoucherOrderFailed($orderNumber, $status, $paymentData = [])
{
    if (!ensurePublicVoucherTables()) {
        return false;
    }
    $safe = sanitizePublicVoucherOrderNumber($orderNumber);
    if ($safe === '') {
        return false;
    }
    $order = fetchOne("SELECT * FROM hotspot_voucher_orders WHERE order_number = ?", [$safe]);
    if (!$order) {
        return false;
    }
    if (($order['status'] ?? '') === 'paid') {
        return true;
    }
    $failedStatus = strtolower((string) $status) === 'expired' ? 'expired' : 'failed';
    return update('hotspot_voucher_orders', [
        'status' => $failedStatus,
        'payment_payload' => json_encode($paymentData)
    ], 'order_number = ?', [$safe]);
}

function syncPublicVoucherOrderPaymentStatus($orderNumber)
{
    $order = getPublicVoucherOrderByNumber($orderNumber);
    if (!$order) {
        return null;
    }
    if (($order['status'] ?? '') === 'paid') {
        if (($order['fulfillment_status'] ?? '') !== 'success' || ($order['whatsapp_status'] ?? 'pending') !== 'sent') {
            fulfillPublicVoucherOrder($order['order_number']);
            $order = getPublicVoucherOrderByNumber($order['order_number']);
        }
        return $order;
    }
    require_once __DIR__ . '/payment.php';
    $gateway = strtolower((string) ($order['payment_gateway'] ?? 'tripay'));
    $payload = [];
    $status = '';
    if ($gateway === 'midtrans') {
        $result = getMidtransPaymentStatus($order['order_number']);
        if (!($result['success'] ?? false)) {
            return $order;
        }
        $payload = $result['data'] ?? [];
        if (isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }
        $status = strtolower((string) ($payload['transaction_status'] ?? ''));
        if ($status === 'settlement' || $status === 'capture') {
            markPublicVoucherOrderPaid($order['order_number'], 'midtrans', $payload);
        } elseif (in_array($status, ['expire', 'cancel', 'deny'], true)) {
            markPublicVoucherOrderFailed($order['order_number'], $status, $payload);
        }
    } else {
        $result = getTripayPaymentStatus($order['order_number']);
        if (!($result['success'] ?? false)) {
            return $order;
        }
        $payload = $result['data'] ?? [];
        if (isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }
        $status = strtoupper((string) ($payload['status'] ?? ''));
        if ($status === 'PAID') {
            markPublicVoucherOrderPaid($order['order_number'], 'tripay', $payload);
        } elseif (in_array($status, ['EXPIRED', 'FAILED'], true)) {
            markPublicVoucherOrderFailed($order['order_number'], strtolower($status), $payload);
        }
    }
    return getPublicVoucherOrderByNumber($order['order_number']);
}
