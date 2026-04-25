<?php
/**
 * Customers Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Customers';
$hasAutoIsolate = ensureCustomersAutoIsolateColumn();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('customers.php');
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $pppoePassword = isset($_POST['pppoe_password']) ? trim((string) $_POST['pppoe_password']) : '';
                $data = [
                    'name' => sanitize($_POST['name']),
                    'phone' => sanitize($_POST['phone']),
                    'pppoe_username' => sanitize($_POST['pppoe_username']),
                    'package_id' => (int)$_POST['package_id'],
                    'router_id' => (int)($_POST['router_id'] ?? 0),
                    'isolation_date' => (int)$_POST['isolation_date'],
                    'address' => sanitize($_POST['address']),
                    'lat' => (!isset($_POST['lat']) || trim($_POST['lat']) === '') ? null : (string) str_replace(',', '.', trim($_POST['lat'])),
                    'lng' => (!isset($_POST['lng']) || trim($_POST['lng']) === '') ? null : (string) str_replace(',', '.', trim($_POST['lng'])),
                    'installed_by' => !empty($_POST['installed_by']) ? (int)$_POST['installed_by'] : null,
                    'portal_password' => password_hash('1234', PASSWORD_DEFAULT),
                    'created_at' => date('Y-m-d H:i:s')
                ];
                if ($hasAutoIsolate) {
                    $data['auto_isolate'] = isset($_POST['auto_isolate']) ? 1 : 0;
                }
                
                if (insert('customers', $data)) {
                    // Sync to onu_locations if requested
                    $saveOnu = isset($_POST['save_onu']) && $_POST['save_onu'] == '1';
                    $odpId = isset($_POST['odp_id']) && $_POST['odp_id'] !== '' ? (int) $_POST['odp_id'] : null;
                    if ($saveOnu) {
                        try {
                            $serial = $data['pppoe_username']; // Use PPPoE username as identifier if serial not known yet
                            $exists = fetchOne("SELECT id FROM onu_locations WHERE serial_number = ?", [$serial]);
                            $payload = [
                                'name' => $data['name'],
                                'lat' => $data['lat'],
                                'lng' => $data['lng'],
                                'odp_id' => $odpId,
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            if ($exists) {
                                update('onu_locations', $payload, 'serial_number = ?', [$serial]);
                            } else {
                                $payload['serial_number'] = $serial;
                                $payload['created_at'] = date('Y-m-d H:i:s');
                                insert('onu_locations', $payload);
                            }
                            
                            // Synchronize PPPoE Username to GenieACS if applicable
                            if (!empty($serial)) {
                                genieacsSetParameter($serial, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username', $serial);
                                if ($pppoePassword !== '') {
                                    genieacsSetParameter($serial, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Password', $pppoePassword);
                                }
                            }
                        } catch (Exception $e) {
                            // Do not block customer creation if ONU sync fails
                            logError('ONU sync (add customer) failed: ' . $e->getMessage());
                        }
                    }
                    setFlash('success', 'Customer successfully added');
                    logActivity('ADD_CUSTOMER', "Name: {$data['name']}");
                    
                    // Notify Technician if assigned
                    if (!empty($data['installed_by'])) {
                        $tech = fetchOne("SELECT phone, name FROM technician_users WHERE id = ?", [$data['installed_by']]);
                        if ($tech && !empty($tech['phone'])) {
                            require_once '../includes/whatsapp.php';
                            $msg = "🔔 *NEW INSTALLATION TASK*\n\n";
                            $msg .= "Customer: {$data['name']}\n";
                            $msg .= "Address: " . ($data['address'] ?: '-') . "\n";
                            $msg .= "Package: " . fetchOne("SELECT name FROM packages WHERE id = ?", [$data['package_id']])['name'] . "\n";
                            $msg .= "Maps: https://www.google.com/maps?q={$data['lat']},{$data['lng']}\n\n";
                            $msg .= "Please process immediately. Thank you.";
                            
                            sendWhatsAppMessage($tech['phone'], $msg);
                        }
                    }
                } else {
                    setFlash('error', 'Failed to add customer');
                }
                redirect('customers.php');
                break;
                
            case 'edit':
                $customerId = (int)$_POST['customer_id'];
                $existing = fetchOne("SELECT pppoe_username FROM customers WHERE id = ?", [$customerId]);
                $oldPppoe = $existing['pppoe_username'] ?? '';
                $data = [
                    'name' => sanitize($_POST['name']),
                    'phone' => sanitize($_POST['phone']),
                    'pppoe_username' => sanitize($_POST['pppoe_username']),
                    'package_id' => (int)$_POST['package_id'],
                    'router_id' => (int)($_POST['router_id'] ?? 0),
                    'isolation_date' => (int)$_POST['isolation_date'],
                    'address' => sanitize($_POST['address']),
                    'lat' => (!isset($_POST['lat']) || trim($_POST['lat']) === '') ? null : (string) str_replace(',', '.', trim($_POST['lat'])),
                    'lng' => (!isset($_POST['lng']) || trim($_POST['lng']) === '') ? null : (string) str_replace(',', '.', trim($_POST['lng'])),
                    'installed_by' => !empty($_POST['installed_by']) ? (int)$_POST['installed_by'] : null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                if ($hasAutoIsolate) {
                    $data['auto_isolate'] = isset($_POST['auto_isolate']) ? 1 : 0;
                }
                
                if (update('customers', $data, 'id = ?', [$customerId])) {
                    try {
                        $newPppoe = $data['pppoe_username'];
                        if (!empty($oldPppoe) && !empty($newPppoe) && $oldPppoe !== $newPppoe) {
                            $device = genieacsFindDeviceByPppoe($oldPppoe);
                            if (!$device) {
                                $device = genieacsGetDevice($oldPppoe);
                            }
                            if ($device) {
                                genieacsSetParameterValues($device['_id'] ?? $oldPppoe, [
                                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username' => $newPppoe
                                ]);
                            }
                            update('onu_locations', ['serial_number' => $newPppoe, 'updated_at' => date('Y-m-d H:i:s')], 'serial_number = ?', [$oldPppoe]);
                        }
                    } catch (Exception $e) {
                        logError('GenieACS sync (edit customer) failed: ' . $e->getMessage());
                    }
                    // Sync to onu_locations if requested
                    $saveOnu = isset($_POST['save_onu']) && $_POST['save_onu'] == '1';
                    $odpId = isset($_POST['odp_id']) && $_POST['odp_id'] !== '' ? (int) $_POST['odp_id'] : null;
                    if ($saveOnu) {
                        try {
                            // Get PPPoE username for this customer
                            $customer = fetchOne("SELECT pppoe_username FROM customers WHERE id = ?", [$customerId]);
                            if ($customer && !empty($customer['pppoe_username'])) {
                                $serial = $customer['pppoe_username'];
                                $exists = fetchOne("SELECT id FROM onu_locations WHERE serial_number = ?", [$serial]);
                                $payload = [
                                    'name' => $data['name'],
                                    'lat' => $data['lat'],
                                    'lng' => $data['lng'],
                                    'odp_id' => $odpId,
                                    'updated_at' => date('Y-m-d H:i:s')
                                ];
                                if ($exists) {
                                    update('onu_locations', $payload, 'serial_number = ?', [$serial]);
                                } else {
                                    $payload['serial_number'] = $serial;
                                    $payload['created_at'] = date('Y-m-d H:i:s');
                                    insert('onu_locations', $payload);
                                }

                                // Synchronize PPPoE Username to GenieACS if applicable
                                genieacsSetParameter($serial, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username', $serial);
                            }
                        } catch (Exception $e) {
                            logError('ONU sync (edit customer) failed: ' . $e->getMessage());
                        }
                    }
                    setFlash('success', 'Customer updated successfully');
                    logActivity('UPDATE_CUSTOMER', "ID: {$customerId}");
                } else {
                    setFlash('error', 'Failed to update customer');
                }
                redirect('customers.php');
                break;
                
            case 'delete':
                $customerId = (int)$_POST['customer_id'];
                if (delete('customers', 'id = ?', [$customerId])) {
                    setFlash('success', 'Customer deleted successfully');
                    logActivity('DELETE_CUSTOMER', "ID: {$customerId}");
                } else {
                    setFlash('error', 'Failed to delete customer');
                }
                redirect('customers.php');
                break;
                
            case 'unisolate':
                $customerId = (int)$_POST['customer_id'];
                if (unisolateCustomer($customerId)) {
                    setFlash('success', 'Customer un-isolated successfully');
                } else {
                    setFlash('error', 'Failed to un-isolate customer');
                }
                redirect('customers.php');
                break;

            case 'reset_portal_password':
                $customerId = (int)($_POST['customer_id'] ?? 0);
                $customer = fetchOne("SELECT id, name, phone FROM customers WHERE id = ?", [$customerId]);
                if (!$customer) {
                    setFlash('error', 'Customer not found');
                    redirect('customers.php');
                }

                $tempPassword = '1234';
                if (setCustomerPortalPassword($customerId, $tempPassword)) {
                    $waStatus = '';
                    $phone = (string) ($customer['phone'] ?? '');
                    if (trim($phone) !== '') {
                        $msg = "Hello {$customer['name']},\n\n";
                        $msg .= "Your Customer Portal login password has been reset.\n";
                        $msg .= "Temporary password: {$tempPassword}\n\n";
                        $msg .= "Please log in and change your password immediately.";
                        if (function_exists('getWhatsAppFooter')) {
                            $msg .= getWhatsAppFooter();
                        }
                        $waSent = sendWhatsApp($phone, $msg);
                        $waStatus = $waSent ? ' WhatsApp notification sent.' : ' WhatsApp notification failed (check gateway settings).';
                    }

                    setFlash('success', 'Customer portal password reset successfully.' . $waStatus);
                    logActivity('RESET_CUSTOMER_PORTAL_PASSWORD', "ID: {$customerId}");
                } else {
                    setFlash('error', 'Failed to reset customer portal password');
                }
                redirect('customers.php');
                break;

            case 'spread_isolation_dates':
                $groupSize = (int) ($_POST['group_size'] ?? 10);
                if ($groupSize < 1) {
                    $groupSize = 1;
                }
                if ($groupSize > 200) {
                    $groupSize = 200;
                }
                $startDay = (int) ($_POST['start_day'] ?? 1);
                if ($startDay < 1) {
                    $startDay = 1;
                }
                if ($startDay > 28) {
                    $startDay = 28;
                }
                $daysCsv = trim((string) ($_POST['days_csv'] ?? ''));
                $days = [];
                if ($daysCsv !== '') {
                    foreach (preg_split('/[,\s]+/', $daysCsv) as $part) {
                        $part = trim((string) $part);
                        if ($part === '') {
                            continue;
                        }
                        $val = (int) $part;
                        if ($val < 1 || $val > 28) {
                            continue;
                        }
                        $days[$val] = $val;
                    }
                    $days = array_values($days);
                    sort($days);
                }

                $where = "status = 'active'";
                if ($hasAutoIsolate && isset($_POST['only_auto_isolate'])) {
                    $where .= " AND auto_isolate = 1";
                }
                $targets = fetchAll("SELECT id FROM customers WHERE {$where} ORDER BY id ASC");
                if (empty($targets)) {
                    setFlash('warning', 'No active customers to update');
                    redirect('customers.php');
                }

                $updated = 0;
                foreach ($targets as $i => $row) {
                    if (!empty($days)) {
                        $dayIndex = intdiv((int) $i, $groupSize) % count($days);
                        $day = (int) $days[$dayIndex];
                    } else {
                        $day = $startDay + intdiv((int) $i, $groupSize);
                        $day = (($day - 1) % 28) + 1;
                    }
                    if (update('customers', ['isolation_date' => $day], 'id = ?', [(int) $row['id']])) {
                        $updated++;
                    }
                }

                setFlash('success', "Isolation dates spread successfully for {$updated} customer(s).");
                $mode = empty($days) ? "range start_day={$startDay}" : ('days=' . implode('-', $days));
                logActivity('SPREAD_ISOLATION_DATES', "Updated {$updated} customers, group_size={$groupSize}, {$mode}");
                redirect('customers.php');
                break;
        }
    }
}

// Get data with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 20;
$offset = ($page - 1) * $perPage;

$customersTableExists = tableExists('customers');
$packagesTableExists = tableExists('packages');
$routersTableExists = tableExists('routers');

// Get technicians
$technicians = fetchAll("SELECT * FROM technician_users WHERE status = 'active' ORDER BY name ASC");


if ($customersTableExists) {
    $totalCustomers = fetchOne("SELECT COUNT(*) as total FROM customers")['total'] ?? 0;
    $totalPages = ceil($totalCustomers / $perPage);

    $selectParts = [
        'c.*',
        $packagesTableExists ? 'p.name as package_name' : "'No Package' as package_name",
        $packagesTableExists ? 'p.price as package_price' : '0 as package_price',
        $routersTableExists ? 'r.name as router_name' : "'' as router_name",
        "(SELECT odp_id FROM onu_locations WHERE serial_number = c.pppoe_username LIMIT 1) as onu_odp_id"
    ];

    $joinParts = [];
    if ($packagesTableExists) {
        $joinParts[] = 'LEFT JOIN packages p ON c.package_id = p.id';
    }
    if ($routersTableExists) {
        $joinParts[] = 'LEFT JOIN routers r ON c.router_id = r.id';
    }

    $customers = fetchAll("
        SELECT " . implode(', ', $selectParts) . "
        FROM customers c 
        " . implode("\n        ", $joinParts) . "
        ORDER BY c.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
} else {
    $totalCustomers = 0;
    $totalPages = 0;
    $customers = [];
}

$packages = $packagesTableExists ? fetchAll("SELECT * FROM packages ORDER BY name") : [];
$routers = $routersTableExists ? getAllRouters() : [];

ob_start();
?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo (int) $totalCustomers; ?></h3>
            <p>Total Customers</p>
        </div>
    </div>
    
    <?php
    $activeCount = fetchOne("SELECT COUNT(*) as total FROM customers WHERE status = 'active'")['total'] ?? 0;
    $isolatedCount = fetchOne("SELECT COUNT(*) as total FROM customers WHERE status = 'isolated'")['total'] ?? 0;
    
    // Calculate unpaid customers for current month
    // Logic: Active customers who don't have a 'paid' invoice for current month
    $currentMonth = date('m');
    $currentYear = date('Y');
    $unpaidCount = fetchOne("
        SELECT COUNT(*) as total 
        FROM customers c 
        WHERE c.status = 'active' 
        AND NOT EXISTS (
            SELECT 1 FROM invoices i 
            WHERE i.customer_id = c.id 
            AND MONTH(i.due_date) = ? 
            AND YEAR(i.due_date) = ? 
            AND i.status = 'paid'
        )
    ", [$currentMonth, $currentYear])['total'] ?? 0;
    ?>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $activeCount; ?></h3>
            <p>Active</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-ban"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $isolatedCount; ?></h3>
            <p>Isolated</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $unpaidCount; ?></h3>
            <p>Unpaid</p>
        </div>
    </div>
</div>

<style>
    /* Make stats grid responsive for 4 cards */
    .stats-grid {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 15px;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .stat-card {
            padding: 15px;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }
        .stat-info h3 {
            font-size: 1.5rem;
        }
        .stat-info p {
            font-size: 0.8rem;
        }
    }
