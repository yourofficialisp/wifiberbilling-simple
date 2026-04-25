<?php
// Step 4: Finish Installation
?>

<h2>🎉 Ready to Install!</h2>
<p style="margin-bottom: 20px; color: #666;">Installer is ready to install GEMBOK to your server.</p>

<div class="alert alert-info">
    <strong>📋 Summary:</strong>
    <ul style="margin: 10px 0 0 20px; color: #666;">
        <li>Database: <?php echo htmlspecialchars($_SESSION['db_config']['name'] ?? 'Not configured'); ?></li>
        <li>Admin Username: <?php echo htmlspecialchars($_SESSION['admin_config']['username'] ?? 'Not configured'); ?></li>
    </ul>
</div>

<div class="alert alert-success">
    <strong>✅ What the installer will do:</strong>
    <ul style="margin: 10px 0 0 20px; color: #666;">
        <li>Create configuration file (includes/config.php)</li>
        <li>Create all database tables</li>
        <li>Insert admin user and basic settings (without sample data)</li>
        <li>Create installation lock file</li>
    </ul>
</div>

<div class="alert alert-warning">
    <strong>⚠️ Important:</strong>
    <ul style="margin: 10px 0 0 20px; color: #666;">
        <li>Make sure database has been created in cPanel/phpMyAdmin</li>
        <li>Installer will delete all data in the same database</li>
        <li>Backup important data before continuing</li>
        <li>Installer will send domain & installation time to relay server (if you agreed in the initial step)</li>
        <li>After successful installation, delete install.sh file from server if used for installation to prevent re-running and deleting existing data</li>
    </ul>
</div>

<form method="POST" action="install.php?step=4">
    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <a href="install.php?step=3" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            🚀 Install Now
        </button>
    </div>
</form>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to install GEMBOK?\n\nDatabase will be reset and all data will be deleted.\n\nContinue?')) {
        e.preventDefault();
        return false;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Installing... Please wait...';
});
</script>
