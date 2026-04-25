<?php
/**
 * GEMBOK ISP - Modern Landing Page
 */

// Check for installation
if (!file_exists(__DIR__ . '/includes/installed.lock')) {
    header("Location: install.php");
    exit;
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'public_register') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php?reg=csrf');
    }

    $name = trim((string) sanitize($_POST['name'] ?? ''));
    $phoneRaw = trim((string) sanitize($_POST['phone'] ?? ''));
    $address = trim((string) sanitize($_POST['address'] ?? ''));
    $package = trim((string) sanitize($_POST['package'] ?? ''));
    $notes = trim((string) sanitize($_POST['notes'] ?? ''));

    if (mb_strlen($name) < 3 || mb_strlen($phoneRaw) < 8 || mb_strlen($address) < 6) {
        redirect('index.php?reg=invalid');
    }

    $digits = preg_replace('/\D+/', '', $phoneRaw);
    if ($digits === '') {
        redirect('index.php?reg=invalid');
    }
    if (strpos($digits, '0') === 0) {
        $digits = '62' . substr($digits, 1);
    } elseif (strpos($digits, '62') !== 0) {
        $digits = '62' . $digits;
    }

    $appNameForMsg = trim((string) getSetting('app_name', defined('APP_NAME') ? APP_NAME : ''));
    if ($appNameForMsg === '') {
        $appNameForMsg = 'GEMBOK';
    }
    $adminWa = trim((string) getSetting('WHATSAPP_ADMIN_NUMBER', ''));

    $adminMsg = "New Customer Registration\n\n";
    $adminMsg .= "Name: {$name}\n";
    $adminMsg .= "Phone: {$digits}\n";
    $adminMsg .= "Address: {$address}\n";
    if ($package !== '') {
        $adminMsg .= "Package: {$package}\n";
    }
    if ($notes !== '') {
        $adminMsg .= "Notes: {$notes}\n";
    }
    $adminMsg .= "\nSource: Landing Page";
    if (function_exists('getWhatsAppFooter')) {
        $adminMsg .= getWhatsAppFooter();
    }

    $customerMsg = "Hello {$name},\n\n";
    $customerMsg .= "Thank you, your registration has been received.\n";
    $customerMsg .= "The {$appNameForMsg} team will contact you for the next process.\n";
    if ($adminWa !== '') {
        $adminDigits = preg_replace('/\D+/', '', $adminWa);
        if ($adminDigits !== '') {
            if (strpos($adminDigits, '0') === 0) {
                $adminDigits = '62' . substr($adminDigits, 1);
            } elseif (strpos($adminDigits, '62') !== 0) {
                $adminDigits = '62' . $adminDigits;
            }
            $customerMsg .= "\nCS/WA: {$adminDigits}";
        }
    }
    if (function_exists('getWhatsAppFooter')) {
        $customerMsg .= getWhatsAppFooter();
    }

    $adminDigits = preg_replace('/\D+/', '', $adminWa);
    if ($adminDigits !== '') {
        if (strpos($adminDigits, '0') === 0) {
            $adminDigits = '62' . substr($adminDigits, 1);
        } elseif (strpos($adminDigits, '62') !== 0) {
            $adminDigits = '62' . $adminDigits;
        }
        sendWhatsApp($adminDigits, $adminMsg);
    }

    $techs = fetchAll("SELECT phone FROM technician_users WHERE status = 'active' AND phone IS NOT NULL AND phone <> '' LIMIT 1");
    foreach ($techs as $t) {
        $tDigits = preg_replace('/\D+/', '', (string) ($t['phone'] ?? ''));
        if ($tDigits === '') {
            continue;
        }
        if (strpos($tDigits, '0') === 0) {
            $tDigits = '62' . substr($tDigits, 1);
        } elseif (strpos($tDigits, '62') !== 0) {
            $tDigits = '62' . $tDigits;
        }
        sendWhatsApp($tDigits, $adminMsg);
    }

    sendWhatsApp($digits, $customerMsg);

    redirect('index.php?reg=success');
}

