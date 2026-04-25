<?php
/**
 * Hotspot Profileeeeeeeeeeee Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Hotspot Profileeeeeeeeeeees';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $shared = sanitize($_POST['shared_users'] ?? '1');
        $rate = sanitize($_POST['rate_limit'] ?? '');
        $validity = sanitize($_POST['validity'] ?? '');
        $price = sanitize($_POST['price'] ?? '0');
        $selling = sanitize($_POST['selling_price'] ?? '0');
        $expiry = sanitize($_POST['expiry_mode'] ?? 'none');
        $compat = sanitize($_POST['ros_compat'] ?? 'auto');
        $pool = sanitize($_POST['address_pool'] ?? 'none');
        $parent = sanitize($_POST['parent_queue'] ?? 'none');
        $idle = sanitize($_POST['idle_timeout'] ?? '');

        // Mikhmon v3: Store price/validity/selling in on-login script as comma-separated
        $onLogin = generateHotspotExpiryScript($expiry, $price, $validity, $selling, 'Disable', $compat);

        $comment = '';

        $data = [
            'name' => $name,
            'shared-users' => $shared,
            'on-login' => $onLogin
        ];
        if ($rate !== '') {
            $data['rate-limit'] = $rate;
        }
        if ($idle !== '') {
            $data['idle-timeout'] = $idle;
        }
        if ($pool !== '' && $pool !== 'none') {
            $data['address-pool'] = $pool;
        }
        if ($parent !== '' && $parent !== 'none') {
            $data['parent-queue'] = $parent;
        }

        if ($action === 'add') {
            if (mikrotikAddHotspotProfileeeeeeeeeeee($data)) {
                setFlash('success', "Profileeeeeeeeeeee $name successfully added.");
            } else {
                setFlash('error', "Failed to add profile.");
            }
        } else {
            $id = $_POST['id'];
            if (mikrotikUpdateHotspotProfileeeeeeeeeeee($id, $data)) {
                setFlash('success', "Profileeeeeeeeeeee $name successfully updated.");
            } else {
                setFlash('error', "Failed to update profile.");
            }
        }
        redirect('hotspot-profile.php');
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        if (mikrotikDeleteHotspotProfileeeeeeeeeeee($id)) {
            setFlash('success', "Profileeeeeeeeeeee successfully deleted.");
        } else {
            setFlash('error', "Failed to delete profile.");
        }
        redirect('hotspot-profile.php');
    }
}

// Get Data
$hotspotProfileeeeeeeeeeees = mikrotikGetHotspotProfileeeeeeeeeeees();
$addressPools = mikrotikGetAddressPools();
$parentQueues = mikrotikGetParentQueues();
$activeUsers = mikrotikGetHotspotActive();

// Count active users per profile
$profileActiveCount = [];
foreach ($activeUsers as $au) {
    $prof = $au['profile'] ?? 'default';
    if (!isset($profileActiveCount[$prof]))
        $profileActiveCount[$prof] = 0;
    $profileActiveCount[$prof]++;
}

ob_start();
?>

<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Add/Edit Profileeeeeeeeeeee</h3>
    </div>
    <form method="POST" id="profileForm">
        <input type="hidden" name="action" value="add" id="formAction">
        <input type="hidden" name="id" id="profileId">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" id="pName" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Shared Users</label>
                <input type="number" name="shared_users" id="pShared" class="form-control" value="1">
            </div>
            <div class="form-group">
                <label class="form-label">Rate Limit (e.g. 1M/1M)</label>
                <input type="text" name="rate_limit" id="pRate" class="form-control" placeholder="1M/1M">
            </div>
            <div class="form-group">
                <label class="form-label">Validity (e.g. 1d, 30d)</label>
                <input type="text" name="validity" id="pValidity" class="form-control" placeholder="1d">
            </div>
            <div class="form-group">
                <label class="form-label">Base Price</label>
                <input type="number" name="price" id="pPrice" class="form-control" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Selling Price</label>
                <input type="number" name="selling_price" id="pSelling" class="form-control" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Expiry Mode</label>
                <select name="expiry_mode" id="pExpiry" class="form-control">
                    <option value="none">None</option>
                    <option value="remove">Remove</option>
                    <option value="notice">Notice</option>
                    <option value="record">Record</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">ROS Compatibility</label>
                <select name="ros_compat" id="pCompat" class="form-control">
                    <option value="auto">Auto</option>
                    <option value="ros6">ROS 6</option>
                    <option value="ros7">ROS 7</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Address Pool</label>
                <select name="address_pool" id="pPool" class="form-control">
                    <option value="none">none</option>
                    <?php foreach ($addressPools as $pool): ?>
                        <option value="<?php echo htmlspecialchars($pool['name']); ?>">
                            <?php echo htmlspecialchars($pool['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Parent Queue</label>
                <select name="parent_queue" id="pParent" class="form-control">
                    <option value="none">none</option>
                    <?php foreach ($parentQueues as $q): ?>
                        <option value="<?php echo htmlspecialchars($q['name']); ?>">
                            <?php echo htmlspecialchars($q['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Idle Timeout</label>
                <input type="text" name="idle_timeout" id="pIdle" class="form-control" placeholder="00:05:00">
            </div>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profileeeeeeeeeeee</button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
        </div>
    </form>
</div>

<!-- List Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Hotspot Profileeeeeeeeeeee List</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Shared</th>
                    <th>Rate Limit</th>
                    <th>Price</th>
                    <th>Validity</th>
                    <th>Active</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hotspotProfileeeeeeeeeeees as $p): ?>
                    <?php $pData = parseMikhmonOnLogin($p['on-login'] ?? ''); ?>
                    <tr>
                        <td data-label="Name"><strong>
                                <?php echo htmlspecialchars($p['name'] ?? ''); ?>
                            </strong></td>
                        <td data-label="Shared">
                            <?php echo $p['shared-users'] ?? '1'; ?>
                        </td>
                        <td data-label="Rate Limit">
                            <?php echo htmlspecialchars($p['rate-limit'] ?? '∞'); ?>
                        </td>
                        <td data-label="Price">
                            <?php echo $pData['price'] > 0 ? formatCurrency($pData['price']) : '-'; ?>
                        </td>
                        <td data-label="Validity">
                            <small><?php echo htmlspecialchars($pData['validity']); ?></small>
                        </td>
                        <td data-label="Active">
                            <span
                                class="badge <?php echo ($profileActiveCount[$p['name']] ?? 0) > 0 ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $profileActiveCount[$p['name']] ?? 0; ?>
                            </span>
                        </td>
                        <td data-label="Action">
                            <div style="display: flex; gap: 5px;">
                                <button onclick='editProfileeeeeeeeeeee(<?php echo json_encode($p); ?>)'
                                    class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this profile?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['.id']); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function editProfileeeeeeeeeeee(p) {
        document.getElementById('formAction').value = 'edit';
        document.getElementById('profileId').value = p['.id'];
        document.getElementById('pName').value = p['name'];
        document.getElementById('pShared').value = p['shared-users'] || '1';
        document.getElementById('pRate').value = p['rate-limit'] || '';

        // Parse on-login script for price/validity/selling/compat (Mikhmon v3 format)
        let price = 0, validity = '', selling = 0, compat = 'auto';
        if (p['on-login']) {
            const m = p['on-login'].match(/:put\s*\(\s*"(.*?)"\s*\)\s*;?/i);
            if (m && m[1]) {
                const header = m[1].split(',');
                if (header[2]) price = header[2];
                if (header[3]) validity = header[3];
                if (header[4]) selling = header[4];
                const c = (header[7] || header[8] || '').toLowerCase();
                if (c === 'auto' || c === 'ros6' || c === 'ros7') compat = c;
            } else {
                const parts = p['on-login'].split(',');
                if (parts[2]) price = parts[2];
                if (parts[3]) validity = parts[3];
                if (parts[4]) selling = parts[4];
            }
        }
        document.getElementById('pPrice').value = price;
        document.getElementById('pValidity').value = validity;
        document.getElementById('pSelling').value = selling;
        document.getElementById('pCompat').value = compat;
        document.getElementById('pPool').value = p['address-pool'] || 'none';
        document.getElementById('pParent').value = p['parent-queue'] || 'none';
        document.getElementById('pIdle').value = p['idle-timeout'] || '';

        // We can't easily reverse the script back to validity/mode, 
        // but the user can re-input if they want to change.

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('profileForm').reset();
        document.getElementById('formAction').value = 'add';
        document.getElementById('profileId').value = '';
    }
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
