<?php
/**
 * Traffic Monitor API Endpoint
 * Returns real-time Tx/Rx bits per second for a given interface
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdminLogin();

try {
    $interface = $_GET['interface'] ?? 'ether1';

    // Sanitize interface name — only alphanumeric, dash, dot allowed
    if (!preg_match('/^[a-zA-Z0-9.\-]+$/', $interface)) {
        echo json_encode(['success' => false, 'message' => 'Invalid interface name']);
        exit;
    }

    $data = mikrotikMonitorTraffic($interface);

    if ($data === false || !is_array($data)) {
        echo json_encode([
            ['data' => 0],
            ['data' => 0],
        ]);
        exit;
    }

    echo json_encode([
        ['data' => $data['tx'] ?? 0],
        ['data' => $data['rx'] ?? 0],
    ]);
} catch (Exception $e) {
    logError("API Error (traffic.php): " . $e->getMessage());
    echo json_encode([
        ['data' => 0],
        ['data' => 0],
    ]);
}