</style>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Spread Due Dates</h3>
    </div>
    <form method="POST" onsubmit="return confirm('Spread isolation dates for active customers? This will update existing data.');">
        <input type="hidden" name="action" value="spread_isolation_dates">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label class="form-label">Per Group (Customers)</label>
                <input type="number" name="group_size" class="form-control" value="10" min="1" max="200">
            </div>
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="number" name="start_day" class="form-control" value="1" min="1" max="28">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Use Dates (Optional)</label>
                <input type="text" name="days_csv" class="form-control" value="20,21,22,23,24,25" placeholder="Example: 20,21,22,23,24,25">
                <small style="color: var(--text-muted);">If provided, the system will only use these dates and distribute customers per group (e.g. 10 customers per date).</small>
            </div>
            <?php if ($hasAutoIsolate): ?>
            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="only_auto_isolate" value="1">
                    <span>Auto Isolation Only</span>
                </label>
            </div>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-random"></i> Apply</button>
        </div>
        <div style="margin-top: 8px; color: var(--text-muted); font-size: 0.9rem;">
            Billing reminders are sent based on invoice due dates. Invoice due dates follow the customer isolation date, so spreading isolation dates helps reduce simultaneous WhatsApp messages.
        </div>
    </form>
</div>

<!-- Add Customer Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> Add Customer</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; max-width: 100%;">
            <div class="form-group">
                <label class="form-label">Customer Name</label>
                <input type="text" name="name" class="form-control" required placeholder="Full Name">
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone Number (WhatsApp)</label>
                <input type="text" name="phone" class="form-control" required placeholder="08xxxxxxxxxx">
            </div>
            
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Username PPPoE</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="text" name="pppoe_username" id="pppoe_username_input" class="form-control" required placeholder="Select or type username" style="flex: 1 1 200px; min-width: 0;">
                    <button type="button" class="btn btn-secondary" onclick="openPppoeUserModal()" style="flex: 0 0 auto; white-space: nowrap;">Select from MikroTik</button>
                </div>
                <small style="color: var(--text-muted);">Select the PPPoE username from MikroTik users to avoid input errors</small>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">PPPoE Password (Optional)</label>
                <input type="text" name="pppoe_password" class="form-control" placeholder="Leave blank to keep existing PPPoE password">
                <small style="color: var(--text-muted);">If provided, this password will be sent to the device (GenieACS). The app cannot read passwords from MikroTik.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Subscription Package</label>
                <select name="package_id" class="form-control" required style="color: var(--text-primary); background: var(--bg-card);">
                    <option value="">Select Package</option>
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?php echo $pkg['id']; ?>">
                            <?php echo htmlspecialchars($pkg['name']); ?> (<?php echo formatCurrency($pkg['price']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Router / MikroTik</label>
                <select name="router_id" class="form-control" required style="color: var(--text-primary); background: var(--bg-card);">
                    <option value="0">Default Router</option>
                    <?php foreach ($routers as $r): ?>
                        <option value="<?php echo $r['id']; ?>">
                            <?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['host']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Installation Technician (Optional)</label>
                <select name="installed_by" class="form-control" style="color: var(--text-primary); background: var(--bg-card);">
                    <option value="">-- Select Technician --</option>
                    <?php foreach ($technicians as $tech): ?>
                        <option value="<?php echo $tech['id']; ?>"><?php echo htmlspecialchars($tech['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Isolation Date (1-28)</label>
                <input type="number" name="isolation_date" class="form-control" value="20" min="1" max="28" required>
            </div>

            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="auto_isolate" value="1" checked>
                    <span>Auto Isolation</span>
                </label>
                <small style="color: var(--text-muted);">If disabled, this customer will be skipped by the auto-isolation when the bill is unpaid.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Home address"></textarea>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Location (Latitude, Longitude)</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <input type="text" name="lat" class="form-control" placeholder="Latitude" readonly>
                <input type="text" name="lng" class="form-control" placeholder="Longitude" readonly>
            </div>
            <small style="color: var(--text-muted);">Click on the map to set location</small>
        </div>
        
        <div style="height: 300px; margin-top: 15px; border-radius: 8px; overflow: hidden;" id="map-picker"></div>

        <div class="form-group" style="margin-top: 15px; background: var(--bg-card); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
            <label class="form-label" style="display: block; margin-bottom: 10px;">
                Mapping ONU
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="save_onu" value="1" checked>
                    <span>Also save location to ONU Locations</span>
                </label>
                <div>
                    <label class="form-label">ODP (Optional)</label>
                    <select name="odp_id" id="add_odp_select" class="form-control" style="color: var(--text-primary); background: var(--bg-card);">
                        <option value="">-- Select ODP --</option>
                    </select>
                    <small style="color: var(--text-muted);">If not listed, add an ODP in the GenieACS Map menu</small>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-save"></i> Save Customer
        </button>
    </form>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Customer List</h3>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="searchCustomer" class="form-control" placeholder="Search customers..." style="width: 250px;">
            <a href="export.php" class="btn btn-primary btn-sm">
                <i class="fas fa-file-excel"></i> Export/Import
            </a>
        </div>
    </div>
    
    <table class="data-table" id="customerTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name & Contact</th>
                <th>Package & Router</th>
                <th>Status</th>
                <th>PPPoE</th>
                <th>Isolation Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;" data-label="Data">
                        No customer data yet
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td data-label="ID">#<?php echo $c['id']; ?></td>
                    <td data-label="Name & Contact">
                        <strong><?php echo htmlspecialchars($c['name']); ?></strong><br>
                        <small><i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($c['phone']); ?></small>
                    </td>
                    <td data-label="Package & Router">
                        <?php echo htmlspecialchars($c['package_name'] ?? 'No Package'); ?><br>
                        <small style="color: var(--neon-cyan);">
                            <i class="fas fa-server"></i> <?php echo htmlspecialchars($c['router_name'] ?? 'Default Router'); ?>
                        </small>
                    </td>
                    <td data-label="Status">
                        <?php if ($c['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Isolated</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="PPPoE">
                        <code style="background: rgba(255,255,255,0.1); padding: 2px 4px; border-radius: 4px;">
                            <?php echo htmlspecialchars($c['pppoe_username']); ?>
                        </code>
                    </td>
                    <td data-label="Isolation Date">
                        <span class="badge badge-info">Day <?php echo $c['isolation_date']; ?></span>
                    </td>
                    <td data-label="Action">
                        <a href="pay_process.php?id=<?php echo $c['id']; ?>" class="btn btn-success btn-sm" title="Pay Bill">
                            <i class="fas fa-money-bill-wave"></i>
                        </a>
                        <button class="btn btn-secondary btn-sm" onclick="editCustomer(<?php echo htmlspecialchars(json_encode($c)); ?>)" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Reset this customer portal password to 1234?');">
                            <input type="hidden" name="action" value="reset_portal_password">
                            <input type="hidden" name="customer_id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <button type="submit" class="btn btn-secondary btn-sm" title="Reset Password Portal">
                                <i class="fas fa-key"></i>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="customer_id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php if ($c['status'] === 'isolated'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="unisolate">
                                <input type="hidden" name="customer_id" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <button type="submit" class="btn btn-success btn-sm" title="Remove Isolation">
                                    <i class="fas fa-unlock"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px;">
        <a href="?page=1" class="btn btn-secondary btn-sm" <?php echo $page === 1 ? 'disabled style="opacity: 0.5;"' : ''; ?>>
            <i class="fas fa-angle-double-left"></i>
        </a>
        <a href="?page=<?php echo max(1, $page - 1); ?>" class="btn btn-secondary btn-sm" <?php echo $page === 1 ? 'disabled style="opacity: 0.5;"' : ''; ?>>
            <i class="fas fa-angle-left"></i>
        </a>
        
        <span style="color: var(--text-secondary);">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
            (Total: <?php echo $totalCustomers; ?> customers)
        </span>
        
        <a href="?page=<?php echo min($totalPages, $page + 1); ?>" class="btn btn-secondary btn-sm" <?php echo $page === $totalPages ? 'disabled style="opacity: 0.5;"' : ''; ?>>
            <i class="fas fa-angle-right"></i>
        </a>
        <a href="?page=<?php echo $totalPages; ?>" class="btn btn-secondary btn-sm" <?php echo $page === $totalPages ? 'disabled style="opacity: 0.5;"' : ''; ?>>
            <i class="fas fa-angle-double-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
        
<!-- PPPoE User Modal -->
<div id="pppoeUserModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000;">
    <div class="card" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 360px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; color: var(--neon-cyan);">
                <i class="fas fa-network-wired"></i> Select PPPoE Username
            </h3>
            <button type="button" onclick="closePppoeUserModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">&times;</button>
        </div>
        <div class="form-group" style="margin-bottom: 15px;">
            <input type="text" id="pppoeUserSearch" class="form-control" placeholder="Search PPPoE username...">
        </div>
        <div id="pppoeUserList" style="max-height: 60vh; overflow-y: auto;"></div>
    </div>
</div>
        
<!-- Edit Customer Modal -->
<div id="editCustomerModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 800px; max-width: 90%; margin: 2rem; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: var(--neon-cyan);">
                <i class="fas fa-edit"></i> Edit Customer
            </h3>
            <button onclick="closeEditModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">&times;</button>
        </div>
        
        <form method="POST" id="editCustomerForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="customer_id" id="edit_customer_id">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Customer Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required placeholder="Full Name">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number (WhatsApp)</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control" required placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username PPPoE</label>
                    <input type="text" name="pppoe_username" id="edit_pppoe_username" class="form-control" required placeholder="PPPoE username (stored internally, does not update MikroTik)">
                    <small style="color: var(--text-muted);">PPPoE username cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Subscription Package</label>
                    <select name="package_id" id="edit_package_id" class="form-control" required style="color: var(--text-primary); background: var(--bg-card);">
                        <option value="">Select Package</option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?php echo $pkg['id']; ?>">
                                <?php echo htmlspecialchars($pkg['name']); ?> (<?php echo formatCurrency($pkg['price']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Router / MikroTik</label>
                    <select name="router_id" id="edit_router_id" class="form-control" required style="color: var(--text-primary); background: var(--bg-card);">
                        <option value="0">Default Router</option>
                        <?php foreach ($routers as $r): ?>
                            <option value="<?php echo $r['id']; ?>">
                                <?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['host']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Isolation Date (1-28)</label>
                    <input type="number" name="isolation_date" id="edit_isolation_date" class="form-control" min="1" max="28" required>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="auto_isolate" id="edit_auto_isolate" value="1">
                        <span>Auto Isolation</span>
                    </label>
                    <small style="color: var(--text-muted);">If disabled, this customer will be skipped by the auto-isolation when the bill is unpaid.</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="2" placeholder="Home address"></textarea>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Location (Latitude, Longitude)</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <input type="text" name="lat" id="edit_lat" class="form-control" placeholder="Latitude" readonly>
                    <input type="text" name="lng" id="edit_lng" class="form-control" placeholder="Longitude" readonly>
                </div>
                <small style="color: var(--text-muted);">Click on the map to set location</small>
            </div>
            
            <div style="height: 300px; margin-top: 15px; border-radius: 8px; overflow: hidden;" id="edit-map-picker"></div>

            <div class="form-group" style="margin-top: 15px; background: var(--bg-card); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <label class="form-label" style="display: block; margin-bottom: 10px;">
                    Mapping ONU
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="save_onu" value="1" checked>
                        <span>Update location in ONU Locations</span>
                    </label>
                    <div>
                        <label class="form-label">ODP (Optional)</label>
                        <select name="odp_id" id="edit_odp_select" class="form-control" style="color: var(--text-primary); background: var(--bg-card);">
                            <option value="">-- Select ODP --</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="flex: 1;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
let map, marker;
let editMap, editMarker;
let pppoeUsers = [];

function openPppoeUserModal() {
    const modal = document.getElementById('pppoeUserModal');
    if (!modal) {
        return;
    }
    modal.style.display = 'flex';
    
    const list = document.getElementById('pppoeUserList');
    if (list) {
        list.innerHTML = '<div style="padding: 10px; color: var(--text-secondary);">Loading data from MikroTik...</div>';
    }
    
    fetch('../api/mikrotik.php?action=users')
        .then(response => response.text())
        .then(text => {
            let data = null;
            try {
                const start = text.indexOf('{');
                if (start !== -1) {
                    data = JSON.parse(text.slice(start));
                }
            } catch (e) {
                console.error('Invalid MikroTik response:', text, e);
            }
            
            if (data && data.success && data.data && Array.isArray(data.data.users)) {
                pppoeUsers = data.data.users;
                renderPppoeUserList(pppoeUsers);
            } else if (list) {
                list.innerHTML = '<div style="padding: 10px; color: var(--text-secondary);">Failed to retrieve data from MikroTik</div>';
            }
        })
        .catch(error => {
            console.error('Fetch MikroTik error:', error);
            if (list) {
                list.innerHTML = '<div style="padding: 10px; color: var(--text-secondary);">Failed to retrieve data from MikroTik</div>';
            }
        });
}

function renderPppoeUserList(users) {
    const list = document.getElementById('pppoeUserList');
    if (!list) {
        return;
    }
    
    if (!users || users.length === 0) {
        list.innerHTML = '<div style="padding: 10px; color: var(--text-secondary);">No PPPoE users found</div>';
        return;
    }
    
    list.innerHTML = '';
    
    users.forEach(user => {
        const username = user.name || user['name'];
        if (!username) {
            return;
        }
        
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'btn btn-secondary';
        item.style.display = 'block';
        item.style.width = '100%';
        item.style.textAlign = 'left';
        item.style.marginBottom = '8px';
        item.textContent = username;
        item.onclick = function() {
            const input = document.getElementById('pppoe_username_input') || document.querySelector('input[name="pppoe_username"]');
            if (input) {
                input.value = username;
            }
            closePppoeUserModal();
        };
        
        list.appendChild(item);
    });
}

function closePppoeUserModal() {
    const modal = document.getElementById('pppoeUserModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('pppoeUserSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const filtered = (pppoeUsers || []).filter(user => {
                const username = user.name || user['name'] || '';
                return username.toLowerCase().includes(term);
            });
            renderPppoeUserList(filtered);
        });
    }
    
    const modal = document.getElementById('pppoeUserModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closePppoeUserModal();
            }
        });
    }
});

function initMap() {
    // Add map
    map = L.map('map-picker').setView([-6.200000, 106.816666], 13);
    
    // Base layers
    var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
        subdomains: ['a', 'b', 'c']
    });
    
    var googleSat = L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
    });

    // Add default layer
    osm.addTo(map);

    // Layer control
    var baseMaps = {
        "OpenStreetMap": osm,
        "Satellite": googleSat
    };
    L.control.layers(baseMaps).addTo(map);
    
    map.on('click', function(e) {
        if (marker) {
            map.removeLayer(marker);
        }
        
        marker = L.marker(e.latlng).addTo(map);
        
        document.querySelector('input[name="lat"]').value = e.latlng.lat.toFixed(6);
        document.querySelector('input[name="lng"]').value = e.latlng.lng.toFixed(6);
    });
}

