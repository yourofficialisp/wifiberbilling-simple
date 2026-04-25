<?php
require_once '../../includes/auth.php';
requireTechnicianLogin();

$pageTitle = 'Device Management';
$tech = $_SESSION['technician'];
$username = $_GET['username'] ?? '';
$serial = $_GET['serial'] ?? '';

if (empty($username) && empty($serial)) {
    redirect('search.php');
}

// 1. Try to find customer in DB
$customer = null;
if (!empty($username)) {
    $customer = fetchOne("SELECT * FROM customers WHERE pppoe_username = ?", [$username]);
} 
if (!$customer && !empty($serial)) {
    // Try both serial number and pppoe_username (because map might pass pppoe_username as serial)
    $customer = fetchOne("SELECT * FROM customers WHERE serial_number = ? OR pppoe_username = ?", [$serial, $serial]);
}

// If customer not found, create a dummy customer object for display if we have device
if (!$customer) {
    // Check if we can proceed with just device lookup
    if (empty($username) && empty($serial)) {
        setFlash('error', 'Customer data not found.');
        redirect('search.php');
    }
    
    // Create placeholder customer
    $customer = [
        'name' => 'Unregistered Device',
        'pppoe_username' => $username ?: $serial,
        'serial_number' => $serial ?: '',
        'address' => 'Address tidak diketahui',
        'status' => 'unknown'
    ];
}

// Fetch Device from GenieACS
$device = null;
$error = null;

// A. Try by Serial Number if available
if (!empty($customer['serial_number'])) {
    $device = genieacsGetDevice($customer['serial_number']);
} else if (!empty($serial)) {
    $device = genieacsGetDevice($serial);
}

// B. If not found, try by PPPoE Username
if (!$device && !empty($customer['pppoe_username'])) {
    // Try finding by VirtualParameters.pppoeUsername
    $device = genieacsFindDeviceByPppoe($customer['pppoe_username']);
}

// C. If still not found and we have a username that might be a serial
if (!$device && !empty($username)) {
    $device = genieacsGetDevice($username);
}

// Helper to extract value safely
function getDeviceVal($data, $path) {
    // Use the robust genieacsGetValue from functions.php
    return genieacsGetValue($data, $path);
}

