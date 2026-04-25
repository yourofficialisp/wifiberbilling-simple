<?php

// Ensure config is loaded
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

function getMikrotikSettings($routerId = null)
{
    // If routerId is provided, always fetch that specific router
    if ($routerId !== null && (int)$routerId > 0) {
        $router = fetchOne("SELECT * FROM routers WHERE id = ?", [$routerId]);
        if ($router) {
            return [
                'id' => $router['id'],
                'host' => $router['host'],
                'user' => $router['username'],
                'pass' => $router['password'],
                'port' => (int) $router['port'],
                'name' => $router['name']
            ];
        }
    }

    static $settings = null;
    if ($settings === null) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $activeRouterId = $_SESSION['active_router_id'] ?? null;

        $router = null;
        if ($activeRouterId) {
            $router = fetchOne("SELECT * FROM routers WHERE id = ?", [$activeRouterId]);
        }

        if (!$router) {
            // Try to get active router or first router
            $router = fetchOne("SELECT * FROM routers WHERE is_active = 1 LIMIT 1");
            if (!$router) {
                $router = fetchOne("SELECT * FROM routers LIMIT 1");
            }
        }

        if ($router) {
            $_SESSION['active_router_id'] = $router['id'];
            $settings = [
                'id' => $router['id'],
                'host' => $router['host'],
                'user' => $router['username'],
                'pass' => $router['password'],
                'port' => (int) $router['port'],
                'name' => $router['name']
            ];
            return $settings;
        }

        // Bridge migration/Fallback: Get from legacy settings table
        $settings = [
            'id' => 0,
            'host' => defined('MIKROTIK_HOST') ? MIKROTIK_HOST : '',
            'user' => defined('MIKROTIK_USER') ? MIKROTIK_USER : '',
            'pass' => defined('MIKROTIK_PASS') ? MIKROTIK_PASS : '',
            'port' => defined('MIKROTIK_PORT') ? MIKROTIK_PORT : 8728,
            'name' => 'Default Router'
        ];

        $dbSettings = fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('MIKROTIK_HOST', 'MIKROTIK_USER', 'MIKROTIK_PASS', 'MIKROTIK_PORT')");
        foreach ($dbSettings as $s) {
            switch ($s['setting_key']) {
                case 'MIKROTIK_HOST':
                    $settings['host'] = $s['setting_value'];
                    break;
                case 'MIKROTIK_USER':
                    $settings['user'] = $s['setting_value'];
                    break;
                case 'MIKROTIK_PASS':
                    $settings['pass'] = $s['setting_value'];
                    break;
                case 'MIKROTIK_PORT':
                    $settings['port'] = (int) $s['setting_value'];
                    break;
            }
        }
    }
    return $settings;
}

// Get all routers from database
function getAllRouters()
{
    if (!tableExists('routers')) {
        return [];
    }
    return fetchAll("SELECT * FROM routers ORDER BY name ASC");
}
/**
 * Get a persistent MikroTik connection for the remainder of the request
 */
function getMikrotikConnection($routerId = null)
{
    static $sockets = [];
    static $lastHosts = [];

    $mikrotik = getMikrotikSettings($routerId);
    $rId = (int)($mikrotik['id'] ?? 0);
    $currentHost = $mikrotik['host'] . ':' . $mikrotik['port'];

    // If socket is dead or doesn't exist for this router, reconnect
    if (!isset($sockets[$rId]) || !is_resource($sockets[$rId]) || feof($sockets[$rId]) || ($lastHosts[$rId] ?? '') !== $currentHost) {
        if (isset($sockets[$rId]) && is_resource($sockets[$rId])) {
            @fclose($sockets[$rId]);
        }

        $sockets[$rId] = mikrotikConnect($routerId);
        if ($sockets[$rId]) {
            if (!mikrotikLogin($sockets[$rId], $routerId)) {
                @fclose($sockets[$rId]);
                $sockets[$rId] = null;
            } else {
                $lastHosts[$rId] = $currentHost;
            }
        }
    }

    return $sockets[$rId];
}

function mikrotikConnect($routerId = null)
{
    $mikrotik = getMikrotikSettings($routerId);

    if (empty($mikrotik['host']) || empty($mikrotik['user'])) {
        logError("MikroTik config incomplete: host or user is empty");
        return false;
    }

    $socket = @fsockopen($mikrotik['host'], $mikrotik['port'], $errno, $errstr, 5);

    if (!$socket) {
        logError("MikroTik connection failed: $errstr ($errno)");
        return false;
    }

    stream_set_timeout($socket, 5);
    stream_set_blocking($socket, true);

    return $socket;
}

function mikrotikLogin($socket, $routerId = null)
{
    $mikrotik = getMikrotikSettings($routerId);
    $username = $mikrotik['user'];
    $password = $mikrotik['pass'];

    // Method 1: Plain text password (RouterOS 6.43+)
    // This is the preferred method for modern RouterOS
    mikrotikWrite($socket, '/login');
    mikrotikWrite($socket, '=name=' . $username);
    mikrotikWrite($socket, '=password=' . $password);
    mikrotikWrite($socket, ''); // End sentence

    $response = mikrotikReadSentence($socket);

    // Check if login succeeded
    foreach ($response as $word) {
        if ($word === '!done') {
            return true;
        }
    }

    // If plain text method failed, try MD5 challenge-response (older RouterOS)
    // Reconnect is needed, but we'll try a different approach

    // Method 2: MD5 Challenge-Response (RouterOS pre-6.43)
    mikrotikWrite($socket, '/login');
    mikrotikWrite($socket, ''); // End sentence

    $response = mikrotikReadSentence($socket);

    if (empty($response)) {
        return false;
    }

    // Extract challenge from response
    $challenge = null;
    foreach ($response as $word) {
        if (strpos($word, '=ret=') === 0) {
            $challenge = substr($word, 5);
            break;
        }
    }

    if (!$challenge) {
        return false;
    }

    // Calculate MD5 hash
    $hash = md5(chr(0) . $password . pack('H*', $challenge), true);

    // Send login with hash
    mikrotikWrite($socket, '/login');
    mikrotikWrite($socket, '=name=' . $username);
    mikrotikWrite($socket, '=response=' . bin2hex($hash));
    mikrotikWrite($socket, ''); // End sentence

    // Read response
    $response = mikrotikReadSentence($socket);

    // Check if login succeeded
    foreach ($response as $word) {
        if ($word === '!done') {
            return true;
        }
    }

    return false;
}

