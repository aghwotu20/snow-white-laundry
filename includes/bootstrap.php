<?php
/**
 * Snow White Laundry — Application Bootstrap, Auth, Uploads, Maintenance & Paystack Engine
 */

require_once __DIR__ . '/../config/config.php';

define('SWL_ROOT', realpath(__DIR__ . '/..'));

// Dynamic HTTPS & Cross-Origin Preview Iframe Cookie Support (Works on cPanel HTTP/HTTPS + Arena Preview)
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
    (($_SERVER['SERVER_PORT'] ?? '') == '443')
);

if (session_status() === PHP_SESSION_NONE) {
    if ($isHttps) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);
    } else {
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_name('SWL_PORTAL_SESS');
    session_start();
}

/**
 * Compute base URL dynamically so deployment works at root (`public_html/`) OR subdirectory (`public_html/laundry/`)
 */
function swl_base_url(string $path = ''): string
{
    static $basePath = null;
    if ($basePath === null) {
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        $appRoot = SWL_ROOT;
        if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
            $rel = trim(str_replace('\\', '/', substr($appRoot, strlen($docRoot))), '/');
            $basePath = $rel === '' ? '' : '/' . $rel;
        } else {
            $basePath = '';
        }
    }
    $clean = ltrim($path, '/');
    return $clean === '' ? ($basePath ?: '/') : $basePath . '/' . $clean;
}

/**
 * Full absolute URL including protocol and host (used for Paystack callbacks & webhooks)
 */
function swl_full_url(string $path = ''): string
{
    $proto = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $proto . '://' . $host . swl_base_url($path);
}

/**
 * Singleton PDO MySQL connection with friendly redirect to cPanel Installer if not yet configured
 */
function swl_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Check core table exists
        $pdo->query('SELECT 1 FROM settings LIMIT 1');
        return $pdo;
    } catch (Throwable $e) {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== 'install.php') {
            header('Location: ' . swl_base_url('install.php'));
            exit;
        }
        throw $e;
    }
}

/**
 * Escape HTML safely
 */
function e(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format Nigerian Naira cleanly
 */
function swl_money($amount, string $currency = 'NGN'): string
{
    $num = (float)$amount;
    $symbol = match (strtoupper($currency)) {
        'NGN'   => '₦',
        'USD'   => '$',
        'GHS'   => 'GH₵',
        default => strtoupper($currency) . ' ',
    };
    return $symbol . number_format($num, 0, '.', ',');
}

/**
 * Settings cache & persistence
 */
function &swl_settings_cache(): array
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $stmt = swl_db()->query('SELECT setting_key, setting_value FROM settings');
            foreach ($stmt->fetchAll() as $row) {
                $settings[$row['setting_key']] = (string)($row['setting_value'] ?? '');
            }
        } catch (Throwable $t) {
            $settings = [];
        }
    }
    return $settings;
}

function swl_setting(string $key, string $default = ''): string
{
    $cache = &swl_settings_cache();
    return array_key_exists($key, $cache) && $cache[$key] !== '' ? $cache[$key] : $default;
}

function swl_setting_raw(string $key, string $default = ''): string
{
    $cache = &swl_settings_cache();
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function swl_save_setting(string $key, string $value, string $group = 'general'): void
{
    $stmt = swl_db()->prepare(
        'INSERT INTO settings (setting_key, setting_value, setting_group)
         VALUES (:k, :v, :g)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)'
    );
    $stmt->execute([':k' => $key, ':v' => $value, ':g' => $group]);
    $cache = &swl_settings_cache();
    $cache[$key] = $value;
}

/**
 * CSRF Token Helpers
 */
function swl_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function swl_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(swl_csrf_token()) . '">';
}

function swl_verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals(swl_csrf_token(), (string)$token)) {
        swl_flash('error', 'Your session security token expired. Please submit the form again.');
        $back = $_SERVER['HTTP_REFERER'] ?? swl_base_url();
        header('Location: ' . $back);
        exit;
    }
}

/**
 * Flash Notification Helpers
 */
function swl_flash(string $type, string $message): void
{
    $_SESSION['swl_flashes'][] = ['type' => $type, 'message' => $message];
}

function swl_get_flashes(): array
{
    $flashes = $_SESSION['swl_flashes'] ?? [];
    unset($_SESSION['swl_flashes']);
    return $flashes;
}

function swl_redirect(string $path): never
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . swl_base_url($path));
    }
    exit;
}

/**
 * Audit Log Helper
 */
