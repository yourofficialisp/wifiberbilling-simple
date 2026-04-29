<?php
require_once '../includes/auth.php';
requireTechnicianLogin();

$tech = $_SESSION['technician'];
$pageTitle = 'Profileeeeeeeeeee Saya';

// Handle Update Profileeeeeeeeeeee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    
    $updateData = [
        'name' => $name,
        'phone' => $phone,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if (!empty($password)) {
        $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    if (update('technician_users', $updateData, 'id = ?', [$tech['id']])) {
        // Update Session
        $_SESSION['technician']['name'] = $name;
        $_SESSION['technician']['phone'] = $phone;
        
        setFlash('success', 'Profile updated successfully');
        redirect('profile.php');
    } else {
        setFlash('error', 'Failed to update profile');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($tech['name']); ?></title>
    <meta name="theme-color" content="#0a0a12">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" href="../assets/icons/icon-192x192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00f5ff;
            --bg-dark: #0a0a12;
            --bg-card: #161628;
            --text-primary: #ffffff;
            --text-secondary: #b0b0c0;
            --danger: #ff4757;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body {
            background: var(--bg-dark);
            color: var(--text-primary);
            padding-bottom: 80px;
        }
        
        .header {
            background: var(--bg-card);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .back-btn {
            color: var(--text-primary);
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .container { padding: 20px; }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary), #bf00ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 15px;
            box-shadow: 0 0 20px rgba(0, 245, 255, 0.3);
        }
        
        .card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: var(--text-secondary); }
        .form-control {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-primary);
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            border: none;
            border-radius: 10px;
            color: #000;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .btn-logout {
            display: block;
            width: 100%;
            padding: 15px;
            background: rgba(255, 71, 87, 0.1);
            color: var(--danger);
            border: 1px solid rgba(255, 71, 87, 0.3);
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h2>Profileeeeeeeeeee Saya</h2>
    </div>

    <div class="container">
        <div class="profile-header">
            <div class="avatar">
                <i class="fas fa-user"></i>
            </div>
            <h3><?php echo htmlspecialchars($tech['name']); ?></h3>
            <p style="color: var(--text-secondary);">@<?php echo htmlspecialchars($tech['username']); ?></p>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 15px; color: var(--primary);">Edit Profileeeeeeeeeee</h3>
            
            <?php if (hasFlash('success')): ?>
                <div style="background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                    <?php echo getFlash('success'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($tech['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($tech['username']); ?>" readonly style="opacity: 0.5">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone / WA</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($tech['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Change Password (Optional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin ubah">
                </div>
                
                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
            
            <a href="logout.php" class="btn-logout" onclick="return confirm('Logout from application?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <?php require_once 'includes/bottom_nav.php'; ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../sw.js');
            });
        }
    </script>
</body>
</html>
