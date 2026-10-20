<?php
require_once __DIR__ . '/includes/bootstrap.php';

$u = swl_current_user();
if ($u) {
    swl_log_activity((int)$u['id'], $u['role'], 'auth.logout', $u['full_name'] . ' signed out.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
}
session_destroy();

session_start();
swl_flash('success', 'You have signed out safely.');
swl_redirect('login.php');
