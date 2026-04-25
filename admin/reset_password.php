<?php
/**
 * Reset Password Page
 */

require_once '../includes/auth.php';

// Check if already logged in
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

$token = $_GET['token'] ?? '';

// Check if token is valid
if (empty($token)) {
    setFlash('error', 'Reset token is invalid or has expired.');
    redirect('login.php');
}

// Get admin by reset token
$admin = fetchOne("SELECT * FROM admin_users WHERE reset_token = ? AND reset_expiry > NOW()", [$token]);

if (!$admin) {
    setFlash('error', 'Reset token is invalid or has expired.');
    redirect('login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        setFlash('error', 'Password minimum 6 characters');
        redirect('reset_password.php?token=' . $token);
    }
    
    if ($password !== $confirmPassword) {
        setFlash('error', 'Passwords do not match!');
        redirect('reset_password.php?token=' . $token);
    }
    
    // Update password
    if (updateAdminPassword($admin['id'], $password)) {
        // Clear reset token
        update('admin_users', [
            'reset_token' => null,
            'reset_expiry' => null
        ], 'id = ?', [$admin['id']]);
        
        logActivity('PASSWORD_RESET_SUCCESS', "Admin ID: {$admin['id']}");
        
        setFlash('success', 'Password successfully changed. Please login with new password.');
        redirect('login.php');
    } else {
        setFlash('error', 'Failed to change password. Please try again.');
    }
}

$pageTitle = 'Reset Password';

ob_start();
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; box-shadow: var(--shadow-card);">
        <div style="text-align: center; margin-bottom: 30px;">
            <i class="fas fa-key" style="font-size: 3rem; color: var(--neon-cyan); margin-bottom: 15px;"></i>
            <h1 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 5px;">Reset Password</h1>
            <p style="color: var(--text-secondary);">Enter new password for admin account</p>
        </div>
        
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars(getFlash('error')); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 0.9rem;">
            <a href="login.php" style="color: var(--neon-cyan);">← Back to Login</a>
        </div>
    </div>
</div>

<style>
.form-group { margin-bottom: 20px; }
.form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #fff; }
.form-control {
    width: 100%;
    padding: 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    color: #fff;
    font-size: 1rem;
}
.form-control:focus { outline: none; border-color: #00f5ff; }
.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
    transition: all 0.3s;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,245,255,0.3);
}
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-error {
    background: rgba(255,71,87,0.2);
    border: 1px solid #ff4757;
    color: #ff4757;
}
</style>

<?php
$content = ob_get_clean();
echo $content;