function mikrotikWrite($socket, $word)
{
    if ($word === '') {
        fwrite($socket, chr(0));
        return;
    }

    $len = strlen($word);
    $encodedLen = '';

    if ($len < 0x80) {
        $encodedLen = chr($len);
    } elseif ($len < 0x4000) {
        $encodedLen = chr(($len >> 8) | 0x80) . chr($len & 0xFF);
    } elseif ($len < 0x200000) {
        $encodedLen = chr(($len >> 16) | 0xC0) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
    } elseif ($len < 0x10000000) {
        $encodedLen = chr(($len >> 24) | 0xE0) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
    } else {
        $encodedLen = chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF);
    }

    fwrite($socket, $encodedLen . $word);
}

function mikrotikWriteCommand($socket, $command)
{
    mikrotikWrite($socket, $command);
}

function mikrotikWriteWord($socket, $word)
{
    mikrotikWrite($socket, $word);
}

function mikrotikReadSentence($socket)
{
    $words = [];
    while (true) {
        $byte = fread($socket, 1);
        if ($byte === false || $byte === '')
            break;

        $byte = ord($byte);
        $len = 0;

        if (($byte & 0x80) == 0x00) {
            $len = $byte;
        } elseif (($byte & 0xC0) == 0x80) {
            $len = (($byte & 0x3F) << 8) + ord(fread($socket, 1));
        } elseif (($byte & 0xE0) == 0xC0) {
            $len = (($byte & 0x1F) << 16) + (ord(fread($socket, 1)) << 8) + ord(fread($socket, 1));
        } elseif (($byte & 0xF0) == 0xE0) {
            $len = (($byte & 0x0F) << 24) + (ord(fread($socket, 1)) << 16) + (ord(fread($socket, 1)) << 8) + ord(fread($socket, 1));
        } elseif (($byte & 0xF8) == 0xF0) {
            $len = (ord(fread($socket, 1)) << 24) + (ord(fread($socket, 1)) << 16) + (ord(fread($socket, 1)) << 8) + ord(fread($socket, 1));
        }

        if ($len == 0) {
            break;
        }

        $word = '';
        $remaining = $len;
        while ($remaining > 0) {
            $chunk = fread($socket, $remaining);
            if ($chunk === false || $chunk === '')
                break;
            $word .= $chunk;
            $remaining -= strlen($chunk);
        }

        $words[] = $word;
    }

    return $words;
}

function mikrotikRead($socket)
{
    return mikrotikReadSentence($socket);
}

function mikrotikTrapMessageFromResponse($response)
{
    if (!is_array($response) || empty($response)) {
        return '';
    }
    $isTrap = false;
    foreach ($response as $word) {
        if ($word === '!trap') {
            $isTrap = true;
            break;
        }
    }
    if (!$isTrap) {
        return '';
    }
    $parts = [];
    foreach ($response as $word) {
        if (strpos($word, '=message=') === 0) {
            $parts[] = substr($word, 9);
        } elseif (strpos($word, '=category=') === 0) {
            $parts[] = 'category=' . substr($word, 10);
        } elseif (strpos($word, '=reason=') === 0) {
            $parts[] = 'reason=' . substr($word, 8);
        }
    }
    return implode(' | ', $parts);
}

function mikrotikQuery($command, $params = [])
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return false;
    }

    // Send command
    mikrotikWrite($socket, $command);
    foreach ($params as $key => $value) {
        mikrotikWrite($socket, '=' . $key . '=' . $value);
    }
    mikrotikWrite($socket, ''); // End sentence

    // Read response — mikrotikRead() returns an array of words
    $allWords = [];
    $done = false;
    $timeout = time() + 10;

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    return mikrotikParseResponse($allWords);
}

function mikrotikParseResponse($response)
{
    // $response is an array of words from binary protocol
    $result = [];

    foreach ($response as $word) {
        if ($word === '!done' || strpos($word, '!trap') === 0) {
            break;
        }

        if (strpos($word, '=') === 0) {
            $word = substr($word, 1); // Remove leading '='
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $result[$parts[0]] = $parts[1];
            }
        }
    }

    return $result;
}

function mikrotikSetProfileeeeeeeeeeee($username, $profile, $routerId = null)
{
    $socket = getMikrotikConnection($routerId);
    if (!$socket) {
        return false;
    }

    // Find user and get their secret ID
    mikrotikWrite($socket, '/ppp/secret/print');
    mikrotikWrite($socket, '?name=' . $username);
    mikrotikWrite($socket, ''); // End sentence

    // Read ALL sentences until !done
    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $parsed = mikrotikParseUsers($allWords);

    if (empty($parsed)) {
        return false;
    }

    // Get the secret ID from first user
    $secretId = $parsed[0]['.id'] ?? null;
    if (!$secretId) {
        return false;
    }

    // Update profile using secret ID
    mikrotikWrite($socket, '/ppp/secret/set');
    mikrotikWrite($socket, '=.id=' . $secretId);
    mikrotikWrite($socket, '=profile=' . $profile);
    mikrotikWrite($socket, ''); // End sentence

    // Read response to confirm
    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    foreach ($allWords as $word) {
        if (strpos($word, '!trap') === 0) {
            $trap = mikrotikTrapMessageFromResponse($allWords);
            if ($trap !== '') {
                logError('MikroTik set profile failed: ' . $trap);
            } else {
                logError('MikroTik set profile failed.');
            }
            return false;
        }
    }

    mikrotikRemoveActiveSessionByName($username, $routerId);
    return true;
}

function mikrotikGetPppoeUsers()
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return [];
    }

    mikrotikWrite($socket, '/ppp/secret/print');
    mikrotikWrite($socket, ''); // End sentence

    // Read ALL sentences until !done
    $allWords = [];
    $done = false;
    $timeout = time() + 30; // 30 second timeout for large user lists

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words)) {
            break;
        }

        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    return mikrotikParseUsers($allWords);
}

