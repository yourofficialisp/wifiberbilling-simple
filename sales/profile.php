<?php
/**
 * Sales Profileeeeeeeeeeee - Change Password
 */

require_once '../includes/auth.php';
requireSalesLogin();

$pageTitle = 'Profileeeeeeeeeeee Sales';
$salesId = $_SESSION['sales']['id'];

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid session.');
        redirect('profile.php');
    }

    $currentPass = $_POST['current_password'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    // Get current user data
    $salesUser = getSalesUser($salesId);

    if (!password_verify($currentPass, $salesUser['password'])) {
        setFlash('error', 'Current password is incorrect.');
    } elseif ($newPass !== $confirmPass) {
        setFlash('error', 'New password confirmation does not match.');
    } elseif (strlen($newPass) < 6) {
        setFlash('error', 'New password must be at least 6 characters.');
    } else {
        // Update password
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        if (update('sales_users', ['password' => $hashed], 'id = ?', [$salesId])) {
            setFlash('success', 'Password changed successfully.');
        } else {
            setFlash('error', 'Failed to change password.');
        }
    }
    redirect('profile.php');
}

$salesUser = getSalesUser($salesId);
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-circle"></i> Profileeeeeeeeeeee Sales</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #fff;">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <h2 style="margin: 0; color: var(--text-primary);"><?php echo htmlspecialchars($salesUser['name']); ?></h2>
                <div style="color: var(--neon-cyan);">@<?php echo htmlspecialchars($salesUser['username']); ?></div>
                <div style="color: var(--text-muted); margin-top: 5px;"><?php echo htmlspecialchars($salesUser['phone'] ?? '-'); ?></div>
            </div>
        </div>

        <hr style="border-color: var(--border-color); margin-bottom: 30px;">

        <h4 style="margin-bottom: 20px; color: var(--text-secondary);"><i class="fas fa-lock"></i> Change Password</h4>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i> Save Password
            </button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/sales_layout.php';
?>
