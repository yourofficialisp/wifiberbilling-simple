<?php
/**
 * Export Hotspot Users to MikroTik Script (.rsc)
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Export to MikroTik Script';

// Get Data
$users = mikrotikGetHotspotUsers();
$profiles = mikrotikGetHotspotProfileeeeeeeeeeees();

// Handle Export
if (isset($_GET['download'])) {
    $profile_filter = $_GET['profile'] ?? 'all';
    $server_filter = $_GET['server'] ?? 'all';

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="hotspot_users_' . date('Ymd_His') . '.rsc"');

    echo "# Hotspot Users Export from WiFiber Billing Application\r\n";
    echo "# Date: " . date('Y-m-d H:i:s') . "\r\n\r\n";
    echo "/ip hotspot user\r\n";

    foreach ($users as $u) {
        if ($profile_filter !== 'all' && ($u['profile'] ?? '') !== $profile_filter)
            continue;
        if ($server_filter !== 'all' && ($u['server'] ?? '') !== $server_filter)
            continue;

        $name = $u['name'] ?? '';
        $pass = $u['password'] ?? '';
        $prof = $u['profile'] ?? 'default';
        $comment = $u['comment'] ?? '';
        $limit_uptime = $u['limit-uptime'] ?? '';

        $cmd = "add name=\"" . $name . "\" password=\"" . $pass . "\" profile=\"" . $prof . "\"";
        if ($comment)
            $cmd .= " comment=\"" . $comment . "\"";
        if ($limit_uptime)
            $cmd .= " limit-uptime=\"" . $limit_uptime . "\"";

        echo $cmd . "\r\n";
    }
    exit;
}

ob_start();
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-export"></i> Export Users (.rsc)</h3>
    </div>
    <div class="card-body">
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            Use this feature to export hotspot user list to MikroTik script format (.rsc).
            This script can be imported directly through MikroTik Terminal or WinBox.
        </p>

        <form method="GET">
            <input type="hidden" name="download" value="1">

            <div class="form-group">
                <label class="form-label">Filter Profileeeeeeeeeeee</label>
                <select name="profile" class="form-control">
                    <option value="all">All Profileeeeeeeeeeees</option>
                    <?php foreach ($profiles as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['name']); ?>">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Filter Server</label>
                <select name="server" class="form-control">
                    <option value="all">All Servers</option>
                    <option value="all">Hotspot1</option> <!-- Static as example, or fetch from MT -->
                </select>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <button type="submit" class="btn btn-primary btn-lg" style="padding: 12px 30px;">
                    <i class="fas fa-download"></i> Download .rsc File
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