function initEditMap() {
    if (editMap) return;
    
    editMap = L.map('edit-map-picker').setView([-6.200000, 106.816666], 13);
    
    // Base layers
    var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
        subdomains: ['a', 'b', 'c']
    });
    
    var googleSat = L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
    });

    // Add default layer
    osm.addTo(editMap);

    // Layer control
    var baseMaps = {
        "OpenStreetMap": osm,
        "Satellite": googleSat
    };
    L.control.layers(baseMaps).addTo(editMap);
    
    editMap.on('click', function(e) {
        if (editMarker) {
            editMap.removeLayer(editMarker);
        }
        
        editMarker = L.marker(e.latlng).addTo(editMap);
        
        document.getElementById('edit_lat').value = e.latlng.lat.toFixed(6);
        document.getElementById('edit_lng').value = e.latlng.lng.toFixed(6);
    });
}

// Search functionality
document.getElementById('searchCustomer').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#customerTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// Edit customer
function editCustomer(customer) {
    // If id is passed (number or string), fetch data (backward compatibility)
    if (typeof customer !== 'object') {
        fetch(`../api/customers.php?id=${customer}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    editCustomer(data.data);
                } else {
                    alert('Failed to retrieve customer data: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while retrieving customer data');
            });
        return;
    }

    document.getElementById('edit_customer_id').value = customer.id;
    document.getElementById('edit_name').value = customer.name;
    document.getElementById('edit_phone').value = customer.phone;
    document.getElementById('edit_pppoe_username').value = customer.pppoe_username;
    document.getElementById('edit_package_id').value = customer.package_id;
    document.getElementById('edit_router_id').value = customer.router_id || 0;
    document.getElementById('edit_isolation_date').value = customer.isolation_date || 20;
    const autoIsolate = document.getElementById('edit_auto_isolate');
    if (autoIsolate) {
        autoIsolate.checked = String(customer.auto_isolate ?? 1) === '1';
    }
    document.getElementById('edit_address').value = customer.address || '';
    document.getElementById('edit_lat').value = customer.lat || '';
    document.getElementById('edit_lng').value = customer.lng || '';
    
    // Set technician
    const techSelect = document.getElementById('edit_installed_by');
    if (techSelect) {
        techSelect.value = customer.installed_by || '';
    }

    // Set ODP
    const odpSelect = document.getElementById('edit_odp_select');
    if (odpSelect) {
        odpSelect.value = customer.onu_odp_id || '';
    }
    
    // Show modal
    document.getElementById('editCustomerModal').style.display = 'flex';
    
    // Initialize map if needed and set view
    setTimeout(() => {
        initEditMap();
        editMap.invalidateSize();
        
        if (customer.lat && customer.lng) {
            const latlng = [customer.lat, customer.lng];
            editMap.setView(latlng, 15);
            
            if (editMarker) editMap.removeLayer(editMarker);
            editMarker = L.marker(latlng).addTo(editMap);
        }
    }, 100);
}

function closeEditModal() {
    document.getElementById('editCustomerModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editCustomerModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Initialize map when page loads
setTimeout(initMap, 500);

// Load ODP list for dropdowns
function loadOdpOptions() {
    fetch('../api/onu_locations.php')
        .then(r => r.json())
        .then(j => {
            if (!j.success) return;
            const odps = j.odps || [];
            const addSel = document.getElementById('add_odp_select');
            const editSel = document.getElementById('edit_odp_select');
            const makeOptions = (sel) => {
                if (!sel) return;
                // keep first option
                sel.innerHTML = '<option value="">-- Select ODP --</option>';
                odps.forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.id;
                    opt.textContent = o.name + (o.code ? (' (' + o.code + ')') : '');
                    sel.appendChild(opt);
                });
            };
            makeOptions(addSel);
            makeOptions(editSel);
        })
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', loadOdpOptions);
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
