<?php
/**
 * Hotspot User Edit & Details
 */

require_once '../includes/auth.php';
requireAdminLogin();

$name = $_GET['name'] ?? '';
if (empty($name))
    redirect('hotspot-user.php');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($action === 'edit') {
        $data = [
            'name' => sanitize($_POST['name']),
            'password' => sanitize($_POST['password']),
            'profile' => sanitize($_POST['profile']),
            'limit-uptime' => sanitize($_POST['timelimit']),
            'limit-bytes-total' => sanitize($_POST['datalimit']),
            'comment' => sanitize($_POST['comment'])
        ];

        if (mikrotikUpdateHotspotUser($id, $data)) {
            setFlash('success', "User successfully updated.");
        } else {
            setFlash('error', "Failed to update user.");
        }
        redirect("hotspot-user-edit.php?name=" . urlencode($data['name']));
    }
}

// Get User Data
$users = mikrotikGetHotspotUsers();
$user = null;
foreach ($users as $u) {
    if ($u['name'] === $name) {
        $user = $u;
        break;
    }
}

if (!$user) {
    setFlash('error', "User not found.");
    redirect('hotspot-user.php');
}

// Get Active Session
$activeSessions = mikrotikGetHotspotActive();
$active = null;
foreach ($activeSessions as $a) {
    if ($a['user'] === $name) {
        $active = $a;
        break;
    }
}

$hotspotProfileeeeeeeeeeees = mikrotikGetHotspotProfileeeeeeeeeeees();
$pageTitle = 'Edit User: ' . $name;

ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <a href="hotspot-user.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="printVoucher()"><i class="fas fa-print"></i> Print</button>
        <button class="btn btn-success" onclick="shareWA()"><i class="fab fa-whatsapp"></i> Share</button>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px;">
    <!-- Edit Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Voucher Information</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['.id']); ?>">

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="name" class="form-control"
                    value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="text" name="password" class="form-control"
                    value="<?php echo htmlspecialchars($user['password'] ?? ''); ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Profileeeeeeeeeeee</label>
                    <select name="profile" class="form-control">
                        <?php foreach ($hotspotProfileeeeeeeeeeees as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['name']); ?>" <?php echo ($user['profile'] === $p['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Comment</label>
                    <input type="text" name="comment" class="form-control"
                        value="<?php echo htmlspecialchars($user['comment'] ?? ''); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Time Limit</label>
                    <input type="text" name="timelimit" class="form-control"
                        value="<?php echo htmlspecialchars($user['limit-uptime'] ?? ''); ?>" placeholder="Example: 1d">
                </div>
                <div class="form-group">
                    <label class="form-label">Data Limit</label>
                    <input type="text" name="datalimit" class="form-control"
                        value="<?php echo htmlspecialchars($user['limit-bytes-total'] ?? ''); ?>"
                        placeholder="Example: 1000M">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                <button type="button" class="btn btn-danger" onclick="deleteUser()"><i class="fas fa-trash"></i>
                    Delete</button>
            </div>
        </form>
    </div>

    <!-- Status & QR -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Live Status</h3>
            </div>
            <div style="text-align: center; margin-bottom: 20px;">
                <div
                    style="font-size: 2.5rem; font-weight: 800; color: <?php echo $active ? 'var(--neon-green)' : 'var(--text-muted)'; ?>">
                    <?php echo $active ? 'ONLINE' : 'OFFLINE'; ?>
                </div>
                <?php if ($active): ?>
                    <small style="color: var(--text-secondary);">IP:
                        <?php echo $active['address']; ?>
                    </small>
                <?php endif; ?>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div class="stat-info">
                    <p>Uptime</p>
                    <strong>
                        <?php echo $user['uptime'] ?: '0s'; ?>
                    </strong>
                </div>
                <div class="stat-info">
                    <p>Data Used</p>
                    <strong>
                        <?php echo formatBytes(($user['bytes-in'] ?? 0) + ($user['bytes-out'] ?? 0)); ?>
                    </strong>
                </div>
                <?php if ($active): ?>
                    <div class="stat-info">
                        <p>Bytes In/Out</p>
                        <strong><i class="fas fa-arrow-down" style="color: var(--neon-cyan);"></i>
                            <?php echo formatBytes($active['bytes-in']); ?> / <i class="fas fa-arrow-up"
                                style="color: var(--neon-pink);"></i>
                            <?php echo formatBytes($active['bytes-out']); ?>
                        </strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="text-align: center;">
            <div id="qrcode"
                style="margin: 0 auto 15px; background: white; padding: 10px; width: 170px; height: 170px; display: flex; align-items: center; justify-content: center;">
            </div>
            <small style="color: var(--text-muted);">Scan for Automatic Login</small>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" action="hotspot-user.php" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    window.onload = function () {
        new QRCode(document.getElementById("qrcode"), {
            text: "http://hotspot.net/login?username=<?php echo urlencode($user['name']); ?>&password=<?php echo urlencode($user['password'] ?? ''); ?>",
            width: 150,
            height: 150,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    };

    function deleteUser() {
        if (confirm('Are you sure you want to delete this user?')) {
            document.getElementById('deleteForm').submit();
        }
    }

    function shareWA() {
        const text = `*HOTSPOT VOUCHER DETAILS*\n\n` +
            `Username: *<?php echo $user['name']; ?>*\n` +
            `Password: *<?php echo $user['password'] ?? ''; ?>*\n` +
            `Profileeeeeeeeeeee: <?php echo $user['profile']; ?>\n` +
            `Price: <?php echo isset($user['comment']) ? str_replace('parent:', '', $user['comment']) : ''; ?>\n\n` +
            `_Thank you for subscribing!_`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    }

    function printVoucher() {
        window.print();
    }
</script>

<style>
    @media print {

        .btn,
        .sidebar,
        .header,
        .card-header,
        form,
        .stat-info {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            padding: 0 !important;
        }

        #qrcode {
            display: block !important;
            margin: 0 auto !important;
        }

        .card {
            border: none !important;
            background: white !important;
            color: black !important;
        }
    }
</style>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
