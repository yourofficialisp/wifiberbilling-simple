<?php
/**
 * Customer Portal Logout
 */

require_once '../includes/auth.php';
customerLogout();

// Redirect to portal login page
header('Location: login.php');
exit;
