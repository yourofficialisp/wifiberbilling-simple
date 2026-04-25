<?php
require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Hotspot Login Template';

$appName = getSetting('app_name', 'GEMBOK');
$appUrl = rtrim((string) APP_URL, '/');

$contactPhone = (string) getSiteSetting('contact_phone', '');
$contactEmail = (string) getSiteSetting('contact_email', '');

$waDigits = preg_replace('/\D+/', '', $contactPhone);
if ($waDigits !== '') {
    if (strpos($waDigits, '0') === 0) {
        $waDigits = '62' . substr($waDigits, 1);
    } elseif (strpos($waDigits, '62') !== 0) {
        $waDigits = '62' . $waDigits;
    }
}
$supportWhatsAppUrl = $waDigits !== '' ? ('https://wa.me/' . $waDigits) : '';

$config = [
    'brandName' => $appName,
    'buyVoucherUrl' => $appUrl . '/voucher',
    'voucherStatusUrl' => $appUrl . '/voucher-status.php',
    'customerPortalUrl' => $appUrl . '/portal/login.php',
    'supportWhatsAppUrl' => $supportWhatsAppUrl,
    'supportEmail' => $contactEmail
];

$configJs = "window.GembokHotspotConfig = " . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ";\n";

ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-wifi"></i> Hotspot Login Template</h3>
    </div>

    <p style="color: var(--text-muted); margin-bottom: 14px;">
        Copy the content below to file <strong>mikrotik-hotspot-login/config.js</strong> before uploading to MikroTik.
    </p>

    <textarea id="configJs" class="form-control" style="height: 260px; font-family: monospace; white-space: pre;"><?php echo htmlspecialchars($configJs); ?></textarea>

    <div style="display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
        <button type="button" class="btn btn-primary" onclick="copyConfig()"><i class="fas fa-copy"></i> Copy</button>
        <a class="btn btn-secondary" href="settings.php"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<script>
function copyConfig() {
    const el = document.getElementById('configJs');
    if (!el) return;
    el.focus();
    el.select();
    el.setSelectionRange(0, 999999);
    navigator.clipboard.writeText(el.value).then(function () {
        alert('Config copied successfully.');
    }).catch(function () {
        document.execCommand('copy');
        alert('Config copied successfully.');
    });
}
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';

