<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('admin');
$pdo      = swl_db();

$paidRevenue   = (float)$pdo->query("SELECT COALESCE(SUM(total_amount_ngn),0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$totalOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$activeStaff   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff' AND status = 'active'")->fetchColumn();
$suspStaff     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff' AND status = 'suspended'")->fetchColumn();
$visitorsCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'visitor'")->fetchColumn();

$recentOrders = $pdo->query(
    'SELECT o.*, u.full_name AS staff_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.assigned_staff_id
     ORDER BY o.id DESC LIMIT 6'
)->fetchAll();

$recentLogs = $pdo->query('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 7')->fetchAll();

$maintActive  = swl_is_maintenance();
$pskHasKeys   = swl_paystack_has_live_keys();
$avatarUrl    = swl_avatar_url($authUser);

$dashTitle = 'Administrator Control Center';
$activeTab = 'dashboard';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- Executive Welcome & Quick System Controls Card -->
<div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:24px;background:linear-gradient(135deg,#0f2133 0%,#14283e 65%,#0369a1 100%);color:#fff;border:none;">
  <div style="display:flex;align-items:center;gap:18px;">
    <div class="passport-avatar" style="width:74px;height:88px;border-width:3px;">
      <?php if ($avatarUrl): ?>
        <img src="<?= e($avatarUrl) ?>" alt="Admin Passport Photograph">
      <?php else: ?>
        <span><?= e(swl_initials($authUser['full_name'])) ?></span>
      <?php endif; ?>
    </div>
    <div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
        <span class="badge badge-active">SUPER ADMINISTRATOR</span>
        <?php if ($maintActive): ?>
          <span class="badge badge-warning">MAINTENANCE MODE ACTIVE</span>
        <?php else: ?>
          <span class="badge badge-paid">WEBSITE LIVE</span>
        <?php endif; ?>
        <span class="badge badge-confirmed">PAYSTACK: <?= $pskHasKeys ? strtoupper(swl_setting('paystack_mode', 'TEST')) . ' KEYS SET' : 'SANDBOX MODE' ?></span>
      </div>
      <h2 style="color:#fff;margin:0 0 4px;font-size:1.45rem;"><?= e($authUser['full_name']) ?></h2>
      <div style="font-size:0.85rem;opacity:0.86;"><?= e($authUser['email']) ?> &bull; Full System Privileges</div>
    </div>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="<?= e(swl_base_url('admin/settings.php')) ?>" class="btn btn-primary">
      <?= swl_icon('settings', 16) ?> Logo, Paystack &amp; Settings
    </a>
    <a href="<?= e(swl_base_url('admin/staff.php')) ?>" class="btn btn-outline" style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.28);color:#fff;">
      <?= swl_icon('users', 16) ?> Manage / Suspend Staff
    </a>
  </div>
</div>

<!-- KPI Summary Cards -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Verified Revenue</div>
    <div class="stat-value"><?= swl_money($paidRevenue) ?></div>
    <div class="stat-sub">Confirmed paid orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Laundry Orders</div>
    <div class="stat-value"><?= $totalOrders ?></div>
    <div class="stat-sub"><a href="<?= e(swl_base_url('admin/orders.php')) ?>">Manage all bookings &rarr;</a></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Staff Workforce</div>
    <div class="stat-value"><?= $activeStaff ?> Active</div>
    <div class="stat-sub"><?= $suspStaff ?> Suspended &bull; <a href="<?= e(swl_base_url('admin/staff.php')) ?>">Manage</a></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Website Visitors</div>
    <div class="stat-value"><?= $visitorsCount ?></div>
    <div class="stat-sub"><a href="<?= e(swl_base_url('admin/visitors.php')) ?>">View customer accounts &rarr;</a></div>
  </div>
</div>