function swl_log_activity(?int $userId, ?string $role, string $action, string $description): void
{
    try {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = trim(explode(',', (string)$ip)[0]);
        $stmt = swl_db()->prepare(
            'INSERT INTO activity_logs (user_id, actor_role, action, description, ip_address)
             VALUES (:uid, :role, :action, :desc, :ip)'
        );
        $stmt->execute([
            ':uid'    => $userId,
            ':role'   => $role,
            ':action' => $action,
            ':desc'   => mb_substr($description, 0, 250),
            ':ip'     => mb_substr($ip, 0, 45),
        ]);
    } catch (Throwable $e) {
        // Non-blocking audit logging
    }
}

/**
 * Current Authenticated User (Re-hydrated live from MySQL so Staff Suspension takes effect IMMEDIATELY!)
 */
function swl_current_user(bool $refresh = false): ?array
{
    static $cachedUser = false;
    if ($cachedUser !== false && !$refresh) {
        return $cachedUser;
    }

    if (empty($_SESSION['user_id'])) {
        $cachedUser = null;
        return null;
    }

    $stmt = swl_db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        $cachedUser = null;
        return null;
    }

    // Immediate real-time suspension enforcement!
    if ($user['status'] === 'suspended') {
        $reason = !empty($user['suspension_reason']) ? ' Reason: ' . $user['suspension_reason'] : '';
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        $cachedUser = null;
        swl_flash('error', 'Your account has been suspended by an administrator.' . $reason);
        return null;
    }

    $cachedUser = $user;
    return $cachedUser;
}

function swl_dashboard_path(string $role): string
{
    return match ($role) {
        'admin' => 'admin/dashboard.php',
        'staff' => 'staff/dashboard.php',
        default => 'visitor/dashboard.php',
    };
}

/**
 * Require authentication and optionally enforce specific role(s)
 */
function swl_require_auth(array|string|null $roles = null): array
{
    $user = swl_current_user();
    if (!$user) {
        swl_flash('warning', 'Please sign in to access your portal dashboard.');
        swl_redirect('login.php');
    }

    // Enforce Maintenance Mode even inside non-admin portals!
    if (swl_is_maintenance() && $user['role'] !== 'admin') {
        require SWL_ROOT . '/includes/maintenance_view.php';
        exit;
    }

    if ($roles !== null) {
        $allowed = (array)$roles;
        if (!in_array($user['role'], $allowed, true)) {
            swl_flash('error', 'You do not have permission to view that section.');
            swl_redirect(swl_dashboard_path($user['role']));
        }
    }

    return $user;
}

/**
 * Maintenance Mode Guard for Public Pages
 * Admins can still browse the full website smoothly while seeing a clear top indicator bar!
 */
function swl_is_maintenance(): bool
{
    return swl_setting_raw('maintenance_mode', '0') === '1';
}

function swl_enforce_public_maintenance(): void
{
    if (!swl_is_maintenance()) {
        return;
    }
    $user = swl_current_user();
    if ($user && $user['role'] === 'admin') {
        return; // Allow logged-in Administrator to preview website anytime
    }
    http_response_code(503);
    require SWL_ROOT . '/includes/maintenance_view.php';
    exit;
}

/**
 * Resolve User Avatar / Passport Photograph URL (or clean initials fallback)
 */
function swl_avatar_url(?array $user): ?string
{
    if (!$user || empty($user['passport_photo'])) {
        return null;
    }
    $rel = ltrim((string)$user['passport_photo'], '/');
    if (is_file(SWL_ROOT . '/' . $rel)) {
        return swl_base_url($rel) . '?v=' . filemtime(SWL_ROOT . '/' . $rel);
    }
    return null;
}

function swl_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out !== '' ? $out : 'SW';
}

/**
 * Resolve Active Brand Logo & Favicon URLs with Cache-Busting
 */
function swl_logo_url(): string
{
    $rel = ltrim(swl_setting('site_logo', 'uploads/branding/default-logo.svg'), '/');
    if (is_file(SWL_ROOT . '/' . $rel)) {
        return swl_base_url($rel) . '?v=' . filemtime(SWL_ROOT . '/' . $rel);
    }
    return swl_base_url('uploads/branding/default-logo.svg');
}

function swl_favicon_url(): string
{
    $rel = ltrim(swl_setting('site_favicon', 'uploads/branding/default-favicon.svg'), '/');
    if (is_file(SWL_ROOT . '/' . $rel)) {
        return swl_base_url($rel) . '?v=' . filemtime(SWL_ROOT . '/' . $rel);
    }
    return swl_base_url('uploads/branding/default-favicon.svg');
}

/**
 * Secure Image Uploader for Passport Photographs, Website Logo, and Favicon
 * Validates upload size, true MIME type via finfo, and image integrity.
 */
