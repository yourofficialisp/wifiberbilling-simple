<?php
/**
 * API: Dashboard Statistics
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdminLogin();

try {
    // Get statistics
    $stats = [
        'totalCustomers' => fetchOne("SELECT COUNT(*) as total FROM customers")['total'] ?? 0,
        'activeCustomers' => fetchOne("SELECT COUNT(*) as total FROM customers WHERE status = 'active'")['total'] ?? 0,
        'isolatedCustomers' => fetchOne("SELECT COUNT(*) as total FROM customers WHERE status = 'isolated'")['total'] ?? 0,
        'totalPackages' => fetchOne("SELECT COUNT(*) as total FROM packages")['total'] ?? 0,
        'totalInvoices' => fetchOne("SELECT COUNT(*) as total FROM invoices")['total'] ?? 0,
        'paidInvoices' => fetchOne("SELECT COUNT(*) as total FROM invoices WHERE status = 'paid'")['total'] ?? 0,
        'pendingInvoices' => fetchOne("SELECT COUNT(*) as total FROM invoices WHERE status = 'unpaid'")['total'] ?? 0,
        'totalRevenue' => fetchOne("
            SELECT SUM(amount) as total 
            FROM invoices 
            WHERE status = 'paid' 
            AND paid_at IS NOT NULL
            AND DATE_FORMAT(paid_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        ")['total'] ?? 0,
    ];

    // Get recent invoices
    $recentInvoices = fetchAll("
        SELECT i.*, c.name as customer_name 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        ORDER BY i.created_at DESC 
        LIMIT 10
    ");

    // Get recent customers
    $recentCustomers = fetchAll("
        SELECT c.*, p.name as package_name 
        FROM customers c 
        LEFT JOIN packages p ON c.package_id = p.id 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");

    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => $stats,
            'recentInvoices' => $recentInvoices,
            'recentCustomers' => $recentCustomers
        ]
    ]);

} catch (Exception $e) {
    logError("API Error (dashboard.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
