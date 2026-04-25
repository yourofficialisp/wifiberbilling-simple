<?php
/**
 * Hotspot User Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Hotspot Users';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        $qty = (int) $_POST['qty'];
        $server = sanitize($_POST['server'] ?? 'all');
        $userMode = sanitize($_POST['user_mode'] ?? 'up'); // u=p (username=password) or up (username & password)
        $length = (int) $_POST['length'];
        $prefix = sanitize($_POST['prefix'] ?? '');
        $profile = sanitize($_POST['profile']);
        $timelimit = sanitize($_POST['timelimit'] ?? '');
        $datalimit = sanitize($_POST['datalimit'] ?? '');
        $charMode = sanitize($_POST['char_mode'] ?? 'alphanumeric'); // alphanumeric, numeric, alpha

        $profilePrice = 0;
        $profileValidity = '';
        $profileSelling = 0;
        foreach ($hotspotProfileeeeeeeeeeees as $hp) {
            if ($hp['name'] === $profile) {
                $pData = parseMikhmonOnLogin($hp['on-login'] ?? '');
                $profilePrice = $pData['price'];
                $profileSelling = $pData['selling_price'];
                $profileValidity = $pData['validity'];
                break;
            }
        }
        // Use profile validity if user didn't specify a timelimit
        if (empty($timelimit) && $profileValidity !== '-') {
            $timelimit = $profileValidity;
        }
        // Add 'vc' comment for Mikhmon scheduler compatibility
        $mikhmonComment = 'vc-' . date('d.m.y') . '-' . $profile;

        $successCount = 0;
        $generatedVouchers = [];
        for ($i = 0; $i < $qty; $i++) {
            $user = $prefix . generateRandomString($length, $charMode);
            $pass = ($userMode === 'up') ? generateRandomString($length, $charMode) : $user;

            $extraData = [
                'server' => $server,
                'limit-uptime' => $timelimit,
                'limit-bytes-total' => $datalimit,
                'comment' => $mikhmonComment
            ];

            if (mikrotikAddHotspotUser($user, $pass, $profile, $extraData)) {
                $successCount++;
                // Record sale if price is set
                if ($profilePrice > 0) {
                    recordHotspotSale($user, $profile, $profilePrice, $profileSelling, $prefix);
                }
                // Store generated voucher for printing
                $generatedVouchers[] = [
                    'username' => $user,
                    'password' => $pass,
                    'profile' => $profile,
                    'price' => $profilePrice > 0 ? formatCurrency($profilePrice) : '-',
                    'validity' => $timelimit ?: '-'
                ];
            }
        }

        // Store generated vouchers in session for printing
        if (!empty($generatedVouchers)) {
            $_SESSION['generated_vouchers'] = $generatedVouchers;
        }

        setFlash('success', "Successfully generated $successCount vouchers.");
        redirect('hotspot-user.php');
    }

    if ($action === 'delete') {
        $name = $_POST['name'];
        if (mikrotikDeleteHotspotUser($name)) {
            setFlash('success', "User $name successfully deleted.");
        } else {
            setFlash('error', "Failed to delete user $name.");
        }
        redirect('hotspot-user.php');
    }

    if ($action === 'bulk_delete') {
        $names = $_POST['names'] ?? [];
        if (!empty($names)) {
            $successCount = 0;
            foreach ($names as $name) {
                if (mikrotikDeleteHotspotUser($name)) {
                    $successCount++;
                }
            }
            setFlash('success', "Successfully deleted $successCount selected users.");
        }
        redirect('hotspot-user.php');
    }
}

// Get Data
$hotspotUsers = mikrotikGetHotspotUsers();
$hotspotProfileeeeeeeeeeees = mikrotikGetHotspotProfileeeeeeeeeeees();
$activeUsers = mikrotikGetHotspotActive();
$activeUsernames = array_column($activeUsers, 'user');

// Build profile price lookup from on-login scripts (Mikhmon v3 style)
$profilePriceMap = [];
foreach ($hotspotProfileeeeeeeeeeees as $p) {
    $pData = parseMikhmonOnLogin($p['on-login'] ?? '');
    $profilePriceMap[$p['name'] ?? ''] = $pData;
}

$totalUsers = count($hotspotUsers);
$onlineCount = count($activeUsers);

// Extract unique values for filters
$filterServers = array_unique(array_filter(array_column($hotspotUsers, 'server')));
sort($filterServers);

$filterProfileeeeeeeeeeees = array_unique(array_filter(array_column($hotspotUsers, 'profile')));
sort($filterProfileeeeeeeeeeees);

$filterComments = array_unique(array_filter(array_column($hotspotUsers, 'comment')));
sort($filterComments);

ob_start();
?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo $totalUsers; ?>
            </h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-plug"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo $onlineCount; ?>
            </h3>
            <p>Online</p>
        </div>
    </div>
</div>

<!-- Mass Generator Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-magic"></i> Mass Generate</h3>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="generate">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="form-group">
                <label class="form-label">Qty</label>
                <input type="number" name="qty" class="form-control" value="10" required>
            </div>
            <div class="form-group">
                <label class="form-label">Server</label>
                <select name="server" class="form-control">
                    <option value="all">All</option>
                    <option value="hotspot1">Hotspot1</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">User Mode</label>
                <select name="user_mode" class="form-control">
                    <option value="up">Username & Password</option>
                    <option value="u=p">Username = Password</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Length</label>
                <input type="number" name="length" class="form-control" value="6">
            </div>
            <div class="form-group">
                <label class="form-label">Prefix</label>
                <input type="text" name="prefix" class="form-control" placeholder="ABC-">
            </div>
            <div class="form-group">
                <label class="form-label">Characters</label>
                <select name="char_mode" class="form-control">
                    <option value="alphanumeric">Letters & Numbers</option>
                    <option value="numeric">Numbers Only</option>
                    <option value="alpha">Letters Only</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Profileeeeeeeeeeee</label>
                <select name="profile" class="form-control" required>
                    <?php foreach ($hotspotProfileeeeeeeeeeees as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['name']); ?>">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Time Limit (1d, 12h)</label>
                <input type="text" name="timelimit" class="form-control" placeholder="Example: 1d">
            </div>
            <div class="form-group">
                <label class="form-label">Data Limit (MB/GB)</label>
                <input type="text" name="datalimit" class="form-control" placeholder="Example: 1000M">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
            <i class="fas fa-rocket"></i> Generate
        </button>
        <?php if (isset($_SESSION['generated_vouchers']) && !empty($_SESSION['generated_vouchers'])): ?>
        <button type="button" class="btn btn-success" style="margin-top: 10px;" onclick="printGeneratedVouchers()">
            <i class="fas fa-print"></i> Print Voucher
        </button>
        <?php endif; ?>
    </form>
</div>

<!-- User List Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Hotspot User List</h3>
    </div>
    <!-- Filter Toolbar -->
    <div style="display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 15px; background: var(--bg-secondary); border-bottom: 1px solid var(--border-color); align-items: center;">
        <div style="display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-server" style="color: var(--text-muted); font-size: 0.8rem;"></i>
            <select id="filterServer" class="form-control" style="width: auto; min-width: 110px; padding: 6px 10px; font-size: 0.85rem;">
                <option value="">All Servers</option>
                <?php foreach ($filterServers as $s): ?>
                    <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-id-badge" style="color: var(--text-muted); font-size: 0.8rem;"></i>
            <select id="filterProfileeeeeeeeeeee" class="form-control" style="width: auto; min-width: 120px; padding: 6px 10px; font-size: 0.85rem;">
                <option value="">All Profileeeeeeeeeeees</option>
                <?php foreach ($filterProfileeeeeeeeeeees as $p): ?>
                    <option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-tag" style="color: var(--text-muted); font-size: 0.8rem;"></i>
            <select id="filterComment" class="form-control" style="width: auto; min-width: 140px; padding: 6px 10px; font-size: 0.85rem;">
                <option value="">All Batches</option>
                <?php foreach ($filterComments as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-left: auto; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-search" style="color: var(--text-muted); font-size: 0.8rem;"></i>
            <input type="text" id="searchUser" class="form-control" placeholder="Search user..." style="width: 180px; padding: 6px 10px; font-size: 0.85rem;">
        </div>
        <button type="button" id="btnResetFilter" class="btn btn-secondary btn-sm" style="padding: 6px 10px; font-size: 0.8rem;" title="Reset Filter">
            <i class="fas fa-times"></i> Reset
        </button>
    </div>
    <form method="POST" id="bulkForm">
        <input type="hidden" name="action" value="bulk_delete">

        <!-- Bulk Action Bar (Hidden by default) -->
        <div id="bulkActionBar"
            style="display: none; background: var(--bg-secondary); padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; align-items: center; justify-content: space-between; border: 1px solid var(--neon-cyan); box-shadow: 0 0 10px rgba(0, 245, 255, 0.1);">
            <div style="font-weight: 600; color: var(--text-primary);">
                <span id="selectedCount">0</span> items selected
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-danger btn-sm"
                    onclick="return confirm('Delete all selected users?');">
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAll" style="cursor: pointer;">
                        </th>
                        <th>User</th>
                        <th>Profileeeeeeeeeeee</th>
                        <th>Price</th>
                        <th>Validity</th>
                        <th>Comment</th>
                        <th>Limit</th>
                        <th>Usage</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hotspotUsers as $user): ?>
                        <?php
                        $userName = $user['name'] ?? '';
                        $userProfileeeeeeeeeeee = $user['profile'] ?? 'default';
                        $isOnline = $userName !== '' && in_array($userName, $activeUsernames);
                        $userComment = $user['comment'] ?? '';
                        // Price/Validity from profile on-login script (Mikhmon v3 style)
                        $pInfo = $profilePriceMap[$userProfileeeeeeeeeeee] ?? ['price' => 0, 'validity' => '-', 'selling_price' => 0];
                        ?>
                        <tr data-server="<?php echo htmlspecialchars($user['server'] ?? ''); ?>"
                            data-profile="<?php echo htmlspecialchars($userProfileeeeeeeeeeee); ?>"
                            data-comment="<?php echo htmlspecialchars($userComment); ?>">
                            <td style="text-align: center;">
                                <input type="checkbox" name="names[]" value="<?php echo htmlspecialchars($userName); ?>"
                                    class="user-checkbox" style="cursor: pointer;" onchange="updateBulkBar()">
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $isOnline ? 'var(--neon-green)' : 'var(--text-muted)'; ?>">
                                    </div>
                                    <strong>
                                        <?php echo htmlspecialchars($userName); ?>
                                    </strong><br>
                                    <small
                                        class="text-muted"><?php echo htmlspecialchars($user['password'] ?? ''); ?></small>
                                </div>
                            </td>
                            <td data-label="Profileeeeeeeeeeee"><span class="badge badge-info">
                                    <?php echo htmlspecialchars($userProfileeeeeeeeeeee); ?>
                                </span></td>
                            <td data-label="Price">
                                <small><?php echo $pInfo['price'] > 0 ? formatCurrency($pInfo['price']) : '-'; ?></small>
                            </td>
                            <td data-label="Validity">
                                <small><?php echo htmlspecialchars($pInfo['validity']); ?></small>
                            </td>
                            <td data-label="Comment">
                                <small><?php echo htmlspecialchars($userComment ?: '-'); ?></small>
                            </td>
                            <td data-label="Limit">
                                <small>
                                    T: <?php echo $user['limit-uptime'] ?: '∞'; ?><br>
                                    D:
                                    <?php echo $user['limit-bytes-total'] ? formatBytes($user['limit-bytes-total']) : '∞'; ?>
                                </small>
                            </td>
                            <td data-label="Usage">
                                <small>
                                    U: <?php echo $user['uptime'] ?: '0'; ?><br>
                                    D: <?php echo formatBytes(($user['bytes-in'] ?? 0) + ($user['bytes-out'] ?? 0)); ?>
                                </small>
                            </td>
                            <td data-label="Actions">
                                <div style="display: flex; gap: 5px;">
                                    <a href="hotspot-user-edit.php?name=<?php echo urlencode($userName); ?>"
                                        class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-danger btn-sm"
                                        onclick="deleteSingleUser('<?php echo htmlspecialchars($userName, ENT_QUOTES); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
    // Build profile-to-comments mapping from table data
    const profileCommentsMap = {};
    document.querySelectorAll('.data-table tbody tr').forEach(row => {
        const profile = row.getAttribute('data-profile') || '';
        const comment = row.getAttribute('data-comment') || '';
        if (!profileCommentsMap[profile]) profileCommentsMap[profile] = new Set();
        if (comment) profileCommentsMap[profile].add(comment);
    });

    // All comments for reset
    const allComments = <?php echo json_encode(array_values($filterComments)); ?>;

    function updateCommentDropdown() {
        const profile = document.getElementById('filterProfileeeeeeeeeeee').value;
        const commentSelect = document.getElementById('filterComment');
        const currentVal = commentSelect.value;

        // Clear options
        commentSelect.innerHTML = '<option value="">All Batches</option>';

        let relevantComments;
        if (profile && profileCommentsMap[profile]) {
            relevantComments = Array.from(profileCommentsMap[profile]).sort();
        } else {
            relevantComments = allComments;
        }

        relevantComments.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === currentVal) opt.selected = true;
            commentSelect.appendChild(opt);
        });
    }

    function filterTable() {
        const search = document.getElementById('searchUser').value.toLowerCase();
        const server = document.getElementById('filterServer').value;
        const profile = document.getElementById('filterProfileeeeeeeeeeee').value;
        const comment = document.getElementById('filterComment').value;

        const rows = document.querySelectorAll('.data-table tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowServer = row.getAttribute('data-server');
            const rowProfileeeeeeeeeeee = row.getAttribute('data-profile');
            const rowComment = row.getAttribute('data-comment');
            const rowText = row.textContent.toLowerCase();

            const matchServer = !server || rowServer === server;
            const matchProfileeeeeeeeeeee = !profile || rowProfileeeeeeeeeeee === profile;
            const matchComment = !comment || rowComment === comment;
            const matchSearch = !search || rowText.includes(search);

            if (matchServer && matchProfileeeeeeeeeeee && matchComment && matchSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Uncheck hidden checkboxes
        document.querySelectorAll('.user-checkbox').forEach(cb => {
            if (cb.closest('tr').style.display === 'none') cb.checked = false;
        });
        updateBulkBar();
    }

    document.getElementById('searchUser').addEventListener('input', filterTable);
    document.getElementById('filterServer').addEventListener('change', filterTable);
    document.getElementById('filterProfileeeeeeeeeeee').addEventListener('change', function() {
        updateCommentDropdown();
        filterTable();
    });
    document.getElementById('filterComment').addEventListener('change', filterTable);

    // Reset filter button
    document.getElementById('btnResetFilter').addEventListener('click', function() {
        document.getElementById('filterServer').value = '';
        document.getElementById('filterProfileeeeeeeeeeee').value = '';
        document.getElementById('filterComment').value = '';
        document.getElementById('searchUser').value = '';
        updateCommentDropdown();
        filterTable();
    });

    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const bulkBar = document.getElementById('bulkActionBar');
    const selectedCount = document.getElementById('selectedCount');

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(cb => {
            if (cb.closest('tr').style.display !== 'none') {
                cb.checked = this.checked;
            }
        });
        updateBulkBar();
    });

    function updateBulkBar() {
        const checked = document.querySelectorAll('.user-checkbox:checked').length;
        selectedCount.textContent = checked;
        bulkBar.style.display = checked > 0 ? 'flex' : 'none';

        // Update selectAll state
        const visibleCbs = Array.from(checkboxes).filter(cb => cb.closest('tr').style.display !== 'none');
        if (visibleCbs.length > 0) {
            selectAll.checked = visibleCbs.every(cb => cb.checked);
        }
    }

    function deleteSingleUser(name) {
        if (!confirm('Delete this user?')) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.innerHTML = '<input type="hidden" name="action" value="delete">'
            + '<input type="hidden" name="name" value="' + name.replace(/"/g, '&quot;') + '">';
        document.body.appendChild(form);
        form.submit();
    }

    function printGeneratedVouchers() {
        // Get generated vouchers from PHP session
        const generatedVouchers = <?php echo isset($_SESSION['generated_vouchers']) ? json_encode($_SESSION['generated_vouchers']) : '[]'; ?>;
        
        if (generatedVouchers.length === 0) {
            alert('No vouchers available to print.');
            return;
        }

        // Prepare voucher data for print
        const voucherData = generatedVouchers.map(v => ({
            username: v.username,
            password: v.password,
            profile: v.profile,
            price: v.price,
            validity: v.validity,
            hotspotname: 'Gembok WiFi',
            dnsname: 'hotspot.net'
        }));

        // Get selected template from dropdown or use default
        const templateSelect = document.getElementById('templateSelect');
        const selectedTemplate = templateSelect ? templateSelect.value : 'mikhmon_style.php';

        // Open print page with voucher data and template
        const printUrl = 'print_vouchers.php?vouchers=' + encodeURIComponent(JSON.stringify(voucherData)) + '&template=' + encodeURIComponent(selectedTemplate);
        window.open(printUrl, '_blank');
    }
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
