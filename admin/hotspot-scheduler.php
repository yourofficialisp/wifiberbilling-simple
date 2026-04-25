<?php
/**
 * System Scheduler Viewer
 */

require_once '../includes/auth.php';
requireAdminLogin();

$pageTitle = 'System Schedulers';

// Get Data
$schedulers = mikrotikGetSchedulers();
$totalSchedulers = count($schedulers);

ob_start();
?>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon pink"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo $totalSchedulers; ?>
            </h3>
            <p>Total Schedulers</p>
        </div>
    </div>
</div>

<!-- Schedulers Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-alt"></i> System Scheduler List</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>Start Time</th>
                    <th>Interval</th>
                    <th>Next Run</th>
                    <th>Run Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedulers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No scheduler found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($schedulers as $s): ?>
                        <tr>
                            <td><strong>
                                    <?php echo htmlspecialchars($s['name'] ?? '-'); ?>
                                </strong></td>
                            <td>
                                <?php echo htmlspecialchars($s['start-date'] ?? '-'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($s['start-time'] ?? '-'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($s['interval'] ?? '-'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($s['next-run'] ?? '-'); ?>
                            </td>
                            <td><span class="badge badge-info">
                                    <?php echo htmlspecialchars($s['run-count'] ?? '0'); ?>
                                </span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../includes/layout.php';
