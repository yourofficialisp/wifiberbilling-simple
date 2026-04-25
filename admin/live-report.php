<?php
/**
 * Live Report - Real-time Hotspot Logs
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Live Report';

ob_start();
?>

<!-- Header with Live Indicator -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <div
            style="width: 12px; height: 12px; background: #00ff00; border-radius: 50%; box-shadow: 0 0 10px #00ff00; animation: blink 1.5s infinite;">
        </div>
        <h3 style="margin: 0; font-weight: 600;">Real-time Monitoring</h3>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="clearLogs()" class="btn btn-secondary btn-sm"><i class="fas fa-trash"></i> Clear View</button>
        <select id="logLimit" class="form-control" style="width: 100px; padding: 4px 10px; height: auto;"
            onchange="loadLiveLogs()">
            <option value="20">20 lines</option>
            <option value="50">50 lines</option>
            <option value="100">100 lines</option>
        </select>
    </div>
</div>

<!-- Logs Container -->
<div class="card" style="padding: 0; overflow: hidden; background: #000; border: 1px solid #333;">
    <div style="height: 65vh; overflow-y: auto; padding: 15px; font-family: 'Consolas', 'Monaco', monospace; line-height: 1.6;"
        id="liveLogContainer">
        <div id="liveLogContent">
            <div style="color: #666;">Initializing connection to MikroTik log stream...</div>
        </div>
    </div>
</div>

<style>
    @keyframes blink {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.3;
        }

        100% {
            opacity: 1;
        }
    }

    .log-entry {
        margin-bottom: 4px;
        border-bottom: 1px solid #111;
        padding-bottom: 4px;
        display: flex;
        gap: 15px;
    }

    .log-time {
        color: #00f5ff;
        min-width: 80px;
    }

    .log-user {
        color: #bf00ff;
        font-weight: bold;
        min-width: 120px;
    }

    .log-msg {
        color: #eee;
    }

    .log-msg.important {
        color: #ffeb3b;
    }

    .log-msg.error {
        color: #f44336;
    }
</style>

<script>
    let lastLogHash = '';

    function loadLiveLogs() {
        const limit = document.getElementById('logLimit').value;
        fetch('../api/hotspot-log.php?limit=' + limit)
            .then(r => r.json())
            .then(logs => {
                const container = document.getElementById('liveLogContent');
                if (!logs || logs.length === 0) return;

                // Simple hash to avoid re-rendering if nothing changed
                const currentHash = JSON.stringify(logs);
                if (currentHash === lastLogHash) return;
                lastLogHash = currentHash;

                let html = '';
                logs.forEach(log => {
                    let msgClass = 'log-msg';
                    if (log.message.toLowerCase().includes('error') || log.message.toLowerCase().includes('failed')) msgClass += ' error';
                    if (log.message.toLowerCase().includes('logged in') || log.message.toLowerCase().includes('logged out')) msgClass += ' important';

                    html += '<div class="log-entry">' +
                        '<span class="log-time">[' + escapeHtml(log.time) + ']</span>' +
                        '<span class="log-user">' + escapeHtml(log.user) + '</span>' +
                        '<span class="' + msgClass + '">' + escapeHtml(log.message) + '</span>' +
                        '</div>';
                });
                container.innerHTML = html;

                // Keep scroll at bottom if already near bottom
                const scrollContainer = document.getElementById('liveLogContainer');
                scrollContainer.scrollTop = scrollContainer.scrollHeight;
            })
            .catch(err => console.error('Live log error:', err));
    }

    function clearLogs() {
        document.getElementById('liveLogContent').innerHTML = '<div style="color: #666;">View cleared. Waiting for new logs...</div>';
    }

    function escapeHtml(text) {
        if (!text) return '-';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadLiveLogs();
        setInterval(loadLiveLogs, 3000);
    });
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
