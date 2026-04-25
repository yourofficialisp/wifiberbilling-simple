<?php
/**
 * Sales User Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Sales Management';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $phone = sanitize($_POST['phone']);
        $voucher_mode = sanitize($_POST['voucher_mode'] ?? 'mix');
        $voucher_length = (int) $_POST['voucher_length'];
        $voucher_type = sanitize($_POST['voucher_type'] ?? 'upp');
        $bill_discount = (float) $_POST['bill_discount'];
        
        // Check if username exists
        if (fetchOne("SELECT id FROM sales_users WHERE username = ?", [$username])) {
            setFlash('error', 'Username already used.');
        } else {
            $data = [
                'name' => $name,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $phone,
                'deposit_balance' => 0,
                'status' => 'active',
                'voucher_mode' => $voucher_mode,
                'voucher_length' => $voucher_length,
                'voucher_type' => $voucher_type,
                'bill_discount' => $bill_discount
            ];
            
            if (insert('sales_users', $data)) {
                setFlash('success', 'Sales successfully added.');
            } else {
                setFlash('error', 'Failed to add sales.');
            }
        }
        redirect('sales-users.php');
    }

    if ($action === 'edit') {
        $id = (int) $_POST['id'];
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $status = sanitize($_POST['status']);
        $voucher_mode = sanitize($_POST['voucher_mode'] ?? 'mix');
        $voucher_length = (int) $_POST['voucher_length'];
        $voucher_type = sanitize($_POST['voucher_type'] ?? 'upp');
        $bill_discount = (float) $_POST['bill_discount'];
        
        $data = [
            'name' => $name,
            'phone' => $phone,
            'status' => $status,
            'voucher_mode' => $voucher_mode,
            'voucher_length' => $voucher_length,
            'voucher_type' => $voucher_type,
            'bill_discount' => $bill_discount
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        if (update('sales_users', $data, 'id = ?', [$id])) {
            setFlash('success', 'Sales data successfully updated.');
        } else {
            setFlash('error', 'Failed to update data.');
        }
        redirect('sales-users.php');
    }

    if ($action === 'topup') {
        $id = (int) $_POST['id'];
        $amount = (float) $_POST['amount'];
        
        if ($amount <= 0) {
            setFlash('error', 'Topup amount must be greater than 0.');
        } else {
            $sales = fetchOne("SELECT * FROM sales_users WHERE id = ?", [$id]);
            if ($sales) {
                $newBalance = $sales['deposit_balance'] + $amount;
                
                // Update Balance
                update('sales_users', ['deposit_balance' => $newBalance], 'id = ?', [$id]);
                
                // Record Transaction
                insert('sales_transactions', [
                    'sales_user_id' => $id,
                    'type' => 'deposit',
                    'amount' => $amount,
                    'description' => 'Topup by Admin: ' . ($_SESSION['admin']['username'] ?? 'Admin'),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                setFlash('success', 'Topup successful. Current balance: ' . formatCurrency($newBalance));
            } else {
                setFlash('error', 'Sales not found.');
            }
        }
        redirect('sales-users.php');
    }
    
    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        // Delete user (transactions and profiles will cascade if configured, but let's be safe)
        // Check FK constraints. createDatabaseTables used ON DELETE CASCADE for profiles and transactions.
        if (delete('sales_users', 'id = ?', [$id])) {
            setFlash('success', 'Sales successfully deleted.');
        } else {
            setFlash('error', 'Failed to delete sales.');
        }
        redirect('sales-users.php');
    }
}

$salesUsers = fetchAll("SELECT * FROM sales_users ORDER BY created_at DESC");

ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Sales List</h3>
        <button type="button" class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Add Sales
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="salesTable" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 25%;">Name</th>
                        <th style="width: 20%;">Username</th>
                        <th style="width: 15%;">Balance</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 25%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesUsers as $s): ?>
                    <tr>
                        <td style="padding: 15px;">
                            <strong style="font-size: 1.1em;"><?php echo htmlspecialchars($s['name']); ?></strong><br>
                            <small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($s['phone']); ?></small>
                        </td>
                        <td style="padding: 15px; vertical-align: middle;"><?php echo htmlspecialchars($s['username']); ?></td>
                        <td class="text-success font-weight-bold" style="padding: 15px; vertical-align: middle; font-size: 1.1em;"><?php echo formatCurrency($s['deposit_balance']); ?></td>
                        <td style="padding: 15px; vertical-align: middle;">
                            <?php if ($s['status'] === 'active'): ?>
                                <span class="badge badge-success" style="padding: 8px 12px; font-size: 0.9em;">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger" style="padding: 8px 12px; font-size: 0.9em;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; vertical-align: middle;">
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-sm btn-info" onclick='showEditModal(<?php echo json_encode($s); ?>)' title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick='showTopupModal(<?php echo json_encode($s); ?>)' title="Topup Balance">
                                    <i class="fas fa-wallet"></i>
                                </button>
                                <a href="sales-profiles.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-warning" title="Manage Packages">
                                    <i class="fas fa-tags"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name']); ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div class="modal-content" style="background: var(--bg-card); margin: 5% auto; padding: 20px; width: 90%; max-width: 500px; border-radius: 10px; border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto;">
        <h3>Add New Sales</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control">
            </div>
            
            <hr>
            <h5 style="color: var(--neon-cyan);">Voucher Settings</h5>
            <div class="form-group">
                <label>Character Format</label>
                <select name="voucher_mode" class="form-control">
                    <option value="mix">Mixed (Numbers & Letters)</option>
                    <option value="num">Numbers Only</option>
                    <option value="alp">Letters Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Character Length</label>
                <select name="voucher_length" class="form-control">
                    <?php for($i=4; $i<=10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i==6 ? 'selected' : ''; ?>><?php echo $i; ?> Characters</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Voucher Type</label>
                <select name="voucher_type" class="form-control">
                    <option value="upp">Username = Password</option>
                    <option value="up">Username & Password Different</option>
                </select>
            </div>
            
            <hr>
            <h5 style="color: var(--neon-cyan);">Sales Profit</h5>
            <div class="form-group">
                <label>Discount per Monthly Bill (Rp)</label>
                <input type="number" name="bill_discount" class="form-control" value="0" min="0">
                <small class="text-muted">Amount of sales balance saved when paying bills. Example: 5000</small>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div class="modal-content" style="background: var(--bg-card); margin: 5% auto; padding: 20px; width: 90%; max-width: 500px; border-radius: 10px; border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto;">
        <h3>Edit Sales</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" id="edit_phone" class="form-control">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <hr>
            <h5 style="color: var(--neon-cyan);">Voucher Settings</h5>
            <div class="form-group">
                <label>Character Format</label>
                <select name="voucher_mode" id="edit_voucher_mode" class="form-control">
                    <option value="mix">Mixed (Numbers & Letters)</option>
                    <option value="num">Numbers Only</option>
                    <option value="alp">Letters Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Character Length</label>
                <select name="voucher_length" id="edit_voucher_length" class="form-control">
                    <?php for($i=4; $i<=10; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Characters</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Voucher Type</label>
                <select name="voucher_type" id="edit_voucher_type" class="form-control">
                    <option value="upp">Username = Password</option>
                    <option value="up">Username & Password Different</option>
                </select>
            </div>

            <hr>
            <h5 style="color: var(--neon-cyan);">Sales Profit</h5>
            <div class="form-group">
                <label>Discount per Monthly Bill (Rp)</label>
                <input type="number" name="bill_discount" id="edit_bill_discount" class="form-control" value="0" min="0">
                <small class="text-muted">Amount of sales balance saved when paying bills. Example: 5000</small>
            </div>

            <div class="form-group">
                <label>Reset Password (Leave blank if not changing)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Topup Modal -->
<div id="topupModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div class="modal-content" style="background: var(--bg-card); margin: 10% auto; padding: 20px; width: 90%; max-width: 400px; border-radius: 10px; border: 1px solid var(--border-color);">
        <h3>Topup Balance</h3>
        <p>Sales: <strong id="topup_name"></strong></p>
        <form method="POST">
            <input type="hidden" name="action" value="topup">
            <input type="hidden" name="id" id="topup_id">
            <div class="form-group">
                <label>Topup Amount (Rp)</label>
                <input type="number" name="amount" class="form-control" required min="1">
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('topupModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Topup Now</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function showEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_phone').value = data.phone;
    document.getElementById('edit_status').value = data.status;
    
    // Set Voucher Settings (Default values if null)
    document.getElementById('edit_voucher_mode').value = data.voucher_mode || 'mix';
    document.getElementById('edit_voucher_length').value = data.voucher_length || 6;
    document.getElementById('edit_voucher_type').value = data.voucher_type || 'upp';
    document.getElementById('edit_bill_discount').value = data.bill_discount || 0;
    
    document.getElementById('editModal').style.display = 'block';
}

function showTopupModal(data) {
    document.getElementById('topup_id').value = data.id;
    document.getElementById('topup_name').textContent = data.name;
    document.getElementById('topupModal').style.display = 'block';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete sales ' + name + '? All transaction history will be deleted!')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
    }
}
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>
