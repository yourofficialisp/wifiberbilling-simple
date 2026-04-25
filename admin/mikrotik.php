<?php
/**
 * MikroTik Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'MikroTik PPPoE';

// Get MikroTik settings
$mikrotikSettings = getMikrotikSettings();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $result = mikrotikAddSecret(
                sanitize($_POST['username']),
                sanitize($_POST['password']),
                sanitize($_POST['profile']),
                sanitize($_POST['service'])
            );
            
            if ($result['success']) {
                setFlash('success', 'PPPoE User successfully added');
                logActivity('ADD_PPPOE_USER', "Username: " . $_POST['username']);
            } else {
                setFlash('error', 'Failed to add user: ' . $result['message']);
            }
            redirect('mikrotik.php');
            break;
            
        case 'edit':
            $id = $_POST['user_id'];
            $data = [
                'name' => sanitize($_POST['username']),
                'password' => sanitize($_POST['password']),
                'profile' => sanitize($_POST['profile']),
                'service' => sanitize($_POST['service'])
            ];
            
            $result = mikrotikUpdateSecret($id, $data);
            
            if ($result['success']) {
                setFlash('success', 'PPPoE User successfully updated');
                logActivity('UPDATE_PPPOE_USER', "ID: $id");
            } else {
                setFlash('error', 'Failed to update user: ' . $result['message']);
            }
            redirect('mikrotik.php');
            break;
            
        case 'delete':
            $id = $_POST['user_id'];
            $result = mikrotikDeleteSecret($id);
            
            if ($result['success']) {
                setFlash('success', 'PPPoE User successfully deleted');
                logActivity('DELETE_PPPOE_USER', "ID: $id");
            } else {
                setFlash('error', 'Failed to delete user: ' . $result['message']);
            }
            redirect('mikrotik.php');
            break;
            
        case 'toggle':
            $id = $_POST['user_id'];
            $currentStatus = $_POST['current_status'] ?? 'false';
            $newStatus = ($currentStatus === 'true') ? 'false' : 'true';
            
            $result = mikrotikUpdateSecret($id, ['disabled' => $newStatus]);
            
            if ($result['success']) {
                $status = ($newStatus === 'true') ? 'disabled' : 'enabled';
                setFlash('success', "PPPoE user successfully $status");
                logActivity('TOGGLE_PPPOE_USER', "ID: $id, Status: $status");
            } else {
                setFlash('error', 'Failed to change user status: ' . $result['message']);
            }
            redirect('mikrotik.php');
            break;
    }
}

// Get MikroTik users (secrets)
$mikrotikUsers = mikrotikGetPppoeUsers();
$totalUsers = count($mikrotikUsers);

// Get active PPPoE sessions (currently online)
$activeSessions = mikrotikGetActiveSessions();
$onlineCount = count($activeSessions);

// Create list of online usernames
$onlineUsernames = array_column($activeSessions, 'name');

// Calculate stats
$disabledCount = count(array_filter($mikrotikUsers, fn($u) => ($u['disabled'] ?? 'false') === 'true'));
$offlineCount = $totalUsers - $onlineCount;

// Get MikroTik profiles
$mikrotikProfileeeeeeeeeeees = mikrotikGetProfileeeeeeeeeeees();
if (empty($mikrotikProfileeeeeeeeeeees)) {
    $mikrotikProfileeeeeeeeeeees = [['name' => 'default']];
}

ob_start();
?>

<!-- Stats -->
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

<!-- Connection Status -->
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

<!-- Add User Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus"></i> Add PPPoE User</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="PPPoE Username">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="text" name="password" class="form-control" required placeholder="PPPoE Password">
            </div>
            
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee</label>
                <select name="profile" class="form-control" required>
                    <?php foreach ($mikrotikProfileeeeeeeeeeees as $profile): ?>
                        <option value="<?php echo htmlspecialchars($profile['name']); ?>">
                            <?php echo htmlspecialchars($profile['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Service</label>
                <select name="service" class="form-control" required>
                    <option value="pppoe">PPPoE</option>
                    <option value="any">Any</option>
                </select>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-save"></i> Add User
        </button>
    </form>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-network-wired"></i> PPPoE User List</h3>
        <input type="text" id="searchUser" class="form-control" placeholder="Search user..." style="width: 250px;">
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Profileeeeeeeeeeee</th>
                <th>Service</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mikrotikUsers)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;" data-label="Data">
                        <i class="fas fa-network-wired" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        No PPPoE users yet or not connected to MikroTik
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($mikrotikUsers as $user): ?>
                <?php 
                    $isOnline = in_array($user['name'] ?? '', $onlineUsernames);
                    $isDisabled = ($user['disabled'] ?? 'false') === 'true';
                ?>
                <tr>
                    <td data-label="Username">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; background: var(--gradient-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; position: relative;">
                                <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                                <?php if ($isOnline && !$isDisabled): ?>
                                    <span style="position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: #00ff00; border-radius: 50%; border: 2px solid var(--bg-secondary);"></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></strong>
                                <?php if (!empty($user['password'])): ?>
                                    <br><small style="color: var(--text-muted);">Pass: <?php echo htmlspecialchars($user['password']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td data-label="Profileeeeeeeeeeee">
                        <span class="badge badge-info"><?php echo htmlspecialchars($user['profile'] ?? 'default'); ?></span>
                    </td>
                    <td data-label="Service"><?php echo htmlspecialchars($user['service'] ?? 'pppoe'); ?></td>
                    <td data-label="Status">
                        <?php if ($isDisabled): ?>
                            <span class="badge badge-danger">Disabled</span>
                        <?php elseif ($isOnline): ?>
                            <span class="badge badge-success"><i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i> Online</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Offline</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Last Login"><?php echo $user['last-login'] ?? 'Never'; ?></td>
                    <td data-label="Action">
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-secondary btn-sm" onclick="editUser('<?php echo htmlspecialchars($user['.id'] ?? ''); ?>', '<?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['password'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['profile'] ?? 'default'); ?>', '<?php echo htmlspecialchars($user['service'] ?? 'pppoe'); ?>')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle this user status?')">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['.id'] ?? ''); ?>">
                                <input type="hidden" name="current_status" value="<?php echo $user['disabled'] ?? 'false'; ?>">
                                <button type="submit" class="btn btn-sm <?php echo ($user['disabled'] ?? 'false') === 'true' ? 'btn-primary' : 'btn-warning'; ?>" title="<?php echo ($user['disabled'] ?? 'false') === 'true' ? 'Enable' : 'Disable'; ?>">
                                    <i class="fas fa-<?php echo ($user['disabled'] ?? 'false') === 'true' ? 'check' : 'ban'; ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete user <?php echo htmlspecialchars($user['name'] ?? ''); ?>?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['.id'] ?? ''); ?>">
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

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 450px; max-width: 90%; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Edit PPPoE User</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="text" name="password" id="edit_password" class="form-control" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label class="form-label">Profileeeeeeeeeeee</label>
                    <select name="profile" id="edit_profile" class="form-control" required>
                        <?php foreach ($mikrotikProfileeeeeeeeeeees as $profile): ?>
                            <option value="<?php echo htmlspecialchars($profile['name']); ?>">
                                <?php echo htmlspecialchars($profile['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Service</label>
                    <select name="service" id="edit_service" class="form-control" required>
                        <option value="pppoe">PPPoE</option>
                        <option value="any">Any</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const usersData = <?php echo json_encode($mikrotikUsers); ?>;

document.getElementById('searchUser').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

function editUser(id, name, password, profile, service) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = name;
    document.getElementById('edit_password').value = password;
    document.getElementById('edit_profile').value = profile;
    document.getElementById('edit_service').value = service;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
