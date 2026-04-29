<?php
/**
 * Sales - Pay Customer Bill
 */

require_once '../includes/auth.php';
requireSalesLogin();

$pageTitle = 'Pay Bill';
$salesId = $_SESSION['sales']['id'];

$searchResults = [];
$searchQuery = '';

if (isset($_GET['q'])) {
    $searchQuery = sanitize($_GET['q']);
    if (strlen($searchQuery) >= 3) {
        $searchResults = fetchAll("SELECT * FROM customers 
            WHERE (name LIKE ? OR phone LIKE ? OR pppoe_username LIKE ?) 
            LIMIT 20", 
            ["%$searchQuery%", "%$searchQuery%", "%$searchQuery%"]);
    }
}

ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-search"></i> Search Customer</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="pay.php" style="display: flex; gap: 10px;">
            <input type="text" name="q" class="form-control" placeholder="Name, Phone Number, or PPPoE Username..." value="<?php echo htmlspecialchars($searchQuery); ?>" required minlength="3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>
</div>

<?php if (!empty($searchQuery)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Hasil Pencarian</h3>
        </div>
        <div class="card-body">
            <?php if (empty($searchResults)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Customer data not found.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($searchResults as $c): ?>
                        <a href="pay_process.php?id=<?php echo $c['id']; ?>" class="list-group-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid var(--border-color); text-decoration: none; color: inherit;">
                            <div>
                                <h5 style="margin: 0; color: var(--neon-cyan);"><?php echo htmlspecialchars($c['name']); ?></h5>
                                <small style="color: var(--text-secondary);">
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($c['phone']); ?> | 
                                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($c['pppoe_username']); ?>
                                </small>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge badge-primary">Select <i class="fas fa-chevron-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once '../includes/sales_layout.php';
?>
