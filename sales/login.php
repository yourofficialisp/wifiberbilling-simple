<?php
/**
 * Sales Login Page
 */

require_once '../includes/auth.php';

// Check if already logged in
if (isSalesLoggedIn()) {
    redirect('dashboard.php');
    exit;
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

    $loginResult = salesLogin($username, $password);

    if ($loginResult === true) {
        setFlash('success', 'Login successful! Welcome.');
        redirect('dashboard.php');
    } elseif ($loginResult === 'inactive') {
        setFlash('error', 'Your account has been deactivated. Contact Admin.');
        redirect('login.php');
    } else {
        setFlash('error', 'Username or password is incorrect!');
        redirect('login.php');
    }
}

$appName = getSetting('app_name', 'WiFiber Billing');
$pageTitle = 'Login Sales';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo htmlspecialchars($appName); ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0a0a12">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Sales Portal">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../assets/icons/icon-192x192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #0a0a0f;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #12121a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .brand i {
            font-size: 3rem;
            color: #00f5ff;
            margin-bottom: 15px;
        }
        .brand h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ccc;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }
        .form-control:focus {
            outline: none;
            border-color: #00f5ff;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-error {
            background: rgba(255, 71, 87, 0.2);
            border: 1px solid #ff4757;
            color: #ff4757;
        }
        .alert-success {
            background: rgba(46, 213, 115, 0.2);
            border: 1px solid #2ed573;
            color: #2ed573;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <i class="fas fa-wallet"></i>
            <h1>SALES PORTAL</h1>
            <p style="color: #666;">Login untuk akses penjualan</p>
        </div>

        <?php if (hasFlash('error')): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars(getFlash('error')); ?>
            </div>
        <?php endif; ?>

        <?php if (hasFlash('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars(getFlash('success')); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Loginkan username" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Loginkan password" required>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px;">
            <a href="../index.php" style="color: #666; text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='#00f5ff'" onmouseout="this.style.color='#666'">
                <i class="fas fa-arrow-left"></i> Back ke Beranda
            </a>
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
</body>
</html>
