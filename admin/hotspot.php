<?php
require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'MikroTik Hotspot';

$mikrotikSettings = getMikrotikSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('hotspot.php');
    }

    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $ok = mikrotikAddHotspotUser(
                sanitize($_POST['username']),
                sanitize($_POST['password']),
                sanitize($_POST['profile'])
            );
            
            if ($ok) {
                setFlash('success', 'Hotspot User successfully added');
                logActivity('ADD_HOTSPOT_USER', "Username: " . $_POST['username']);
            } else {
                setFlash('error', 'Failed to add Hotspot user');
            }
            redirect('hotspot.php');
            break;
            
        case 'edit':
            $id = $_POST['user_id'];
            $data = [
                'name' => sanitize($_POST['username']),
                'password' => sanitize($_POST['password']),
                'profile' => sanitize($_POST['profile'])
            ];
            
            $result = mikrotikUpdateHotspotUser($id, $data);
            
            if ($result['success']) {
                setFlash('success', 'Hotspot User successfully updated');
                logActivity('UPDATE_HOTSPOT_USER', "ID: $id");
            } else {
                setFlash('error', 'Failed to update user: ' . $result['message']);
            }
            redirect('hotspot.php');
            break;
            
        case 'delete':
            $username = sanitize($_POST['username']);
            $ok = mikrotikDeleteHotspotUser($username);
            
            if ($ok) {
                setFlash('success', 'Hotspot User successfully deleted');
                logActivity('DELETE_HOTSPOT_USER', "Username: $username");
            } else {
                setFlash('error', 'Failed to delete Hotspot user');
            }
            redirect('hotspot.php');
            break;
            
        case 'toggle':
            $id = $_POST['user_id'];
            $currentStatus = $_POST['current_status'] ?? 'false';
            $newStatus = ($currentStatus === 'true') ? 'false' : 'true';
            
            $result = mikrotikUpdateHotspotUser($id, ['disabled' => $newStatus]);
            
            if ($result['success']) {
                $status = ($newStatus === 'true') ? 'disabled' : 'enabled';
                setFlash('success', "Hotspot user successfully $status");
                logActivity('TOGGLE_HOTSPOT_USER', "ID: $id, Status: $status");
            } else {
                setFlash('error', 'Failed to change user status: ' . $result['message']);
            }
            redirect('hotspot.php');
            break;
            
        case 'profile_add':
            $result = mikrotikAddHotspotProfileeeeeeeeeeee(
                sanitize($_POST['profile_name']),
                sanitize($_POST['rate_limit']),
                sanitize($_POST['shared_users'])
            );
            
            if ($result['success']) {
                setFlash('success', 'Hotspot profile successfully added');
                logActivity('ADD_HOTSPOT_PROFILE', "Profileeeeeeeeeeee: " . $_POST['profile_name']);
            } else {
                setFlash('error', 'Failed to add profile: ' . $result['message']);
            }
            redirect('hotspot.php');
            break;
            
        case 'profile_edit':
            $id = $_POST['profile_id'];
            $data = [
                'name' => sanitize($_POST['profile_name']),
                'rate_limit' => sanitize($_POST['rate_limit']),
                'shared_users' => sanitize($_POST['shared_users'])
            ];
            
            $result = mikrotikUpdateHotspotProfileeeeeeeeeeee($id, $data);
            
            if ($result['success']) {
                setFlash('success', 'Hotspot profile successfully updated');
                logActivity('UPDATE_HOTSPOT_PROFILE', "ID: $id");
            } else {
                setFlash('error', 'Failed to update profile: ' . $result['message']);
            }
            redirect('hotspot.php');
            break;
            
        case 'profile_delete':
            $id = $_POST['profile_id'];
            $result = mikrotikDeleteHotspotProfileeeeeeeeeeee($id);
            
            if ($result['success']) {
                setFlash('success', 'Hotspot profile successfully deleted');
                logActivity('DELETE_HOTSPOT_PROFILE', "ID: $id");
            } else {
                setFlash('error', 'Failed to delete profile: ' . $result['message']);
            }
            redirect('hotspot.php');
            break;
    }
}

$hotspotUsers = mikrotikGetHotspotUsers();
$totalUsers = count($hotspotUsers);

$activeSessions = mikrotikGetHotspotActiveSessions();
$onlineCount = count($activeSessions);

$onlineUsernames = [];
foreach ($activeSessions as $session) {
    if (!empty($session['user'])) {
        $onlineUsernames[] = $session['user'];
    } elseif (!empty($session['name'])) {
        $onlineUsernames[] = $session['name'];
    }
}

