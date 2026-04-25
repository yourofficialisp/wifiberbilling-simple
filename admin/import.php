<?php
/**
 * Import Customers from Excel/CSV
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Import Customers';

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['importFile'])) {
        $file = $_FILES['importFile'];
        $filename = strtolower($file['name']);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $rows = [];
        
        // Parse file based on extension
        if ($extension === 'csv') {
            // Parse CSV
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                setFlash('error', 'Failed to open file!');
                redirect('export.php');
            }
            
            // Skip header row
            $headers = fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
            
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            // For Excel files, we'll convert to CSV format using a simple approach
            // Since we don't have PHPSpreadsheet, we'll use a workaround
            
            // Try to read as XML (for .xls XML format)
            $content = file_get_contents($file['tmp_name']);
            
            if (strpos($content, '<?xml') !== false) {
                // XML Spreadsheet format
                $xml = simplexml_load_string($content);
                if ($xml) {
                    $namespaces = $xml->getNamespaces(true);
                    $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
                    
                    $cells = $xml->xpath('//ss:Row/ss:Cell/ss:Data');
                    $colCount = 0;
                    $row = [];
                    
                    // Determine column count from first row
                    foreach ($xml->xpath('//ss:Row[1]/ss:Cell') as $cell) {
                        $colCount++;
                    }
                    
                    $rowIndex = 0;
                    $currentRow = [];
                    foreach ($cells as $cell) {
                        $currentRow[] = (string)$cell;
                        if (count($currentRow) == $colCount) {
                            $rows[] = $currentRow;
                            $currentRow = [];
                        }
                    }
                    
                    // Remove header row
                    if (!empty($rows)) {
                        array_shift($rows);
                    }
                }
            } else {
                // Try to parse as tab-separated (common Excel export)
                $lines = explode("\n", $content);
                if (count($lines) > 1) {
                    // Skip header
                    for ($i = 1; $i < count($lines); $i++) {
                        $line = trim($lines[$i]);
                        if (!empty($line)) {
                            // Try tab first, then comma
                            if (strpos($line, "\t") !== false) {
                                $rows[] = explode("\t", $line);
                            } else {
                                $rows[] = str_getcsv($line);
                            }
                        }
                    }
                }
            }
        } else {
            setFlash('error', 'File format not supported! Use CSV, XLS, or XLSX.');
            redirect('export.php');
        }
        
        // Process rows
        foreach ($rows as $rowNum => $data) {
            $actualRow = $rowNum + 2; // +2 because row 1 is header
            
            // Map columns - expected order:
            // Name, Phone, PPPoE Username, Package, Status, Isolation Date, Auto Isolate (optional), Address, Latitude, Longitude
            $name = trim($data[0] ?? '');
            $phone = trim($data[1] ?? '');
            $pppoeUsername = trim($data[2] ?? '');
            $packageName = trim($data[3] ?? '');
            $statusText = trim($data[4] ?? 'Active');
            $isolationDate = trim($data[5] ?? '20');
            $autoIsolateText = trim($data[6] ?? 'Yes');
            $address = trim($data[7] ?? '');
            $lat = str_replace(',', '.', trim($data[8] ?? ''));
            $lng = str_replace(',', '.', trim($data[9] ?? ''));
            
            // Validate required fields
            if (empty($name) || empty($phone) || empty($pppoeUsername)) {
                $errors[] = "Row {$actualRow}: Incomplete data (name, phone, PPPoE username are required)";
                $errorCount++;
                continue;
            }
            
            // Check if customer already exists
            $existing = fetchOne("SELECT id FROM customers WHERE pppoe_username = ?", [$pppoeUsername]);
            
            if ($existing) {
                $errors[] = "Row {$actualRow}: Customer with PPPoE username '{$pppoeUsername}' already exists!";
                $errorCount++;
                continue;
            }
            
            // Get package info
            $package = null;
            if (!empty($packageName)) {
                $package = fetchOne("SELECT id FROM packages WHERE name = ? OR name LIKE ?", [$packageName, "%{$packageName}%"]);
            }
            
            if (!$package) {
                $errors[] = "Row {$actualRow}: Package '{$packageName}' not found!";
                $errorCount++;
                continue;
            }

            // Map status
            $status = (strtolower($statusText) === 'isolir' || strtolower($statusText) === 'isolated') ? 'isolated' : 'active';
            
            // Insert customer
            $customerData = [
                'name' => sanitize($name),
                'phone' => sanitize($phone),
                'pppoe_username' => sanitize($pppoeUsername),
                'package_id' => $package['id'],
                'status' => $status,
                'isolation_date' => (int)($isolationDate ?: 20),
                'auto_isolate' => (strtolower($autoIsolateText) === 'tidak' || strtolower($autoIsolateText) === 'no' || $autoIsolateText === '0') ? 0 : 1,
                'address' => sanitize($address),
                'lat' => $lat ? (float)$lat : null,
                'lng' => $lng ? (float)$lng : null,
                'portal_password' => password_hash('1234', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (insert('customers', $customerData)) {
                $successCount++;
            } else {
                $errors[] = "Row {$actualRow}: Failed to save customer!";
                $errorCount++;
            }
        }
        
        if ($errorCount > 0) {
            setFlash('warning', "Import completed. Success: {$successCount}, Failed: {$errorCount}");
            if (!empty($errors)) {
                logActivity('IMPORT_CUSTOMERS', "Success: {$successCount}, Failed: {$errorCount}");
            }
        } else {
            setFlash('success', "Import successful! {$successCount} customers successfully imported.");
            logActivity('IMPORT_CUSTOMERS', "Imported {$successCount} customers");
        }
        
        redirect('customers.php');
    }
}

ob_start();
?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-upload"></i> Import Customers</h3>
        </div>
        
        <p style="margin-bottom: 20px; color: var(--text-secondary);">
            Upload Excel or CSV file for bulk customer import.
        </p>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Select File (Excel/CSV)</label>
                <input type="file" name="importFile" class="form-control" accept=".csv,.xls,.xlsx" required>
                <small style="color: var(--text-muted);">Supported formats: CSV, XLS, XLSX</small>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload & Import
                </button>
                <a href="export.php?action=export_excel" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Download Template
                </a>
                <a href="customers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle"></i> Format File</h3>
        </div>
        
        <p style="color: var(--text-secondary);">
            The file must contain the following columns (first row as header):
        </p>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Description</th>
                    <th>Example</th>
                    <th>Required</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Name</td>
                    <td>Customer full name</td>
                    <td>John Doe</td>
                    <td><span class="badge badge-success">Yes</span></td>
                </tr>
                <tr>
                    <td>Phone</td>
                    <td>WhatsApp number</td>
                    <td>08123456789</td>
                    <td><span class="badge badge-success">Yes</span></td>
                </tr>
                <tr>
                    <td>PPPoE Username</td>
                    <td>PPPoE username in MikroTik</td>
                    <td>customer01</td>
                    <td><span class="badge badge-success">Yes</span></td>
                </tr>
                <tr>
                    <td>Package</td>
                    <td>Package name (must match name in the system)</td>
                    <td>10 Mbps Package</td>
                    <td><span class="badge badge-success">Yes</span></td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>Customer status (Active / Isolated)</td>
                    <td>Active</td>
                    <td><span class="badge badge-info">Optional</span></td>
                </tr>
                <tr>
                    <td>Isolation Date</td>
                    <td>Isolation day of month (1-28)</td>
                    <td>20</td>
                    <td><span class="badge badge-info">Optional</span></td>
                </tr>
                <tr>
                    <td>Address</td>
                    <td>Full address</td>
                    <td>123 Example St.</td>
                    <td><span class="badge badge-info">Optional</span></td>
                </tr>
                <tr>
                    <td>Latitude</td>
                    <td>Coordinate point</td>
                    <td>-6.200000</td>
                    <td><span class="badge badge-info">Optional</span></td>
                </tr>
                <tr>
                    <td>Longitude</td>
                    <td>Coordinate point</td>
                    <td>106.816666</td>
                    <td><span class="badge badge-info">Optional</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}
.card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--neon-cyan);
}
.form-group { margin-bottom: 20px; }
.form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-secondary); }
.form-control {
    width: 100%;
    padding: 12px;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 1rem;
}
.form-control:focus { outline: none; border-color: var(--neon-cyan); }
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    background: linear-gradient(135deg, #00f5ff 0%, #bf00ff 100%);
    transition: all 0.3s;
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,245,255,0.3); }
.btn-secondary {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}
.btn-secondary:hover { background: rgba(255, 255,255,0.05); }
.badge-success { background: var(--neon-green); color: #000; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
.badge-info { background: var(--neon-cyan); color: #000; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
.data-table {
    width: 100%;
    border-collapse: collapse;
}
.data-table thead { background: var(--bg-secondary); }
.data-table th, .data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}
.data-table th {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.9rem;
}
</style>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
