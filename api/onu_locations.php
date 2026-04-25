<?php
/**
 * API: ONU Locations with GenieACS Integration
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Allow Admin or Technician
if (!isAdminLoggedIn() && !isTechnicianLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function ensureOdpSchema()
{
    if (!tableExists('odps')) {
        $created = query("CREATE TABLE IF NOT EXISTS odps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(50) UNIQUE,
            lat DECIMAL(11,8),
            lng DECIMAL(11,8),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (!$created) {
            return ['success' => false, 'message' => 'Failed to create ODP table'];
        }
    }

    if (!tableExists('odp_links')) {
        $created = query("CREATE TABLE IF NOT EXISTS odp_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_odp_id INT NOT NULL,
            to_odp_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (from_odp_id) REFERENCES odps(id) ON DELETE CASCADE,
            FOREIGN KEY (to_odp_id) REFERENCES odps(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (!$created) {
            return ['success' => false, 'message' => 'Failed to create ODP path table'];
        }
    }

    $column = fetchOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'onu_locations' AND COLUMN_NAME = 'odp_id'", [DB_NAME]);
    if (!$column) {
        $altered = query("ALTER TABLE onu_locations ADD COLUMN odp_id INT NULL, ADD CONSTRAINT fk_onu_odp FOREIGN KEY (odp_id) REFERENCES odps(id) ON DELETE SET NULL");
        if (!$altered) {
            return ['success' => false, 'message' => 'Failed to add ODP column to ONU'];
        }
    }

    return ['success' => true];
}

$method = $_SERVER['REQUEST_METHOD'];

try {

if ($method === 'GET') {
    $schema = ensureOdpSchema();
    if (!$schema['success']) {
        echo json_encode(['success' => false, 'message' => $schema['message']]);
        exit;
    }
    $onuLocations = fetchAll("SELECT * FROM onu_locations ORDER BY name");

    foreach ($onuLocations as &$onu) {
        try {
            $deviceInfo = genieacsGetDeviceInfo($onu['serial_number']);

            if ($deviceInfo) {
                $onu['status'] = $deviceInfo['status'];
                $onu['device_info'] = $deviceInfo;
                $onu['ssid'] = $deviceInfo['ssid'] ?? '';
                $onu['password'] = $deviceInfo['wifi_password'] ?? '';
            } else {
                $onu['status'] = 'unknown';
                $onu['device_info'] = null;
            }
        } catch (Exception $e) {
            $onu['status'] = 'unknown';
            $onu['device_info'] = null;
        }
    }

    $odps = fetchAll("SELECT * FROM odps ORDER BY name");
    $odpLinks = fetchAll("SELECT * FROM odp_links");

    echo json_encode([
        'success' => true,
        'data' => $onuLocations,
        'odps' => $odps,
        'odp_links' => $odpLinks
    ]);

} elseif ($method === 'POST') {
    $schema = ensureOdpSchema();
    if (!$schema['success']) {
        echo json_encode(['success' => false, 'message' => $schema['message']]);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? 'onu';

    if ($type === 'odp' || $type === 'odp_update') {
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $code = trim($input['code'] ?? '');
        $lat = ($input['lat'] === '' || $input['lat'] === null) ? null : $input['lat'];
        $lng = ($input['lng'] === '' || $input['lng'] === null) ? null : $input['lng'];

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'ODP name is required']);
            exit;
        }

        if ($code !== '') {
            $existingCode = fetchOne("SELECT id FROM odps WHERE code = ?", [$code]);
            if ($existingCode && (!$id || (int) $existingCode['id'] !== (int) $id)) {
                echo json_encode(['success' => false, 'message' => 'ODP code already used']);
                exit;
            }
        }

        if ($id && $type === 'odp_update') {
            $updated = update('odps', [
                'name' => $name,
                'code' => $code ?: null,
                'lat' => $lat,
                'lng' => $lng
            ], 'id = ?', [$id]);
            
            echo json_encode(['success' => true, 'message' => 'ODP successfully updated']);
        } else {
            $inserted = insert('odps', [
                'name' => $name,
                'code' => $code ?: null,
                'lat' => $lat,
                'lng' => $lng
            ]);
            
            if ($inserted) {
                echo json_encode(['success' => true, 'message' => 'ODP successfully added']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add ODP']);
            }
        }
        exit;
    }

    if ($type === 'odp_delete') {
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $deleted = delete('odps', 'id = ?', [$id]);
            if ($deleted !== false) {
                echo json_encode(['success' => true, 'message' => 'ODP deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete ODP']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ODP']);
        }
        exit;
    }

    if ($type === 'odp_link') {
        $fromId = (int) ($input['from_odp_id'] ?? 0);
        $toId = (int) ($input['to_odp_id'] ?? 0);
        if ($fromId <= 0 || $toId <= 0 || $fromId === $toId) {
            echo json_encode(['success' => false, 'message' => 'Invalid ODP path']);
            exit;
        }

        $exists = fetchOne("SELECT id FROM odp_links WHERE (from_odp_id = ? AND to_odp_id = ?) OR (from_odp_id = ? AND to_odp_id = ?)", [$fromId, $toId, $toId, $fromId]);
        if (!$exists) {
            $inserted = insert('odp_links', [
                'from_odp_id' => $fromId,
                'to_odp_id' => $toId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            if (!$inserted) {
                echo json_encode(['success' => false, 'message' => 'Failed to add ODP path']);
                exit;
            }
        }
        echo json_encode(['success' => true, 'message' => 'ODP path added']);
        exit;
    }

    if ($type === 'odp_link_delete') {
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $deleted = delete('odp_links', 'id = ?', [$id]);
            if ($deleted !== false) {
                echo json_encode(['success' => true, 'message' => 'ODP path deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete ODP path']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid path']);
        }
        exit;
    }

    if ($type === 'onu_odp') {
        $serial = $input['serial'] ?? '';
        $odpId = $input['odp_id'] ?? null;
        if ($serial === '') {
            echo json_encode(['success' => false, 'message' => 'Serial number is required']);
            exit;
        }
        $updated = update('onu_locations', [
            'odp_id' => $odpId,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'serial_number = ?', [$serial]);
        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'ODP ONU updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update ODP ONU']);
        }
        exit;
    }

    $serial = $input['serial'] ?? '';
    $name = $input['name'] ?? '';
    // Debug Log
    error_log("API onu_locations.php received: " . print_r($input, true));
    
    $lat = ($input['lat'] === '' || $input['lat'] === null) ? null : str_replace(',', '.', trim($input['lat']));
    $lng = ($input['lng'] === '' || $input['lng'] === null) ? null : str_replace(',', '.', trim($input['lng']));
    $odpId = $input['odp_id'] ?? null;

    if (empty($serial)) {
        echo json_encode(['success' => false, 'message' => 'Serial number is required']);
        exit;
    }

    $existing = fetchOne("SELECT id FROM onu_locations WHERE serial_number = ?", [$serial]);

    if ($existing) {
        $updated = update('onu_locations', [
            'name' => $name,
            'lat' => $lat,
            'lng' => $lng,
            'odp_id' => $odpId,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'serial_number = ?', [$serial]);
        
        // Also update customers table if serial matches (assuming we can link by pppoe/serial)
        // Or try to match by serial number if we store it
        // Since we don't strictly have serial in customers table yet (we use pppoe_username),
        // we might need to look it up.
        // But for now, let's assume onu_locations IS the source of truth for map.
        
        // However, the technician map uses `customers` table for coordinates.
        // We MUST update `customers` table too if we can link them.
        // Let's try to link via GenieACS pppoeUsername -> customers.pppoe_username
        
        $deviceInfo = genieacsGetDeviceInfo($serial);
        if ($deviceInfo && !empty($deviceInfo['pppoe_username'])) {
            update('customers', [
                'lat' => $lat,
                'lng' => $lng,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'pppoe_username = ?', [$deviceInfo['pppoe_username']]);
        }

        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'ONU location updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update ONU']);
        }
    } else {
        $inserted = insert('onu_locations', [
            'name' => $name,
            'serial_number' => $serial,
            'lat' => $lat,
            'lng' => $lng,
            'odp_id' => $odpId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Sync to customers table
        $deviceInfo = genieacsGetDeviceInfo($serial);
        if ($deviceInfo && !empty($deviceInfo['pppoe_username'])) {
            update('customers', [
                'lat' => $lat,
                'lng' => $lng,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'pppoe_username = ?', [$deviceInfo['pppoe_username']]);
        }
        
        if ($inserted) {
            echo json_encode(['success' => true, 'message' => 'ONU location added']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add ONU']);
        }
    }
}

} catch (Exception $e) {
    logError("API Error (onu_locations.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