function mikrotikParseUsers($response)
{
    // $response is now an array of words from binary protocol
    // Format: =key=value (e.g., =name=user1)
    $users = [];
    $currentUser = [];

    foreach ($response as $word) {
        if ($word === '!done') {
            if (!empty($currentUser)) {
                $users[] = $currentUser;
                $currentUser = [];
            }
            break;
        }

        if ($word === '!re') {
            if (!empty($currentUser)) {
                $users[] = $currentUser;
                $currentUser = [];
            }
        } elseif (strpos($word, '=') === 0) {
            // Format: =key=value, so remove first '=' then split
            $word = substr($word, 1); // Remove leading '='
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $currentUser[$parts[0]] = $parts[1];
            }
        }
    }

    return $users;
}

// Add PPPoE Secret
function mikrotikAddSecret($name, $password, $profile = 'default', $service = 'pppoe')
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return ['success' => false, 'message' => 'Cannot connect to MikroTik'];
    }

    mikrotikWrite($socket, '/ppp/secret/add');
    mikrotikWrite($socket, '=name=' . $name);
    mikrotikWrite($socket, '=password=' . $password);
    mikrotikWrite($socket, '=profile=' . $profile);
    mikrotikWrite($socket, '=service=' . $service);
    mikrotikWrite($socket, ''); // End sentence

    $response = mikrotikReadSentence($socket);

    foreach ($response as $word) {
        if ($word === '!done') {
            return ['success' => true, 'message' => 'User added successfully'];
        }
        if (strpos($word, '!trap') === 0) {
            $message = 'Unknown error';
            foreach ($response as $w) {
                if (strpos($w, '=message=') === 0) {
                    $message = substr($w, 9);
                    break;
                }
            }
            return ['success' => false, 'message' => $message];
        }
    }

    return ['success' => false, 'message' => 'Unknown response'];
}

// Update PPPoE Secret
function mikrotikUpdateSecret($id, $data)
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return ['success' => false, 'message' => 'Cannot connect to MikroTik'];
    }

    mikrotikWrite($socket, '/ppp/secret/set');
    mikrotikWrite($socket, '=.id=' . $id);

    if (isset($data['name']))
        mikrotikWrite($socket, '=name=' . $data['name']);
    if (isset($data['password']))
        mikrotikWrite($socket, '=password=' . $data['password']);
    if (isset($data['profile']))
        mikrotikWrite($socket, '=profile=' . $data['profile']);
    if (isset($data['service']))
        mikrotikWrite($socket, '=service=' . $data['service']);
    if (isset($data['disabled']))
        mikrotikWrite($socket, '=disabled=' . $data['disabled']);

    mikrotikWrite($socket, ''); // End sentence

    $response = mikrotikReadSentence($socket);

    foreach ($response as $word) {
        if ($word === '!done') {
            return ['success' => true, 'message' => 'User updated successfully'];
        }
        if (strpos($word, '!trap') === 0) {
            $message = 'Unknown error';
            foreach ($response as $w) {
                if (strpos($w, '=message=') === 0) {
                    $message = substr($w, 9);
                    break;
                }
            }
            return ['success' => false, 'message' => $message];
        }
    }

    return ['success' => false, 'message' => 'Unknown response'];
}

// Delete PPPoE Secret
function mikrotikDeleteSecret($id)
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return ['success' => false, 'message' => 'Cannot connect to MikroTik'];
    }

    mikrotikWrite($socket, '/ppp/secret/remove');
    mikrotikWrite($socket, '=.id=' . $id);
    mikrotikWrite($socket, ''); // End sentence

    $response = mikrotikReadSentence($socket);

    foreach ($response as $word) {
        if ($word === '!done') {
            return ['success' => true, 'message' => 'User deleted successfully'];
        }
        if (strpos($word, '!trap') === 0) {
            $message = 'Unknown error';
            foreach ($response as $w) {
                if (strpos($w, '=message=') === 0) {
                    $message = substr($w, 9);
                    break;
                }
            }
            return ['success' => false, 'message' => $message];
        }
    }

    return ['success' => false, 'message' => 'Unknown response'];
}

// Get Active PPPoE Sessions (users currently connected)
function mikrotikGetActiveSessions()
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return [];
    }

    mikrotikWrite($socket, '/ppp/active/print');
    mikrotikWrite($socket, ''); // End sentence

    // Read ALL sentences until !done
    $allWords = [];
    $done = false;
    $timeout = time() + 30; // 30 second timeout for large user lists

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words)) {
            break;
        }

        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    // Parse active sessions
    $sessions = [];
    $currentSession = [];

    foreach ($allWords as $word) {
        if ($word === '!done') {
            if (!empty($currentSession)) {
                $sessions[] = $currentSession;
            }
            break;
        }

        if ($word === '!re') {
            if (!empty($currentSession)) {
                $sessions[] = $currentSession;
                $currentSession = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $currentSession[$parts[0]] = $parts[1];
            }
        }
    }

    return $sessions;
}

function mikrotikGetProfileeeeeeeeeeees()
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return [];
    }

    logActivity('MIKROTIK_API', "Fetching PPPoE profiles");

    // Send print command
    mikrotikWrite($socket, '/ppp/profile/print');

    // End sentence
    mikrotikWrite($socket, '');

    // Read ALL sentences until !done (MikroTik sends multiple sentences)
    $allWords = [];
    $done = false;
    $timeout = time() + 10; // 10 second timeout

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words)) {
            break;
        }

        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $profiles = mikrotikParseProfileeeeeeeeeeees($allWords);

    return $profiles;
}

function mikrotikParseProfileeeeeeeeeeees($response)
{
    // $response is now an array of words from binary protocol
    // Format: =key=value (e.g., =name=default)
    $profiles = [];
    $currentProfileeeeeeeeeeee = [];

    foreach ($response as $word) {
        if ($word === '!done') {
            if (!empty($currentProfileeeeeeeeeeee)) {
                $profiles[] = $currentProfileeeeeeeeeeee;
                $currentProfileeeeeeeeeeee = [];
            }
            break;
        }

        if ($word === '!re') {
            if (!empty($currentProfileeeeeeeeeeee)) {
                $profiles[] = $currentProfileeeeeeeeeeee;
                $currentProfileeeeeeeeeeee = [];
            }
        } elseif (strpos($word, '=') === 0) {
            // Format: =key=value, so remove first '=' then split
            $word = substr($word, 1); // Remove leading '='
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $currentProfileeeeeeeeeeee[$parts[0]] = $parts[1];
            }
        }
    }

    return $profiles;
}

