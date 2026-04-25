<?php
/**
 * API: ONU WiFi Settings
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Allow admin, customer, and technician
if (!isCustomerLoggedIn() && !isAdminLoggedIn() && !isTechnicianLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $pppoeUsername = $_GET['pppoe_username'] ?? '';
        $serial = $_GET['serial'] ?? '';
        
        if (empty($pppoeUsername) && empty($serial)) {
            echo json_encode(['success' => false, 'message' => 'PPPoE Username or serial required']);
            exit;
        }

        if (!empty($serial)) {
            $deviceData = genieacsGetDeviceInfo($serial);
            if ($deviceData) {
                echo json_encode(['success' => true, 'data' => $deviceData]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Device not found or offline']);
            }
            exit;
        }

        $device = genieacsFindDeviceByPppoe($pppoeUsername);
        if ($device) {
            $deviceId = $device['_id'];
            $deviceData = genieacsGetDeviceInfo($deviceId);
            if ($deviceData) {
                echo json_encode(['success' => true, 'data' => $deviceData]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to retrieve device info']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Device not found or offline']);
        }
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (isCustomerLoggedIn()) {
        requireApiCsrfToken($input);
    }

    $pppoeUsername = $input['pppoe_username'] ?? '';
    $serial = $input['serial'] ?? '';  // Keep for backward compatibility
    $ssid = $input['ssid'] ?? '';
    $password = $input['password'] ?? '';
    $ssid5g = $input['ssid_5g'] ?? '';
    $password5g = $input['password_5g'] ?? '';

    // If customer is logged in, enforce ownership
    if (isCustomerLoggedIn()) {
        $customer = getCurrentCustomer();
        // If pppoe_username is provided, it MUST match the customer's
        if (!empty($pppoeUsername) && $pppoeUsername !== $customer['pppoe_username']) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access to this device']);
            exit;
        }
        // If only serial is provided, we still need to verify ownership (more complex, so enforce pppoe_username for customers)
        if (empty($pppoeUsername)) {
            // Force use of customer's pppoe_username
            $pppoeUsername = $customer['pppoe_username'];
        }
    }

    // Use either pppoe_username or serial
    if (empty($pppoeUsername) && empty($serial)) {
        echo json_encode(['success' => false, 'message' => 'PPPoE username or serial number is required']);
        exit;
    }

    // If PPPoE username is provided, find the device
    if (!empty($pppoeUsername)) {
        $device = genieacsFindDeviceByPppoe($pppoeUsername);
        if (!$device) {
            echo json_encode(['success' => false, 'message' => 'Device not found for PPPoE username: ' . $pppoeUsername]);
            exit;
        }
        $serial = $device['_id'] ?? $device['DeviceID']['_SerialNumber'] ?? $pppoeUsername; // Fallback to _id first, then serial, then username
    }

    // Validate SSID
    if (!empty($ssid) && strlen($ssid) < 3) {
        echo json_encode(['success' => false, 'message' => 'SSID minimum 3 characters']);
        exit;
    }
    if (!empty($ssid5g) && strlen($ssid5g) < 3) {
        echo json_encode(['success' => false, 'message' => 'SSID 5G minimum 3 characters']);
        exit;
    }

    // Validate password
    if (!empty($password) && strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password minimum 8 characters']);
        exit;
    }
    if (!empty($password5g) && strlen($password5g) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password 5G minimum 8 characters']);
        exit;
    }

    $targetDevice = genieacsGetDevice($serial);
    if (!$targetDevice) {
        echo json_encode(['success' => false, 'message' => 'Device not found or offline']);
        exit;
    }

    $wifi5gSsidPath = null;
    $wifi5gPassPath = null;
    foreach ([5, 6, 7, 8, 9] as $idx) {
        $v = genieacsGetValue($targetDevice, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.' . $idx . '.SSID');
        if ($v !== null) {
            $wifi5gSsidPath = 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.' . $idx . '.SSID';
            $wifi5gPassPath = 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.' . $idx . '.PreSharedKey.1.KeyPassphrase';
            break;
        }
    }
    if ($wifi5gSsidPath === null) {
        $v = genieacsGetValue($targetDevice, 'Device.WiFi.SSID.2.SSID');
        if ($v !== null) {
            $wifi5gSsidPath = 'Device.WiFi.SSID.2.SSID';
            $wifi5gPassPath = 'Device.WiFi.AccessPoint.2.Security.KeyPassphrase';
        }
    }

    $updatedFields = [];

    if (!empty($ssid)) {
        $result = genieacsSetParameter($serial, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID', $ssid);
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => 'Failed to update SSID: ' . ($result['message'] ?? 'Unknown error')]);
            exit;
        }
        $updatedFields[] = 'ssid';
    }

    if (!empty($password)) {
        $result = genieacsSetParameter($serial, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase', $password);
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => 'Failed to update password: ' . ($result['message'] ?? 'Unknown error')]);
            exit;
        }
        $updatedFields[] = 'password';
    }

    if (!empty($ssid5g)) {
        $ssid5g = trim((string) $ssid5g);
        if (!preg_match('/-5g$/i', $ssid5g)) {
            $ssid5g = rtrim($ssid5g);
            $ssid5g = preg_replace('/\\s*5g$/i', '', $ssid5g);
            $ssid5g = $ssid5g . '-5G';
        }
        if ($wifi5gSsidPath === null) {
            echo json_encode(['success' => false, 'message' => 'ONU does not support WiFi 5G']);
            exit;
        }
        $result = genieacsSetParameter($serial, $wifi5gSsidPath, $ssid5g);
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => 'Failed to update SSID 5G: ' . ($result['message'] ?? 'Unknown error')]);
            exit;
        }
        $updatedFields[] = 'ssid_5g';
    }

    if (!empty($password5g)) {
        if ($wifi5gPassPath === null) {
            echo json_encode(['success' => false, 'message' => 'ONU does not support WiFi 5G']);
            exit;
        }
        $result = genieacsSetParameter($serial, $wifi5gPassPath, $password5g);
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => 'Failed to update password 5G: ' . ($result['message'] ?? 'Unknown error')]);
            exit;
        }
        $updatedFields[] = 'password_5g';
    }

    if (!isCustomerLoggedIn() && (isAdminLoggedIn() || isTechnicianLoggedIn()) && (!empty($updatedFields))) {
        $customer = null;
        if (!empty($pppoeUsername)) {
            $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ? LIMIT 1", [$pppoeUsername]);
        }
        if (!$customer) {
            $customer = fetchOne("SELECT * FROM customers WHERE serial_number = ? LIMIT 1", [$serial]);
        }

        if ($customer && !empty($customer['phone'])) {
            $actor = isAdminLoggedIn() ? 'Admin' : 'Technician';
            $lines = [];
            $lines[] = "Hello {$customer['name']},";
            $lines[] = "";
            $lines[] = "{$actor} has just updated your WiFi settings:";
            if (!empty($ssid)) {
                $lines[] = "SSID: {$ssid}";
            }
            if (!empty($password)) {
                $lines[] = "Password: {$password}";
            }
            if (!empty($ssid5g)) {
                $lines[] = "SSID 5G: {$ssid5g}";
            }
            if (!empty($password5g)) {
                $lines[] = "Password 5G: {$password5g}";
            }
            $lines[] = "";
            $lines[] = "If you experience connection issues after this change, please restart your modem/ONT.";

            sendWhatsApp($customer['phone'], implode("\n", $lines));
        }

        $logFields = implode(',', $updatedFields);
        logActivity('WIFI_UPDATE', "Serial: {$serial}, PPPoE: {$pppoeUsername}, Fields: {$logFields}, Actor: " . (isAdminLoggedIn() ? 'admin' : 'technician'));
    }

    echo json_encode(['success' => true, 'message' => 'WiFi settings updated successfully']);

} catch (Exception $e) {
    logError("API Error (onu_wifi.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
