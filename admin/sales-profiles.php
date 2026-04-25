<?php
/**
 * Sales Profileeeeeeeeeeee Management
 */

require_once '../includes/auth.php';
requireAdminLogin();

$salesId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$salesUser = fetchOne("SELECT * FROM sales_users WHERE id = ?", [$salesId]);

if (!$salesUser) {
    setFlash('error', 'Sales not found');
    redirect('sales-users.php');
}

$pageTitle = 'Sales Packages: ' . $salesUser['name'];

// Get Mikrotik Profileeeeeeeeeeees
$mikrotikProfileeeeeeeeeeees = mikrotikGetHotspotProfileeeeeeeeeeees();

// Get Assigned Profileeeeeeeeeeees
$assignedProfileeeeeeeeeeees = fetchAll("SELECT * FROM sales_profile_prices WHERE sales_user_id = ?", [$salesId]);
$assignedMap = [];
foreach ($assignedProfileeeeeeeeeeees as $ap) {
    $assignedMap[$ap['profile_name']] = $ap;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profileName = $_POST['profile_name'];
    $basePrice = (float)$_POST['base_price'];
    $sellingPrice = (float)$_POST['selling_price'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if exists
    $exists = fetchOne("SELECT id FROM sales_profile_prices WHERE sales_user_id = ? AND profile_name = ?", [$salesId, $profileName]);
    
    if ($exists) {
        update('sales_profile_prices', [
            'base_price' => $basePrice,
            'selling_price' => $sellingPrice,
            'is_active' => $isActive
        ], 'id = ?', [$exists['id']]);
    } else {
        insert('sales_profile_prices', [
            'sales_user_id' => $salesId,
            'profile_name' => $profileName,
            'base_price' => $basePrice,
            'selling_price' => $sellingPrice,
            'is_active' => $isActive
        ]);
    }
    setFlash('success', "Profileeeeeeeeeeee $profileName updated successfully.");
    redirect("sales-profiles.php?id=$salesId");
}

ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags"></i> Set Prices & Packages</h3>
        <a href="sales-users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Set the base price (deposit) and selling price for each profile that this sales user is allowed to sell.
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>MikroTik Profileeeeeeeeeeee</th>
                        <th>Status</th>
                        <th>Base Price (Deposit)</th>
                        <th>Selling Price (Customer)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mikrotikProfileeeeeeeeeeees as $index => $p): 
                        $pName = $p['name'];
                        $assigned = $assignedMap[$pName] ?? null;
                        
                        // Parse default prices from Mikrotik comment/on-login
                        $mikhmonData = parseMikhmonOnLogin($p['on-login'] ?? '');
                        
                        $defaultBase = $assigned ? $assigned['base_price'] : ($mikhmonData['price'] ?? 0);
                        $defaultSelling = $assigned ? $assigned['selling_price'] : ($mikhmonData['selling_price'] ?? 0);
                        $isActive = $assigned ? $assigned['is_active'] : 0;
                        
                        $formId = "form_" . md5($pName);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($pName); ?></strong>
                        </td>
                        <td>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="is_active" id="active_<?php echo $index; ?>" form="<?php echo $formId; ?>" <?php echo $isActive ? 'checked' : ''; ?> onchange="document.getElementById('<?php echo $formId; ?>').submit()">
                                <label class="custom-control-label" for="active_<?php echo $index; ?>"><?php echo $isActive ? 'Active' : 'Inactive'; ?></label>
                            </div>
                        </td>
                        <td>
                            <input type="number" name="base_price" class="form-control" value="<?php echo $defaultBase; ?>" style="width: 150px;" form="<?php echo $formId; ?>">
                        </td>
                        <td>
                            <input type="number" name="selling_price" class="form-control" value="<?php echo $defaultSelling; ?>" style="width: 150px;" form="<?php echo $formId; ?>">
                        </td>
                        <td>
                            <form id="<?php echo $formId; ?>" method="POST">
                                <input type="hidden" name="profile_name" value="<?php echo htmlspecialchars($pName); ?>">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>
