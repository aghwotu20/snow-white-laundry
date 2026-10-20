<?php
/**
 * Optional Browser-Based cPanel Database Connection Wizard (`install.php`)
 * If `config/config.php` is already connected to a valid MySQL database, `install.php` stays safely locked!
 * If freshly uploaded to cPanel without editing `config/config.php` yet, this page lets you connect MySQL
 * and optionally auto-import `database/snow_white_laundry.sql` in one click!
 */
require_once __DIR__ . '/config/config.php';

$alreadyConnected = false;
try {
    $testDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $testPdo = new PDO($testDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $testPdo->query('SELECT 1 FROM settings LIMIT 1');
    $alreadyConnected = true;
} catch (Throwable $e) {
    $alreadyConnected = false;
}

$msg = '';
$err = '';

if (!$alreadyConnected && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = trim($_POST['db_host'] ?? 'localhost');
    $port   = trim($_POST['db_port'] ?? '3306');
    $name   = trim($_POST['db_name'] ?? '');
    $user   = trim($_POST['db_user'] ?? '');
    $pass   = $_POST['db_pass'] ?? '';
    $import = !empty($_POST['auto_import']);

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        if ($import) {
            $sqlFile = __DIR__ . '/database/snow_white_laundry.sql';
            if (is_file($sqlFile)) {
                $pdo->exec((string)file_get_contents($sqlFile));
            }
        }

        $cfgContent = "<?php\n"
            . "define('DB_HOST', " . var_export($host, true) . ");\n"
            . "define('DB_PORT', " . var_export($port, true) . ");\n"
            . "define('DB_NAME', " . var_export($name, true) . ");\n"
            . "define('DB_USER', " . var_export($user, true) . ");\n"
            . "define('DB_PASS', " . var_export($pass, true) . ");\n";

        file_put_contents(__DIR__ . '/config/config.local.php', $cfgContent);
        header('Location: index.php');
        exit;
    } catch (Throwable $ex) {
        $err = 'MySQL Connection Failed: ' . $ex->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Snow White Laundry — cPanel Database Setup</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:var(--ice-blue);padding:24px;">
  <main class="card" style="max-width:560px;width:100%;padding:38px;">
    <?php if ($alreadyConnected): ?>
      <span class="badge badge-paid" style="margin-bottom:12px;">SYSTEM ACTIVE &amp; LOCKED</span>
      <h2>Database Already Connected!</h2>
      <p>Your Snow White Laundry MySQL database is live and properly configured. For security, setup changes via browser are disabled once connected.</p>
      <a href="index.php" class="btn btn-primary">Go to Live Website &rarr;</a>
    <?php else: ?>
      <span class="eyebrow">cPanel Quick Setup</span>
      <h2>Connect Your cPanel MySQL Database</h2>
      <p style="font-size:0.9rem;">Enter the database credentials created in <strong>cPanel &rarr; MySQL Databases</strong>. You can either import <code>database/snow_white_laundry.sql</code> manually in phpMyAdmin or tick auto-import below.</p>

      <?php if ($err !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-row">
          <div class="form-group">
            <label>MySQL Host</label>
            <input type="text" name="db_host" class="form-control" value="localhost" required>
          </div>
          <div class="form-group">
            <label>Port</label>
            <input type="text" name="db_port" class="form-control" value="3306" required>
          </div>
        </div>
        <div class="form-group">
          <label>cPanel Database Name (e.g. cpaneluser_snowwhite) *</label>
          <input type="text" name="db_name" class="form-control" required>
        </div>
        <div class="form-group">
          <label>cPanel Database Username *</label>
          <input type="text" name="db_user" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Database Password</label>
          <input type="password" name="db_pass" class="form-control">
        </div>
        <label style="display:flex;align-items:center;gap:9px;margin-bottom:20px;cursor:pointer;">
          <input type="checkbox" name="auto_import" value="1" checked>
          <span>Also import tables &amp; default settings automatically if database is empty</span>
        </label>
        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Test Connection &amp; Launch Website</button>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
