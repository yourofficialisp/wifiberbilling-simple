<?php
/**
 * WiFiber Billing Configuration File (SAMPLE)
 * Rename this file to config.php and update values
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gembok_simple2');
define('DB_USER', 'root');
define('DB_PASS', '');

// MikroTik Configuration
define('MIKROTIK_HOST', '192.168.88.1');
define('MIKROTIK_USER', 'admin');
define('MIKROTIK_PASS', 'password');
define('MIKROTIK_PORT', 8728);

// Application Configuration
define('APP_NAME', 'NBB Wifiber');
define('APP_URL', 'http://localhost/wifiber');
define('APP_VERSION', '2.0.6');
define('WIFIBER_UPDATE_VERSION_URL', 'https://raw.githubusercontent.com/yourofficialisp/wifiber-billing/main/version.txt');

// Pagination and currency
define('ITEMS_PER_PAGE', 20);
define('CURRENCY', 'IDR');
define('CURRENCY_SYMBOL', 'Rp');
define('INVOICE_PREFIX', 'INV');
define('INVOICE_START', 1);

// Security
define('ENCRYPTION_KEY', 'your-secret-key-here-32-chars-long');

// WhatsApp Configuration
define('WHATSAPP_API_URL', '');
define('WHATSAPP_TOKEN', '');

// Tripay Configuration
define('TRIPAY_API_KEY', '');
define('TRIPAY_PRIVATE_KEY', '');
define('TRIPAY_MERCHANT_CODE', '');

// Telegram Configuration
define('TELEGRAM_BOT_TOKEN', '');

// GenieACS Configuration
define('GENIEACS_URL', 'http://localhost:7557');
define('GENIEACS_USERNAME', '');
define('GENIEACS_PASSWORD', '');