function mikrotikAddPppoeProfileeeeeeeeeeee($data)
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return false;
    }

    mikrotikWrite($socket, '/ppp/profile/add');
    if (isset($data['name'])) {
        mikrotikWrite($socket, '=name=' . $data['name']);
    }
    if (isset($data['local-address'])) {
        mikrotikWrite($socket, '=local-address=' . $data['local-address']);
    }
    if (isset($data['remote-address'])) {
        mikrotikWrite($socket, '=remote-address=' . $data['remote-address']);
    }
    if (isset($data['rate-limit'])) {
        mikrotikWrite($socket, '=rate-limit=' . $data['rate-limit']);
    }
    if (isset($data['dns-server'])) {
        mikrotikWrite($socket, '=dns-server=' . $data['dns-server']);
    }
    if (isset($data['comment'])) {
        mikrotikWrite($socket, '=comment=' . $data['comment']);
    }
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0) {
            $trap = mikrotikTrapMessageFromResponse($response);
            if ($trap !== '') {
                logError('MikroTik add PPPoE profile failed: ' . $trap);
            } else {
                logError('MikroTik add PPPoE profile failed.');
            }
            return false;
        }
    }
    return true;
}

function mikrotikUpdatePppoeProfileeeeeeeeeeee($id, $data)
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return false;
    }

    mikrotikWrite($socket, '/ppp/profile/set');
    mikrotikWrite($socket, '=.id=' . $id);
    if (isset($data['name'])) {
        mikrotikWrite($socket, '=name=' . $data['name']);
    }
    if (isset($data['local-address'])) {
        mikrotikWrite($socket, '=local-address=' . $data['local-address']);
    }
    if (isset($data['remote-address'])) {
        mikrotikWrite($socket, '=remote-address=' . $data['remote-address']);
    }
    if (isset($data['rate-limit'])) {
        mikrotikWrite($socket, '=rate-limit=' . $data['rate-limit']);
    }
    if (isset($data['dns-server'])) {
        mikrotikWrite($socket, '=dns-server=' . $data['dns-server']);
    }
    if (isset($data['comment'])) {
        mikrotikWrite($socket, '=comment=' . $data['comment']);
    }
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0) {
            $trap = mikrotikTrapMessageFromResponse($response);
            if ($trap !== '') {
                logError('MikroTik update PPPoE profile failed: ' . $trap);
            } else {
                logError('MikroTik update PPPoE profile failed.');
            }
            return false;
        }
    }
    return true;
}

function mikrotikDeletePppoeProfileeeeeeeeeeee($id)
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return false;
    }

    mikrotikWrite($socket, '/ppp/profile/remove');
    mikrotikWrite($socket, '=.id=' . $id);
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    fclose($socket);
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0) {
            $trap = mikrotikTrapMessageFromResponse($response);
            if ($trap !== '') {
                logError('MikroTik delete PPPoE profile failed: ' . $trap);
            } else {
                logError('MikroTik delete PPPoE profile failed.');
            }
            return false;
        }
    }
    return true;
}

// Get MikroTik Hotspot Servers
function mikrotikGetHotspotServers()
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/ip/hotspot/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $servers = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re') {
            if (!empty($current)) {
                $servers[] = $current;
                $current = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2)
                $current[$parts[0]] = $parts[1];
        }
    }
    return $servers;
}

// Get MikroTik Hotspot User Profileeeeeeeeeeees
function mikrotikGetHotspotProfileeeeeeeeeeees()
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return [];
    }

    // Get hotspot user profiles
    mikrotikWrite($socket, '/ip/hotspot/user/profile/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $profiles = [];
    $currentProfileeeeeeeeeeee = [];

    foreach ($allWords as $word) {
        if ($word === '!re' || $word === '!done') {
            if (!empty($currentProfileeeeeeeeeeee)) {
                // Ensure default keys
                $currentProfileeeeeeeeeeee['name'] = $currentProfileeeeeeeeeeee['name'] ?? '';
                $currentProfileeeeeeeeeeee['comment'] = $currentProfileeeeeeeeeeee['comment'] ?? '';
                $currentProfileeeeeeeeeeee['shared-users'] = $currentProfileeeeeeeeeeee['shared-users'] ?? '1';
                $currentProfileeeeeeeeeeee['rate-limit'] = $currentProfileeeeeeeeeeee['rate-limit'] ?? '';
                $currentProfileeeeeeeeeeee['.id'] = $currentProfileeeeeeeeeeee['.id'] ?? '';

                $profiles[] = $currentProfileeeeeeeeeeee;
                $currentProfileeeeeeeeeeee = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $currentProfileeeeeeeeeeee[$parts[0]] = $parts[1];
            }
        }
    }

    return $profiles;
}

// Add MikroTik Hotspot User with Mikhmon Metadata support
function mikrotikAddHotspotUser($username, $password, $profile = 'default', $extraData = [])
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return false;
    }

    // Add hotspot user
    mikrotikWrite($socket, '/ip/hotspot/user/add');
    mikrotikWrite($socket, '=name=' . $username);
    mikrotikWrite($socket, '=password=' . $password);
    mikrotikWrite($socket, '=profile=' . $profile);

    // Add extra parameters if provided
    if (isset($extraData['server'])) {
        mikrotikWrite($socket, '=server=' . $extraData['server']);
    }
    if (isset($extraData['limit-uptime'])) {
        mikrotikWrite($socket, '=limit-uptime=' . $extraData['limit-uptime']);
    }
    if (isset($extraData['limit-bytes-total'])) {
        mikrotikWrite($socket, '=limit-bytes-total=' . $extraData['limit-bytes-total']);
    }

    // Mikhmon Style Comment
    $comment = $extraData['comment'] ?? "parent:{$profile}";
    mikrotikWrite($socket, '=comment=' . $comment);

    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);

    // Check for success (no !trap error)
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0) {
            return false;
        }
    }

    return true;
}

