<?php
/**
 * Packages Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Internet Packages';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('packages.php');
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'name' => sanitize($_POST['name']),
                    'price' => (float)$_POST['price'],
                    'profile_normal' => sanitize($_POST['profile_normal']),
                    'profile_isolir' => sanitize($_POST['profile_isolir']),
                    'description' => sanitize($_POST['description']),
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                if (insert('packages', $data)) {
                    setFlash('success', 'Package successfully added');
                    logActivity('ADD_PACKAGE', "Name: {$data['name']}");
                } else {
                    setFlash('error', 'Failed to add package');
                }
                redirect('packages.php');
                break;
                
            case 'edit':
                $packageId = (int)$_POST['package_id'];
                $data = [
                    'name' => sanitize($_POST['name']),
                    'price' => (float)$_POST['price'],
                    'profile_normal' => sanitize($_POST['profile_normal']),
                    'profile_isolir' => sanitize($_POST['profile_isolir']),
                    'description' => sanitize($_POST['description']),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if (update('packages', $data, 'id = ?', [$packageId])) {
                    setFlash('success', 'Package successfully updated');
                    logActivity('UPDATE_PACKAGE', "ID: {$packageId}");
                } else {
                    setFlash('error', 'Failed to update package');
                }
                redirect('packages.php');
                break;
                
            case 'delete':
                $packageId = (int)$_POST['package_id'];
                
                // Check if package has customers
                $customerCount = fetchOne("SELECT COUNT(*) as total FROM customers WHERE package_id = ?", [$packageId])['total'];
                if ($customerCount > 0) {
                    setFlash('error', "Cannot delete package that still has {$customerCount} customers");
                    redirect('packages.php');
                }
                
                if (delete('packages', 'id = ?', [$packageId])) {
                    setFlash('success', 'Package successfully deleted');
                    logActivity('DELETE_PACKAGE', "ID: {$packageId}");
                } else {
                    setFlash('error', 'Failed to delete package');
                }
                redirect('packages.php');
                break;
        }
    }
}

// Get data
$packages = fetchAll("
    SELECT p.*, COUNT(c.id) as customer_count 
    FROM packages p 
    LEFT JOIN customers c ON p.id = c.package_id 
    GROUP BY p.id 
    ORDER BY p.name
");

// Get MikroTik profiles from actual MikroTik
$mikrotikConnected = true;
$mikrotikProfileeeeeeeeeeees = mikrotikGetProfileeeeeeeeeeees();

// If connection fails, use fallback profiles
if (empty($mikrotikProfileeeeeeeeeeees)) {
    $mikrotikConnected = false;
    $mikrotikProfileeeeeeeeeeees = [
        ['name' => 'default'],
        ['name' => '10Mbps'],
        ['name' => '20Mbps'],
        ['name' => '50Mbps'],
        ['name' => 'isolir-10Mbps'],
        ['name' => 'isolir-20Mbps'],
        ['name' => 'isolir-50Mbps']
    ];
}

ob_start();
?>

<?php if (!$mikrotikConnected): ?>
<div style="background: rgba(255, 0, 0, 0.1); border: 1px solid #ff4444; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 10px; color: #ff6666;">
        <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem;"></i>
        <div>
            <strong>Failed to connect to MikroTik!</strong>
            <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #ffaaaa;">
                The profiles displayed are default profiles. 
                Please check MikroTik settings in <a href="settings.php" style="color: #66ccff;">Settings</a> 
                to ensure credentials are correct.
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo count($packages); ?></h3>
            <p>Total Packages</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <?php 
            $totalCustomers = array_sum(array_column($packages, 'customer_count'));
            ?>
            <h3><?php echo $totalCustomers; ?></h3>
            <p>Active Customers</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-info">
            <?php 
            $mostPopularPackage = '-';
            $maxCustomers = 0;
            $avgPrice = 0;
            $totalPrice = 0;
            $countPrice = 0;
            
            foreach ($packages as $p) {
                if ($p['customer_count'] > $maxCustomers) {
                    $maxCustomers = $p['customer_count'];
                    $mostPopularPackage = $p['name'];
                }
                $totalPrice += $p['price'];
                $countPrice++;
            }
            
            if ($countPrice > 0) {
                $avgPrice = $totalPrice / $countPrice;
            }
            ?>
            <h3 style="font-size: 1.2rem;"><?php echo htmlspecialchars($mostPopularPackage); ?></h3>
            <p>Most Popular Package</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-check-double"></i>
        </div>
        <div class="stat-info">
            <?php 
            $activePackages = 0;
            foreach ($packages as $p) {
                if ($p['customer_count'] > 0) {
                    $activePackages++;
                }
            }
            ?>
            <h3><?php echo $activePackages; ?></h3>
            <p>Used Packages</p>
        </div>
    </div>
</div>

<style>
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

<!-- Add Package Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Add New Package</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div class="form-group">
                <label class="form-label">Package Name</label>
                <input type="text" name="name" class="form-control" required placeholder="Example: 10 Mbps Package">
            </div>
            
            <div class="form-group">
                <label class="form-label">Price per Month</label>
                <input type="number" name="price" class="form-control" required placeholder="250000">
            </div>
            
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee MikroTik (Normal)</label>
                <select name="profile_normal" id="profile_normal" class="form-control" required style="color: var(--text-primary); background: var(--bg-card);">
                    <?php foreach ($mikrotikProfileeeeeeeeeeees as $profile): ?>
                        <option value="<?php echo htmlspecialchars($profile['name']); ?>">
                            <?php echo htmlspecialchars($profile['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="profile_info_normal" class="profile-info-display" style="margin-top: 8px; font-size: 0.85rem; padding: 8px; border-radius: 6px; background: rgba(0,255,255,0.05); border: 1px dashed rgba(0,255,255,0.2); display: none;"></div>
                <small style="color: var(--text-muted);">Profileeeeeeeeeeee when customer is active</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee MikroTik (Isolation)</label>
                <select name="profile_isolir" id="profile_isolir" class="form-control" required style="color: var(--text-primary); background: var(--bg-card);">
                    <?php foreach ($mikrotikProfileeeeeeeeeeees as $profile): ?>
                        <option value="<?php echo htmlspecialchars($profile['name']); ?>">
                            <?php echo htmlspecialchars($profile['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="profile_info_isolir" class="profile-info-display" style="margin-top: 8px; font-size: 0.85rem; padding: 8px; border-radius: 6px; background: rgba(255,150,0,0.05); border: 1px dashed rgba(255,150,0,0.2); display: none;"></div>
                <small style="color: var(--text-muted);">Profileeeeeeeeeeee when customer hasn't paid</small>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Additional description (optional)"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Package
        </button>
    </form>
</div>

<!-- Packages Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Package List</h3>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Package Name</th>
                <th>Price</th>
                <th>Normal Profileeeeeeeeeeee</th>
                <th>Isolation Profileeeeeeeeeeee</th>
                <th>Customers</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($packages)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                        No packages registered yet
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($packages as $pkg): ?>
                <tr>
                    <td data-label="Package Name">
                        <strong><?php echo htmlspecialchars($pkg['name']); ?></strong>
                        <?php if ($pkg['description']): ?>
                            <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($pkg['description']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td data-label="Price">
                        <strong style="color: var(--neon-green);">
                            <?php echo formatCurrency($pkg['price']); ?>
                        </strong>
                    </td>
                    <td data-label="Profileeeeeeeeeeee Normal">
                        <span class="badge badge-success"><?php echo htmlspecialchars($pkg['profile_normal']); ?></span>
                    </td>
                    <td data-label="Isolation Profileeeeeeeeeeee">
                        <span class="badge badge-warning"><?php echo htmlspecialchars($pkg['profile_isolir']); ?></span>
                    </td>
                    <td data-label="Customers"><?php echo $pkg['customer_count']; ?> customers</td>
                    <td data-label="Action">
                        <button class="btn btn-secondary btn-sm" onclick="editPackage(<?php echo $pkg['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="deletePackage(<?php echo $pkg['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Package Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; overflow-y: auto; padding: 40px 0;">
    <div class="card" style="width: 500px; max-width: 90%; margin: 0 auto; position: relative;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Edit Package</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editForm" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="package_id" id="edit_package_id">
            
            <div class="form-group">
                <label class="form-label">Package Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Price per Month</label>
                <input type="number" name="price" id="edit_price" class="form-control" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label class="form-label">Profileeeeeeeeeeee Normal</label>
                    <select name="profile_normal" id="edit_profile_normal" class="form-control" required style="color: var(--text-primary); background: var(--bg-card);">
                        <?php foreach ($mikrotikProfileeeeeeeeeeees as $profile): ?>
                            <option value="<?php echo htmlspecialchars($profile['name']); ?>">
                                <?php echo htmlspecialchars($profile['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="edit_profile_info_normal" class="profile-info-display" style="margin-top: 8px; font-size: 0.85rem; padding: 8px; border-radius: 6px; background: rgba(0,255,255,0.05); border: 1px dashed rgba(0,255,255,0.2); display: none;"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Isolation Profileeeeeeeeeeee</label>
                    <select name="profile_isolir" id="edit_profile_isolir" class="form-control" required style="color: var(--text-primary); background: var(--bg-card);">
                        <?php foreach ($mikrotikProfileeeeeeeeeeees as $profile): ?>
                            <option value="<?php echo htmlspecialchars($profile['name']); ?>">
                                <?php echo htmlspecialchars($profile['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="edit_profile_info_isolir" class="profile-info-display" style="margin-top: 8px; font-size: 0.85rem; padding: 8px; border-radius: 6px; background: rgba(255,150,0,0.05); border: 1px dashed rgba(255,150,0,0.2); display: none;"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const packagesData = <?php echo json_encode($packages); ?>;
const mikrotikProfileeeeeeeeeeees = <?php echo json_encode($mikrotikProfileeeeeeeeeeees); ?>;

function updateProfileeeeeeeeeeeeInfo(selectId, displayId) {
    const select = document.getElementById(selectId);
    const display = document.getElementById(displayId);
    const profileName = select.value;
    
    if (!profileName || !mikrotikProfileeeeeeeeeeees) {
        display.style.display = 'none';
        return;
    }
    
    // Find profile in our list
    const profile = mikrotikProfileeeeeeeeeeees.find(p => p.name === profileName);
    
    if (profile) {
        let html = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px;">';
        
        // Key fields to display
        if (profile['rate-limit']) {
            html += `<div><strong><i class="fas fa-tachometer-alt"></i> Limit:</strong> ${profile['rate-limit']}</div>`;
        }
        if (profile['local-address']) {
            html += `<div><strong><i class="fas fa-server"></i> Local:</strong> ${profile['local-address']}</div>`;
        }
        if (profile['remote-address']) {
            html += `<div><strong><i class="fas fa-globe"></i> Remote Pool:</strong> ${profile['remote-address']}</div>`;
        }
        if (profile['session-timeout']) {
            html += `<div><strong><i class="fas fa-clock"></i> Timeout:</strong> ${profile['session-timeout']}</div>`;
        }
        if (profile['only-one']) {
            html += `<div><strong><i class="fas fa-user-lock"></i> Only One:</strong> ${profile['only-one']}</div>`;
        }
        
        html += '</div>';
        
        // If no key fields found, show "General profile info"
        if (html === '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px;"></div>') {
            html = `<div><i class="fas fa-info-circle"></i> Profileeeeeeeeeeee: <strong>${profile.name}</strong></div>`;
        }
        
        display.innerHTML = html;
        display.style.display = 'block';
    } else {
        display.style.display = 'none';
    }
}

// Initial update for Add form
document.addEventListener('DOMContentLoaded', function() {
    updateProfileeeeeeeeeeeeInfo('profile_normal', 'profile_info_normal');
    updateProfileeeeeeeeeeeeInfo('profile_isolir', 'profile_info_isolir');
});

// Event listeners for Add form
document.getElementById('profile_normal').addEventListener('change', () => updateProfileeeeeeeeeeeeInfo('profile_normal', 'profile_info_normal'));
document.getElementById('profile_isolir').addEventListener('change', () => updateProfileeeeeeeeeeeeInfo('profile_isolir', 'profile_info_isolir'));

// Event listeners for Edit modal
document.getElementById('edit_profile_normal').addEventListener('change', () => updateProfileeeeeeeeeeeeInfo('edit_profile_normal', 'edit_profile_info_normal'));
document.getElementById('edit_profile_isolir').addEventListener('change', () => updateProfileeeeeeeeeeeeInfo('edit_profile_isolir', 'edit_profile_info_isolir'));

function editPackage(id) {
    const pkg = packagesData.find(p => p.id == id);
    if (!pkg) {
        alert('Package not found!');
        return;
    }
    
    document.getElementById('edit_package_id').value = pkg.id;
    document.getElementById('edit_name').value = pkg.name || '';
    document.getElementById('edit_price').value = pkg.price || '';
    document.getElementById('edit_profile_normal').value = pkg.profile_normal || '';
    document.getElementById('edit_profile_isolir').value = pkg.profile_isolir || '';
    document.getElementById('edit_description').value = pkg.description || '';
    
    // Update profile info in modal
    updateProfileeeeeeeeeeeeInfo('edit_profile_normal', 'edit_profile_info_normal');
    updateProfileeeeeeeeeeeeInfo('edit_profile_isolir', 'edit_profile_info_isolir');
    
    document.getElementById('editForm').action = 'packages.php';
    document.getElementById('editModal').style.display = 'flex';
}

function deletePackage(id) {
    const pkg = packagesData.find(p => p.id == id);
    if (!pkg) return;
    
    if (confirm('Are you sure you want to delete package "' + pkg.name + '"?\n\nCustomers using this package will be affected!')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="package_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
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
