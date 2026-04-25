<?php
// Step 4: MikroTik Setup (Optional)
?>

<h2>📡 MikroTik Setup (Optional)</h2>
<p style="margin-bottom: 20px; color: #666;">Setup connection to MikroTik Router for PPPoE & Hotspot management.</p>

<div class="alert alert-info">
    <strong>ℹ️ Info:</strong> This setup is optional. If you don't have MikroTik yet or want to setup later, you can skip this step.
</div>

<form method="POST" action="install.php?step=4">
    <div class="form-group">
        <label for="mikrotik_host">MikroTik IP Address</label>
        <input type="text" id="mikrotik_host" name="mikrotik_host" placeholder="192.168.1.1">
        <small style="color: #666;">IP address MikroTik Router</small>
    </div>
    
    <div class="form-group">
        <label for="mikrotik_user">MikroTik Username</label>
        <input type="text" id="mikrotik_user" name="mikrotik_user" placeholder="admin">
        <small style="color: #666;">MikroTik login username</small>
    </div>
    
    <div class="form-group">
        <label for="mikrotik_pass">MikroTik Password</label>
        <input type="password" id="mikrotik_pass" name="mikrotik_pass" placeholder="Enter MikroTik password">
        <small style="color: #666;">MikroTik login password</small>
    </div>
    
    <div class="form-group">
        <label for="mikrotik_port">API Port</label>
        <input type="number" id="mikrotik_port" name="mikrotik_port" value="8728">
        <small style="color: #666;">Default: 8728 (API port)</small>
    </div>
    
    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <a href="install.php?step=3" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn btn-primary">Continue →</button>
    </div>
</form>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    // MikroTik is optional, so allow empty values
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
});
</script>