// Delete MikroTik Hotspot User
function mikrotikDeleteHotspotUser($username)
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return false;
    }

    // Find user first
    mikrotikWrite($socket, '/ip/hotspot/user/print');
    mikrotikWrite($socket, '?name=' . $username);
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    // Find the .id
    $userId = null;
    foreach ($allWords as $word) {
        if (strpos($word, '=.id=') === 0) {
            $userId = substr($word, 5);
            break;
        }
    }

    if (!$userId) {
        return false; // User not found
    }

    // Remove user
    mikrotikWrite($socket, '/ip/hotspot/user/remove');
    mikrotikWrite($socket, '=.id=' . $userId);
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);

    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0) {
            return false;
        }
    }

    return true;
}

// Toggle Hotspot User (Enable/Disable)
function mikrotikToggleHotspotUser($username, $status)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return false;

    // Find user first
    mikrotikWrite($socket, '/ip/hotspot/user/print');
    mikrotikWrite($socket, '?name=' . $username);
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 5;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    // Find the .id
    $userId = null;
    foreach ($allWords as $word) {
        if (strpos($word, '=.id=') === 0) {
            $userId = substr($word, 5);
            break;
        }
    }

    if (!$userId) {
        return false;
    }

    // Toggle
    mikrotikWrite($socket, '/ip/hotspot/user/set');
    mikrotikWrite($socket, '=.id=' . $userId);
    mikrotikWrite($socket, '=disabled=' . ($status === 'enable' ? 'no' : 'yes'));
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);

    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0)
            return false;
    }

    return true;
}

// Get MikroTik Hotspot Users
function mikrotikGetHotspotUsers()
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return [];
    }

    // Get hotspot users
    mikrotikWrite($socket, '/ip/hotspot/user/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    // Do NOT fclose() — this is a shared persistent connection

    $users = [];
    $currentUser = [];

    foreach ($allWords as $word) {
        if ($word === '!re' || $word === '!done') {
            if (!empty($currentUser)) {
                // Ensure default keys
                $currentUser['name'] = $currentUser['name'] ?? '';
                $currentUser['profile'] = $currentUser['profile'] ?? 'default';
                $currentUser['comment'] = $currentUser['comment'] ?? '';
                $currentUser['limit-uptime'] = $currentUser['limit-uptime'] ?? '∞';
                $currentUser['limit-bytes-total'] = $currentUser['limit-bytes-total'] ?? 0;
                $currentUser['uptime'] = $currentUser['uptime'] ?? '0s';
                $currentUser['bytes-in'] = $currentUser['bytes-in'] ?? 0;
                $currentUser['bytes-out'] = $currentUser['bytes-out'] ?? 0;

                $users[] = $currentUser;
                $currentUser = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $currentUser[$parts[0]] = $parts[1];
            }
        }
    }

    return $users;
}
// Update MikroTik Hotspot User
function mikrotikUpdateHotspotUser($id, $data)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return false;

    mikrotikWrite($socket, '/ip/hotspot/user/set');
    mikrotikWrite($socket, '=.id=' . $id);
    if (isset($data['name']))
        mikrotikWrite($socket, '=name=' . $data['name']);
    if (isset($data['password']))
        mikrotikWrite($socket, '=password=' . $data['password']);
    if (isset($data['profile']))
        mikrotikWrite($socket, '=profile=' . $data['profile']);
    if (isset($data['limit-uptime']))
        mikrotikWrite($socket, '=limit-uptime=' . $data['limit-uptime']);
    if (isset($data['limit-bytes-total']))
        mikrotikWrite($socket, '=limit-bytes-total=' . $data['limit-bytes-total']);
    if (isset($data['comment']))
        mikrotikWrite($socket, '=comment=' . $data['comment']);
    if (isset($data['disabled']))
        mikrotikWrite($socket, '=disabled=' . $data['disabled']);
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    // Do NOT fclose() — this is a shared persistent connection
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0)
            return false;
    }
    return true;
}

// Get Active Hotspot Users
function mikrotikGetHotspotActive()
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/ip/hotspot/active/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }
    // Do NOT fclose() — this is a shared persistent connection

    $active = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re' || $word === '!done') {
            if (!empty($current)) {
                $current['user'] = $current['user'] ?? '';
                $current['address'] = $current['address'] ?? '';
                $current['uptime'] = $current['uptime'] ?? '0s';

                $active[] = $current;
                $current = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2)
                $current[$parts[0]] = $parts[1];
        }
    }
    return $active;
}

// Update Hotspot Profileeeeeeeeeeee
function mikrotikUpdateHotspotProfileeeeeeeeeeee($id, $data)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return false;

    mikrotikWrite($socket, '/ip/hotspot/user/profile/set');
    mikrotikWrite($socket, '=.id=' . $id);
    if (isset($data['name']))
        mikrotikWrite($socket, '=name=' . $data['name']);
    if (isset($data['shared-users']))
        mikrotikWrite($socket, '=shared-users=' . $data['shared-users']);
    if (isset($data['rate-limit']))
        mikrotikWrite($socket, '=rate-limit=' . $data['rate-limit']);
    if (isset($data['keepalive-timeout']))
        mikrotikWrite($socket, '=keepalive-timeout=' . $data['keepalive-timeout']);
    if (isset($data['idle-timeout']))
        mikrotikWrite($socket, '=idle-timeout=' . $data['idle-timeout']);
    if (isset($data['address-pool']))
        mikrotikWrite($socket, '=address-pool=' . $data['address-pool']);
    if (isset($data['parent-queue']))
        mikrotikWrite($socket, '=parent-queue=' . $data['parent-queue']);
    if (isset($data['on-login']))
        mikrotikWrite($socket, '=on-login=' . $data['on-login']);
    if (isset($data['comment']))
        mikrotikWrite($socket, '=comment=' . $data['comment']);
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0) {
            $trap = mikrotikTrapMessageFromResponse($response);
            if ($trap !== '') {
                logError('MikroTik update hotspot profile failed: ' . $trap);
            } else {
                logError('MikroTik update hotspot profile failed.');
            }
            return false;
        }
    }
    return true;
}

