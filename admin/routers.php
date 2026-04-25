<?php
/**
 * Router Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Router Management';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('routers.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $data = [
            'name' => $_POST['name'],
            'host' => $_POST['host'],
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'port' => (int) ($_POST['port'] ?: 8728),
            'description' => $_POST['description'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        if ($data['is_active']) {
            query("UPDATE routers SET is_active = 0");
        }

        if ($action === 'add') {
            insert('routers', $data);
            setFlash('success', 'Router successfully added.');
        } else {
            $id = $_POST['id'];
            update('routers', $data, "id = ?", [$id]);
            setFlash('success', 'Router successfully updated.');
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        query("DELETE FROM routers WHERE id = ?", [$id]);
        setFlash('success', 'Router successfully deleted.');
    } elseif ($action === 'switch') {
        $id = $_POST['id'];
        $_SESSION['active_router_id'] = $id;
        setFlash('success', 'Successfully switched to another router.');
    }

    redirect('routers.php');
}

$routers = getAllRouters();

ob_start();
?>

<!-- Add Router Button -->
<div style="margin-bottom: 20px; text-align: right;">
    <button onclick="showAddModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Router</button>
</div>

<!-- Routers Table -->
<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Host</th>
                    <th>Username</th>
                    <th>Port</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($routers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No routers added yet
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($routers as $r): ?>
                        <tr
                            style="<?php echo ($_SESSION['active_router_id'] ?? '') == $r['id'] ? 'background: rgba(0, 245, 255, 0.05);' : ''; ?>">
                            <td data-label="Name">
                                <strong>
                                    <?php echo htmlspecialchars($r['name']); ?>
                                </strong>
                                <?php if (($_SESSION['active_router_id'] ?? '') == $r['id']): ?>
                                    <span class="badge badge-info" style="margin-left: 5px;">Active</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Host"><code><?php echo htmlspecialchars($r['host']); ?></code></td>
                            <td data-label="Username">
                                <?php echo htmlspecialchars($r['username']); ?>
                            </td>
                            <td data-label="Port">
                                <?php echo htmlspecialchars($r['port']); ?>
                            </td>
                            <td data-label="Status">
                                <?php if ($r['is_active']): ?>
                                    <span class="badge badge-success">Default</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Action">
                                <div style="display: flex; gap: 5px;">
                                    <?php if (($_SESSION['active_router_id'] ?? '') != $r['id']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="switch">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-play"></i>
                                                Switch</button>
                                        </form>
                                    <?php endif; ?>
                                    <button onclick='editRouter(<?php echo json_encode($r); ?>)'
                                        class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this router?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="routerModal" class="modal"
    style="display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.8); overflow-y: auto; padding: 20px 0;">
    <div class="card" style="width: 500px; max-width: 95%; margin: 0 auto; position: relative;">
        <div class="card-header">
            <h3 class="card-title" id="modalTitle">Add Router</h3>
            <button onclick="closeModal()"
                style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST" id="routerForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="routerId">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label class="form-label">Router Name</label>
                    <input type="text" name="name" id="routerName" class="form-control" required
                        placeholder="Example: Main Router">
                </div>

                <div class="form-group">
                    <label class="form-label">Host (IP/URL)</label>
                    <input type="text" name="host" id="routerHost" class="form-control" required
                        placeholder="192.168.1.1">
                </div>

                <div class="row" style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="routerUser" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="routerPass" class="form-control">
                    </div>
                </div>

                <div class="row" style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">API Port</label>
                        <input type="number" name="port" id="routerPort" class="form-control" value="8728">
                    </div>
                    <div class="form-group" style="flex:1; display:flex; align-items:flex-end; padding-bottom:10px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="is_active" id="routerActive"> Set as Default
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="routerDesc" class="form-control" rows="2"></textarea>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showAddModal() {
        document.getElementById('modalTitle').innerText = 'Add Router';
        document.getElementById('formAction').value = 'add';
        document.getElementById('routerForm').reset();
        document.getElementById('routerModal').style.display = 'block';
    }

    function editRouter(data) {
        document.getElementById('modalTitle').innerText = 'Edit Router';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('routerId').value = data.id;
        document.getElementById('routerName').value = data.name;
        document.getElementById('routerHost').value = data.host;
        document.getElementById('routerUser').value = data.username;
        document.getElementById('routerPass').value = data.password;
        document.getElementById('routerPort').value = data.port;
        document.getElementById('routerActive').checked = data.is_active == 1;
        document.getElementById('routerDesc').value = data.description || '';
        document.getElementById('routerModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('routerModal').style.display = 'none';
    }

    // Close on outside click
    window.onclick = function (event) {
        if (event.target == document.getElementById('routerModal')) {
            closeModal();
        }
    }
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