$disabledCount = count(array_filter($hotspotUsers, function($u) {
    return ($u['disabled'] ?? 'false') === 'true';
}));
$offlineCount = max(0, $totalUsers - $onlineCount);

$hotspotProfileeeeeeeeeeees = mikrotikGetHotspotProfileeeeeeeeeeees();
if (empty($hotspotProfileeeeeeeeeeees)) {
    $hotspotProfileeeeeeeeeeees = [['name' => 'default']];
}

ob_start();
?>

<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-signal"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $onlineCount; ?></h3>
            <p>Online</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-wifi-slash"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $offlineCount; ?></h3>
            <p>Offline</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-user-slash"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $disabledCount; ?></h3>
            <p>Disabled</p>
        </div>
    </div>
</div>

<?php if (mikrotikConnect()): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        Connected to MikroTik: <?php echo htmlspecialchars($mikrotikSettings['host']); ?>
    </div>
<?php else: ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        Cannot connect to MikroTik. Please check configuration in Settings.
    </div>
<?php endif; ?>

<div class="card">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="#hotspotProfileeeeeeeeeeeesSection" class="btn btn-secondary">
            <i class="fas fa-layer-group"></i> Hotspot Profileeeeeeeeeeees
        </a>
        <a href="#hotspotUsersSection" class="btn btn-secondary">
            <i class="fas fa-wifi"></i> Hotspot Users
        </a>
    </div>
</div>

