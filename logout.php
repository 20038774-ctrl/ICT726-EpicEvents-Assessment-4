<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if (!is_post()) { http_response_code(405); exit('Method not allowed'); }
verify_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
}
session_destroy();
session_start();
flash('success', 'You have been logged out safely.');
redirect('index.php');
