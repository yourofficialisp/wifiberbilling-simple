<?php
/**
 * Web-accessible Cron Runner
 */

require_once __DIR__ . '/../includes/auth.php'; // For getSettingValue and db access

// Get cron token from settings
$settings = [];
$settingsData = fetchAll("SELECT * FROM settings WHERE setting_key = 'CRON_TOKEN'");
if ($settingsData) {
    $cronToken = $settingsData[0]['setting_value'];
} else {
    // Generate token if not exists (lazy sync)
    $cronToken = bin2hex(random_bytes(16));
    insert('settings', ['setting_key' => 'CRON_TOKEN', 'setting_value' => $cronToken]);
}

// Check token
if (!isset($_GET['token']) || $_GET['token'] !== $cronToken) {
    header('HTTP/1.1 403 Forbidden');
    die("Invalid or missing cron token.");
}

// Load scheduler
require_once __DIR__ . '/scheduler.php';

// Set content type to plain text for easy reading of logs
header('Content-Type: text/plain');

// Run scheduler
runScheduler();
