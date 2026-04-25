<?php
/**
 * Forgot Password Page
 */

require_once '../includes/auth.php';

// Check if already logged in
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

$pageTitle = 'Forgot Password';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        setFlash('error', 'Email must be filled!');
        redirect('forgot_password.php');
    }
    
    // Check if email exists
    $admin = fetchOne("SELECT * FROM admin_users WHERE email = ?", [$email]);
    
    if (!$admin) {
        // Don't reveal if email exists or not (security)
        setFlash('success', 'If email is registered, password reset instructions will be sent.');
        redirect('login.php');
    }
    
    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Save reset token to database (via settings table)
    update('admin_users', [
        'reset_token' => $resetToken,
        'reset_expiry' => $resetExpiry
    ], 'id = ?', [$admin['id']]);
    
    // Generate reset link
    $resetLink = APP_URL . '/admin/reset_password.php?token=' . $resetToken;
    
    // Send email with reset link (simulated)
    // In production, use actual email sending
    $subject = 'Reset Password - ' . APP_NAME;
    $message = "Hello {$admin['username']},\n\n";
    $message .= "You requested a password reset for your admin account.\n\n";
    $message .= "Click the following link to reset password:\n";
    $message .= $resetLink . "\n\n";
    $message .= "This link will expire in 1 hour.\n\n";
    $message .= "If you did not request a password reset, ignore this email.\n\n";
    $message .= "Thank you.";
    
    // Log the reset request
    logActivity('PASSWORD_RESET_REQUEST', "Email: {$email}");
    
    setFlash('success', 'Password reset instructions have been sent to your email.');
    redirect('login.php');
}

ob_start();
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; box-shadow: var(--shadow-card);">
        <div style="text-align: center; margin-bottom: 30px;">
            <i class="fas fa-key" style="font-size: 3rem; color: var(--neon-cyan); margin-bottom: 15px;"></i>
            <h1 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 5px;">Forgot Password</h1>
            <p style="color: var(--text-secondary);">Enter admin email to reset password</p>
        </div>
        
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars(getFlash('error')); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Admin Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@example.com" required autofocus>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-paper-plane"></i> Send Reset Link
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
