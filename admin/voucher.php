<?php
/**
 * Voucher Generator for MikroTik Hotspot
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Voucher Generator';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate') {
        $profile = sanitize($_POST['profile']);
        $qty = (int)$_POST['qty'];
        $prefix = sanitize($_POST['prefix']);
        $length = (int)$_POST['length'];
        $saveToMikrotik = isset($_POST['save_to_mikrotik']);
        
        $vouchers = [];
        $successCount = 0;
        $errorCount = 0;
        
        for ($i = 0; $i < $qty; $i++) {
            $randomStr = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
            $username = $prefix . $randomStr;
            $password = $randomStr;
            
            $voucher = [
                'username' => $username,
                'password' => $password,
                'profile' => $profile,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($saveToMikrotik) {
                // Add to MikroTik
                $result = mikrotikAddHotspotUser($username, $password, $profile);
                if ($result) {
                    $successCount++;
                    $voucher['status'] = 'saved';
                } else {
                    $errorCount++;
                    $voucher['status'] = 'failed';
                }
            } else {
                $voucher['status'] = 'local';
                $successCount++;
            }
            
            $vouchers[] = $voucher;
        }
        
        // Save to database for history
        foreach ($vouchers as $v) {
            $voucherData = [
                'username' => $v['username'],
                'password' => $v['password'],
                'profile' => $v['profile'],
                'status' => $v['status'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Check if vouchers table exists, if not skip
            try {
                insert('vouchers', $voucherData);
            } catch (Exception $e) {
                // Table might not exist, continue
            }
        }
        
        echo json_encode([
            'success' => true,
            'vouchers' => $vouchers,
            'message' => "Generated {$qty} vouchers. Saved to MikroTik: {$successCount}, Failed: {$errorCount}"
        ]);
        exit;
    }
    
    if ($action === 'delete') {
        $username = sanitize($_POST['username']);
        $result = mikrotikDeleteHotspotUser($username);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Voucher deleted from MikroTik' : 'Failed to delete voucher'
        ]);
        exit;
    }
}

// Get MikroTik profiles
$profiles = mikrotikGetProfileeeeeeeeeeees();
$mikrotikConnected = !empty($profiles);

// Get hotspot user profiles (for hotspot, not PPPoE)
$hotspotProfileeeeeeeeeeees = [];
if ($mikrotikConnected) {
    // Try to get hotspot profiles
    $hotspotProfileeeeeeeeeeees = mikrotikGetHotspotProfileeeeeeeeeeees();
}

ob_start();
?>

<!-- Connection Status -->
<?php if (!$mikrotikConnected): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Not connected to MikroTik. Vouchers will be generated locally only (not saved to MikroTik).
        <a href="settings.php" style="color: inherit; text-decoration: underline;">Check MikroTik Settings</a>
    </div>
<?php else: ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        Connected to MikroTik
    </div>
<?php endif; ?>

<!-- Generate Voucher Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-ticket-alt"></i> Generate Voucher Hotspot</h3>
    </div>
    
    <form id="generateForm">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="generate">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Hotspot Profileeeeeeeeeeee</label>
                <select name="profile" id="voucherProfileeeeeeeeeeee" class="form-control" required style="background: #161628; color: #ffffff; padding: 12px; border: 1px solid #2a2a40; border-radius: 8px; font-size: 1rem; width: 100%;">
                    <?php if (!empty($hotspotProfileeeeeeeeeeees)): ?>
                        <?php foreach ($hotspotProfileeeeeeeeeeees as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    <?php elseif (!empty($profiles)): ?>
                        <?php foreach ($profiles as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="default">default</option>
                    <?php endif; ?>
                </select>
                <small style="color: var(--text-muted);">Profileeeeeeeeeeee from MikroTik Hotspot User Profileeeeeeeeeeees</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Voucher Quantity</label>
                <input type="number" name="qty" id="voucherQty" class="form-control" value="10" min="1" max="100" required>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Prefix Username</label>
                <input type="text" name="prefix" id="voucherPrefix" class="form-control" value="VCH-" placeholder="Example: VCH-">
            </div>
            
            <div class="form-group">
                <label class="form-label">Character Length</label>
                <input type="number" name="length" id="voucherLength" class="form-control" value="6" min="4" max="12" required>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="save_to_mikrotik" id="saveToMikrotik" <?php echo $mikrotikConnected ? 'checked' : 'disabled'; ?>>
                <span>Save to MikroTik Hotspot</span>
            </label>
            <?php if (!$mikrotikConnected): ?>
                <small style="color: var(--danger);">MikroTik not connected</small>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-magic"></i> Generate Voucher
        </button>
    </form>
</div>

<!-- Generated Vouchers -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Generated Vouchers</h3>
        <button class="btn btn-secondary btn-sm" onclick="printVouchers()" id="printBtn" style="display: none;">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
    
    <div id="voucherList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px;">
        <p style="color: var(--text-muted); grid-column: 1/-1; text-align: center; padding: 40px;">
            <i class="fas fa-ticket-alt" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
            No vouchers generated yet
        </p>
    </div>
</div>

<!-- Print Template (hidden) -->
<div id="printArea" style="display: none;"></div>

<script>
let generatedVouchers = [];

document.getElementById('generateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    fetch('voucher.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic"></i> Generate Voucher';
        
        if (data.success) {
            generatedVouchers = data.vouchers;
            displayVouchers(data.vouchers);
            document.getElementById('printBtn').style.display = 'inline-flex';
            alert(data.message);
        } else {
            alert('Failed to generate vouchers: ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-magic"></i> Generate Voucher';
        alert('An error occurred: ' + error.message);
    });
});

function displayVouchers(vouchers) {
    const container = document.getElementById('voucherList');
    container.innerHTML = '';
    
    vouchers.forEach((v, index) => {
        const card = document.createElement('div');
        card.className = 'voucher-card';
        card.innerHTML = `
            <div style="text-align: center; padding: 15px; background: linear-gradient(135deg, rgba(0,245,255,0.1) 0%, rgba(191,0,255,0.1) 100%); border: 1px solid var(--border-color); border-radius: 8px; position: relative;">
                <div style="font-family: 'Courier New', monospace; font-size: 1.1rem; font-weight: 700; color: var(--neon-cyan); letter-spacing: 1px; margin: 5px 0;">
                    ${v.username}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 5px;">
                    ${v.profile}
                </div>
                <div style="font-size: 0.85rem; color: var(--neon-green);">
                    Pass: ${v.password}
                </div>
                <div style="font-size: 0.7rem; margin-top: 5px;">
                    ${v.status === 'saved' ? '<span style="color: var(--neon-green);">✓ MikroTik</span>' : 
                      v.status === 'failed' ? '<span style="color: var(--danger);">✗ Failed</span>' : 
                      '<span style="color: var(--text-muted);">Local</span>'}
                </div>
                <button onclick="copyVoucher(${index})" style="position: absolute; top: 5px; right: 5px; background: rgba(255,255,255,0.1); border: none; color: var(--text-secondary); cursor: pointer; padding: 5px 8px; border-radius: 4px; font-size: 0.75rem;">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        `;
        container.appendChild(card);
    });
}

function copyVoucher(index) {
    const v = generatedVouchers[index];
    const text = `Username: ${v.username}\nPassword: ${v.password}\nProfileeeeeeeeeeee: ${v.profile}`;
    navigator.clipboard.writeText(text).then(() => {
        alert('Voucher copied to clipboard');
    });
}

function printVouchers() {
    const printArea = document.getElementById('printArea');
    printArea.innerHTML = '';
    printArea.style.display = 'block';
    
    let html = `
        <style>
            @media print {
                body * { visibility: hidden; }
                #printArea, #printArea * { visibility: visible; }
                #printArea { position: absolute; left: 0; top: 0; width: 100%; }
                .voucher-print { 
                    display: inline-block; 
                    width: 45%; 
                    margin: 10px; 
                    padding: 15px; 
                    border: 1px dashed #333;
                    text-align: center;
                    font-family: Arial, sans-serif;
                }
                .voucher-print .code { font-size: 14pt; font-weight: bold; }
            }
        </style>
    `;
    
    generatedVouchers.forEach(v => {
        html += `
            <div class="voucher-print">
                <div style="font-weight: bold; font-size: 12pt; margin-bottom: 5px;">VOUCHER INTERNET</div>
                <div class="code">${v.username}</div>
                <div style="font-size: 10pt; margin: 5px 0;">Password: ${v.password}</div>
                <div style="font-size: 9pt; color: #666;">Profileeeeeeeeeeee: ${v.profile}</div>
            </div>
        `;
    });
    
    printArea.innerHTML = html;
    window.print();
    printArea.style.display = 'none';
}
</script>

<style>
.voucher-card {
    transition: transform 0.2s;
}

.voucher-card:hover {
    transform: scale(1.02);
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-success { background: rgba(0, 255, 136, 0.1); border: 1px solid var(--neon-green); color: var(--neon-green); }
.alert-warning { background: rgba(255, 193, 7, 0.1); border: 1px solid var(--neon-orange); color: var(--neon-orange); }
</style>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
