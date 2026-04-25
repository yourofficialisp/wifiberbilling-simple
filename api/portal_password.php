<?php
/**
 * API: Change Portal Password
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

try {
    // Check if customer is logged in
    if (!isCustomerLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    requireApiCsrfToken($input);
    $password = $input['password'] ?? '';
    
    // Validate password
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimum 6 characters']);
        exit;
    }
    
    $customer = getCurrentCustomer();
    
    if (setCustomerPortalPassword($customer['id'], $password)) {
        if (isset($_SESSION['customer']) && is_array($_SESSION['customer'])) {
            $_SESSION['customer']['must_change_password'] = false;
            $_SESSION['customer']['login_time'] = time();
        }
        echo json_encode(['success' => true, 'message' => 'Password successfully changed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to change password']);
    }
    
} catch (Exception $e) {
    logError("API Error (portal_password.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