<!-- Quick Control Strip: Maintenance Mode + Branding + Paystack -->
<div class="grid-3" style="margin-bottom:26px;">
  <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
    <div>
      <span class="eyebrow"><?= swl_icon('power', 13) ?> Site Availability</span>
      <h4>Maintenance Mode Control</h4>
      <p style="font-size:0.86rem;">
        Status: <strong style="color:<?= $maintActive ? '#b45309' : '#0d9488' ?>;">
          <?= $maintActive ? 'ON (Visitors see Maintenance Page)' : 'OFF (Website Publicly Accessible)' ?>
        </strong>
      </p>
    </div>
    <form method="post" action="<?= e(swl_base_url('admin/settings.php')) ?>">
      <?= swl_csrf_field() ?>
      <input type="hidden" name="form_action" value="quick_toggle_maintenance">
      <button type="submit" class="btn <?= $maintActive ? 'btn-success' : 'btn-warning' ?> btn-sm" style="width:100%;">
        <?= swl_icon('power', 15) ?>
        <?= $maintActive ? 'Turn Maintenance Mode OFF (Go Live)' : 'Enable Maintenance Mode Now' ?>
      </button>
    </form>
  </div>

  <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
    <div>
      <span class="eyebrow">Brand Identity</span>
      <h4>Website Logo &amp; Favicon</h4>
      <div style="display:flex;align-items:center;gap:14px;padding:10px;background:var(--off-white);border-radius:10px;margin-bottom:12px;">
        <img src="<?= e(swl_logo_url()) ?>" alt="Active Logo" style="height:36px;width:auto;">
        <img src="<?= e(swl_favicon_url()) ?>" alt="Active Favicon" style="width:32px;height:32px;border-radius:6px;margin-left:auto;">
      </div>
    </div>
    <a href="<?= e(swl_base_url('admin/settings.php#branding')) ?>" class="btn btn-outline btn-sm" style="width:100%;">
      Upload New Logo / Favicon &rarr;
    </a>
  </div>

  <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
    <div>
      <span class="eyebrow"><?= swl_icon('credit-card', 13) ?> Online Payments</span>
      <h4>Paystack Gateway Credentials</h4>
      <p style="font-size:0.86rem;">
        <?= $pskHasKeys
            ? 'Configured (' . strtoupper(swl_setting('paystack_mode', 'test')) . ' mode). Ready for live card & transfer payments.'
            : 'Running in built-in Sandbox mode. Enter your pk_... and sk_... keys to go live.' ?>
      </p>
    </div>
    <a href="<?= e(swl_base_url('admin/settings.php#paystack')) ?>" class="btn btn-primary btn-sm" style="width:100%;">
      Configure Paystack Keys &rarr;
    </a>
  </div>
</div>

<!-- Recent Orders + Audit Log -->
<div class="grid-2" style="grid-template-columns:1.3fr 0.7fr;align-items:start;">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;">Recent Customer Orders</h3>
      <a href="<?= e(swl_base_url('admin/orders.php')) ?>" class="btn btn-outline btn-sm">All Orders</a>
    </div>
    <div class="table-wrap">
      <table class="swl-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Pay</th>
            <th>Stage</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td><strong><?= e($o['order_number']) ?></strong></td>
              <td><?= e($o['customer_name']) ?></td>
              <td><strong><?= swl_money($o['total_amount_ngn']) ?></strong></td>
              <td><span class="badge badge-<?= e($o['payment_status']) ?>"><?= e(strtoupper($o['payment_status'])) ?></span></td>
              <td><span class="badge badge-<?= e($o['order_status']) ?>"><?= e(strtoupper(str_replace('_', ' ', $o['order_status']))) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-bottom:14px;">System Audit Trail</h3>
    <div style="display:grid;gap:10px;">
      <?php foreach ($recentLogs as $log): ?>
        <div style="padding:10px 12px;border-radius:10px;background:var(--off-white);border:1px solid var(--border-subtle);font-size:0.82rem;">
          <strong style="color:var(--deep-navy);display:block;"><?= e($log['description']) ?></strong>
          <span style="color:var(--text-subtle);font-size:0.75rem;">
            <?= e(strtoupper($log['actor_role'] ?? 'system')) ?> &bull; <?= e(date('d M, g:i A', strtotime($log['created_at']))) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
