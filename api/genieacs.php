<?php
/**
 * API: GenieACS
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Allow admin and technician
if (!isAdminLoggedIn() && !isTechnicianLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'GET') {
        if ($action === 'devices') {
            // Get all devices
            $devices = genieacsGetDevices();

            echo json_encode([
                'success' => true,
                'data' => [
                    'devices' => $devices,
                    'total' => count($devices)
                ]
            ]);
        } elseif ($action === 'device') {
            $serial = $_GET['serial'] ?? '';

            if (empty($serial)) {
                echo json_encode(['success' => false, 'message' => 'Serial number required']);
                exit;
            }

            $deviceInfo = genieacsGetDeviceInfo($serial);

            if ($deviceInfo) {
                echo json_encode([
                    'success' => true,
                    'data' => $deviceInfo
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Device not found']);
            }
        }
    } elseif ($method === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'reboot') {
            $deviceId = $_POST['device_id'] ?? '';
            
            // If device_id is username, we need to find serial number first
            // But genieacsReboot usually takes device ID (serial or _id)
            // Let's try to map it if it's not a serial
            
            if (empty($deviceId)) {
                echo json_encode(['success' => false, 'message' => 'Device ID required']);
                exit;
            }
            
            // Check if it's a PPPoE username and get Serial
            $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$deviceId]);
            if ($customer) {
                // Find device by username in GenieACS
                $device = genieacsFindDeviceByPppoe($deviceId);
                if ($device) {
                    $deviceId = $device['_id'];
                }
            }

            if (genieacsReboot($deviceId)) {
                echo json_encode(['success' => true, 'message' => 'Reboot command successfully sent']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send reboot command']);
            }
        } elseif ($action === 'ping') {
            $deviceId = $_POST['device_id'] ?? '';
            $host = $_POST['host'] ?? '8.8.8.8';
            
            if (empty($deviceId)) {
                echo json_encode(['success' => false, 'message' => 'Device ID required']);
                exit;
            }
            
            // Resolve ID if needed (same logic as above)
            $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$deviceId]);
            if ($customer) {
                // Find device by username in GenieACS
                $device = genieacsFindDeviceByPppoe($deviceId);
                if ($device) {
                    $deviceId = $device['_id'];
                }
            }
            
            // Send Diagnostics Request
            // IPPingDiagnostics
            $params = [
                'InternetGatewayDevice.IPPingDiagnostics.Host' => $host,
                'InternetGatewayDevice.IPPingDiagnostics.NumberOfRepetitions' => 3,
                'InternetGatewayDevice.IPPingDiagnostics.Timeout' => 1000,
                'InternetGatewayDevice.IPPingDiagnostics.DiagnosticsState' => 'Requested'
            ];
            
            if (genieacsSetParameterValues($deviceId, $params)) {
                echo json_encode(['success' => true, 'message' => 'Ping request sent to ' . $host]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send ping request']);
            }
        } elseif ($action === 'get_ping_result') {
            $deviceId = $_POST['device_id'] ?? '';
            
            if (empty($deviceId)) {
                echo json_encode(['success' => false, 'message' => 'Device ID required']);
                exit;
            }
            
            // Resolve ID
            $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$deviceId]);
            if ($customer) {
                // Find device by username in GenieACS
                $device = genieacsFindDeviceByPppoe($deviceId);
                if ($device) {
                    $deviceId = $device['_id'];
                }
            }
            
            // Fetch device data
            $device = genieacsGetDevice($deviceId);
            if ($device) {
                $diagState = genieacsGetValue($device, 'InternetGatewayDevice.IPPingDiagnostics.DiagnosticsState') ?? 'None';
                $success = genieacsGetValue($device, 'InternetGatewayDevice.IPPingDiagnostics.SuccessCount') ?? 0;
                $failure = genieacsGetValue($device, 'InternetGatewayDevice.IPPingDiagnostics.FailureCount') ?? 0;
                $avgTime = genieacsGetValue($device, 'InternetGatewayDevice.IPPingDiagnostics.AverageResponseTime') ?? 0;
                
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'state' => $diagState,
                        'success_count' => $success,
                        'failure_count' => $failure,
                        'avg_time' => $avgTime
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Device not found']);
            }
        }
    }

} catch (Exception $e) {
    logError("API Error (genieacs.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