// Fetch Packages
$packages = [];
try {
    $pdo = getDB();
    $packages = $pdo->query("SELECT * FROM packages ORDER BY price ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fail silently
}

// App settings
$appName = getSetting('app_name', 'WiFiber Billing');

// Landing settings
$heroTitle = getSiteSetting('hero_title', 'Fast Internet <br>Without Limits');
$heroDesc = getSiteSetting('hero_description', 'Enjoy super fast, stable, and unlimited fiber optic internet connection for your home or business needs. Join now!');
$contactPhone = getSiteSetting('contact_phone', '+92 303-678-3333');
$contactEmail = getSiteSetting('contact_email', 'your.official.isp@gmail.com');
$contactAddress = getSiteSetting('contact_address', 'Pakistan');
$footerAbout = getSiteSetting('footer_about', 'Trusted internet service provider with quality fiber optic network to support your digital activities.');

// Feature settings
$f1_title = getSiteSetting('feature_1_title', 'High Speed');
$f1_desc = getSiteSetting('feature_1_desc', 'Fiber optic connection with symmetrical upload and download speeds.');

$f2_title = getSiteSetting('feature_2_title', 'Unlimited Quota');
$f2_desc = getSiteSetting('feature_2_desc', 'Unlimited internet access without quota restrictions (FUP).');

$f3_title = getSiteSetting('feature_3_title', 'Support 24/7');
$f3_desc = getSiteSetting('feature_3_desc', 'Our technical team is ready to help you anytime if there are disruptions.');

// Social settings
$s_fb = getSiteSetting('social_facebook', '#');
$s_ig = getSiteSetting('social_instagram', '#');
$s_tw = getSiteSetting('social_twitter', '#');
$s_yt = getSiteSetting('social_youtube', '#');

// Theme settings
$themeColor = getSiteSetting('theme_color', 'neon');

// Landing template settings
$landingTemplate = getSiteSetting('landing_template', 'neon');

// Map template names to file paths
$templateFiles = [
    'neon' => 'templates/landing/template_neon.php',
    'modern' => 'templates/landing/template_modern.php',
    'corporate' => 'templates/landing/template_corporate.php',
    'minimal' => 'templates/landing/template_minimal.php',
    'glassmorphism' => 'templates/landing/template_glassmorphism.php',
    'neumorphism' => 'templates/landing/template_neumorphism.php',
    'bento' => 'templates/landing/template_bento.php',
    'modern_ultra' => 'templates/landing/template_modern_ultra.php'
];

// Validate template selection
$templateFile = isset($templateFiles[$landingTemplate]) ? $templateFiles[$landingTemplate] : $templateFiles['neon'];

$voucherOrderUrl = rtrim(APP_URL, '/') . '/voucher-order.php';

ob_start();
if (file_exists(__DIR__ . '/' . $templateFile)) {
    include __DIR__ . '/' . $templateFile;
} else {
    include __DIR__ . '/templates/landing/template_neon.php';
}
$html = ob_get_clean();

$showPublicButtons = !isAdminLoggedIn() && !isSalesLoggedIn() && !isTechnicianLoggedIn() && !isCustomerLoggedIn();
$voucherButton = '';
if ($showPublicButtons) {
    $voucherButton = '<a href="' . htmlspecialchars($voucherOrderUrl, ENT_QUOTES, 'UTF-8') . '" style="position:fixed;right:16px;bottom:16px;z-index:9999;background:#22d3ee;color:#0f172a;padding:10px 14px;border-radius:999px;font-weight:700;text-decoration:none;box-shadow:0 8px 20px rgba(0,0,0,.25);font-family:Arial,sans-serif;font-size:13px;">Order Voucher</a>';
}

$csrf = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
$pkgOptions = '<option value="">Select package (optional)</option>';
foreach ($packages as $p) {
    $pName = trim((string) ($p['name'] ?? ''));
    if ($pName === '') continue;
    $pkgOptions .= '<option value="' . htmlspecialchars($pName, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($pName, ENT_QUOTES, 'UTF-8') . '</option>';
}
$pkgOptions .= '<option value="Lainnya">Other</option>';

$registerModal = '
<div id="gembokRegOverlay" style="position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:10000;display:none;align-items:center;justify-content:center;padding:16px;">
  <div style="width:100%;max-width:520px;background:#0b1220;color:#fff;border:1px solid rgba(34,211,238,.35);border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.35);overflow:hidden;font-family:Arial,sans-serif;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:rgba(34,211,238,.08);border-bottom:1px solid rgba(34,211,238,.18);">
      <div style="font-weight:800;">New Customer Registration</div>
      <button type="button" onclick="window.__gembokCloseRegisterModal && window.__gembokCloseRegisterModal()" style="background:transparent;border:none;color:#fff;font-size:18px;cursor:pointer;line-height:1;">×</button>
    </div>
    <form method="POST" action="' . htmlspecialchars(rtrim(APP_URL, '/') . '/index.php', ENT_QUOTES, 'UTF-8') . '" style="padding:16px;">
      <input type="hidden" name="action" value="public_register">
      <input type="hidden" name="csrf_token" value="' . $csrf . '">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label style="display:block;font-size:12px;color:rgba(255,255,255,.7);margin-bottom:6px;">Name</label>
          <input name="name" required minlength="3" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(0,0,0,.25);color:#fff;">
        </div>
        <div>
          <label style="display:block;font-size:12px;color:rgba(255,255,255,.7);margin-bottom:6px;">Phone (WA)</label>
          <input name="phone" required minlength="8" placeholder="08xxxx or 62xxxx" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(0,0,0,.25);color:#fff;">
        </div>
      </div>
      <div style="margin-top:12px;">
        <label style="display:block;font-size:12px;color:rgba(255,255,255,.7);margin-bottom:6px;">Address</label>
        <textarea name="address" required minlength="6" rows="2" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(0,0,0,.25);color:#fff;resize:vertical;"></textarea>
      </div>
      <div style="margin-top:12px;">
        <label style="display:block;font-size:12px;color:rgba(255,255,255,.7);margin-bottom:6px;">Package (optional)</label>
        <select name="package" id="gembokRegPackage" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(0,0,0,.25);color:#fff;">
          ' . $pkgOptions . '
        </select>
      </div>
      <div style="margin-top:12px;">
        <label style="display:block;font-size:12px;color:rgba(255,255,255,.7);margin-bottom:6px;">Notes (optional)</label>
        <input name="notes" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(0,0,0,.25);color:#fff;" placeholder="Example: location, house landmark, preferred contact time">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px;">
        <button type="button" onclick="window.__gembokCloseRegisterModal && window.__gembokCloseRegisterModal()" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);color:#fff;padding:10px 12px;border-radius:8px;cursor:pointer;">Cancel</button>
        <button type="submit" style="background:#00ff88;border:none;color:#052e1a;padding:10px 14px;border-radius:8px;cursor:pointer;font-weight:800;">Submit</button>
      </div>
    </form>
  </div>
</div>
<script>
  (function() {
    const overlay = document.getElementById("gembokRegOverlay");
    const packageSelect = document.getElementById("gembokRegPackage");
    window.__gembokOpenRegisterModal = function() { if (overlay) overlay.style.display = "flex"; };
    window.__gembokCloseRegisterModal = function() { if (overlay) overlay.style.display = "none"; };
    window.__gembokOpenRegisterModalWithPackage = function(pkg) {
      if (packageSelect && typeof pkg === "string" && pkg !== "") {
        let found = false;
        for (let i = 0; i < packageSelect.options.length; i++) {
          if (packageSelect.options[i].value === pkg) {
            found = true;
            break;
          }
        }
        packageSelect.value = found ? pkg : "Lainnya";
      }
      window.__gembokOpenRegisterModal && window.__gembokOpenRegisterModal();
    };
    if (overlay) {
      overlay.addEventListener("click", function(e) { if (e.target === overlay) window.__gembokCloseRegisterModal(); });
    }
    const p = new URLSearchParams(window.location.search);
    const reg = p.get("reg");
    if (reg === "success") alert("Registration successfully sent. We will contact you soon.");
    if (reg === "invalid") alert("Data incomplete. Please check Name/Phone/Address again.");
    if (reg === "csrf") alert("Session invalid. Please refresh the page and try again.");
    if (reg === "open") {
      const pkg = p.get("pkg") || "";
      window.__gembokOpenRegisterModalWithPackage && window.__gembokOpenRegisterModalWithPackage(pkg);
    }
  })();
</script>';

$inject = $voucherButton . $registerModal;
if (stripos($html, '</body>') !== false) {
    $html = preg_replace('/<\/body>/i', $inject . '</body>', $html, 1);
} else {
    $html .= $inject;
}
echo $html;
?>
