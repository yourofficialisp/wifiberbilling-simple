<?php
/**
 * Voucher Template Editor
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Voucher Template Editor';
$templateDir = '../templates/vouchers/';

// Ensure directory exists
if (!is_dir($templateDir)) {
    mkdir($templateDir, 0777, true);
}

$message = '';
$error = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        if ($_POST['action'] === 'save') {
            $filename = basename($_POST['filename']);
            $content = $_POST['content'];

            if (empty($filename)) {
                $error = 'Filename cannot be empty';
            } else {
                if (!str_ends_with($filename, '.php')) {
                    $filename .= '.php';
                }

                if (file_put_contents($templateDir . $filename, $content) !== false) {
                    $message = "Template '$filename' saved successfully.";
                } else {
                    $error = "Failed to save template '$filename'.";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $filename = basename($_POST['filename']);
            if ($filename !== 'default.php' && file_exists($templateDir . $filename)) {
                unlink($templateDir . $filename);
                $message = "Template '$filename' deleted.";
            }
        }
    }
}

// Get templates
$templates = glob($templateDir . '*.php');
$templateList = array_map('basename', $templates);

$selectedTemplate = $_GET['template'] ?? (in_array('default.php', $templateList) ? 'default.php' : ($templateList[0] ?? ''));
$currentContent = '';

if ($selectedTemplate && file_exists($templateDir . $selectedTemplate)) {
    $currentContent = file_get_contents($templateDir . $selectedTemplate);
}

ob_start();
?>

<div class="row" style="display: flex; flex-wrap: wrap; gap: 20px;">
    <!-- Left Column: Edit Template -->
    <div style="flex: 2; min-width: 500px;">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title"><i class="fas fa-edit"></i> Edit Template:
                    <?php echo htmlspecialchars($selectedTemplate); ?>
                </h3>
                <?php if ($selectedTemplate !== 'default.php' && $selectedTemplate !== ''): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this template?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="filename" value="<?php echo htmlspecialchars($selectedTemplate); ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="templateForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="save">
                    <div class="form-group">
                        <label>Select Template</label>
                        <select name="template_select" id="templateSelect" class="form-control" onchange="changeTemplate()">
                            <?php foreach ($templateList as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $t === $selectedTemplate ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>File Name</label>
                        <input type="text" name="filename" id="filenameInput" class="form-control"
                            value="<?php echo htmlspecialchars($selectedTemplate); ?>" <?php echo $selectedTemplate === 'default.php' ? 'readonly' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label>Template Content (HTML/CSS)</label>
                        <textarea name="content" id="templateEditor" class="form-control"
                            style="height: 500px; font-family: 'Cascadia Code', 'Fira Code', monospace; background: #0d0d15; color: #00f5ff; border: 1px solid #2a2a40; line-height: 1.5;"><?php echo htmlspecialchars($currentContent); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Save Template
                    </button>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-th-large"></i> Preview All Templates</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                    <?php foreach ($templateList as $t): ?>
                        <div onclick="selectTemplate('<?php echo urlencode($t); ?>')"
                            style="cursor: pointer; border: 2px solid <?php echo $t === $selectedTemplate ? 'var(--neon-cyan)' : 'var(--border-color)'; ?>; 
                                   border-radius: 8px; overflow: hidden; transition: all 0.3s;"
                            onmouseover="this.style.borderColor='var(--neon-cyan)'"
                            onmouseout="this.style.borderColor='<?php echo $t === $selectedTemplate ? 'var(--neon-cyan)' : 'var(--border-color)'; ?>'"
                            title="Click to edit: <?php echo htmlspecialchars($t); ?>">
                            <div style="padding: 8px; background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-secondary) 100%); 
                                        text-align: center; font-size: 11px; color: var(--text-secondary);">
                                <?php echo htmlspecialchars(str_replace('.php', '', $t)); ?>
                            </div>
                            <iframe src="preview_template.php?template=<?php echo urlencode($t); ?>"
                                style="width: 100%; height: 120px; border: none; background: white;"></iframe>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Live Preview & Others -->
    <div style="flex: 1; min-width: 350px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-cyan"><i class="fas fa-eye"></i> Live Preview</h3>
            </div>
            <div class="card-body"
                style="padding: 10px; background: #f0f2f5; border-radius: 8px; overflow: hidden; min-height: 250px; display: flex; justify-content: center; align-items: flex-start;">
                <div id="previewContainer" style="transform: scale(0.9); transform-origin: top center; width: 100%;">
                    <iframe id="previewFrame"
                        style="width: 100%; border: none; height: 400px; background: white; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);"></iframe>
                </div>
            </div>
            <div class="card-footer"
                style="padding: 10px; font-size: 0.75rem; color: var(--text-muted); text-align: center;">
                <i class="fas fa-info-circle"></i> The display above is a voucher simulation.
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-folder"></i> Template List</h3>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($templateList as $t): ?>
                        <a href="?template=<?php echo urlencode($t); ?>"
                            class="list-group-item <?php echo $t === $selectedTemplate ? 'active' : ''; ?>"
                            style="display: flex; justify-content: space-between; align-items: center;">
                            <span><?php echo htmlspecialchars($t); ?></span>
                            <button onclick="copyTemplate('<?php echo htmlspecialchars($t); ?>')" 
                                style="background: none; border: none; color: inherit; cursor: pointer;"
                                title="Copy Template">
                                <i class="fas fa-copy"></i>
                            </button>
                        </a>
                    <?php endforeach; ?>
                </div>
                <hr>
                <a href="voucher-editor.php" class="btn btn-secondary btn-sm" style="width: 100%;">
                    <i class="fas fa-plus"></i> New Template
                </a>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> Available Variables</h3>
            </div>
            <div class="card-body">
                <p><small>Use double curly braces, example: <code>{{username}}</code></small></p>
                <table class="table table-sm">
                    <tr>
                        <td><code>{{username}}</code></td>
                        <td>Username</td>
                    </tr>
                    <tr>
                        <td><code>{{password}}</code></td>
                        <td>Password</td>
                    </tr>
                    <tr>
                        <td><code>{{hotspotname}}</code></td>
                        <td>Hotspot Name</td>
                    </tr>
                    <tr>
                        <td><code>{{dnsname}}</code></td>
                        <td>DNS Name</td>
                    </tr>
                    <tr>
                        <td><code>{{price}}</code></td>
                        <td>Price</td>
                    </tr>
                    <tr>
                        <td><code>{{validity}}</code></td>
                        <td>Validity Period</td>
                    </tr>
                    <tr>
                        <td><code>{{profile}}</code></td>
                        <td>Profileeeeeeeeeeee</td>
                    </tr>
                    <tr>
                        <td><code>{{num}}</code></td>
                        <td>Serial Number</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const editor = document.getElementById('templateEditor');
    const previewFrame = document.getElementById('previewFrame');
    const templateSelect = document.getElementById('templateSelect');
    const filenameInput = document.getElementById('filenameInput');

    function updatePreview() {
        let content = editor.value;
        const dummy = {
            '{{username}}': 'MARWAN-USER',
            '{{password}}': 'SECRET123',
            '{{price}}': 'Rp 5.000',
            '{{validity}}': '24 Hours',
            '{{hotspotname}}': 'Gembok WiFi',
            '{{dnsname}}': 'hotspot.net',
            '{{num}}': '1',
            '{{profile}}': 'Member-1',
            '{{price_small}}': 'Rp',
            '{{price_big}}': '5.000',
            '{{timelimit}}': '1h',
            '{{datalimit}}': '1GB',
            '{{logo}}': 'https://placehold.co/85x20/000000/FFFFFF?text=LOGO',
            '{{qrcode}}': '<div style="width:50px;height:50px;background:#000;color:#fff;display:flex;align-items:center;justify-content:center;font-size:8px;border:2px solid #fff;">QR CODE</div>'
        };

        // Replace all placeholders
        for (let key in dummy) {
            content = content.split(key).join(dummy[key]);
        }

        const doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
        doc.open();
        // Add a small helper to center the voucher in the iframe
        const wrappedContent = `
            <style>
                body { margin: 0; display: flex; justify-content: center; padding: 10px; background: transparent; }
                * { box-sizing: border-box; }
            </style>
            ${content}
        `;
        doc.write(wrappedContent);
        doc.close();
    }

    // Change template function
    function changeTemplate() {
        const selectedTemplate = templateSelect.value;
        
        // Update filename input
        filenameInput.value = selectedTemplate;
        
        // Load template content
        fetch('../templates/vouchers/' + selectedTemplate)
            .then(response => response.text())
            .then(content => {
                // Update editor content
                editor.value = content;
                
                // Update preview
                updatePreview();
            })
            .catch(error => {
                console.error('Failed to load template:', error);
            });
    }

    // Initial preview
    updatePreview();

    // Live update on input
    editor.addEventListener('input', updatePreview);

    // Copy template function
    function copyTemplate(templateName) {
        event.stopPropagation();
        
        if (confirm('Copy template "' + templateName + '"?')) {
            // Get template content
            const templateDir = '../templates/vouchers/';
            fetch(templateDir + templateName)
                .then(response => response.text())
                .then(content => {
                    // Generate new filename
                    const baseName = templateName.replace('.php', '');
                    const newName = baseName + '_copy.php';
                    
                    // Set the editor content and filename
                    editor.value = content;
                    document.querySelector('input[name="filename"]').value = newName;
                    
                    // Update preview
                    updatePreview();
                    
                    alert('Template "' + templateName + '" successfully copied as "' + newName + '"');
                })
                .catch(error => {
                    alert('Failed to copy template: ' + error.message);
                });
        }
    }

    // Select template function
    function selectTemplate(templateName) {
        window.location.href = '?template=' + encodeURIComponent(templateName);
    }
</script>

<style>
    .text-cyan {
        color: var(--neon-cyan) !important;
    }

    .list-group-item {
        display: block;
        padding: 10px 15px;
        color: var(--text-primary);
        text-decoration: none;
        border: 1px solid var(--border-color);
        margin-bottom: 5px;
        border-radius: 4px;
    }

    .list-group-item.active {
        background: var(--neon-cyan);
        color: #000;
        border-color: var(--neon-cyan);
    }

    .list-group-item:hover:not(.active) {
        background: rgba(255, 255, 255, 0.05);
    }
</style>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>