// Add Hotspot Profileeeeeeeeeeee
function mikrotikAddHotspotProfileeeeeeeeeeee($data)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return false;

    mikrotikWrite($socket, '/ip/hotspot/user/profile/add');
    if (isset($data['name']))
        mikrotikWrite($socket, '=name=' . $data['name']);
    if (isset($data['shared-users']))
        mikrotikWrite($socket, '=shared-users=' . $data['shared-users']);
    if (isset($data['rate-limit']))
        mikrotikWrite($socket, '=rate-limit=' . $data['rate-limit']);
    if (isset($data['keepalive-timeout']))
        mikrotikWrite($socket, '=keepalive-timeout=' . $data['keepalive-timeout']);
    if (isset($data['idle-timeout']))
        mikrotikWrite($socket, '=idle-timeout=' . $data['idle-timeout']);
    if (isset($data['address-pool']))
        mikrotikWrite($socket, '=address-pool=' . $data['address-pool']);
    if (isset($data['parent-queue']))
        mikrotikWrite($socket, '=parent-queue=' . $data['parent-queue']);
    if (isset($data['on-login']))
        mikrotikWrite($socket, '=on-login=' . $data['on-login']);
    if (isset($data['comment']))
        mikrotikWrite($socket, '=comment=' . $data['comment']);
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0) {
            $trap = mikrotikTrapMessageFromResponse($response);
            if ($trap !== '') {
                logError('MikroTik add hotspot profile failed: ' . $trap);
            } else {
                logError('MikroTik add hotspot profile failed.');
            }
            return false;
        }
    }
    return true;
}

// Delete Hotspot Profileeeeeeeeeeee
function mikrotikDeleteHotspotProfileeeeeeeeeeee($id)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return false;

    mikrotikWrite($socket, '/ip/hotspot/user/profile/remove');
    mikrotikWrite($socket, '=.id=' . $id);
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    fclose($socket);
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0) {
            $trap = mikrotikTrapMessageFromResponse($response);
            if ($trap !== '') {
                logError('MikroTik delete hotspot profile failed: ' . $trap);
            } else {
                logError('MikroTik delete hotspot profile failed.');
            }
            return false;
        }
    }
    return true;
}

// Generate Mikhmon v3-style on-login script
// Expected header format (as used by Mikhmon v3):
// :put (",rem,2000,1d,3000,,Disable,");
// then actual on-login body script.
function generateHotspotExpiryScript($mode, $price = 0, $validity = '', $sellingPrice = 0, $lockUser = 'Disable', $compatMode = 'auto')
{
    $price = (int) $price;
    $sellingPrice = (int) $sellingPrice;
    $validity = trim((string) $validity);
    $compatMode = strtolower(trim((string) $compatMode));

    $mode = strtolower((string) $mode);
    $modeShort = '0';
    if ($mode === 'remove') {
        $modeShort = 'rem';
    } elseif ($mode === 'notice') {
        $modeShort = 'not';
    } elseif ($mode === 'record') {
        $modeShort = 'rec';
    }

    $interval = $validity !== '' ? $validity : '1d';

    $compatTag = $compatMode;
    if (!in_array($compatTag, ['auto', 'ros6', 'ros7'], true)) {
        $compatTag = 'auto';
    }
    $meta = ':put (",' . $modeShort . ',' . $price . ',' . $validity . ',' . $sellingPrice . ',,' . $lockUser . ',' . $compatTag . ',");';

    if ($mode === 'none' || $mode === '') {
        return $meta . ' :nothing';
    }

    $script = '{:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment];';
    $script .= ' :local ucode [:pick $comment 0 2];';
    $script .= ' :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={';
    $script .= ' :local date [ /system clock get date ];';
    $script .= ' :local year "";';
    if ($compatMode === 'ros7') {
        $script .= ' :set year [:pick $date 0 4];';
    } elseif ($compatMode === 'ros6') {
        $script .= ' :set year [:pick $date 7 11];';
    } else {
        $script .= ' :if ([:find $date "-"] != nil) do={ :set year [:pick $date 0 4]; } else={ :set year [:pick $date 7 11]; };';
    }
    $script .= ' /sys sch add name="$user" disable=no start-date=$date interval="' . $interval . '";';
    $script .= ' :delay 5s;';
    $script .= ' :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run];';
    $script .= ' :local getxp [len $exp];';
    $script .= ' :if ([:find $exp "-"] != nil) do={ /ip hotspot user set comment="$exp" [find where name="$user"]; } else={';
    $script .= ' :if ($getxp = 15) do={';
    $script .= ' :local d [:pick $exp 0 6];';
    $script .= ' :local t [:pick $exp 7 16];';
    $script .= ' :local s ("/");';
    $script .= ' :local exp ("$d$s$year $t");';
    $script .= ' /ip hotspot user set comment="$exp" [find where name="$user"];';
    $script .= ' };';
    $script .= ' :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"]; };';
    $script .= ' :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"]; };';
    $script .= ' };';
    $script .= ' :delay 5s;';
    $script .= ' /sys sch remove [find where name="$user"]';
    $script .= ' }';
    $script .= ' }';

    return $meta . ' ' . $script;
}

