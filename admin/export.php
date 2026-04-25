<?php
/**
 * Export Customers to Excel/CSV
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Export Customers';

// Handle Excel export
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $customers = fetchAll("
        SELECT 
            c.id,
            c.name,
            c.phone,
            c.pppoe_username,
            c.package_id,
            p.name as package_name,
            p.price as package_price,
            c.status,
            c.isolation_date,
            c.auto_isolate,
            c.address,
            c.lat,
            c.lng,
            c.created_at,
            c.updated_at
        FROM customers c
        LEFT JOIN packages p ON c.package_id = p.id
        ORDER BY c.created_at DESC
    ");
    
    // Set headers for Excel download (XML Spreadsheet format)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output Excel XML format
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    echo '<Worksheet ss:Name="Customers">' . "\n";
    echo '<Table>' . "\n";
    
    // Header row
    echo '<Row>' . "\n";
    $headers = ['Name', 'Phone', 'PPPoE Username', 'Package', 'Status', 'Isolation Date', 'Auto Isolate', 'Address', 'Latitude', 'Longitude'];
    foreach ($headers as $header) {
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
    }
    echo '</Row>' . "\n";
    
    // Data rows
    foreach ($customers as $customer) {
        echo '<Row>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($customer['name']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($customer['phone']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($customer['pppoe_username']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($customer['package_name'] ?? 'No Package') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . ($customer['status'] == 'active' ? 'Active' : 'Isolated') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . $customer['isolation_date'] . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . ((int)($customer['auto_isolate'] ?? 1) === 1 ? 'Yes' : 'No') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($customer['address'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . ($customer['lat'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . ($customer['lng'] ?? '') . '</Data></Cell>' . "\n";
        echo '</Row>' . "\n";
    }
    
    echo '</Table>' . "\n";
    echo '</Worksheet>' . "\n";
    echo '</Workbook>' . "\n";
    exit;
}

// Handle CSV export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $customers = fetchAll("
        SELECT 
            c.id,
            c.name,
            c.phone,
            c.pppoe_username,
            c.package_id,
            p.name as package_name,
            p.price as package_price,
            c.status,
            c.isolation_date,
            c.auto_isolate,
            c.address,
            c.lat,
            c.lng,
            c.created_at,
            c.updated_at
        FROM customers c
        LEFT JOIN packages p ON c.package_id = p.id
        ORDER BY c.created_at DESC
    ");
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Write CSV header
    fputcsv($output, [
        'Name',
        'Phone',
        'PPPoE Username',
        'Package',
        'Status',
        'Isolation Date',
        'Auto Isolate',
        'Address',
        'Latitude',
        'Longitude'
    ]);
    
    // Write data rows
    foreach ($customers as $customer) {
        fputcsv($output, [
            $customer['name'],
            $customer['phone'],
            $customer['pppoe_username'],
            $customer['package_name'] ?? 'No Package',
            $customer['status'] == 'active' ? 'Active' : 'Isolated',
            $customer['isolation_date'],
            ((int)($customer['auto_isolate'] ?? 1) === 1 ? 'Yes' : 'No'),
            $customer['address'] ?? '',
            $customer['lat'] ?? '',
            $customer['lng'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

ob_start();
?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-download"></i> Export Customers</h3>
        </div>
        
        <p style="margin-bottom: 20px; color: var(--text-secondary);">
            Download customer data in Excel or CSV format.
        </p>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="?action=export_excel" class="btn btn-primary">
                <i class="fas fa-file-excel"></i> Download Excel
            </a>
            <a href="?action=export_csv" class="btn btn-secondary">
                <i class="fas fa-file-csv"></i> Download CSV
            </a>
        </div>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-upload"></i> Import Customers</h3>
        </div>
        
        <p style="margin-bottom: 20px; color: var(--text-secondary);">
            Upload an Excel or CSV file to bulk import customers.
        </p>
        
        <form method="POST" action="import.php" enctype="multipart/form-data">
            <div style="margin-bottom: 20px;">
                <label class="form-label">Select File (Excel/CSV)</label>
                <input type="file" name="importFile" class="form-control" accept=".csv,.xls,.xlsx" required>
                <small style="color: var(--text-muted);">Supported formats: CSV, XLS, XLSX</small>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload & Import
                </button>
                <a href="?action=export_excel" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Download Template
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
                    <td>Auto Isolate</td>
                    <td>Whether the customer is subject to auto isolation (Yes/No)</td>
                    <td>Yes</td>
                    <td><span class="badge badge-warning">No</span></td>
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
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-info { background: rgba(0, 245, 255, 0.1); border: 1px solid var(--neon-cyan); color: var(--neon-cyan); }
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
    text-transform: uppercase;
}
</style>

<script>
function importCSV() {
    const fileInput = document.getElementById('csvFile');
    const progressDiv = document.getElementById('importProgress');
    
    if (fileInput.files.length === 0) {
        alert('Please select a CSV file first!');
        return;
    }
    
    const file = fileInput.files[0];
    
    if (file.type !== 'text/csv' && file.type !== 'text/plain') {
        alert('File must be in CSV format!');
        return;
    }
    
    const formData = new FormData();
    formData.append('csvFile', file);
    
    progressDiv.style.display = 'block';
    
    fetch('?action=import', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        progressDiv.style.display = 'none';
        
        if (data.success) {
            alert('Import successful! ' + data.message);
            location.reload();
        } else {
            alert('Import failed: ' + data.message);
        }
    })
    .catch(error => {
        progressDiv.style.display = 'none';
        alert('An error occurred: ' + error.message);
    });
}
</script>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
