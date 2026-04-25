<?php
/**
 * Technician Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Technician Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('technicians.php');
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = sanitize($_POST['username']);
                
                // Validate username format
                if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                    setFlash('error', 'Username minimum 3 characters, only letters, numbers, and underscore');
                    redirect('technicians.php');
                }
                
                // Validate password length
                if (strlen($_POST['password']) < 6) {
                    setFlash('error', 'Password minimum 6 characters');
                    redirect('technicians.php');
                }
                
                // Check if username exists
                $existing = fetchOne("SELECT id FROM technician_users WHERE username = ?", [$username]);
                if ($existing) {
                    setFlash('error', 'Username already used');
                    redirect('technicians.php');
                }
                
                $data = [
                    'name' => sanitize($_POST['name']),
                    'username' => $username,
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'phone' => sanitize($_POST['phone']),
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                if (insert('technician_users', $data)) {
                    setFlash('success', 'Technician successfully added');
                    logActivity('ADD_TECHNICIAN', "Name: {$data['name']}");
                } else {
                    setFlash('error', 'Failed to add technician');
                }
                redirect('technicians.php');
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $data = [
                    'name' => sanitize($_POST['name']),
                    'phone' => sanitize($_POST['phone']),
                    'status' => sanitize($_POST['status']),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Update password only if provided
                if (!empty($_POST['password'])) {
                    if (strlen($_POST['password']) < 6) {
                        setFlash('error', 'Password minimum 6 characters');
                        redirect('technicians.php');
                    }
                    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                if (update('technician_users', $data, 'id = ?', [$id])) {
                    setFlash('success', 'Technician data successfully updated');
                    logActivity('UPDATE_TECHNICIAN', "ID: {$id}");
                } else {
                    setFlash('error', 'Failed to update technician');
                }
                redirect('technicians.php');
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Check dependencies
                $activeTickets = fetchOne("SELECT COUNT(*) as total FROM trouble_tickets WHERE technician_id = ? AND status != 'resolved'", [$id]);
                if ($activeTickets['total'] > 0) {
                    setFlash('error', 'This technician still has active tasks. Cannot be deleted.');
                    redirect('technicians.php');
                }
                
                if (delete('technician_users', 'id = ?', [$id])) {
                    setFlash('success', 'Technician successfully deleted');
                    logActivity('DELETE_TECHNICIAN', "ID: {$id}");
                } else {
                    setFlash('error', 'Failed to delete technician');
                }
                redirect('technicians.php');
                break;
        }
    }
}

// Get technicians
$technicians = fetchAll("
    SELECT t.*, 
    (SELECT COUNT(*) FROM trouble_tickets WHERE technician_id = t.id AND status != 'resolved') as active_tickets,
    (SELECT COUNT(*) FROM customers WHERE installed_by = t.id AND status = 'registered') as pending_installs
    FROM technician_users t 
    ORDER BY t.name ASC
");

// Start buffering
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tools"></i> Technician List</h3>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Technician
        </button>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Workload</th>
                <th>Last Login</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($technicians)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                        No technician data yet
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($technicians as $t): ?>
                <tr>
                    <td data-label="Name"><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                    <td data-label="Username"><?php echo htmlspecialchars($t['username']); ?></td>
                    <td data-label="Phone"><?php echo htmlspecialchars($t['phone']); ?></td>
                    <td data-label="Status">
                        <?php if ($t['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Workload">
                        <span class="badge badge-warning" title="Trouble Tickets"><?php echo $t['active_tickets']; ?> Tickets</span>
                        <span class="badge badge-info" title="New Installation"><?php echo $t['pending_installs']; ?> PSB</span>
                    </td>
                    <td data-label="Last Login"><?php echo $t['last_login'] ? formatDate($t['last_login']) : '-'; ?></td>
                    <td data-label="Action">
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-primary btn-sm" onclick='openEditModal(<?php echo json_encode($t); ?>)' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($t['active_tickets'] == 0 && $t['pending_installs'] == 0): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this technician?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled title="Still has active tasks">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                            <?php if ($t['phone']): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $t['phone'])); ?>" target="_blank" class="btn btn-success btn-sm" title="Chat on WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Technician</h3>
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required minlength="3" placeholder="Technician name">
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone / WhatsApp</label>
                <input type="tel" name="phone" class="form-control" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,15}" title="Enter 10-15 digit phone number">
            </div>
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required minlength="3" pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscore" autocomplete="username">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password" placeholder="Minimum 6 characters">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">Save</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Technician</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" id="edit_username" class="form-control" readonly style="background: rgba(255,255,255,0.05);">
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone / WhatsApp</label>
                <input type="tel" name="phone" id="edit_phone" class="form-control" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,15}" title="Enter 10-15 digit phone number">
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="edit_status" class="form-control" style="background: var(--bg-card); color: var(--text-primary);">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">New Password (Leave blank if not changing)</label>
                <input type="password" name="password" class="form-control" placeholder="******" minlength="6" autocomplete="new-password">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">Save Changes</button>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addModal').style.display = 'flex';
    }
    
    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }
    
    function openEditModal(tech) {
        document.getElementById('edit_id').value = tech.id;
        document.getElementById('edit_name').value = tech.name;
        document.getElementById('edit_username').value = tech.username;
        document.getElementById('edit_phone').value = tech.phone;
        document.getElementById('edit_status').value = tech.status;
        
        document.getElementById('editModal').style.display = 'flex';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList && event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function(modal) {
                modal.style.display = 'none';
            });
        }
    });
</script>

<?php 
$content = ob_get_clean();
require_once '../includes/layout.php';
?>
