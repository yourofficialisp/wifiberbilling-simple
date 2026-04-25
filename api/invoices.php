<?php
/**
 * API: Invoices
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdminLogin();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    // $page = $_GET['page'] ?? 1; // Moved inside GET block and casted
    // $perPage = $_GET['per_page'] ?? 20; // Moved inside GET block and casted

    if ($method === 'GET') {
        // Get invoices with pagination
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $invoices = fetchAll("
            SELECT i.*, c.name as customer_name, c.pppoe_username 
            FROM invoices i 
            LEFT JOIN customers c ON i.customer_id = c.id 
            ORDER BY i.created_at DESC 
            LIMIT {$perPage} OFFSET {$offset}
        ");

        $totalResult = fetchOne("SELECT COUNT(*) as total FROM invoices");
        $total = $totalResult['total'] ?? 0;

        echo json_encode([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]
        ]);
    }

} catch (Exception $e) {
    logError("API Error (invoices.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
