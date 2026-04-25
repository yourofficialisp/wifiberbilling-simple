<?php
/**
 * Hotspot Active Sessions
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Hotspot Active Sessions';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'kick') {
        $user = $_POST['user'] ?? '';
        if (mikrotikKickHotspotUser($user)) {
            setFlash('success', "User $user successfully kicked.");
        } else {
            setFlash('error', "Failed to kick user $user.");
        }
    }
    redirect('hotspot-active.php');
}

// Get Data
$activeSessions = mikrotikGetHotspotActive();
$servers = mikrotikGetHotspotServers();

// Handle filter
$server_filter = $_GET['server'] ?? 'all';

if ($server_filter !== 'all') {
    $activeSessions = array_filter($activeSessions, function ($s) use ($server_filter) {
        return ($s['server'] ?? '') === $server_filter;
    });
}

$activeCount = count($activeSessions);

ob_start();
?>

<!-- Filter & Stats -->
<div
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div class="stats-grid" style="margin-bottom: 0; flex: 1;">
        <div class="stat-card" style="margin-bottom: 0; padding: 10px 20px;">
            <div class="stat-icon green" style="width: 35px; height: 35px;"><i class="fas fa-signal"
                    style="font-size: 0.9rem;"></i></div>
            <div class="stat-info">
                <h3 style="font-size: 1.2rem;">
                    <?php echo $activeCount; ?>
                </h3>
                <p style="font-size: 0.75rem;">Active Users</p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0; padding: 10px 20px; display: flex; align-items: center; gap: 15px;">
        <form method="GET" id="filterForm" style="display: flex; align-items: center; gap: 10px;">
            <label class="form-label" style="margin-bottom: 0; white-space: nowrap;">Filter Server:</label>
            <select name="server" class="form-control" onchange="this.form.submit()" style="width: 150px;">
                <option value="all">All Servers</option>
                <?php foreach ($servers as $srv): ?>
                    <option value="<?php echo htmlspecialchars($srv['name']); ?>" <?php echo $server_filter === $srv['name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($srv['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="searchActive" class="form-control" placeholder="Search user/IP..."
                style="width: 200px;">
        </form>
    </div>
</div>

<!-- Active Table -->
<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="activeTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>MAC Address</th>
                    <th>Uptime</th>
                    <th>Session Time</th>
                    <th>Server</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeSessions)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            <i class="fas fa-laptop" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                            No active users on this server
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeSessions as $s): ?>
                        <tr>
                            <td data-label="User"><strong>
                                    <?php echo htmlspecialchars($s['user'] ?? '-'); ?>
                                </strong></td>
                            <td data-label="IP Address"><code><?php echo htmlspecialchars($s['address'] ?? '-'); ?></code></td>
                            <td data-label="MAC Address"><small>
                                    <?php echo htmlspecialchars($s['mac-address'] ?? '-'); ?>
                                </small></td>
                            <td data-label="Uptime">
                                <?php echo htmlspecialchars($s['uptime'] ?? '0'); ?>
                            </td>
                            <td data-label="Session Time">
                                <?php echo htmlspecialchars($s['session-time-left'] ?? '∞'); ?>
                            </td>
                            <td data-label="Server"><span class="badge badge-info">
                                    <?php echo htmlspecialchars($s['server'] ?? '-'); ?>
                                </span></td>
                            <td data-label="Actions">
                                <form method="POST" action="hotspot-active.php" onsubmit="return confirm('Kick this user?')">
                                    <input type="hidden" name="action" value="kick">
                                    <input type="hidden" name="user" value="<?php echo htmlspecialchars($s['user'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i>
                                        Kick</button>
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
    document.getElementById('searchActive').addEventListener('input', function (e) {
        const search = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#activeTable tbody tr');
        rows.forEach(row => {
            const user = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            const ip = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const mac = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const match = user.includes(search) || ip.includes(search) || mac.includes(search);
            row.style.display = match ? '' : 'none';
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
