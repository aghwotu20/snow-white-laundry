<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('staff');
$pdo      = swl_db();

$sid = (int)$authUser['id'];

$myAssigned   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE assigned_staff_id = {$sid} AND order_status NOT IN ('delivered','cancelled')")->fetchColumn();
$unassigned   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE assigned_staff_id IS NULL AND order_status != 'cancelled'")->fetchColumn();
$readyPickup  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'ready'")->fetchColumn();
$openEnqCount = (int)$pdo->query("SELECT COUNT(*) FROM enquiries WHERE status IN ('new','in_review')")->fetchColumn();

$activeQueue = $pdo->query(
    'SELECT o.*, u.full_name AS staff_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.assigned_staff_id
     WHERE o.order_status NOT IN ("delivered","cancelled")
     ORDER BY o.id DESC LIMIT 10'
)->fetchAll();

$avatarUrl = swl_avatar_url($authUser);
$dashTitle = 'Staff Operations Workspace';
$activeTab = 'dashboard';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:24px;background:linear-gradient(135deg,#14283e 0%,#0369a1 100%);color:#fff;border:none;">
  <div style="display:flex;align-items:center;gap:18px;">
    <div class="passport-avatar" style="width:72px;height:86px;border-width:3px;">
      <?php if ($avatarUrl): ?>
        <img src="<?= e($avatarUrl) ?>" alt="Staff Passport Photograph">
      <?php else: ?>
        <span><?= e(swl_initials($authUser['full_name'])) ?></span>
      <?php endif; ?>
    </div>
    <div>
      <span class="badge badge-active" style="margin-bottom:6px;">ACTIVE STAFF &bull; <?= e($authUser['staff_code'] ?: 'OPERATIONS') ?></span>
      <h2 style="color:#fff;margin:0 0 4px;font-size:1.45rem;"><?= e($authUser['full_name']) ?></h2>
      <div style="font-size:0.86rem;opacity:0.9;">
        <?= e($authUser['job_title'] ?: 'Garment Care Specialist') ?> &bull; <?= e($authUser['email']) ?>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="<?= e(swl_base_url('staff/orders.php')) ?>" class="btn btn-outline" style="background:#fff;color:var(--deep-navy);border-color:#fff;">
      Manage Orders Queue
    </a>
    <a href="<?= e(swl_base_url('staff/profile.php')) ?>" class="btn btn-outline" style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.3);color:#fff;">
      <?= swl_icon('camera', 16) ?> My Passport &amp; Profile
    </a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Assigned To Me</div>
    <div class="stat-value"><?= $myAssigned ?></div>
    <div class="stat-sub">Active orders under your care</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Unassigned Queue</div>
    <div class="stat-value"><?= $unassigned ?></div>
    <div class="stat-sub">Ready to claim at reception</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Ready For Dispatch</div>
    <div class="stat-value"><?= $readyPickup ?></div>
    <div class="stat-sub">Cleaned &amp; pressed garments</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Open Customer Quotes</div>
    <div class="stat-value"><?= $openEnqCount ?></div>
    <div class="stat-sub">Awaiting staff response</div>
  </div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <h3 style="margin:0;">Live Garment Processing Queue</h3>
    <a href="<?= e(swl_base_url('staff/orders.php')) ?>" class="btn btn-primary btn-sm">Open Full Processing Controls &rarr;</a>
  </div>

  <div class="table-wrap">
    <table class="swl-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Schedule</th>
          <th>Payment</th>
          <th>Stage</th>
          <th>Assigned Staff</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($activeQueue as $ord): ?>
          <tr>
            <td><strong><?= e($ord['order_number']) ?></strong></td>
            <td><?= e($ord['customer_name']) ?><br><small><?= e($ord['customer_phone']) ?></small></td>
            <td><?= e(date('d M Y', strtotime($ord['preferred_date']))) ?> (<?= e(ucfirst($ord['service_speed'])) ?>)</td>
            <td><span class="badge badge-<?= e($ord['payment_status']) ?>"><?= e(strtoupper($ord['payment_status'])) ?></span></td>
            <td><span class="badge badge-<?= e($ord['order_status']) ?>"><?= e(strtoupper(str_replace('_', ' ', $ord['order_status']))) ?></span></td>
            <td><?= e($ord['staff_name'] ?? 'Unassigned') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
