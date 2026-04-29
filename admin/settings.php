<?php
/**
 * Admin Settings
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Settings';

// Get current settings
$settings = [];
$settingsData = fetchAll("SELECT * FROM settings");
foreach ($settingsData as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Helper function to get setting with fallback to config.php constant
function getSettingValue($key, $default = '') {
    global $settings;
    
    // First check database
    if (isset($settings[$key]) && $settings[$key] !== '') {
        return $settings[$key];
    }
    
    // Fallback to config.php constant
    if (defined($key)) {
        return constant($key);
    }
    
    return $default;
}

if (isset($_GET['download_backup'])) {
    $backupFile = sanitizeBackupFilename($_GET['download_backup'] ?? '');
    if ($backupFile === '') {
        setFlash('error', 'Invalid backup file name');
        redirect('settings.php');
    }
    $fullPath = getBackupDirectory() . $backupFile;
    if (!is_file($fullPath)) {
        setFlash('error', 'Backup file not found');
        redirect('settings.php');
    }
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $backupFile . '"');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid CSRF token');
        redirect('settings.php');
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_system':
                $systemSettings = [
                    'app_name' => sanitize($_POST['app_name']),
                    'timezone' => sanitize($_POST['timezone']),
                    'currency' => sanitize($_POST['currency']),
                    'invoice_prefix' => sanitize($_POST['invoice_prefix']),
                    'invoice_start' => (int)$_POST['invoice_start'],
                    'invoice_manager_name' => sanitize($_POST['invoice_manager_name'] ?? '')
                ];
                
                foreach ($systemSettings as $key => $value) {
                    $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($existing) {
                        update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
                
                setFlash('success', 'System settings successfully saved');
                redirect('settings.php');
                break;
                
            case 'save_mikrotik':
                $mikrotikSettings = [
                    'MIKROTIK_HOST' => sanitize($_POST['mikrotik_host']),
                    'MIKROTIK_USER' => sanitize($_POST['mikrotik_user']),
                    'MIKROTIK_PASS' => sanitize($_POST['mikrotik_pass']),
                    'MIKROTIK_PORT' => (int)$_POST['mikrotik_port']
                ];
                
                foreach ($mikrotikSettings as $key => $value) {
                    $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($existing) {
                        update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
                
                setFlash('success', 'MikroTik settings successfully saved');
                redirect('settings.php');
                break;
                
            case 'save_genieacs':
                $genieacsSettings = [
                    'GENIEACS_URL' => sanitize($_POST['genieacs_url']),
                    'GENIEACS_USERNAME' => sanitize($_POST['genieacs_username']),
                    'GENIEACS_PASSWORD' => sanitize($_POST['genieacs_password'])
                ];
                
                foreach ($genieacsSettings as $key => $value) {
                    $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($existing) {
                        update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
                
                setFlash('success', 'GenieACS settings successfully saved');
                redirect('settings.php');
                break;
                
            case 'save_integrations':
                $integrationSettings = [
                    'DEFAULT_WHATSAPP_GATEWAY' => sanitize($_POST['default_whatsapp_gateway']),
                    'FONNTE_API_TOKEN' => sanitize($_POST['fonnte_api_token']),
                    'WABLAS_API_TOKEN' => sanitize($_POST['wablas_api_token']),
                    'MPWA_API_KEY' => sanitize($_POST['mpwa_api_key']),
                    'MPWA_SENDER'  => sanitize($_POST['mpwa_sender']),
                    'MPWA_API_URL' => sanitize($_POST['mpwa_api_url'] ?? ''),
                    'TRIPAY_API_KEY' => sanitize($_POST['tripay_api_key']),
                    'TRIPAY_PRIVATE_KEY' => sanitize($_POST['tripay_private_key']),
                    'TRIPAY_MERCHANT_CODE' => sanitize($_POST['tripay_merchant_code']),
                    'TRIPAY_MODE' => sanitize($_POST['tripay_mode'] ?? ''),
                    'MIDTRANS_API_KEY' => sanitize($_POST['midtrans_api_key']),
                    'MIDTRANS_MERCHANT_CODE' => sanitize($_POST['midtrans_merchant_code']),
                    'DEFAULT_PAYMENT_GATEWAY' => sanitize($_POST['default_payment_gateway']),
                    'WHATSAPP_ADMIN_NUMBER' => sanitize($_POST['whatsapp_admin_number']),
                    'CRON_TOKEN' => sanitize($_POST['cron_token'])
                ];
                
                foreach ($integrationSettings as $key => $value) {
                    $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($existing) {
                        update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
                
                setFlash('success', 'Integration settings successfully saved');
                redirect('settings.php');
                break;
            
            case 'save_telegram_settings':
                $telegramSettings = [
                    'TELEGRAM_BOT_TOKEN' => sanitize($_POST['telegram_bot_token'] ?? ''),
                    'TELEGRAM_ADMIN_CHAT_ID' => sanitize($_POST['telegram_admin_chat_id'] ?? '')
                ];

                foreach ($telegramSettings as $key => $value) {
                    $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($existing) {
                        update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
                setFlash('success', 'Telegram settings successfully saved');
                redirect('settings.php');
                break;

            case 'save_whatsapp_settings':
                $whatsAppSettings = [
                    'DEFAULT_WHATSAPP_GATEWAY' => sanitize($_POST['default_whatsapp_gateway']),
                    'FONNTE_API_TOKEN' => sanitize($_POST['fonnte_api_token']),
                    'WABLAS_API_TOKEN' => sanitize($_POST['wablas_api_token']),
                    'MPWA_API_KEY' => sanitize($_POST['mpwa_api_key']),
                    'MPWA_SENDER'  => sanitize($_POST['mpwa_sender']),
                    'MPWA_API_URL' => sanitize($_POST['mpwa_api_url'] ?? ''),
                    'WHATSAPP_ADMIN_NUMBER' => sanitize($_POST['whatsapp_admin_number'])
                ];

                foreach ($whatsAppSettings as $key => $value) {
                    $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($existing) {
                        update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
                setFlash('success', 'WhatsApp settings successfully saved');
                redirect('settings.php');
                break;

            case 'save_payment_settings':
                $paymentSettings = [
                    'TRIPAY_API_KEY' => sanitize($_POST['tripay_api_key']),
                    'TRIPAY_PRIVATE_KEY' => sanitize($_POST['tripay_private_key']),
                    'TRIPAY_MERCHANT_CODE' => sanitize($_POST['tripay_merchant_code']),
                    'TRIPAY_MODE' => sanitize($_POST['tripay_mode'] ?? ''),
                    'MIDTRANS_API_KEY' => sanitize($_POST['midtrans_api_key']),
                    'MIDTRANS_MERCHANT_CODE' => sanitize($_POST['midtrans_merchant_code']),
                    'DEFAULT_PAYMENT_GATEWAY' => sanitize($_POST['default_payment_gateway']),
                    'DUITKU_MERCHANT_CODE' => sanitize($_POST['duitku_merchant_code'] ?? ''),
                    'DUITKU_API_KEY' => sanitize($_POST['duitku_api_key'] ?? ''),
                    'DUITKU_MODE' => sanitize($_POST['duitku_mode'] ?? 'production'),
                    'DUITKU_EXPIRY_MINUTES' => (int) ($_POST['duitku_expiry_minutes'] ?? 60),
                    'XENDIT_SECRET_KEY' => sanitize($_POST['xendit_secret_key'] ?? ''),
                    'XENDIT_CALLBACK_TOKEN' => sanitize($_POST['xendit_callback_token'] ?? ''),
                    'XENDIT_INVOICE_DURATION' => (int) ($_POST['xendit_invoice_duration'] ?? 3600)
                ];

                foreach ($paymentSettings as $key => $value) {
                    $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                    if ($existing) {
                        update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
                setFlash('success', 'Payment Gateway settings successfully saved');
                redirect('settings.php');
                break;

            case 'save_cron_settings':
                $cronToken = sanitize($_POST['cron_token'] ?? '');
                if ($cronToken === '') {
                    $cronToken = bin2hex(random_bytes(16));
                }
                $key = 'CRON_TOKEN';
                $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                if ($existing) {
                    update('settings', ['setting_value' => $cronToken], 'setting_key = ?', [$key]);
                } else {
                    insert('settings', ['setting_key' => $key, 'setting_value' => $cronToken]);
                }
                setFlash('success', 'Cron token successfully saved');
                redirect('settings.php');
                break;

            case 'test_whatsapp':
                $testPhone = trim((string) ($_POST['test_whatsapp_phone'] ?? ''));
                $testMessage = trim((string) ($_POST['test_whatsapp_message'] ?? ''));
                if ($testPhone === '' || $testMessage === '') {
                    setFlash('error', 'WhatsApp number and test message must be filled');
                    redirect('settings.php');
                }
                $digits = preg_replace('/\D+/', '', $testPhone);
                if ($digits !== '') {
                    if (strpos($digits, '0') === 0) {
                        $digits = '62' . substr($digits, 1);
                    } elseif (strpos($digits, '62') !== 0) {
                        $digits = '62' . $digits;
                    }
                }
                require_once '../includes/whatsapp.php';
                $defaultGateway = getSetting('DEFAULT_WHATSAPP_GATEWAY', 'fonnte');
                $result = sendWhatsAppMessage($digits, $testMessage, $defaultGateway);
                if (($result['success'] ?? false) === true) {
                    setFlash('success', 'WhatsApp test sent successfully (gateway: ' . strtoupper($defaultGateway) . ')');
                } else {
                    $msg = $result['message'] ?? 'WhatsApp test failed';
                    setFlash('error', 'WhatsApp test failed (gateway: ' . strtoupper($defaultGateway) . '): ' . $msg);
                }
                redirect('settings.php');
                break;

            case 'test_mpwa_connection':
                $url = trim((string) getSetting('MPWA_API_URL', 'https://mpwa.official.id/api/send'));
                if ($url === '') {
                    $url = 'https://mpwa.official.id/api/send';
                }
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: GEMBOK/2.x (MPWA Probe)']);
                curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErrno = (int) curl_errno($ch);
                $curlError = (string) curl_error($ch);
                unset($ch);
                if ($curlErrno !== 0 || $httpCode === 0) {
                    setFlash('error', 'MPWA connection failed (HTTP ' . $httpCode . ', cURL ' . $curlErrno . '): ' . $curlError);
                } else {
                    setFlash('success', 'MPWA connection OK (HTTP ' . $httpCode . ')');
                }
                redirect('settings.php');
                break;
            
            case 'test_telegram':
                $token = trim((string) getSetting('TELEGRAM_BOT_TOKEN', ''));
                $chatId = trim((string) getSetting('TELEGRAM_ADMIN_CHAT_ID', ''));
                if ($token === '' || $chatId === '') {
                    setFlash('error', 'Telegram Bot Token and Admin Chat ID are required for testing.');
                    redirect('settings.php');
                }
                $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
                $payload = [
                    'chat_id' => $chatId,
                    'text' => 'Test Telegram GEMBOK ' . date('Y-m-d H:i:s'),
                    'parse_mode' => 'HTML'
                ];
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $response = curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErrno = (int) curl_errno($ch);
                $curlError = (string) curl_error($ch);
                unset($ch);
                $decoded = json_decode((string) $response, true);
                if ($curlErrno !== 0 || $httpCode === 0) {
                    setFlash('error', 'Telegram test failed (HTTP ' . $httpCode . ', cURL ' . $curlErrno . '): ' . $curlError);
                } elseif (is_array($decoded) && ($decoded['ok'] ?? false) === true) {
                    setFlash('success', 'Telegram test sent successfully to Chat ID: ' . $chatId);
                } else {
                    $msg = is_array($decoded) ? (string) ($decoded['description'] ?? 'Unknown error') : 'Unknown error';
                    setFlash('error', 'Telegram test failed (HTTP ' . $httpCode . '): ' . $msg);
                }
                redirect('settings.php');
                break;

            case 'telegram_set_webhook':
                $token = trim((string) getSetting('TELEGRAM_BOT_TOKEN', ''));
                if ($token === '') {
                    setFlash('error', 'Telegram Bot Token has not been set.');
                    redirect('settings.php');
                }
                $webhookUrl = rtrim(APP_URL, '/') . '/webhooks/telegram.php';
                if (stripos($webhookUrl, 'localhost') !== false || stripos($webhookUrl, '127.0.0.1') !== false) {
                    setFlash('error', 'APP_URL is still localhost. Telegram cannot access a local webhook. Use a public domain/IP + HTTPS or a tunnel (ngrok/cloudflared).');
                    redirect('settings.php');
                }
                $url = 'https://api.telegram.org/bot' . $token . '/setWebhook';
                $payload = ['url' => $webhookUrl];
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                $response = curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErrno = (int) curl_errno($ch);
                $curlError = (string) curl_error($ch);
                unset($ch);
                $decoded = json_decode((string) $response, true);
                if ($curlErrno !== 0 || $httpCode === 0) {
                    setFlash('error', 'setWebhook failed (HTTP ' . $httpCode . ', cURL ' . $curlErrno . '): ' . $curlError);
                } elseif (is_array($decoded) && ($decoded['ok'] ?? false) === true) {
                    setFlash('success', 'Telegram Webhook successfully set to: ' . $webhookUrl);
                } else {
                    $msg = is_array($decoded) ? (string) ($decoded['description'] ?? 'Unknown error') : 'Unknown error';
                    setFlash('error', 'setWebhook failed (HTTP ' . $httpCode . '): ' . $msg);
                }
                redirect('settings.php');
                break;

            case 'telegram_webhook_info':
                $token = trim((string) getSetting('TELEGRAM_BOT_TOKEN', ''));
                if ($token === '') {
                    setFlash('error', 'Telegram Bot Token has not been set.');
                    redirect('settings.php');
                }
                $url = 'https://api.telegram.org/bot' . $token . '/getWebhookInfo';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $response = curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErrno = (int) curl_errno($ch);
                $curlError = (string) curl_error($ch);
                unset($ch);
                $decoded = json_decode((string) $response, true);
                if ($curlErrno !== 0 || $httpCode === 0) {
                    setFlash('error', 'getWebhookInfo failed (HTTP ' . $httpCode . ', cURL ' . $curlErrno . '): ' . $curlError);
                } elseif (!is_array($decoded) || ($decoded['ok'] ?? false) !== true) {
                    $msg = is_array($decoded) ? (string) ($decoded['description'] ?? 'Unknown error') : 'Unknown error';
                    setFlash('error', 'getWebhookInfo failed (HTTP ' . $httpCode . '): ' . $msg);
                } else {
                    $result = $decoded['result'] ?? [];
                    $currentUrl = (string) ($result['url'] ?? '');
                    $pending = (int) ($result['pending_update_count'] ?? 0);
                    $lastError = (string) ($result['last_error_message'] ?? '');
                    $msg = 'Webhook URL: ' . ($currentUrl !== '' ? $currentUrl : '(empty)') . ' | Pending: ' . $pending;
                    if ($lastError !== '') {
                        $msg .= ' | Last error: ' . $lastError;
                    }
                    setFlash('success', $msg);
                }
                redirect('settings.php');
                break;
                
            case 'save_landing':
                // Auto create table if not exists (lazy migration)
                $pdo = getDB();
                $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(50) UNIQUE NOT NULL,
                    setting_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $landingSettings = [
            'landing_template' => sanitize($_POST['landing_template']),
            // Sanitize landing hero fields to prevent XSS
            'hero_title' => sanitize($_POST['hero_title']),
            'hero_description' => sanitize($_POST['hero_description']),
                    'contact_phone' => sanitize($_POST['contact_phone']),
                    'contact_email' => sanitize($_POST['contact_email']),
                    'contact_address' => sanitize($_POST['contact_address']),
                    'footer_about' => sanitize($_POST['footer_about']),
                    'feature_1_title' => sanitize($_POST['feature_1_title']),
                    'feature_1_desc' => sanitize($_POST['feature_1_desc']),
                    'feature_2_title' => sanitize($_POST['feature_2_title']),
                    'feature_2_desc' => sanitize($_POST['feature_2_desc']),
                    'feature_3_title' => sanitize($_POST['feature_3_title']),
                    'feature_3_desc' => sanitize($_POST['feature_3_desc']),
                    'social_facebook' => sanitize($_POST['social_facebook']),
                    'social_instagram' => sanitize($_POST['social_instagram']),
                    'social_twitter' => sanitize($_POST['social_twitter']),
                    'social_youtube' => sanitize($_POST['social_youtube']),
                    'theme_color' => sanitize($_POST['theme_color'])
                ];
                
                foreach ($landingSettings as $key => $value) {
                    $existing = fetchOne("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
                    if ($existing) {
                        update('site_settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
                    } else {
                        insert('site_settings', ['setting_key' => $key, 'setting_value' => $value]);
                    }
                }
                
                setFlash('success', 'Landing Page settings saved successfully');
                redirect('settings.php');
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                $sessionAdmin = getCurrentAdmin();
                $admin = getAdmin($sessionAdmin['id']);
                
                if (!$admin || !password_verify($currentPassword, $admin['password'])) {
                    setFlash('error', 'Current password is incorrect');
                    redirect('settings.php');
                }
                
                if ($newPassword !== $confirmPassword) {
                    setFlash('error', 'New passwords do not match');
                    redirect('settings.php');
                }
                
                if (strlen($newPassword) < 6) {
                    setFlash('error', 'Password must be at least 6 characters');
                    redirect('settings.php');
                }
                
                if (updateAdminPassword($admin['id'], $newPassword)) {
                    setFlash('success', 'Password changed successfully');
                    logActivity('CHANGE_PASSWORD', 'Admin ID: ' . $admin['id']);
                } else {
                    setFlash('error', 'Failed to change password');
                }
                redirect('settings.php');
                break;

            case 'save_backup_settings':
                $retentionDays = (int) ($_POST['backup_retention_days'] ?? 7);
                if ($retentionDays < 1) {
                    $retentionDays = 1;
                }
                if ($retentionDays > 365) {
                    $retentionDays = 365;
                }
                $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", ['BACKUP_RETENTION_DAYS']);
                if ($existing) {
                    update('settings', ['setting_value' => $retentionDays], 'setting_key = ?', ['BACKUP_RETENTION_DAYS']);
                } else {
                    insert('settings', ['setting_key' => 'BACKUP_RETENTION_DAYS', 'setting_value' => $retentionDays]);
                }
                setFlash('success', 'Backup retention settings saved successfully');
                redirect('settings.php');
                break;

            case 'backup_now':
                $retentionDays = (int) getSettingValue('BACKUP_RETENTION_DAYS', 7);
                $result = createDatabaseBackup($retentionDays);
                if ($result['success']) {
                    $deletedCount = count($result['deleted_files'] ?? []);
                    $message = 'Backup created successfully: ' . ($result['file_name'] ?? '-');
                    if ($deletedCount > 0) {
                        $message .= " ({$deletedCount} old backup(s) deleted)";
                    }
                    setFlash('success', $message);
                    logActivity('BACKUP_NOW', 'File: ' . ($result['file_name'] ?? '-'));
                } else {
                    setFlash('error', $result['message'] ?? 'Failed to create backup');
                }
                redirect('settings.php');
                break;

            case 'restore_backup':
                $backupFile = sanitizeBackupFilename($_POST['backup_file'] ?? '');
                $confirmRestore = strtoupper(trim((string) ($_POST['confirm_restore'] ?? '')));
                if ($backupFile === '') {
                    setFlash('error', 'Please select a valid backup file');
                    redirect('settings.php');
                }
                if ($confirmRestore !== 'RESTORE') {
                    setFlash('error', 'Invalid restore confirmation. Type RESTORE to proceed.');
                    redirect('settings.php');
                }
                set_time_limit(0);
                $result = restoreDatabaseBackup($backupFile);
                if ($result['success']) {
                    setFlash('success', 'Restore completed from file: ' . $backupFile);
                    logActivity('RESTORE_BACKUP', 'File: ' . $backupFile);
                } else {
                    setFlash('error', $result['message'] ?? 'Backup restore failed');
                }
                redirect('settings.php');
                break;
        }
    }
}

$backupRetentionDays = (int) getSettingValue('BACKUP_RETENTION_DAYS', 7);
if ($backupRetentionDays < 1) {
    $backupRetentionDays = 7;
}
$backupFiles = listDatabaseBackups();

ob_start();
?>

<!-- System Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cog"></i> System Settings</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="save_system">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div class="form-group">
            <label class="form-label">Application Name</label>
            <input type="text" name="app_name" class="form-control" value="<?php echo htmlspecialchars($settings['app_name'] ?? 'GEMBOK'); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Timezone</label>
            <select name="timezone" class="form-control">
                <option value="Asia/Karachi" <?php echo ($settings['timezone'] ?? '') === 'Asia/Karachi' ? 'selected' : ''; ?>>Asia/Karachi (PKT)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="currency" class="form-control">
                <option value="PKR" <?php echo ($settings['currency'] ?? '') === 'PKR' ? 'selected' : ''; ?>>PKR - Rupee</option>
                <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - Dollar</option>
            </select>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Invoice Prefix</label>
                <input type="text" name="invoice_prefix" class="form-control" value="<?php echo htmlspecialchars($settings['invoice_prefix'] ?? 'INV'); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Invoice Start Number</label>
                <input type="number" name="invoice_start" class="form-control" value="<?php echo (int)($settings['invoice_start'] ?? 1); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Manager Name (Invoice Signature)</label>
            <input type="text" name="invoice_manager_name" class="form-control" value="<?php echo htmlspecialchars($settings['invoice_manager_name'] ?? ''); ?>" placeholder="Manager Name">
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save
        </button>
    </form>
</div>

<!-- MikroTik Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-network-wired"></i> MikroTik Settings</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="save_mikrotik">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">MikroTik IP Address</label>
                <input type="text" name="mikrotik_host" class="form-control" value="<?php echo htmlspecialchars(getSettingValue('MIKROTIK_HOST')); ?>" placeholder="192.168.1.1">
            </div>
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="mikrotik_user" class="form-control" value="<?php echo htmlspecialchars(getSettingValue('MIKROTIK_USER')); ?>" placeholder="admin">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="mikrotik_pass" class="form-control" value="<?php echo htmlspecialchars(getSettingValue('MIKROTIK_PASS')); ?>" placeholder="Enter password">
            </div>
            
            <div class="form-group">
                <label class="form-label">API Port</label>
                <input type="number" name="mikrotik_port" class="form-control" value="<?php echo (int)getSettingValue('MIKROTIK_PORT', 8728); ?>">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save
        </button>
    </form>
</div>

<!-- GenieACS Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-server"></i> GenieACS Settings</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="save_genieacs">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div class="form-group">
            <label class="form-label">GenieACS URL</label>
            <input type="text" name="genieacs_url" class="form-control" value="<?php echo htmlspecialchars(getSettingValue('GENIEACS_URL')); ?>" placeholder="http://192.168.1.1:7557">
            <small style="color: var(--text-muted);">Full URL including port (default: 7557)</small>
            <?php if (defined('GENIEACS_URL') && GENIEACS_URL && !isset($settings['GENIEACS_URL'])): ?>
                <small style="color: var(--neon-cyan);"><i class="fas fa-info-circle"></i> Value from config.php (not yet saved to database)</small>
            <?php endif; ?>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Username (Optional)</label>
                <input type="text" name="genieacs_username" class="form-control" value="<?php echo htmlspecialchars(getSettingValue('GENIEACS_USERNAME')); ?>" placeholder="Username GenieACS">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password (Optional)</label>
                <input type="password" name="genieacs_password" class="form-control" value="<?php echo htmlspecialchars(getSettingValue('GENIEACS_PASSWORD')); ?>" placeholder="Password GenieACS">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save
        </button>
    </form>
</div>

<!-- Landing Page Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-globe"></i> Landing Page Settings</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="save_landing">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <?php
        $siteSettings = [];
        $siteSettingsData = fetchAll("SELECT * FROM site_settings");
        foreach ($siteSettingsData as $s) {
            $siteSettings[$s['setting_key']] = $s['setting_value'];
        }
        ?>
        
        <div class="form-group">
            <label class="form-label">Main Title (Hero Title)</label>
            <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($siteSettings['hero_title'] ?? 'Fast Internet <br>No Limits'); ?>" placeholder="Fast Internet No Limits">
            <small style="color: var(--text-muted);">Use &lt;br&gt; for line breaks</small>
        </div>
        
        <div class="form-group">
            <label class="form-label">Main Description</label>
            <textarea name="hero_description" class="form-control" rows="3"><?php echo htmlspecialchars($siteSettings['hero_description'] ?? ''); ?></textarea>
        </div>
        
        <h4 style="margin: 20px 0 15px; color: var(--neon-cyan);">Contact Information</h4>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Phone / WhatsApp Number</label>
                <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($siteSettings['contact_phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($siteSettings['contact_email'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Full Address</label>
            <textarea name="contact_address" class="form-control" rows="2"><?php echo htmlspecialchars($siteSettings['contact_address'] ?? 'Karachi, Pakistan'); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Template Landing Page</label>
            <select name="landing_template" class="form-control">
                <optgroup label="🎨 Classic">
                    <option value="neon" <?php echo ($siteSettings['landing_template'] ?? 'neon') === 'neon' ? 'selected' : ''; ?>>Neon Dark (Default)</option>
                    <option value="modern" <?php echo ($siteSettings['landing_template'] ?? '') === 'modern' ? 'selected' : ''; ?>>Modern Clean</option>
                    <option value="corporate" <?php echo ($siteSettings['landing_template'] ?? '') === 'corporate' ? 'selected' : ''; ?>>Corporate Blue</option>
                    <option value="minimal" <?php echo ($siteSettings['landing_template'] ?? '') === 'minimal' ? 'selected' : ''; ?>>Minimum Dark</option>
                </optgroup>
                <optgroup label="✨ Modern & Trending">
                    <option value="glassmorphism" <?php echo ($siteSettings['landing_template'] ?? '') === 'glassmorphism' ? 'selected' : ''; ?>>Glassmorphism (Blur Effects)</option>
                    <option value="neumorphism" <?php echo ($siteSettings['landing_template'] ?? '') === 'neumorphism' ? 'selected' : ''; ?>>Neumorphism (Soft UI)</option>
                </optgroup>
                <optgroup label="🚀 Ultra Modern">
                    <option value="bento" <?php echo ($siteSettings['landing_template'] ?? '') === 'bento' ? 'selected' : ''; ?>>Bento Grid (Smooth Animations)</option>
                    <option value="modern_ultra" <?php echo ($siteSettings['landing_template'] ?? '') === 'modern_ultra' ? 'selected' : ''; ?>>Modern Ultra (3D & Particles)</option>
                </optgroup>
            </select>
            <small class="text-muted">Select landing page template (index.php)</small>
        </div>

        <div class="form-group">
            <label class="form-label">Website Theme Color</label>
            <select name="theme_color" class="form-control">
                <option value="neon" <?php echo ($siteSettings['theme_color'] ?? 'neon') === 'neon' ? 'selected' : ''; ?>>Neon (Cyan & Purple)</option>
                <option value="ocean" <?php echo ($siteSettings['theme_color'] ?? '') === 'ocean' ? 'selected' : ''; ?>>Ocean (Blue & Teal)</option>
                <option value="nature" <?php echo ($siteSettings['theme_color'] ?? '') === 'nature' ? 'selected' : ''; ?>>Nature (Green & Lime)</option>
                <option value="sunset" <?php echo ($siteSettings['theme_color'] ?? '') === 'sunset' ? 'selected' : ''; ?>>Sunset (Orange & Red)</option>
                <option value="royal" <?php echo ($siteSettings['theme_color'] ?? '') === 'royal' ? 'selected' : ''; ?>>Royal (Gold & Dark Purple)</option>
                <option value="crimson" <?php echo ($siteSettings['theme_color'] ?? '') === 'crimson' ? 'selected' : ''; ?>>Crimson (Red & Pink)</option>
            </select>
            <small class="text-muted">Select color scheme for the front page (index.php)</small>
        </div>
        
        <div class="form-group">
            <label class="form-label">About (Footer)</label>
            <textarea name="footer_about" class="form-control" rows="2"><?php echo htmlspecialchars($siteSettings['footer_about'] ?? ''); ?></textarea>
        </div>

        <h4 style="margin: 20px 0 15px; color: var(--neon-cyan);">Features & Services (3 Columns)</h4>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <!-- Feature 1 -->
            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px;">
                <h5>Feature 1</h5>
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="feature_1_title" class="form-control" value="<?php echo htmlspecialchars($siteSettings['feature_1_title'] ?? 'High Speed'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="feature_1_desc" class="form-control" rows="2"><?php echo htmlspecialchars($siteSettings['feature_1_desc'] ?? 'Fiber optic connection with symmetrical upload and download speeds.'); ?></textarea>
                </div>
            </div>

            <!-- Feature 2 -->
            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px;">
                <h5>Feature 2</h5>
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="feature_2_title" class="form-control" value="<?php echo htmlspecialchars($siteSettings['feature_2_title'] ?? 'Unlimited Quota'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="feature_2_desc" class="form-control" rows="2"><?php echo htmlspecialchars($siteSettings['feature_2_desc'] ?? 'Browse the internet freely with no quota restrictions (FUP).'); ?></textarea>
                </div>
            </div>

            <!-- Feature 3 -->
            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px;">
                <h5>Feature 3</h5>
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="feature_3_title" class="form-control" value="<?php echo htmlspecialchars($siteSettings['feature_3_title'] ?? 'Support 24/7'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="feature_3_desc" class="form-control" rows="2"><?php echo htmlspecialchars($siteSettings['feature_3_desc'] ?? 'Our technical team is ready to assist you anytime if an issue arises.'); ?></textarea>
                </div>
            </div>
        </div>

        <h4 style="margin: 20px 0 15px; color: var(--neon-cyan);">Social Media</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label"><i class="fab fa-facebook"></i> Facebook URL</label>
                <input type="text" name="social_facebook" class="form-control" value="<?php echo htmlspecialchars($siteSettings['social_facebook'] ?? '#'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fab fa-instagram"></i> Instagram URL</label>
                <input type="text" name="social_instagram" class="form-control" value="<?php echo htmlspecialchars($siteSettings['social_instagram'] ?? '#'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fab fa-twitter"></i> Twitter URL</label>
                <input type="text" name="social_twitter" class="form-control" value="<?php echo htmlspecialchars($siteSettings['social_twitter'] ?? '#'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><i class="fab fa-youtube"></i> Youtube URL</label>
                <input type="text" name="social_youtube" class="form-control" value="<?php echo htmlspecialchars($siteSettings['social_youtube'] ?? '#'); ?>">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Landing Page
        </button>
    </form>
</div>

<!-- Telegram Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fab fa-telegram-plane"></i> Telegram Bot</h3>
    </div>

    <div style="background: rgba(0,200,255,0.08); border: 1px solid var(--neon-cyan); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <i class="fas fa-link" style="color: var(--neon-cyan);"></i>
            <strong style="color: var(--neon-cyan);">Telegram Webhook URL</strong>
        </div>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
            Paste this URL when setting the webhook in BotFather/Telegram API.
        </p>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="telegram_webhook_url" readonly
                value="<?php echo APP_URL; ?>/webhooks/telegram.php"
                style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,200,255,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer;"
                onclick="this.select()">
            <button type="button" onclick="copyWebhookUrl('telegram_webhook_url', this)" style="background: var(--neon-cyan); color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                <i class="fas fa-copy"></i> Copy
            </button>
        </div>
    </div>

    <?php
    $telegramToken = getSettingValue('TELEGRAM_BOT_TOKEN', '');
    $telegramAdminChatId = getSettingValue('TELEGRAM_ADMIN_CHAT_ID', '');
    ?>

    <form method="POST">
        <input type="hidden" name="action" value="save_telegram_settings">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Telegram Bot Token</label>
                <input type="password" name="telegram_bot_token" class="form-control" value="<?php echo htmlspecialchars($telegramToken); ?>" placeholder="123456:ABC-DEF...">
                <small style="color: var(--text-muted);">Token from BotFather. Stored in the settings database (overrides config.php).</small>
            </div>
            <div class="form-group">
                <label class="form-label">Telegram Admin Chat ID</label>
                <input type="text" name="telegram_admin_chat_id" class="form-control" value="<?php echo htmlspecialchars($telegramAdminChatId); ?>" placeholder="e.g. 123456789 or -1001234567890">
                <small style="color: var(--text-muted);">Chat ID of the admin/group authorized to access the bot admin menu.</small>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Telegram
        </button>
    </form>

    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px;">
        <form method="POST" style="margin: 0;">
            <input type="hidden" name="action" value="test_telegram">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-paper-plane"></i> Send Telegram Test
            </button>
        </form>
        <form method="POST" style="margin: 0;">
            <input type="hidden" name="action" value="telegram_webhook_info">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-info-circle"></i> Check Webhook
            </button>
        </form>
        <form method="POST" style="margin: 0;">
            <input type="hidden" name="action" value="telegram_set_webhook">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-link"></i> Set Webhook
            </button>
        </form>
    </div>
</div>

<!-- WhatsApp Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fab fa-whatsapp"></i> WhatsApp Gateway</h3>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="save_whatsapp_settings">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div style="background: rgba(0,200,255,0.08); border: 1px solid var(--neon-cyan); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <i class="fas fa-link" style="color: var(--neon-cyan);"></i>
                <strong style="color: var(--neon-cyan);">WhatsApp Webhook / Callback URL</strong>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
                Paste this URL into the <strong>Webhook URL</strong> field in your WhatsApp gateway dashboard (works for Fonnte, Wablas, and MPWA).
            </p>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="wa_webhook_url" readonly
                    value="<?php echo APP_URL; ?>/webhooks/whatsapp.php"
                    style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,200,255,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer;"
                    onclick="this.select()">
                <button type="button" onclick="copyWebhookUrl('wa_webhook_url', this)" style="background: var(--neon-cyan); color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">WhatsApp Gateway Default</label>
            <select name="default_whatsapp_gateway" class="form-control">
                <option value="fonnte" <?php echo ($settings['DEFAULT_WHATSAPP_GATEWAY'] ?? '') === 'fonnte' ? 'selected' : ''; ?>>Fonnte</option>
                <option value="wablas" <?php echo ($settings['DEFAULT_WHATSAPP_GATEWAY'] ?? '') === 'wablas' ? 'selected' : ''; ?>>Wablas</option>
                <option value="mpwa" <?php echo ($settings['DEFAULT_WHATSAPP_GATEWAY'] ?? '') === 'mpwa' ? 'selected' : ''; ?>>MPWA</option>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Fonnte API Token</label>
                <input type="password" name="fonnte_api_token" class="form-control" value="<?php echo htmlspecialchars($settings['FONNTE_API_TOKEN'] ?? ''); ?>" placeholder="Enter Fonnte API Token">
            </div>

            <div class="form-group">
                <label class="form-label">Wablas API Token</label>
                <input type="password" name="wablas_api_token" class="form-control" value="<?php echo htmlspecialchars($settings['WABLAS_API_TOKEN'] ?? ''); ?>" placeholder="Enter Wablas API Token">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">MPWA API Key</label>
            <input type="password" name="mpwa_api_key" class="form-control" value="<?php echo htmlspecialchars($settings['MPWA_API_KEY'] ?? ''); ?>" placeholder="Enter MPWA API Key">
        </div>

        <div class="form-group">
            <label class="form-label">MPWA API URL</label>
            <input type="text" name="mpwa_api_url" class="form-control" value="<?php echo htmlspecialchars($settings['MPWA_API_URL'] ?? ''); ?>" placeholder="https://github.com/yourofficialisp">
            <small style="color: var(--text-muted);">Leave blank for default. Example: https://github.com/yourofficialisp</small>
        </div>

        <div class="form-group">
            <label class="form-label">MPWA Sender Number <span style="color: #ff6b6b;">*required</span></label>
            <input type="text" name="mpwa_sender" class="form-control" value="<?php echo htmlspecialchars($settings['MPWA_SENDER'] ?? ''); ?>" placeholder="628xxxxxxxxxx">
            <small style="color: var(--text-muted);">WhatsApp number already scanned via QR in the MPWA dashboard (format: 628...)</small>
        </div>

        <div class="form-group">
            <label class="form-label">WhatsApp Admin Number</label>
            <input type="text" name="whatsapp_admin_number" class="form-control" value="<?php echo htmlspecialchars($settings['WHATSAPP_ADMIN_NUMBER'] ?? ''); ?>" placeholder="628xxxxxxxxxx">
            <small style="color: var(--text-muted);">Admin WhatsApp number for managing the bot (format: 628...)</small>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save WhatsApp
        </button>
    </form>
</div>

<!-- WhatsApp Test -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-paper-plane"></i> Test WhatsApp</h3>
    </div>

    <form method="POST" style="margin-bottom: 14px;">
        <input type="hidden" name="action" value="test_whatsapp">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Recipient Number</label>
                <input type="text" name="test_whatsapp_phone" class="form-control" placeholder="628xxxxxxxxxx atau 08xxxxxxxxxx">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Test Message</label>
                <input type="text" name="test_whatsapp_message" class="form-control" value="Test WhatsApp GEMBOK" placeholder="Test WhatsApp">
            </div>
            <div class="form-group" style="margin-bottom: 0; grid-column: 1 / -1;">
                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane"></i> Send Test</button>
            </div>
        </div>
    </form>

    <form method="POST">
        <input type="hidden" name="action" value="test_mpwa_connection">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <button type="submit" class="btn btn-dark"><i class="fas fa-network-wired"></i> Test MPWA Connection</button>
    </form>
</div>

<!-- Payment Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-credit-card"></i> Payment Gateway</h3>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="save_payment_settings">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <h4 style="margin-bottom: 15px; color: var(--neon-cyan);">Tripay</h4>
        <div style="background: rgba(0,200,255,0.08); border: 1px solid var(--neon-cyan); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <i class="fas fa-link" style="color: var(--neon-cyan);"></i>
                <strong style="color: var(--neon-cyan);">Tripay Callback / Webhook URL</strong>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
                Paste this URL into the <strong>Callback URL</strong> field in your Tripay merchant settings.
            </p>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="tripay_webhook_url" readonly
                    value="<?php echo APP_URL; ?>/webhooks/tripay.php"
                    style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,200,255,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer;"
                    onclick="this.select()">
                <button type="button" onclick="copyWebhookUrl('tripay_webhook_url', this)" style="background: var(--neon-cyan); color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tripay API Key</label>
            <input type="text" name="tripay_api_key" class="form-control" value="<?php echo htmlspecialchars($settings['TRIPAY_API_KEY'] ?? ''); ?>" placeholder="Enter Tripay API Key">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Tripay Private Key</label>
                <input type="password" name="tripay_private_key" class="form-control" value="<?php echo htmlspecialchars($settings['TRIPAY_PRIVATE_KEY'] ?? ''); ?>" placeholder="Enter Private Key">
            </div>

            <div class="form-group">
                <label class="form-label">Tripay Merchant Code</label>
                <input type="text" name="tripay_merchant_code" class="form-control" value="<?php echo htmlspecialchars($settings['TRIPAY_MERCHANT_CODE'] ?? ''); ?>" placeholder="Enter Merchant Code">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tripay Mode</label>
            <select name="tripay_mode" class="form-control">
                <option value="" <?php echo empty($settings['TRIPAY_MODE'] ?? '') ? 'selected' : ''; ?>>Production (default)</option>
                <option value="sandbox" <?php echo (($settings['TRIPAY_MODE'] ?? '') === 'sandbox') ? 'selected' : ''; ?>>Sandbox</option>
            </select>
            <small style="color: var(--text-muted);">Use Sandbox only when using Tripay simulator credentials.</small>
        </div>

        <hr style="margin: 30px 0; border-color: var(--border-color);">

        <h4 style="margin-bottom: 15px; color: var(--neon-cyan);">Midtrans</h4>
        <div style="background: rgba(0,200,255,0.08); border: 1px solid var(--neon-cyan); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <i class="fas fa-link" style="color: var(--neon-cyan);"></i>
                <strong style="color: var(--neon-cyan);">Midtrans Notification / Webhook URL</strong>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
                Paste this URL into the <strong>Payment Notification URL</strong> field in Midtrans Dashboard &rarr; Settings &rarr; Configuration.
            </p>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="midtrans_webhook_url" readonly
                    value="<?php echo APP_URL; ?>/webhooks/midtrans.php"
                    style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,200,255,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer;"
                    onclick="this.select()">
                <button type="button" onclick="copyWebhookUrl('midtrans_webhook_url', this)" style="background: var(--neon-cyan); color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Midtrans API Key</label>
            <input type="text" name="midtrans_api_key" class="form-control" value="<?php echo htmlspecialchars($settings['MIDTRANS_API_KEY'] ?? ''); ?>" placeholder="Enter Midtrans API Key">
        </div>

        <div class="form-group">
            <label class="form-label">Midtrans Merchant Code</label>
            <input type="text" name="midtrans_merchant_code" class="form-control" value="<?php echo htmlspecialchars($settings['MIDTRANS_MERCHANT_CODE'] ?? ''); ?>" placeholder="Enter Merchant Code">
        </div>

        <hr style="margin: 30px 0; border-color: var(--border-color);">

        <h4 style="margin-bottom: 15px; color: var(--neon-cyan);">Default</h4>
        <div class="form-group">
            <label class="form-label">Payment Gateway Default</label>
            <select name="default_payment_gateway" class="form-control">
                <option value="tripay" <?php echo ($settings['DEFAULT_PAYMENT_GATEWAY'] ?? '') === 'tripay' ? 'selected' : ''; ?>>Tripay</option>
                <option value="midtrans" <?php echo ($settings['DEFAULT_PAYMENT_GATEWAY'] ?? '') === 'midtrans' ? 'selected' : ''; ?>>Midtrans</option>
                <option value="duitku" <?php echo ($settings['DEFAULT_PAYMENT_GATEWAY'] ?? '') === 'duitku' ? 'selected' : ''; ?>>Duitku</option>
                <option value="xendit" <?php echo ($settings['DEFAULT_PAYMENT_GATEWAY'] ?? '') === 'xendit' ? 'selected' : ''; ?>>Xendit</option>
            </select>
        </div>

        <hr style="margin: 30px 0; border-color: var(--border-color);">

        <h4 style="margin-bottom: 15px; color: var(--neon-cyan);">Duitku</h4>
        <div style="background: rgba(0,200,255,0.08); border: 1px solid var(--neon-cyan); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <i class="fas fa-link" style="color: var(--neon-cyan);"></i>
                <strong style="color: var(--neon-cyan);">Duitku Callback / Webhook URL</strong>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
                Paste this URL into your Duitku callback settings.
            </p>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="duitku_webhook_url" readonly
                    value="<?php echo APP_URL; ?>/webhooks/duitku.php"
                    style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,200,255,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer;"
                    onclick="this.select()">
                <button type="button" onclick="copyWebhookUrl('duitku_webhook_url', this)" style="background: var(--neon-cyan); color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Duitku Merchant Code</label>
                <input type="text" name="duitku_merchant_code" class="form-control" value="<?php echo htmlspecialchars($settings['DUITKU_MERCHANT_CODE'] ?? ''); ?>" placeholder="DXXXX">
            </div>
            <div class="form-group">
                <label class="form-label">Duitku API Key</label>
                <input type="password" name="duitku_api_key" class="form-control" value="<?php echo htmlspecialchars($settings['DUITKU_API_KEY'] ?? ''); ?>" placeholder="API Key">
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Duitku Mode</label>
                <select name="duitku_mode" class="form-control">
                    <option value="production" <?php echo (($settings['DUITKU_MODE'] ?? '') !== 'sandbox') ? 'selected' : ''; ?>>Production</option>
                    <option value="sandbox" <?php echo (($settings['DUITKU_MODE'] ?? '') === 'sandbox') ? 'selected' : ''; ?>>Sandbox</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Duitku Expiry (minutes)</label>
                <input type="number" name="duitku_expiry_minutes" class="form-control" value="<?php echo (int)($settings['DUITKU_EXPIRY_MINUTES'] ?? 60); ?>" min="5">
            </div>
        </div>

        <hr style="margin: 30px 0; border-color: var(--border-color);">

        <h4 style="margin-bottom: 15px; color: var(--neon-cyan);">Xendit</h4>
        <div style="background: rgba(0,200,255,0.08); border: 1px solid var(--neon-cyan); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <i class="fas fa-link" style="color: var(--neon-cyan);"></i>
                <strong style="color: var(--neon-cyan);">Xendit Callback / Webhook URL</strong>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
                Paste this URL into Xendit &rarr; Webhooks. If using a callback token, fill it in below as well.
            </p>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="xendit_webhook_url" readonly
                    value="<?php echo APP_URL; ?>/webhooks/xendit.php"
                    style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,200,255,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 13px; cursor: pointer;"
                    onclick="this.select()">
                <button type="button" onclick="copyWebhookUrl('xendit_webhook_url', this)" style="background: var(--neon-cyan); color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Xendit Secret Key</label>
                <input type="password" name="xendit_secret_key" class="form-control" value="<?php echo htmlspecialchars($settings['XENDIT_SECRET_KEY'] ?? ''); ?>" placeholder="xnd_...">
            </div>
            <div class="form-group">
                <label class="form-label">Xendit Callback Token (optional)</label>
                <input type="password" name="xendit_callback_token" class="form-control" value="<?php echo htmlspecialchars($settings['XENDIT_CALLBACK_TOKEN'] ?? ''); ?>" placeholder="token webhooks">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Xendit Invoice Duration (seconds)</label>
            <input type="number" name="xendit_invoice_duration" class="form-control" value="<?php echo (int)($settings['XENDIT_INVOICE_DURATION'] ?? 3600); ?>" min="300">
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Payment
        </button>
    </form>
</div>

<!-- Cron Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clock"></i> Cronjob & Task Scheduler</h3>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="save_cron_settings">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div style="background: rgba(0,255,136,0.08); border: 1px solid #00ff88; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <i class="fas fa-clock" style="color: #00ff88;"></i>
                <strong style="color: #00ff88;">Cronjob Configuration</strong>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 10px;">
                Use one of the methods below to run automated tasks (auto-isolation, invoice sending, etc.). It is strongly recommended to run every <strong>1 minute</strong>.
            </p>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; color: #00ff88; margin-bottom: 5px;">Method 1: CLI Script (Recommended for VPS)</label>
                <?php
                $schedulerPath = realpath(__DIR__ . '/../cron/scheduler.php');
                $schedulerPathUnix = $schedulerPath ? str_replace('\\', '/', $schedulerPath) : '/path/to/gembok-simple/cron/scheduler.php';
                $schedulerPathWin = $schedulerPath ? $schedulerPath : 'C:\\path\\to\\gembok-simple\\cron\\scheduler.php';
                ?>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="cron_cli_path" readonly
                        value="* * * * * /usr/bin/php <?php echo htmlspecialchars($schedulerPathUnix); ?>"
                        style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,255,136,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 12px; font-family: monospace; cursor: pointer;"
                        onclick="this.select()">
                    <button type="button" onclick="copyWebhookUrl('cron_cli_path', this)" style="background: #00ff88; color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                    <input type="text" id="cron_cli_windows" readonly
                        value="php.exe <?php echo htmlspecialchars($schedulerPathWin); ?>"
                        style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,255,136,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 12px; font-family: monospace; cursor: pointer;"
                        onclick="this.select()">
                    <button type="button" onclick="copyWebhookUrl('cron_cli_windows', this)" style="background: #00ff88; color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <div style="color: var(--text-muted); font-size: 12px; margin-top: 6px;">
                    Linux/VPS: use the cron format. Windows Task Scheduler: set the <strong>php.exe</strong> path according to your PHP installation.
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12px; color: #00ff88; margin-bottom: 5px;">Method 2: URL Task (For aaPanel / Cloud Hosting)</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php
                    $cronToken = getSettingValue('CRON_TOKEN');
                    if (!$cronToken) {
                        $cronToken = bin2hex(random_bytes(16));
                    }
                    $cronUrl = APP_URL . "/cron/run.php?token=" . $cronToken;
                    ?>
                    <input type="text" id="cron_web_url" readonly
                        value="<?php echo $cronUrl; ?>"
                        style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,255,136,0.3); color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 12px; font-family: monospace; cursor: pointer;"
                        onclick="this.select()">
                    <button type="button" onclick="copyWebhookUrl('cron_web_url', this)" style="background: #00ff88; color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <div style="color: var(--text-muted); font-size: 12px; margin-top: 6px;">
                    Make sure <strong>APP_URL</strong> uses the server's domain/IP (not <strong>localhost</strong>) when called from hosting/panel.
                </div>
            </div>
        </div>

        <input type="hidden" name="cron_token" value="<?php echo htmlspecialchars($cronToken); ?>">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Cron Token
        </button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-database"></i> Backup & Restore Database</h3>
    </div>

    <form method="POST" style="margin-bottom: 20px;">
        <input type="hidden" name="action" value="save_backup_settings">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="form-group">
            <label class="form-label">Automatic Backup Retention (days)</label>
            <input type="number" name="backup_retention_days" class="form-control" min="1" max="365" value="<?php echo $backupRetentionDays; ?>">
            <small style="color: var(--text-muted);">Backups older than this value will be automatically deleted when a backup runs.</small>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Retention
        </button>
    </form>

    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
        <form method="POST" onsubmit="return confirm('Create database backup now?');">
            <input type="hidden" name="action" value="backup_now">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-download"></i> Backup Now
            </button>
        </form>
    </div>

    <h4 style="margin-bottom: 10px; color: var(--neon-cyan);">Backup File List</h4>
    <?php if (empty($backupFiles)): ?>
        <p style="color: var(--text-muted); margin-bottom: 20px;">No backup files yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto; margin-bottom: 20px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backupFiles as $file): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($file['name']); ?></td>
                        <td><?php echo htmlspecialchars(formatBytes($file['size'] ?? 0)); ?></td>
                        <td><?php echo htmlspecialchars($file['modified_at'] ?? '-'); ?></td>
                        <td>
                            <a class="btn btn-secondary btn-sm" href="settings.php?download_backup=<?php echo urlencode($file['name']); ?>">
                                <i class="fas fa-file-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return confirm('Restore will overwrite the current database. Continue?');">
        <input type="hidden" name="action" value="restore_backup">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="form-group">
            <label class="form-label">Select Restore File</label>
            <select name="backup_file" class="form-control" required>
                <option value="">Select backup file</option>
                <?php foreach ($backupFiles as $file): ?>
                    <option value="<?php echo htmlspecialchars($file['name']); ?>"><?php echo htmlspecialchars($file['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Type RESTORE to confirm</label>
            <input type="text" name="confirm_restore" class="form-control" placeholder="RESTORE" required>
        </div>
        <button type="submit" class="btn btn-danger">
            <i class="fas fa-upload"></i> Restore Backup
        </button>
    </form>
</div>

<!-- Change Password -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-key"></i> Change Admin Password</h3>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" placeholder="•••••••••" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
        </div>
        
        <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required minlength="6">
        </div>
        
        <button type="submit" class="btn btn-warning">
            <i class="fas fa-key"></i> Change Password
        </button>
    </form>
</div>

<script>
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    });
});

function copyWebhookUrl(inputId, btn) {
    const input = document.getElementById(inputId);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.style.background = '#00ff88';
        setTimeout(function() {
            btn.innerHTML = original;
            btn.style.background = 'var(--neon-cyan)';
        }, 2000);
    }).catch(function() {
        // Fallback for older browsers
        document.execCommand('copy');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.style.background = '#00ff88';
        setTimeout(function() {
            btn.innerHTML = original;
            btn.style.background = 'var(--neon-cyan)';
        }, 2000);
    });
}

</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
