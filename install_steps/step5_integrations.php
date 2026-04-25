<?php
// Step 5: Integrations Setup (Optional)
?>

<h2>🔗 Integrations Setup (Optional)</h2>
<p style="margin-bottom: 20px; color: #666;">Setup integrations with WhatsApp, Payment Gateway, and Telegram.</p>

<div class="alert alert-info">
    <strong>ℹ️ Info:</strong> This setup is optional. If you want to setup later, you can skip this step and setup through admin panel.
</div>

<form method="POST" action="install.php?step=5">
    <h3 style="margin-bottom: 15px; color: #667eea;">📱 WhatsApp Integration</h3>
    <div class="form-group">
        <label for="whatsapp_url">WhatsApp API URL</label>
        <input type="text" id="whatsapp_url" name="whatsapp_url" placeholder="https://api.fonnte.com/send">
        <small style="color: #666;">WhatsApp API URL (Fonnte, etc.)</small>
    </div>
    
    <div class="form-group">
        <label for="whatsapp_token">WhatsApp Token</label>
        <input type="text" id="whatsapp_token" name="whatsapp_token" placeholder="Enter WhatsApp token">
        <small style="color: #666;">WhatsApp API token</small>
    </div>
    
    <hr style="margin: 30px 0; border-color: #e9ecef;">
    
    <h3 style="margin-bottom: 15px; color: #667eea;">💳 Payment Gateway (Tripay)</h3>
    <div class="form-group">
        <label for="tripay_api_key">Tripay API Key</label>
        <input type="text" id="tripay_api_key" name="tripay_api_key" placeholder="Enter Tripay API Key">
        <small style="color: #666;">API Key from Tripay dashboard</small>
    </div>
    
    <div class="form-group">
        <label for="tripay_private_key">Tripay Private Key</label>
        <input type="text" id="tripay_private_key" name="tripay_private_key" placeholder="Enter Tripay Private Key">
        <small style="color: #666;">Private Key from Tripay dashboard</small>
    </div>
    
    <div class="form-group">
        <label for="tripay_merchant_code">Tripay Merchant Code</label>
        <input type="text" id="tripay_merchant_code" name="tripay_merchant_code" placeholder="Enter Merchant Code">
        <small style="color: #666;">Merchant Code from Tripay dashboard</small>
    </div>
    
    <hr style="margin: 30px 0; border-color: #e9ecef;">
    
    <h3 style="margin-bottom: 15px; color: #667eea;">🤖 Telegram Bot</h3>
    <div class="form-group">
        <label for="telegram_token">Telegram Bot Token</label>
        <input type="text" id="telegram_token" name="telegram_token" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
        <small style="color: #666;">Token from @BotFather</small>
    </div>

    <div class="form-group">
        <label for="telegram_chat_id">Telegram Chat ID (Optional)</label>
        <input type="text" id="telegram_chat_id" name="telegram_chat_id" placeholder="Example: 123456789 or -1001234567890">
        <small style="color: #666;">Target chat ID for installation notifications (user/group/channel)</small>
    </div>

    <div class="form-group" style="margin-top: 10px;">
        <label style="display:flex;gap:10px;align-items:center;">
            <input type="checkbox" name="install_notify_telegram" value="1" style="width:auto;">
            Send installation notification to Telegram (optional)
        </label>
    </div>

    <hr style="margin: 30px 0; border-color: #e9ecef;">

    <h3 style="margin-bottom: 15px; color: #667eea;">📡 Install</h3>
    <div class="form-group">
        <label for="install_relay_url">continue install</label>
        <input type="text" id="install_relay_url" name="install_relay_url" value="Must be checked yes" placeholder="https://github.com/yourofficialisp">
        <small style="color: #666;">continue installation</small>
    </div>

    <div class="form-group" style="margin-top: 10px;">
        <label style="display:flex;gap:10px;align-items:center;">
            <input type="checkbox" name="install_notify_relay" value="1" style="width:auto;" checked required>
            I agree to continue installation
        </label>
        <small style="color: #666;">Must be checked to continue.</small>
    </div>
    
    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <a href="install.php?step=4" class="btn btn-secondary">← Back</a>
        <button type="submit" class="btn btn-primary">Continue →</button>
    </div>
</form>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
});
</script>
