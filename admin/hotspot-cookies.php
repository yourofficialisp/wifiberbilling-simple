<?php
/**
 * Hotspot Cookies Viewer
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Hotspot Cookies';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (mikrotikDeleteHotspotCookie($id)) {
            setFlash('success', 'Cookie successfully deleted.');
        } else {
            setFlash('error', 'Failed to delete cookie.');
        }
    }
    redirect('hotspot-cookies.php');
}

// Get Data
$cookies = mikrotikGetHotspotCookies();
$totalCookies = count($cookies);

ob_start();
?>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-cookie-bite"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo $totalCookies; ?>
            </h3>
            <p>Total Cookies</p>
        </div>
    </div>
</div>

<!-- Cookies Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Hotspot Cookie List</h3>
        <input type="text" id="searchCookie" class="form-control" placeholder="Search user/MAC/IP..."
            style="width: 250px;">
    </div>
    <div class="table-responsive">
        <table class="data-table" id="cookieTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>MAC Address</th>
                    <th>IP Address</th>
                    <th>Expires In</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cookies)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No active hotspot cookies
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cookies as $c): ?>
                        <tr>
                            <td><strong>
                                    <?php echo htmlspecialchars($c['user'] ?? '-'); ?>
                                </strong></td>
                            <td><code><?php echo htmlspecialchars($c['mac-address'] ?? '-'); ?></code></td>
                            <td><small>
                                    <?php echo htmlspecialchars($c['address'] ?? '-'); ?>
                                </small></td>
                            <td>
                                <?php echo htmlspecialchars($c['expires-in'] ?? '-'); ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this cookie?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($c['.id']); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i>
                                        Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('searchCookie').addEventListener('input', function (e) {
        const search = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#cookieTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
