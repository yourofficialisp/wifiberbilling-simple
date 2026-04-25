<?php
/**
 * Customer Portal Login
 */

require_once '../includes/auth.php';

// Check if already logged in
if (isCustomerLoggedIn()) {
    redirect('dashboard.php');
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Session is invalid or has expired. Please try again.');
        redirect('login.php');
    }

    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $throttleStatus = getLoginThrottleStatus('customer', $phone, 5, 900, 900);
    if ($throttleStatus['blocked']) {
        $retryAfter = max(1, (int) ceil($throttleStatus['retry_after'] / 60));
        setFlash('error', 'Too many login attempts. Try again in ' . $retryAfter . ' minutes.');
        redirect('login.php');
    }

    if (customerLogin($phone, $password)) {
        clearLoginFailures('customer', $phone);
        setFlash('success', 'Login successful! Welcome.');
        redirect('dashboard.php');
    } else {
        addLoginFailure('customer', $phone, 5, 900, 900);
        setFlash('error', 'Phone number or password is incorrect!');
        redirect('login.php');
    }
}

$appName = getSetting('app_name', 'WiFiber Billing');
$pageTitle = 'Customer Login';
$content = '';

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($pageTitle . ' - ' . $appName); ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0a0a12">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Customer Portal">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../assets/icons/icon-192x192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Inter', sans-serif;
        overflow-x: hidden;
        background: #0a0a12;
    }

    :root {
        --neon-cyan: #00f5ff;
        --neon-purple: #bf00ff;
    }

    .login-container {
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: radial-gradient(circle at center, #1a1a2e 0%, #0a0a12 100%);
        overflow: hidden;
    }

    .login-card {
        background: #1a1a2e;
        border: 1px solid #2a2a40;
        border-radius: 16px;
        padding: 60px;
        width: 100%;
        max-width: 600px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
    }

    .login-header-icon {
        font-size: 3rem;
        background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 15px;
        display: inline-block;
    }

    .login-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 5px;
        background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .login-subtitle {
        color: #b0b0c0;
        margin: 0;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #ffffff;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        background: #161628;
        border: 1px solid #2a2a40;
        border-radius: 8px;
        color: #ffffff;
        font-size: 1rem;
        box-sizing: border-box;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--neon-cyan);
    }

    .btn-login {
        width: 100%;
        padding: 12px 20px;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        color: #ffffff;
        background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
        transition: all 0.3s;
        box-shadow: 0 4px 20px rgba(191, 0, 255, 0.3);
    }
    
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0, 245, 255, 0.4);
    }

    .login-help {
        margin-top: 20px;
        padding: 15px;
        background: rgba(0, 245, 255, 0.1);
        border: 1px solid var(--neon-cyan);
        border-radius: 8px;
        color: #00f5ff;
        font-size: 0.9rem;
        text-align: center;
    }

    .login-footer {
        text-align: center;
        margin-top: 20px;
        color: #666680;
        font-size: 0.9rem;
        position: relative;
        z-index: 1;
    }
    
    .login-footer a {
        color: #00f5ff;
        text-decoration: none;
        display: inline-block;
        margin-top: 10px;
        transition: color 0.3s;
    }
    
    .login-footer a:hover {
        color: var(--neon-purple);
    }

    @media (max-width: 480px) {
        .login-container {
            padding: 0;
        }

        .login-card {
            padding: 30px 25px;
            max-width: 100%;
            border-radius: 0;
            min-height: 100vh;
            border: none;
            display: flex;
            flex-direction: column;
            justify-content: space-around; /* Spread content vertically */
        }

        .login-title {
            font-size: 2.5rem;
        }

        .login-subtitle {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-label {
            font-size: 1.1rem;
            margin-bottom: 12px;
        }

        .form-control {
            padding: 16px;
            font-size: 1.1rem;
            border-radius: 12px;
        }
        
        .btn-login {
            padding: 16px;
            font-size: 1.2rem;
            border-radius: 14px;
        }

        .login-header-icon {
            font-size: 4rem;
        }

        .login-help, .login-footer {
            font-size: 1rem;
        }
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div style="text-align: center; margin-bottom: 30px; position: relative; z-index: 1;">
            <i class="fas fa-network-wired login-header-icon"></i>
            <h1 class="login-title"><?php echo htmlspecialchars($appName); ?></h1>
            <p class="login-subtitle">Portal Customer</p>
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
            <div class="form-group">
                <label class="form-label">Nomor HP</label>
                <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Loginkan password" required>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="login-help">
            <p style="margin: 0; font-size: 0.85rem;">
                <small>Contact admin jika lupa password</small>
            </p>
        </div>

        <div class="login-footer">
            <p style="margin: 0;">Not yet punya akun? Contact admin.</p>
            <a href="../index.php">← Back ke Beranda</a>
        </div>
    </div>
</div>

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

<?php
$content = ob_get_clean();
echo $content;
?>
</body>
</html>