<div class="card" id="hotspotProfileeeeeeeeeeeesSection">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-layer-group"></i> Hotspot Profileeeeeeeeeeees</h3>
        <input type="text" id="searchHotspotProfileeeeeeeeeeee" class="form-control" placeholder="Search profile..." style="width: 250px;">
    </div>
    
    <form method="POST" style="padding: 0 20px 20px;">
        <input type="hidden" name="action" value="profile_add">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee Name</label>
                <input type="text" name="profile_name" class="form-control" required placeholder="profile-name">
            </div>
            
            <div class="form-group">
                <label class="form-label">Rate Limit</label>
                <input type="text" name="rate_limit" class="form-control" placeholder="2M/2M">
            </div>
            
            <div class="form-group">
                <label class="form-label">Shared Users</label>
                <input type="number" name="shared_users" class="form-control" min="1" placeholder="1">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-save"></i> Add Profileeeeeeeeeeee
        </button>
    </form>
    
    <table class="data-table" id="hotspotProfileeeeeeeeeeeeTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Rate Limit</th>
                <th>Shared Users</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($hotspotProfileeeeeeeeeeees)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;" data-label="Data">
                        <i class="fas fa-layer-group" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        No Hotspot profiles yet or not connected to MikroTik
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($hotspotProfileeeeeeeeeeees as $profile): ?>
                <tr>
                    <td data-label="Name">
                        <strong><?php echo htmlspecialchars($profile['name'] ?? 'N/A'); ?></strong>
                    </td>
                    <td data-label="Rate Limit"><?php echo htmlspecialchars($profile['rate-limit'] ?? '-'); ?></td>
                    <td data-label="Shared Users"><?php echo htmlspecialchars($profile['shared-users'] ?? '-'); ?></td>
                    <td data-label="Action">
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-secondary btn-sm" onclick="editProfileeeeeeeeeeee('<?php echo htmlspecialchars($profile['.id'] ?? ''); ?>', '<?php echo htmlspecialchars($profile['name'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($profile['rate-limit'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($profile['shared-users'] ?? '', ENT_QUOTES); ?>')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete profile <?php echo htmlspecialchars($profile['name'] ?? ''); ?>?')">
                                <input type="hidden" name="action" value="profile_delete">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="profile_id" value="<?php echo htmlspecialchars($profile['.id'] ?? ''); ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus"></i> Add Hotspot User</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Hotspot Username">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="text" name="password" class="form-control" required placeholder="Hotspot Password">
            </div>
            
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee</label>
                <select name="profile" class="form-control" required>
                    <?php foreach ($hotspotProfileeeeeeeeeeees as $profile): ?>
                        <option value="<?php echo htmlspecialchars($profile['name']); ?>">
                            <?php echo htmlspecialchars($profile['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-save"></i> Add User
        </button>
    </form>
</div>

<div class="card" id="hotspotUsersSection">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-wifi"></i> Hotspot User List</h3>
        <input type="text" id="searchHotspotUser" class="form-control" placeholder="Search user..." style="width: 250px;">
    </div>
    
    <table class="data-table" id="hotspotUserTable">
        <thead>
            <tr>
                <th>Username</th>
                <th>Profileeeeeeeeeeee</th>
                <th>Status</th>
                <th>Uptime</th>
                <th>Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($hotspotUsers)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;" data-label="Data">
                        <i class="fas fa-network-wired" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        No Hotspot users yet or not connected to MikroTik
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($hotspotUsers as $user): ?>
                <?php 
                    $name = $user['name'] ?? '';
                    $isOnline = in_array($name, $onlineUsernames);
                    $isDisabled = ($user['disabled'] ?? 'false') === 'true';
                ?>
                <tr>
                    <td data-label="Username">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; background: var(--gradient-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; position: relative;">
                                <?php echo strtoupper(substr($name ?: 'U', 0, 1)); ?>
                                <?php if ($isOnline && !$isDisabled): ?>
                                    <span style="position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: #00ff00; border-radius: 50%; border: 2px solid var(--bg-secondary);"></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($name ?: 'N/A'); ?></strong>
                                <?php if (!empty($user['password'])): ?>
                                    <br><small style="color: var(--text-muted);">Pass: <?php echo htmlspecialchars($user['password']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td data-label="Profileeeeeeeeeeee">
                        <span class="badge badge-info"><?php echo htmlspecialchars($user['profile'] ?? 'default'); ?></span>
                    </td>
                    <td data-label="Status">
                        <?php if ($isDisabled): ?>
                            <span class="badge badge-danger">Disabled</span>
                        <?php elseif ($isOnline): ?>
                            <span class="badge badge-success"><i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i> Online</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Offline</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Uptime"><?php echo $user['uptime'] ?? '-'; ?></td>
                    <td data-label="Address"><?php echo $user['address'] ?? '-'; ?></td>
                    <td data-label="Action">
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-secondary btn-sm" onclick="editUser('<?php echo htmlspecialchars($user['.id'] ?? ''); ?>', '<?php echo htmlspecialchars($name, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['password'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['profile'] ?? 'default'); ?>')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle this user status?')">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['.id'] ?? ''); ?>">
                                <input type="hidden" name="current_status" value="<?php echo $user['disabled'] ?? 'false'; ?>">
                                <button type="submit" class="btn btn-sm <?php echo ($user['disabled'] ?? 'false') === 'true' ? 'btn-primary' : 'btn-warning'; ?>" title="<?php echo ($user['disabled'] ?? 'false') === 'true' ? 'Enable' : 'Disable'; ?>">
                                    <i class="fas fa-<?php echo ($user['disabled'] ?? 'false') === 'true' ? 'check' : 'ban'; ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete user <?php echo htmlspecialchars($name); ?>?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($name); ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="profileModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 450px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Edit Profileeeeeeeeeeee Hotspot</h3>
            <button onclick="closeProfileeeeeeeeeeeeModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="profile_edit">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="profile_id" id="edit_profile_id">
            
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee Name</label>
                <input type="text" name="profile_name" id="edit_profile_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Rate Limit</label>
                <input type="text" name="rate_limit" id="edit_rate_limit" class="form-control" placeholder="2M/2M">
            </div>
            
            <div class="form-group">
                <label class="form-label">Shared Users</label>
                <input type="number" name="shared_users" id="edit_shared_users" class="form-control" min="1">
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeProfileeeeeeeeeeeeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 450px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Edit Hotspot User</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="text" name="password" id="edit_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee</label>
                <select name="profile" id="edit_profile" class="form-control" required>
                    <?php foreach ($hotspotProfileeeeeeeeeeees as $profile): ?>
                        <option value="<?php echo htmlspecialchars($profile['name']); ?>">
                            <?php echo htmlspecialchars($profile['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('searchHotspotUser').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#hotspotUserTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

document.getElementById('searchHotspotProfileeeeeeeeeeee').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#hotspotProfileeeeeeeeeeeeTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

function editUser(id, name, password, profile) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = name;
    document.getElementById('edit_password').value = password;
    document.getElementById('edit_profile').value = profile;
    document.getElementById('editModal').style.display = 'flex';
}

function editProfileeeeeeeeeeee(id, name, rateLimit, sharedUsers) {
    document.getElementById('edit_profile_id').value = id;
    document.getElementById('edit_profile_name').value = name;
    document.getElementById('edit_rate_limit').value = rateLimit;
    document.getElementById('edit_shared_users').value = sharedUsers;
    document.getElementById('profileModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function closeProfileeeeeeeeeeeeModal() {
    document.getElementById('profileModal').style.display = 'none';
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('profileModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProfileeeeeeeeeeeeModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
        closeProfileeeeeeeeeeeeModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
