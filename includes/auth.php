<?php
/**
 * Authentication Functions
 */

if (!file_exists(__DIR__ . '/config.php')) {
    // Detect if we are in a subdirectory
    $installPath = 'install.php';
    if (file_exists('../install.php')) {
        $installPath = '../install.php';
    } elseif (file_exists('../../install.php')) {
        $installPath = '../../install.php';
    }
    
    header("Location: $installPath");
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

date_default_timezone_set('Asia/Jakarta');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_start();
}

// Admin Authentication
function adminLogin($username, $password) {
    $admin = fetchOne("SELECT * FROM admin_users WHERE username = ?", [$username]);
    
    if (!$admin) {
        return false;
    }
    
    if (password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'logged_in' => true,
            'login_time' => time()
        ];
        
        logActivity('ADMIN_LOGIN', "Username: {$username}");
        return true;
    }
    
    return false;
}

function adminLogout() {
    logActivity('ADMIN_LOGOUT', "Username: " . ($_SESSION['admin']['username'] ?? 'unknown'));
    
    unset($_SESSION['admin']);
    session_destroy();
    
    redirect(APP_URL . '/admin/login.php');
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Please login first');
        redirect(APP_URL . '/admin/login.php');
    }
}

// Customer Authentication
function customerLogin($phone, $password) {
    $customer = fetchOne("SELECT * FROM customers WHERE phone = ?", [$phone]);
    
    if (!$customer) {
        return false;
    }
    
    // Check portal password
    if (!password_verify($password, $customer['portal_password'])) {
        return false;
    }
    
    session_regenerate_id(true);
    $_SESSION['customer'] = [
        'id' => $customer['id'],
        'name' => $customer['name'],
        'phone' => $customer['phone'],
        'pppoe_username' => $customer['pppoe_username'],
        'logged_in' => true,
        'login_time' => time(),
        'must_change_password' => password_verify('1234', $customer['portal_password'])
    ];
    
    logActivity('CUSTOMER_LOGIN', "Phone: {$phone}");
    return true;
}

function customerLogout() {
    logActivity('CUSTOMER_LOGOUT', "Phone: " . ($_SESSION['customer']['phone'] ?? 'unknown'));
    
    unset($_SESSION['customer']);
    session_destroy();
    
    redirect(APP_URL . '/portal/login.php');
}

function requireCustomerLogin() {
    if (!isCustomerLoggedIn()) {
        setFlash('error', 'Please login first');
        redirect(APP_URL . '/portal/login.php');
    }

    $mustChange = $_SESSION['customer']['must_change_password'] ?? false;
    if ($mustChange) {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== 'dashboard.php' && $script !== 'logout.php' && $script !== 'payment.php' && $script !== 'invoices.php') {
            redirect(APP_URL . '/portal/dashboard.php');
        }
    }
}

// Check if admin user exists
function adminUserExists($username) {
    $admin = fetchOne("SELECT id FROM admin_users WHERE username = ?", [$username]);
    return $admin !== null;
}

