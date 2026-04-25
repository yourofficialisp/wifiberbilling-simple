<?php
/**
 * Admin - Pay Customer Bill
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Pay Bill';

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
} else {
    // Show active customers by default (limit 10)
    // Or active unpaid invoices?
    // Let's just show recently added customers or active ones
    $searchResults = fetchAll("SELECT * FROM customers ORDER BY created_at DESC LIMIT 10");
}

ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-search"></i> Search Customer</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="pay.php" style="display: flex; gap: 10px;">
                    <input type="text" name="q" class="form-control" placeholder="Name, Phone, or PPPoE Username..." value="<?php echo htmlspecialchars($searchQuery); ?>" required minlength="3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> <?php echo !empty($searchQuery) ? 'Search Results' : 'Latest Customers'; ?></h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($searchResults) && !empty($searchQuery)): ?>
                    <div class="alert alert-warning m-3">
                        <i class="fas fa-exclamation-triangle"></i> Customer data not found.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($searchResults as $c): ?>
                            <a href="pay_process.php?id=<?php echo $c['id']; ?>" class="list-group-item" style="
                                display: flex; 
                                justify-content: space-between; 
                                align-items: center; 
                                padding: 15px; 
                                border-bottom: 1px solid var(--border-color); 
                                text-decoration: none; 
                                color: inherit;
                                transition: background 0.2s;
                            " onmouseover="this.style.background='rgba(0,0,0,0.02)'" onmouseout="this.style.background='transparent'">
                                <div>
                                    <h5 style="margin: 0; color: var(--neon-cyan); font-size: 1rem;"><?php echo htmlspecialchars($c['name']); ?></h5>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                                        <i class="fas fa-wifi"></i> <?php echo htmlspecialchars($c['pppoe_username']); ?>
                                        <span style="margin: 0 5px;">|</span>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($c['phone']); ?>
                                    </div>
                                    <div style="margin-top: 5px;">
                                        <?php if ($c['status'] === 'active'): ?>
                                            <span class="badge badge-success" style="font-size: 0.7rem;">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning" style="font-size: 0.7rem;">Isolated</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <span class="btn btn-success btn-sm">Pay <i class="fas fa-chevron-right"></i></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
?>