// Parse Mikhmon v3 on-login script to extract price, validity, selling price, lock user
// Based on Mikhmon v3 source: process/getvalidprice.php
function parseMikhmonOnLogin($onLoginScript)
{
    $data = [
        'price' => 0,
        'validity' => '-',
        'selling_price' => 0,
        'datalimit' => '',
        'timelimit' => '',
        'lock_user' => 'disable',
        'mode' => 'none',
    ];

    if (empty($onLoginScript))
        return $data;

    $headerParts = null;
    if (preg_match('/:put\\s*\\(\\s*"(.*?)"\\s*\\)\\s*;?/i', $onLoginScript, $m)) {
        $headerParts = explode(',', $m[1]);
    }

    if (is_array($headerParts) && count($headerParts) >= 2) {
        // Header example: ",rem,2000,1d,3000,,Disable,auto,"
        $mode = trim((string) ($headerParts[1] ?? ''));
        $price = trim((string) ($headerParts[2] ?? '0'));
        $validity = trim((string) ($headerParts[3] ?? ''));
        $selling = trim((string) ($headerParts[4] ?? '0'));
        $datalimit = trim((string) ($headerParts[5] ?? ''));
        $tail6 = trim((string) ($headerParts[6] ?? ''));
        $tail7 = trim((string) ($headerParts[7] ?? ''));
        $tail8 = trim((string) ($headerParts[8] ?? ''));

        if ($mode !== '') {
            $data['mode'] = $mode;
        }
        if (is_numeric($price)) {
            $data['price'] = (int) $price;
        }
        if ($validity !== '') {
            $data['validity'] = $validity;
        }
        if (is_numeric($selling)) {
            $data['selling_price'] = (int) $selling;
        }
        if ($datalimit !== '') {
            $data['datalimit'] = $datalimit;
        }

        if (preg_match('/^(Disable|Enable|disable|enable)$/', $tail6)) {
            $data['lock_user'] = strtolower($tail6);
        } else {
            if ($tail6 !== '') {
                $data['timelimit'] = $tail6;
            }
            if (preg_match('/^(Disable|Enable|disable|enable)$/', $tail7)) {
                $data['lock_user'] = strtolower($tail7);
            }
        }
        $compat = strtolower($tail7);
        if (in_array($compat, ['auto', 'ros6', 'ros7'], true)) {
            $data['compat'] = $compat;
        } else {
            $compat2 = strtolower($tail8);
            if (in_array($compat2, ['auto', 'ros6', 'ros7'], true)) {
                $data['compat'] = $compat2;
            } else {
                $data['compat'] = 'auto';
            }
        }

        return $data;
    }

    $parts = explode(',', $onLoginScript);

    // Legacy format (app simplified): script,mode,price,validity,sellingPrice,datalimit,timelimit,lockUser
    if (isset($parts[1]) && !empty($parts[1])) {
        $data['mode'] = $parts[1];
    }
    if (isset($parts[2]) && is_numeric($parts[2])) {
        $data['price'] = (int) $parts[2];
    }
    if (isset($parts[3]) && !empty($parts[3])) {
        $data['validity'] = $parts[3];
    }
    if (isset($parts[4]) && is_numeric($parts[4])) {
        $data['selling_price'] = (int) $parts[4];
    }
    if (isset($parts[5]) && !empty($parts[5])) {
        $data['datalimit'] = $parts[5];
    }
    if (isset($parts[6]) && !empty($parts[6])) {
        $data['timelimit'] = $parts[6];
    }
    if (isset($parts[7]) && !empty($parts[7])) {
        $data['lock_user'] = $parts[7];
    }
    $data['compat'] = 'auto';

    return $data;
}

// Get MikroTik System Resource (CPU, Memory, Uptime, Board Name, etc.)
function mikrotikGetSystemResource()
{
    $socket = getMikrotikConnection();
    if (!$socket) {
        return [
            'board-name' => '-',
            'cpu-load' => 0,
            'free-memory' => 0,
            'total-memory' => 0,
            'uptime' => '-',
            'version' => '-',
            'architecture-name' => '-',
        ];
    }

    mikrotikWrite($socket, '/system/resource/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $resource = [];
    foreach ($allWords as $word) {
        if (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $resource[$parts[0]] = $parts[1];
            }
        }
    }

    return [
        'board-name' => $resource['board-name'] ?? '-',
        'cpu-load' => (int) ($resource['cpu-load'] ?? 0),
        'free-memory' => (int) ($resource['free-memory'] ?? 0),
        'total-memory' => (int) ($resource['total-memory'] ?? 0),
        'uptime' => $resource['uptime'] ?? '-',
        'version' => $resource['version'] ?? '-',
        'architecture-name' => $resource['architecture-name'] ?? '-',
    ];
}

// Get list of MikroTik interfaces
function mikrotikGetInterfaces()
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/interface/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $interfaces = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re') {
            if (!empty($current)) {
                $interfaces[] = $current;
            }
            $current = [];
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $current[$parts[0]] = $parts[1];
            }
        }
    }
    if (!empty($current)) {
        $interfaces[] = $current;
    }

    return $interfaces;
}

// Monitor traffic on a specific interface (one-shot read)
function mikrotikMonitorTraffic($interfaceName)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return ['tx' => 0, 'rx' => 0];

    mikrotikWrite($socket, '/interface/monitor-traffic');
    mikrotikWrite($socket, '=interface=' . $interfaceName);
    mikrotikWrite($socket, '=once=');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $data = [];
    foreach ($allWords as $word) {
        if (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $data[$parts[0]] = $parts[1];
            }
        }
    }

    return [
        'tx' => (int) ($data['tx-bits-per-second'] ?? 0),
        'rx' => (int) ($data['rx-bits-per-second'] ?? 0),
    ];
}

// Get Hotspot Log entries from MikroTik
function mikrotikGetHotspotLog($limit = 20)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/log/print');
    mikrotikWrite($socket, '?topics=hotspot,info,debug');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;

    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $logs = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re') {
            if (!empty($current)) {
                $logs[] = $current;
            }
            $current = [];
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $current[$parts[0]] = $parts[1];
            }
        }
    }
    if (!empty($current)) {
        $logs[] = $current;
    }

    // Return last N entries in reverse order (newest first)
    $logs = array_reverse($logs);
    return array_slice($logs, 0, $limit);
}

// Get MikroTik Address Pools
function mikrotikGetAddressPools()
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/ip/pool/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $pools = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re' || $word === '!done') {
            if (!empty($current)) {
                $pools[] = $current;
                $current = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2)
                $current[$parts[0]] = $parts[1];
        }
    }
    return $pools;
}

// Get MikroTik Parent Queues
function mikrotikGetParentQueues()
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/queue/simple/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $queues = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re' || $word === '!done') {
            if (!empty($current)) {
                $queues[] = $current;
                $current = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2)
                $current[$parts[0]] = $parts[1];
        }
    }
    return $queues;
}

// Record Hotspot Sale in Database
function recordHotspotSale($username, $profile, $price, $sellingPrice, $prefix = '', $salesUserId = null)
{
    $data = [
        'username' => sanitize($username),
        'profile' => sanitize($profile),
        'price' => (float) $price,
        'selling_price' => (float) $sellingPrice,
        'prefix' => sanitize($prefix),
        'sales_user_id' => $salesUserId,
        'created_at' => date('Y-m-d H:i:s')
    ];

    try {
        return insert('hotspot_sales', $data);
    } catch (Exception $e) {
        logError("Failed to record hotspot sale: " . $e->getMessage());
        return false;
    }
}

