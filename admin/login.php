<?php
/**
 * Admin Login Page
 */

require_once '../includes/auth.php';

// Check if already logged in
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Session is invalid or has expired. Please try again.');
        redirect('login.php');
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $throttleStatus = getLoginThrottleStatus('admin', $username, 5, 900, 900);
    if ($throttleStatus['blocked']) {
        $retryAfter = max(1, (int) ceil($throttleStatus['retry_after'] / 60));
        setFlash('error', 'Too many login attempts. Try again in ' . $retryAfter . ' minutes.');
        redirect('login.php');
    }

    if (adminLogin($username, $password)) {
        clearLoginFailures('admin', $username);
        setFlash('success', 'Login successful! Welcome.');
        redirect('dashboard.php');
    } else {
        addLoginFailure('admin', $username, 5, 900, 900);
        setFlash('error', 'Username or password is incorrect!');
        redirect('login.php');
    }
}

$appName = getSetting('app_name', 'WiFiber Billing');
$pageTitle = 'Login Admin';
$content = '';

ob_start();
?>

<div
    style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: radial-gradient(circle at center, #1a1a2e 0%, #0a0a12 100%);">
    <div
        style="background: #1a1a2e; border: 1px solid #2a2a40; border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); position: relative; overflow: hidden;">
        <div style="text-align: center; margin-bottom: 30px; position: relative; z-index: 1;">
            <i class="fas fa-network-wired"
                style="font-size: 3rem; background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 15px; display: inline-block;"></i>
            <h1
                style="font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                <?php echo htmlspecialchars($appName); ?></h1>
            <p style="color: #b0b0c0;">ISP Management System</p>
        </div>

        <?php if (hasFlash('error')): ?>
            <div class="alert alert-error"
                style="margin-bottom: 20px; background: rgba(255, 71, 87, 0.2); border: 1px solid #ff4757; color: #ff4757; padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars(getFlash('error')); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label"
                    style="display: block; margin-bottom: 8px; font-weight: 600; color: #ffffff;">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required
                    autofocus
                    style="width: 100%; padding: 12px; background: #161628; border: 1px solid #2a2a40; border-radius: 8px; color: #ffffff; font-size: 1rem;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label"
                    style="display: block; margin-bottom: 8px; font-weight: 600; color: #ffffff;">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required
                    style="width: 100%; padding: 12px; background: #161628; border: 1px solid #2a2a40; border-radius: 8px; color: #ffffff; font-size: 1rem;">
            </div>

            <button type="submit" class="btn btn-primary"
                style="width: 100%; padding: 12px 20px; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; color: #ffffff; background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%); transition: all 0.3s; box-shadow: 0 4px 20px rgba(191, 0, 255, 0.3); border: 1px solid transparent;">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div
            style="text-align: center; margin-top: 20px; color: #666680; font-size: 0.9rem; position: relative; z-index: 1;">
            <!-- Removed default credentials display for security -->
            <p style="margin-top: 5px;">? Change password after first login!</p>
        </div>
    </div>
</div>

<style>
    @media (max-width: 480px) {
        div[style*="min-height: 100vh"] {
            padding: 10px;
        }

        div[style*="max-width: 400px"] {
            padding: 25px;
            margin: 10px;
        }

        h1[style*="font-size: 1.8rem"] {
            font-size: 1.5rem !important;
        }

        .form-group {
            margin-bottom: 15px !important;
        }

        input.form-control {
            padding: 10px !important;
            font-size: 0.9rem !important;
        }
    }
</style>

<?php
$content = ob_get_clean();

// Simple layout without sidebar for login
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - WiFiber Billing</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0a0a12">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Admin Panel">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../assets/icons/icon-192x192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body>
    <?php echo $content; ?>

    <script>
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }
    </script>
</body>

</html>
