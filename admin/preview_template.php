<?php
/**
 * Voucher Template Preview
 * Used for iframe preview in voucher editor
 */

$template = $_GET['template'] ?? 'mikhmon_style.php';
$templateDir = '../templates/vouchers/';

// Security: only allow .php files
if (!str_ends_with($template, '.php')) {
    $template .= '.php';
}

// Prevent directory traversal
$template = basename($template);

$templatePath = $templateDir . $template;

if (!file_exists($templatePath)) {
    die('Template not found');
}

$content = file_get_contents($templatePath);

// Replace placeholders with dummy data
$dummy = [
    '{{username}}' => 'MARWAN-USER',
    '{{password}}' => 'SECRET123',
    '{{price}}' => 'Rp 5.000',
    '{{price_small}}' => 'Rp',
    '{{price_big}}' => '5.000',
    '{{validity}}' => '24 Jam',
    '{{hotspotname}}' => 'Gembok WiFi',
    '{{dnsname}}' => 'hotspot.net',
    '{{profile}}' => 'Member-1',
    '{{num}}' => '1',
    '{{timelimit}}' => '1h',
    '{{datalimit}}' => '1GB',
    '{{logo}}' => 'https://placehold.co/85x20/000000/FFFFFF?text=LOGO',
    '{{qrcode}}' => '<div style="width:50px;height:50px;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-size:8px;border:2px solid #fff;">QR CODE</div>'
];

foreach ($dummy as $key => $value) {
    $content = str_replace($key, $value, $content);
}

echo $content;
?>