// Parse Data if device found
if ($device) {
    $lastInform = $device['_lastInform'] ?? null;
    $isOnline = $lastInform && (time() - strtotime($lastInform)) < 300;
    
    // Extract Parameters
    // Note: We use the paths that are common in GenieACS for standard ONUs
    // VirtualParameters are often created by presets to normalize data
    
    $rxPower = getDeviceVal($device, 'VirtualParameters.RXPower');
    if ($rxPower === null) {
        // Fallback to standard paths if VirtualParameter is missing
        $rxPower = getDeviceVal($device, 'InternetGatewayDevice.WANDevice.1.X_GponInterafceConfig.RxPower') ?? 
                   getDeviceVal($device, 'Device.Optical.Interface.1.RXPower');
    }

    $temp = getDeviceVal($device, 'VirtualParameters.gettemp') ?? 
            getDeviceVal($device, 'InternetGatewayDevice.DeviceInfo.TemperatureStatus.Temperature') ?? '-';
            
    $uptime = getDeviceVal($device, 'VirtualParameters.getdeviceuptime') ?? 
              getDeviceVal($device, 'InternetGatewayDevice.DeviceInfo.UpTime');
              
    if (is_numeric($uptime)) {
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        $uptimeStr = "{$days}h {$hours}j {$minutes}m";
    } else {
        $uptimeStr = $uptime ?? '-';
    }

    $ponMode = getDeviceVal($device, 'VirtualParameters.getponmode') ?? '-';
    $sn = $device['_deviceId']['_SerialNumber'] ?? '-';
    $model = getDeviceVal($device, 'InternetGatewayDevice.DeviceInfo.ModelName') ?? '-';
    
    // WiFi
    $ssid = getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID') ?? '-';
    $wifiPass = getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase') ?? '***';
    $assocDevices = getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations') ?? '0';
    $ssid5g = getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.5.SSID') ??
              getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.6.SSID') ??
              getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.7.SSID') ??
              getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.8.SSID') ??
              getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.9.SSID');
    $wifiPass5g = getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.5.PreSharedKey.1.KeyPassphrase') ??
                  getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.6.PreSharedKey.1.KeyPassphrase') ??
                  getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.7.PreSharedKey.1.KeyPassphrase') ??
                  getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.8.PreSharedKey.1.KeyPassphrase') ??
                  getDeviceVal($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.9.PreSharedKey.1.KeyPassphrase');
    $has5g = $ssid5g !== null || $wifiPass5g !== null;

    // IP
    $wanIp = getDeviceVal($device, 'VirtualParameters.IPTR069') ?? 
             getDeviceVal($device, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress') ?? '-';

} else {
    $error = "Device not found in GenieACS. Make sure the Serial Number or PPPoE Username is correct.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage ONT - <?php echo htmlspecialchars($customer['name']); ?></title>
    <meta name="theme-color" content="#0a0a12">
    <link rel="manifest" href="../../manifest.json">
    <link rel="apple-touch-icon" href="../../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../../assets/icons/icon-192x192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00f5ff;
            --bg-dark: #0a0a12;
            --bg-card: #161628;
            --text-primary: #ffffff;
            --text-secondary: #b0b0c0;
            --success: #00ff88;
            --danger: #ff4757;
            --warning: #ffcc00;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            padding-bottom: 80px;
        }
        
        .header {
            background: var(--bg-card);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .back-btn {
            color: var(--text-primary);
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .container { padding: 20px; }
        
        .card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .customer-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .customer-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .badge-success { background: rgba(0, 255, 136, 0.2); color: var(--success); border: 1px solid rgba(0, 255, 136, 0.3); }
        .badge-danger { background: rgba(255, 71, 87, 0.2); color: var(--danger); border: 1px solid rgba(255, 71, 87, 0.3); }
        .badge-warning { background: rgba(255, 204, 0, 0.2); color: var(--warning); border: 1px solid rgba(255, 204, 0, 0.3); }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .info-label { color: var(--text-secondary); font-size: 0.9rem; }
        .info-value { font-weight: 600; }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
            transition: 0.2s;
            text-decoration: none;
            margin-bottom: 10px;
        }
        
        .btn-primary { background: var(--primary); color: #000; }
        .btn-danger { background: rgba(255, 71, 87, 0.1); color: var(--danger); border: 1px solid rgba(255, 71, 87, 0.3); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: var(--text-primary); }
        
        .rx-power { font-weight: bold; }
        .rx-good { color: var(--success); }
        .rx-warning { color: var(--warning); }
        .rx-bad { color: var(--danger); }

        .error-msg {
            background: rgba(255, 71, 87, 0.1);
            color: var(--danger);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="search.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h2>Device Details</h2>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
            
            <div class="card">
                <div class="customer-header">
                    <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                    <div style="color: var(--text-secondary); margin-top: 5px;"><?php echo htmlspecialchars($customer['pppoe_username']); ?></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Serial Number (DB)</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer['serial_number'] ?? '-'); ?></span>
                </div>
            </div>

            <a href="search.php" class="btn btn-secondary">Back to Search</a>
        <?php else: ?>
            <!-- Status Card -->
            <div class="card">
                <div class="customer-header">
                    <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                    
                    <?php if ($isOnline): ?>
                        <span class="status-badge badge-success"><i class="fas fa-wifi"></i> Online</span>
                    <?php else: ?>
                        <span class="status-badge badge-danger"><i class="fas fa-wifi-slash"></i> Offline</span>
                    <?php endif; ?>
                    
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 8px;">
                        Last Inform: <?php echo $lastInform ? date('d M Y H:i', strtotime($lastInform)) : '-'; ?>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">RX Power</span>
                        <?php
                            $rxClass = '';
                            $rxVal = floatval($rxPower);
                            if ($rxPower === null || $rxPower === '-' || $rxPower == 0) {
                                $rxClass = '';
                            } elseif ($rxVal > -25) {
                                $rxClass = 'rx-good';
                            } elseif ($rxVal > -28) {
                                $rxClass = 'rx-warning';
                            } else {
                                $rxClass = 'rx-bad';
                            }
                        ?>
                        <span class="info-value rx-power <?php echo $rxClass; ?>">
                            <?php echo $rxPower ? $rxPower . ' dBm' : '-'; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Uptime</span>
                        <span class="info-value"><?php echo $uptimeStr; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Temperature</span>
                        <span class="info-value"><?php echo $temp; ?> °C</span>
                    </div>
                </div>
            </div>

            <!-- Device Info -->
            <div class="card">
                <h3 style="font-size: 1rem; margin-bottom: 15px; color: var(--primary);">Info Device</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Model</span>
                        <span class="info-value"><?php echo htmlspecialchars($model); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Serial Number</span>
                        <span class="info-value" style="font-family: monospace;"><?php echo htmlspecialchars($sn); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">PON Mode</span>
                        <span class="info-value"><?php echo htmlspecialchars($ponMode); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">IP Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($wanIp); ?></span>
                    </div>
                </div>
            </div>

            <!-- WiFi Info -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="font-size: 1rem; color: var(--primary); margin: 0;">WiFi & Klien</h3>
                    <button class="btn btn-secondary" style="width: auto; padding: 5px 15px; margin: 0; font-size: 0.8rem;" onclick="openWifiModal()">
                        <i class="fas fa-edit"></i> Edit WiFi
                    </button>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">SSID</span>
                        <span class="info-value"><?php echo htmlspecialchars($ssid); ?></span>
                    </div>
                    <?php if ($has5g): ?>
                    <div class="info-item">
                        <span class="info-label">SSID 5G</span>
                        <span class="info-value"><?php echo htmlspecialchars($ssid5g ?: '-'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Password</span>
                        <span class="info-value" style="font-family: monospace;"><?php echo htmlspecialchars($wifiPass); ?></span>
                    </div>
                    <?php if ($has5g): ?>
                    <div class="info-item">
                        <span class="info-label">Password 5G</span>
                        <span class="info-value" style="font-family: monospace;"><?php echo htmlspecialchars($wifiPass5g ?: '***'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Connected Devices</span>
                        <span class="info-value"><?php echo htmlspecialchars($assocDevices); ?> User</span>
                    </div>
                </div>
            </div>

            <!-- WiFi Edit Modal -->
            <div id="wifiModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
                <div class="card" style="width: 400px; max-width: 90%;">
                    <div class="customer-header" style="margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="customer-name" style="font-size: 1.1rem; margin: 0;">Edit WiFi</h3>
                        <button onclick="closeWifiModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">&times;</button>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">SSID (Name WiFi)</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="editSsid" class="form-control" value="<?php echo htmlspecialchars($ssid); ?>">
                            <button class="btn btn-primary" style="width: auto; padding: 10px 15px; margin: 0;" onclick="saveSsid()" title="Save SSID">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                    <?php if ($has5g): ?>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">SSID 5G</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="editSsid5g" class="form-control" value="<?php echo htmlspecialchars($ssid5g ?: ''); ?>" placeholder="Auto: SSID + 5G">
                            <button class="btn btn-primary" style="width: auto; padding: 10px 15px; margin: 0;" onclick="saveSsid5g()" title="Save SSID 5G">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Password</label>
                        <div style="display: flex; gap: 10px;">
                            <div style="position: relative; flex: 1;">
                                <input type="text" id="editPassword" class="form-control" value="<?php echo htmlspecialchars($wifiPass); ?>" style="padding-right: 40px;">
                                <i class="fas fa-eye" id="togglePass" onclick="togglePasswordVisibility()" 
                                   style="position: absolute; right: 10px; top: 12px; cursor: pointer; color: var(--text-secondary);"></i>
                            </div>
                            <button class="btn btn-primary" style="width: auto; padding: 10px 15px; margin: 0;" onclick="savePassword()" title="Save Password">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 0.8rem;">Minimum 8 characters</small>
                    </div>
                    <?php if ($has5g): ?>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Password 5G</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="editPassword5g" class="form-control" value="<?php echo htmlspecialchars($wifiPass5g ?: ''); ?>" style="flex: 1;">
                            <button class="btn btn-primary" style="width: auto; padding: 10px 15px; margin: 0;" onclick="savePassword5g()" title="Save Password 5G">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 0.8rem;">Minimum 8 characters</small>
                    </div>
                    <?php endif; ?>

                    <div style="text-align: right; margin-top: 15px;">
                        <button class="btn btn-secondary" style="width: auto; display: inline-block;" onclick="closeWifiModal()">Close</button>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="margin-bottom: 20px;">
                <button class="btn btn-danger" onclick="rebootDevice()">
                    <i class="fas fa-power-off"></i> Reboot ONT
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function rebootDevice() {
            if(confirm('Are you sure you want to restart this device? The customer connection will briefly drop.')) {
                // Call API to reboot
                const formData = new FormData();
                formData.append('action', 'reboot');
                // We use PPPoE username as ID, API will handle lookup
                formData.append('device_id', '<?php echo $customer['pppoe_username']; ?>');

                fetch('../../api/genieacs.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Perintah reboot sent successfully!');
                    } else {
                        alert('Failed: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('A connection error occurred');
                    console.error(error);
                });
            }
        }

        // WiFi Modal Functions
        function openWifiModal() {
            const modal = document.getElementById('wifiModal');
            modal.style.display = 'flex';
        }

        function closeWifiModal() {
            const modal = document.getElementById('wifiModal');
            modal.style.display = 'none';
        }

        function togglePasswordVisibility() {
            const input = document.getElementById('editPassword');
            const icon = document.getElementById('togglePass');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function saveSsid() {
            const ssid = document.getElementById('editSsid').value;

            if (ssid.length < 3) {
                alert('SSID must be at least 3 characters');
                return;
            }

            if(!confirm('Save SSID changes? Device may reconnect.')) return;

            fetch('../../api/onu_wifi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pppoe_username: '<?php echo $customer['pppoe_username']; ?>',
                    ssid: ssid
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('SSID updated successfully!');
                    location.reload();
                } else {
                    alert('Failed: ' + data.message);
                }
            })
            .catch(error => {
                alert('A connection error occurred');
                console.error(error);
            });
        }

        function saveSsid5g() {
            const input = document.getElementById('editSsid5g');
            const base = input ? input.value : '';
            const fallback = document.getElementById('editSsid').value || '';
            const ssid = (base && base.length >= 3) ? base : fallback;
            if (ssid.length < 3) {
                alert('SSID 5G must be at least 3 characters');
                return;
            }
            if(!confirm('Save SSID 5G changes? Device may reconnect.')) return;
            fetch('../../api/onu_wifi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pppoe_username: '<?php echo $customer['pppoe_username']; ?>',
                    ssid_5g: ssid
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('SSID 5G updated successfully!');
                    location.reload();
                } else {
                    alert('Failed: ' + data.message);
                }
            })
            .catch(error => {
                alert('A connection error occurred');
                console.error(error);
            });
        }

        function savePassword() {
            const password = document.getElementById('editPassword').value;

            if (password.length < 8) {
                alert('Password must be at least 8 characters');
                return;
            }

            if(!confirm('Save Password changes? Device may reconnect.')) return;

            fetch('../../api/onu_wifi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pppoe_username: '<?php echo $customer['pppoe_username']; ?>',
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Password updated successfully!');
                    location.reload();
                } else {
                    alert('Failed: ' + data.message);
                }
            })
            .catch(error => {
                alert('A connection error occurred');
                console.error(error);
            });
        }

        function savePassword5g() {
            const input = document.getElementById('editPassword5g');
            const password = input ? input.value : '';
            if (password.length < 8) {
                alert('Password 5G must be at least 8 characters');
                return;
            }
            if(!confirm('Save Password 5G changes? Device may reconnect.')) return;
            fetch('../../api/onu_wifi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pppoe_username: '<?php echo $customer['pppoe_username']; ?>',
                    password_5g: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Password 5G updated successfully!');
                    location.reload();
                } else {
                    alert('Failed: ' + data.message);
                }
            })
            .catch(error => {
                alert('A connection error occurred');
                console.error(error);
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('wifiModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
    
    <?php require_once '../includes/bottom_nav.php'; ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../../sw.js');
            });
        }
    </script>
</body>
</html>
