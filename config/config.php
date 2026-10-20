<?php
/**
 * Snow White Laundry — Primary Database & Application Configuration
 *
 * CPANEL DEPLOYMENT INSTRUCTIONS:
 * 1. Create a MySQL database & user in cPanel -> MySQL Databases (grant ALL PRIVILEGES).
 * 2. Import `database/snow_white_laundry.sql` via cPanel -> phpMyAdmin.
 * 3. Update DB_HOST, DB_NAME, DB_USER, and DB_PASS below (OR visit `/install.php` in your browser).
 */

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('SWL_DB_HOST') ?: 'localhost');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', getenv('SWL_DB_PORT') ?: '3306');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('SWL_DB_NAME') ?: 'snow_white_laundry');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('SWL_DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('SWL_DB_PASS') !== false ? getenv('SWL_DB_PASS') : '');
}
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'Africa/Lagos');
}

date_default_timezone_set(APP_TIMEZONE);