function swl_upload_image(
    array $file,
    string $subFolder,
    string $prefix,
    int $maxBytes = 5242880,
    bool $allowSvgOrIco = false
): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $msg = match ($file['error'] ?? -1) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded image exceeds the maximum file size limit.',
            UPLOAD_ERR_NO_FILE => 'Please choose an image file first.',
            default => 'Image upload failed. Please try again.',
        };
        return ['ok' => false, 'error' => $msg];
    }

    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'error' => 'Image file must be smaller than ' . round($maxBytes / 1048576, 1) . ' MB.'];
    }

    $tmpPath = $file['tmp_name'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = strtolower((string)$finfo->file($tmpPath));

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if ($allowSvgOrIco) {
        $allowedMimes['image/svg+xml'] = 'svg';
        $allowedMimes['text/xml']      = 'svg';
        $allowedMimes['image/x-icon']  = 'ico';
        $allowedMimes['image/vnd.microsoft.icon'] = 'ico';
    }

    if (!isset($allowedMimes[$mime])) {
        return [
            'ok'    => false,
            'error' => $allowSvgOrIco
                ? 'Unsupported file type. Allowed formats: PNG, JPG, WEBP, SVG, ICO.'
                : 'Unsupported photo format. Please upload a JPG, PNG, or WEBP passport photograph.',
        ];
    }

    $ext = $allowedMimes[$mime];

    // Extra sanity check on raster images using getimagesize()
    if (!in_array($ext, ['svg', 'ico'], true)) {
        if (@getimagesize($tmpPath) === false) {
            return ['ok' => false, 'error' => 'The uploaded file is not a valid image.'];
        }
    } elseif ($ext === 'svg') {
        $rawSvg = (string)file_get_contents($tmpPath);
        if (preg_match('/<script|onload\s*=|onerror\s*=|javascript:/i', $rawSvg)) {
            return ['ok' => false, 'error' => 'SVG contains disallowed active scripts.'];
        }
    }

    $targetDir = SWL_ROOT . '/uploads/' . trim($subFolder, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        // Fallback for automated CLI tests where move_uploaded_file checks SAPI upload table
        if (!copy($tmpPath, $destPath)) {
            return ['ok' => false, 'error' => 'Unable to save uploaded image to server storage.'];
        }
    }
    @chmod($destPath, 0644);

    return [
        'ok'   => true,
        'path' => 'uploads/' . trim($subFolder, '/') . '/' . $filename,
    ];
}

/**
 * Paystack REST API Client (Server-to-Server cURL)
 */
function swl_paystack_has_live_keys(): bool
{
    $pub = trim(swl_setting_raw('paystack_public_key', ''));
    $sec = trim(swl_setting_raw('paystack_secret_key', ''));
    return str_starts_with($pub, 'pk_') && str_starts_with($sec, 'sk_') && strlen($sec) > 16;
}

function swl_paystack_request(string $method, string $endpoint, ?array $payload = null): array
{
    $secretKey = trim(swl_setting_raw('paystack_secret_key', ''));
    if ($secretKey === '') {
        return ['status' => false, 'message' => 'Paystack Secret Key has not been configured yet.'];
    }

    $ch = curl_init('https://api.paystack.co' . $endpoint);
    $headers = [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/json',
        'Cache-Control: no-cache',
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload ?? []));
    }

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $err !== '') {
        return ['status' => false, 'message' => 'Network error communicating with Paystack: ' . $err];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : ['status' => false, 'message' => 'Invalid response received from Paystack.'];
}

/**
 * Crisp Embedded SVG Icons (Zero external network dependency — fast & sharp on all devices!)
 */
function swl_icon(string $name, int $size = 20, string $class = ''): string
{
    $cls = $class ? ' class="' . e($class) . '"' : '';
    $attrs = 'xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' . $cls;

    $paths = match ($name) {
        'shirt'     => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
        'basket'    => '<path d="m5 11 4-7"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8c.9 0 1.8-.7 2-1.6l1.7-7.4"/><path d="m9 11 1 9"/><path d="m15 11-1 9"/>',
        'sparkles'  => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/>',
        'briefcase' => '<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'gem'       => '<path d="M6 3h12l4 6-10 13L2 9Z"/><path d="M11 3 8 9l4 13 4-13-3-6"/><path d="M2 9h20"/>',
        'bed'       => '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>',
        'zap'       => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
        'building'  => '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/>',
        'phone'     => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'mail'      => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'map-pin'   => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'shield'    => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'check'     => '<path d="M20 6 9 17l-5-5"/>',
        'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'settings'  => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'grid'      => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
        'credit-card' => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
        'camera'    => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
        'alert'     => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'power'     => '<path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.77.04"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'ban'       => '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
        'search'    => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'whatsapp'  => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'truck'     => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'file-text' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        default     => '<circle cx="12" cy="12" r="9"/><path d="m9 12 2 2 4-4"/>',
    };

    return '<svg ' . $attrs . '>' . $paths . '</svg>';
}
