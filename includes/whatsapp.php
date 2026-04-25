<?php
/**
 * WhatsApp Gateway Integration
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Helper to get settings from database if constant is empty
function getWhatsAppSetting($key, $constantValue) {
    if (!empty($constantValue)) {
        return $constantValue;
    }
    
    // Attempt to fetch from database
    try {
        $row = fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $row ? $row['setting_value'] : '';
    } catch (Exception) {
        return '';
    }
}

function logWhatsAppError($message)
{
    file_put_contents(__DIR__ . '/../logs/whatsapp_error.log', "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

// Fonnte WhatsApp Sender
function sendFonnteWhatsApp($phone, $message) {
    $token = getWhatsAppSetting('FONNTE_API_TOKEN', defined('FONNTE_API_TOKEN') ? constant('FONNTE_API_TOKEN') : '');
    
    if (empty($token)) {
        logWhatsAppError("SENDER_ERROR: Fonnte API token not configured");
        return ['success' => false, 'message' => 'Fonnte API token not configured'];
    }
    
    $url = 'https://api.fonnte.com/send';
    
    $data = [
        'target' => $phone,
        'message' => $message,
        'countryCode' => '62'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: ' . $token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    
    if ($response === false || $httpCode === 0) {
        $errorMsg = "Failed to send WhatsApp via Fonnte (HTTP $httpCode, cURL $curlErrno): $curlError";
        logWhatsAppError("SENDER_ERROR: " . $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    } elseif ($httpCode === 200) {
        return ['success' => true, 'data' => json_decode($response, true)];
    } else {
        $errorMsg = "Failed to send WhatsApp via Fonnte (HTTP $httpCode): $response";
        logWhatsAppError("SENDER_ERROR: " . $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    }
}

// Wablas WhatsApp Sender
function sendWablasWhatsApp($phone, $message) {
    $token = getWhatsAppSetting('WABLAS_API_TOKEN', defined('WABLAS_API_TOKEN') ? constant('WABLAS_API_TOKEN') : '');
    
    if (empty($token)) {
        logWhatsAppError("SENDER_ERROR: Wablas API token not configured");
        return ['success' => false, 'message' => 'Wablas API token not configured'];
    }
    
    $url = 'https://solo.wablas.com/api/send-message';
    
    $data = [
        'phone' => $phone,
        'message' => $message,
        'secret' => $token
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    
    if ($response === false || $httpCode === 0) {
        $errorMsg = "Failed to send WhatsApp via Wablas (HTTP $httpCode, cURL $curlErrno): $curlError";
        logWhatsAppError("SENDER_ERROR: " . $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    } elseif ($httpCode === 200) {
        return ['success' => true, 'data' => json_decode($response, true)];
    } else {
        $errorMsg = "Failed to send WhatsApp via Wablas (HTTP $httpCode): $response";
        logWhatsAppError("SENDER_ERROR: " . $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    }
}

// MPWA WhatsApp Sender
function sendMpwaWhatsApp($phone, $message) {
    $token = getWhatsAppSetting('MPWA_API_KEY', defined('MPWA_API_KEY') ? constant('MPWA_API_KEY') : '');
    
    if (empty($token)) {
        logWhatsAppError("SENDER_ERROR: MPWA API key not configured");
        return ['success' => false, 'message' => 'MPWA API key not configured'];
    }
    
    // Sender number: nomor HP yang sudah di-scan QR di dashboard MPWA
    $sender = getWhatsAppSetting('MPWA_SENDER', defined('MPWA_SENDER') ? constant('MPWA_SENDER') : '');
    
    if (empty($sender)) {
        logWhatsAppError("SENDER_ERROR: MPWA sender number not configured");
        return ['success' => false, 'message' => 'MPWA sender number not configured'];
    }
    
    $url = getWhatsAppSetting('MPWA_API_URL', defined('MPWA_API_URL') ? constant('MPWA_API_URL') : '');
    $url = trim((string) $url);
    if ($url === '') {
        $url = 'https://mpwa.official.id/api/send';
    }
    
    $data = [
        'api_key' => $token,
        'sender'  => $sender,   // nomor pengirim yang terdaftar di MPWA
        'number'  => $phone,    // nomor tujuan
        'message' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: GEMBOK/2.x (MPWA Client)'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    unset($ch);
    
    if ($response === false || $httpCode === 0) {
        $errorMsg = "Failed to send WhatsApp via MPWA (HTTP $httpCode, cURL $curlErrno): $curlError";
        logWhatsAppError("SENDER_ERROR: " . $errorMsg . " | URL: " . (string) $effectiveUrl);
        return ['success' => false, 'message' => $errorMsg];
    }

    if ($httpCode === 200) {
        $decoded = json_decode((string) $response, true);
        if (is_array($decoded)) {
            $status = null;
            if (isset($decoded['success'])) {
                $status = (bool) $decoded['success'];
            } elseif (isset($decoded['status'])) {
                $status = (bool) $decoded['status'];
            }
            if ($status === false) {
                $msg = (string) ($decoded['message'] ?? 'MPWA mengembalikan status gagal');
                $errorMsg = "Failed to send WhatsApp via MPWA (HTTP $httpCode): $msg";
                logWhatsAppError("SENDER_ERROR: " . $errorMsg . " | RAW: " . mb_substr((string) $response, 0, 800));
                return ['success' => false, 'message' => $errorMsg, 'data' => $decoded];
            }
        }
        return ['success' => true, 'data' => $decoded];
    } else {
        $errorMsg = "Failed to send WhatsApp via MPWA (HTTP $httpCode): $response";
        logWhatsAppError("SENDER_ERROR: " . $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    }
}


// Get supported WhatsApp gateways
function getWhatsAppGateways() {
    return [
        [
            'id' => 'fonnte',
            'name' => 'Fonnte',
            'icon' => 'fa-whatsapp',
            'color' => '#25d366',
            'description' => 'WhatsApp API service'
        ],
        [
            'id' => 'wablas',
            'name' => 'Wablas',
            'icon' => 'fa-whatsapp',
            'color' => '#25d366',
            'description' => 'WhatsApp API service'
        ],
        [
            'id' => 'mpwa',
            'name' => 'MPWA',
            'icon' => 'fa-whatsapp',
            'color' => '#25d366',
            'description' => 'WhatsApp API service'
        ]
    ];
}

// Send WhatsApp message based on gateway
function sendWhatsAppMessage($phone, $message, $gateway = 'fonnte') {
    switch ($gateway) {
        case 'fonnte':
            return sendFonnteWhatsApp($phone, $message);
            
        case 'wablas':
            return sendWablasWhatsApp($phone, $message);
            
        case 'mpwa':
            return sendMpwaWhatsApp($phone, $message);
            
        default:
            return [
                'success' => false,
                'message' => 'WhatsApp gateway not supported'
            ];
    }
}