// Kick (remove) an active hotspot user session
function mikrotikKickHotspotUser($username)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return false;

    // First find the session .id
    mikrotikWrite($socket, '/ip/hotspot/active/print');
    mikrotikWrite($socket, '?user=' . $username);
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 5;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $sessionId = null;
    foreach ($allWords as $word) {
        if (strpos($word, '=.id=') === 0) {
            $sessionId = substr($word, 5);
            break;
        }
    }

    if (!$sessionId)
        return false;

    // Remove the session
    mikrotikWrite($socket, '/ip/hotspot/active/remove');
    mikrotikWrite($socket, '=.id=' . $sessionId);
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0)
            return false;
    }
    return true;
}

// Get MikroTik Hotspot Cookies
function mikrotikGetHotspotCookies()
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/ip/hotspot/cookie/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $cookies = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re' || $word === '!done') {
            if (!empty($current)) {
                $cookies[] = $current;
                $current = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2)
                $current[$parts[0]] = $parts[1];
        }
    }
    return $cookies;
}

// Delete a hotspot cookie
function mikrotikDeleteHotspotCookie($id)
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return false;

    mikrotikWrite($socket, '/ip/hotspot/cookie/remove');
    mikrotikWrite($socket, '=.id=' . $id);
    mikrotikWrite($socket, '');

    $response = mikrotikReadSentence($socket);
    foreach ($response as $word) {
        if (strpos($word, '!trap') === 0)
            return false;
    }
    return true;
}

// Get MikroTik Hotspot Hosts (connected devices)
function mikrotikGetHotspotHosts()
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/ip/hotspot/host/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $hosts = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re' || $word === '!done') {
            if (!empty($current)) {
                $hosts[] = $current;
                $current = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2)
                $current[$parts[0]] = $parts[1];
        }
    }
    return $hosts;
}

// Get MikroTik System Schedulers
function mikrotikGetSchedulers()
{
    $socket = getMikrotikConnection();
    if (!$socket)
        return [];

    mikrotikWrite($socket, '/system/scheduler/print');
    mikrotikWrite($socket, '');

    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words))
            break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }

    $schedulers = [];
    $current = [];
    foreach ($allWords as $word) {
        if ($word === '!re' || $word === '!done') {
            if (!empty($current)) {
                $schedulers[] = $current;
                $current = [];
            }
        } elseif (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2)
                $current[$parts[0]] = $parts[1];
        }
    }
    return $schedulers;
}

// Get MikroTik Resource
function mikrotikGetResource() {
    $socket = getMikrotikConnection();
    if (!$socket) {
        return null;
    }
    
    mikrotikWrite($socket, '/system/resource/print');
    mikrotikWrite($socket, '');
    
    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words)) break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }
    
    $resource = [];
    foreach ($allWords as $word) {
        if ($word === '!re') {
            continue;
        }
        if (strpos($word, '=') === 0) {
            $word = substr($word, 1);
            $parts = explode('=', $word, 2);
            if (count($parts) === 2) {
                $resource[$parts[0]] = $parts[1];
            }
        }
    }
    
    return $resource;
}

// Ping from MikroTik
function mikrotikPing($target, $count = 4) {
    $socket = getMikrotikConnection();
    if (!$socket) {
        return null;
    }
    
    mikrotikWrite($socket, '/ping');
    mikrotikWrite($socket, '=address=' . $target);
    mikrotikWrite($socket, '=count=' . (int)$count);
    mikrotikWrite($socket, '');
    
    $allWords = [];
    $done = false;
    $timeout = time() + 15;
    
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words)) break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }
    
    $sent = 0;
    $received = 0;
    $lost = 0;
    $latencies = [];
    
    foreach ($allWords as $word) {
        if (strpos($word, '=sent=') === 0) {
            $sent = (int)substr($word, 6);
        } elseif (strpos($word, '=received=') === 0) {
            $received = (int)substr($word, 10);
        } elseif (strpos($word, '=packet-loss=') === 0) {
            $lost = (int)substr($word, 13);
        } elseif (strpos($word, '=time=') === 0) {
            $latencies[] = (float)substr($word, 6);
        }
    }
    
    $avg = null;
    if (!empty($latencies)) {
        $avg = array_sum($latencies) / count($latencies);
    }
    
    return [
        'sent' => $sent,
        'received' => $received,
        'loss' => $lost,
        'avg' => $avg
    ];
}

// Remove Active Session by Name
function mikrotikRemoveActiveSessionByName($username, $routerId = null) {
    $socket = getMikrotikConnection($routerId);
    if (!$socket) {
        return false;
    }
    
    mikrotikWrite($socket, '/ppp/active/print');
    mikrotikWrite($socket, '?name=' . $username);
    mikrotikWrite($socket, '');
    
    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words)) break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') {
                $done = true;
                break;
            }
        }
    }
    
    $sessions = mikrotikParseUsers($allWords);
    if (empty($sessions)) {
        return true;
    }

    foreach ($sessions as $session) {
        $sessionId = $session['.id'] ?? null;
        if (!$sessionId) {
            continue;
        }
        mikrotikWrite($socket, '/ppp/active/remove');
        mikrotikWrite($socket, '=.id=' . $sessionId);
        mikrotikWrite($socket, '');

        $response = mikrotikReadSentence($socket);
        foreach ($response as $word) {
            if (strpos($word, '!trap') === 0) {
                return false;
            }
        }
    }

    return true;
}

function mikrotikGetSecretByName($username) {
    $socket = getMikrotikConnection();
    if (!$socket) return null;
    
    mikrotikWrite($socket, '/ppp/secret/print');
    mikrotikWrite($socket, '?name=' . $username);
    mikrotikWrite($socket, '');
    
    $allWords = [];
    $done = false;
    $timeout = time() + 10;
    while (!$done && time() < $timeout) {
        $words = mikrotikReadSentence($socket);
        if (empty($words)) break;
        foreach ($words as $word) {
            $allWords[] = $word;
            if ($word === '!done') { $done = true; break; }
        }
    }
    
    $secrets = mikrotikParseUsers($allWords);
    return !empty($secrets) ? $secrets[0] : null;
}
