<?php
/**
 * Local Development Router for PHP Built-in Web Server (`php -S 0.0.0.0:8080 router.php`)
 * On Apache cPanel, `.htaccess` handles routing and security natively.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Mirror .htaccess security rules locally
if (preg_match('#^/(config|database)/#i', $uri) || preg_match('#\.(sql|env)$#i', $uri)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

if (preg_match('#^/uploads/.*\.(php|phtml|phar|sh|py|pl)$#i', $uri)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

$fullPath = __DIR__ . $uri;
if ($uri !== '/' && is_file($fullPath)) {
    return false; // Let PHP serve static assets & PHP scripts directly
}

if (is_dir($fullPath) && is_file(rtrim($fullPath, '/') . '/index.php')) {
    require rtrim($fullPath, '/') . '/index.php';
    return true;
}

require __DIR__ . '/index.php';