// Create admin user
function createAdminUser($username, $password, $email = null) {
    $data = [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'email' => $email,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return insert('admin_users', $data);
}

// Update admin password
function updateAdminPassword($userId, $newPassword) {
    $data = [
        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return update('admin_users', $data, 'id = ?', [$userId]);
}

// Get admin by ID
function getAdmin($id) {
    return fetchOne("SELECT * FROM admin_users WHERE id = ?", [$id]);
}

// Get admin by username
function getAdminByUsername($username) {
    return fetchOne("SELECT * FROM admin_users WHERE username = ?", [$username]);
}

// Update admin profile
function updateAdminProfileeeeeeeeeeee($userId, $data) {
    $updateData = [];
    
    if (isset($data['email'])) {
        $updateData['email'] = $data['email'];
    }
    
    if (isset($data['name'])) {
        $updateData['name'] = $data['name'];
    }
    
    $updateData['updated_at'] = date('Y-m-d H:i:s');
    
    return update('admin_users', $updateData, 'id = ?', [$userId]);
}

// Customer portal password
function setCustomerPortalPassword($customerId, $password) {
    $data = [
        'portal_password' => password_hash($password, PASSWORD_DEFAULT),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return update('customers', $data, 'id = ?', [$customerId]);
}

// Check if customer has portal password
function customerHasPortalPassword($customerId) {
    $customer = fetchOne("SELECT portal_password FROM customers WHERE id = ?", [$customerId]);
    return $customer && !empty($customer['portal_password']);
}

// Generate portal password for customer
function generateCustomerPortalPassword($customerId) {
    $password = generateRandomString(8);
    setCustomerPortalPassword($customerId, $password);
    return $password;
}

// Sales Authentication
function salesLogin($username, $password) {
    $sales = fetchOne("SELECT * FROM sales_users WHERE username = ?", [$username]);
    
    if (!$sales) {
        return false;
    }
    
    if ($sales['status'] !== 'active') {
        return 'inactive';
    }
    
    if (password_verify($password, $sales['password'])) {
        session_regenerate_id(true);
        $_SESSION['sales'] = [
            'id' => $sales['id'],
            'name' => $sales['name'],
            'username' => $sales['username'],
            'deposit_balance' => $sales['deposit_balance'],
            'logged_in' => true,
            'login_time' => time()
        ];
        
        logActivity('SALES_LOGIN', "Username: {$username}");
        return true;
    }
    
    return false;
}

function salesLogout() {
    logActivity('SALES_LOGOUT', "Username: " . ($_SESSION['sales']['username'] ?? 'unknown'));
    
    unset($_SESSION['sales']);
    session_destroy();
    
    redirect(APP_URL . '/sales/login.php');
}

function isSalesLoggedIn() {
    if (!isset($_SESSION['sales']) || !isset($_SESSION['sales']['logged_in']) || $_SESSION['sales']['logged_in'] !== true) {
        return false;
    }
    $loginTime = $_SESSION['sales']['login_time'] ?? null;
    if (is_numeric($loginTime) && (time() - (int) $loginTime) > 43200) {
        unset($_SESSION['sales']);
        return false;
    }
    return true;
}

function requireSalesLogin() {
    if (!isSalesLoggedIn()) {
        setFlash('error', 'Please login first');
        redirect(APP_URL . '/sales/login.php');
    }
}

function getSalesUser($id) {
    return fetchOne("SELECT * FROM sales_users WHERE id = ?", [$id]);
}

// Technician Authentication
function technicianLogin($username, $password) {
    $tech = fetchOne("SELECT * FROM technician_users WHERE username = ?", [$username]);
    
    if (!$tech) {
        return false;
    }
    
    if ($tech['status'] !== 'active') {
        return 'inactive';
    }
    
    if (password_verify($password, $tech['password'])) {
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['technician'] = [
            'id' => $tech['id'],
            'name' => $tech['name'],
            'username' => $tech['username'],
            'phone' => $tech['phone'] ?? '',
            'logged_in' => true,
            'login_time' => time()
        ];
        
        // Update last login
        update('technician_users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$tech['id']]);
        
        logActivity('TECH_LOGIN', "Username: {$username}");
        return true;
    }
    
    return false;
}

function technicianLogout() {
    logActivity('TECH_LOGOUT', "Username: " . ($_SESSION['technician']['username'] ?? 'unknown'));
    
    unset($_SESSION['technician']);
    session_destroy();
    
    redirect(APP_URL . '/technician/login.php');
}

function isTechnicianLoggedIn() {
    if (!isset($_SESSION['technician']) || !isset($_SESSION['technician']['logged_in']) || $_SESSION['technician']['logged_in'] !== true) {
        return false;
    }
    $loginTime = $_SESSION['technician']['login_time'] ?? null;
    if (is_numeric($loginTime) && (time() - (int) $loginTime) > 43200) {
        unset($_SESSION['technician']);
        return false;
    }
    return true;
}

function requireTechnicianLogin() {
    if (!isTechnicianLoggedIn()) {
        setFlash('error', 'Please login first');
        redirect(APP_URL . '/technician/login.php');
    }
}
