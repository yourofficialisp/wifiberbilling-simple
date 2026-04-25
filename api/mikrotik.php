<?php
/**
 * API: MikroTik
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdminLogin();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'GET') {
        if ($action === 'users') {
            // Get all PPPoE users
            $users = mikrotikGetPppoeUsers();

            echo json_encode([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'total' => count($users)
                ]
            ]);
        } elseif ($action === 'active') {
            // Get active PPPoE sessions using shared helper
            $activeSessions = mikrotikGetActiveSessions();

            echo json_encode([
                'success' => true,
                'data' => [
                    'active' => $activeSessions,
                    'total' => count($activeSessions)
                ]
            ]);
        } elseif ($action === 'profiles') {
            // Get PPPoE profiles using shared helper
            $profiles = mikrotikGetProfileeeeeeeeeeees();

            echo json_encode([
                'success' => true,
                'data' => [
                    'profiles' => $profiles,
                    'total' => is_array($profiles) ? count($profiles) : 0
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if ($action === 'add_user') {
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            $profile = $input['profile'] ?? 'default';
            $service = $input['service'] ?? 'pppoe';

            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Username and password required']);
                exit;
            }

            // Use the proper shared helper function
            $result = mikrotikAddSecret($username, $password, $profile, $service);

            echo json_encode($result);
        }
    }

} catch (Exception $e) {
    logError("API Error (mikrotik.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
