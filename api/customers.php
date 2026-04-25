<?php
/**
 * API: Customers
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdminLogin();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
    $search = $_GET['search'] ?? '';

    if ($method === 'GET') {
        // Get single customer
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);

            if ($customer) {
                echo json_encode(['success' => true, 'data' => $customer]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Customer not found']);
            }
            exit;
        }

        // Get customers with pagination
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];

        if (!empty($search)) {
            $where = "WHERE c.name LIKE ? OR c.phone LIKE ? OR c.pppoe_username LIKE ?";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }

        $customers = fetchAll("
            SELECT c.*, p.name as package_name, p.price as package_price 
            FROM customers c 
            LEFT JOIN packages p ON c.package_id = p.id 
            {$where}
            ORDER BY c.created_at DESC 
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        $totalResult = fetchOne("SELECT COUNT(*) as total FROM customers c {$where}", $params);
        $total = $totalResult['total'] ?? 0;

        echo json_encode([
            'success' => true,
            'data' => [
                'customers' => $customers,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]
        ]);
    }

} catch (Exception $e) {
    logError("API Error (customers.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
