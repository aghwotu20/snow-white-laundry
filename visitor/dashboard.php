<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('visitor');
$pdo      = swl_db();

$uid = (int)$authUser['id'];
$em  = $authUser['email'];

$totalOrders = (int)$pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? OR customer_email = ?')
    ->execute([$uid, $em]);
$cntStmt = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount_ngn),0) AS spend FROM orders WHERE user_id = :u OR customer_email = :e');
$cntStmt->execute([':u' => $uid, ':e' => $em]);
$summary = $cntStmt->fetch();

$activeStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE (user_id = :u OR customer_email = :e) AND order_status NOT IN ("delivered","cancelled")');
$activeStmt->execute([':u' => $uid, ':e' => $em]);
$activeCount = (int)$activeStmt->fetchColumn();

$unpaidStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE (user_id = :u OR customer_email = :e) AND payment_status = "unpaid" AND order_status != "cancelled"');
$unpaidStmt->execute([':u' => $uid, ':e' => $em]);
$unpaidCount = (int)$unpaidStmt->fetchColumn();

$ordersStmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :u OR customer_email = :e ORDER BY id DESC LIMIT 8');
$ordersStmt->execute([':u' => $uid, ':e' => $em]);
$recentOrders = $ordersStmt->fetchAll();

$avatarUrl = swl_avatar_url($authUser);
$dashTitle = 'Visitor / Customer Dashboard';
$activeTab = 'dashboard';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- Customer Identity & Quick Action Banner -->
<div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:24px;background:linear-gradient(135deg,#14283e 0%,#1d3d5e 100%);color:#fff;border:none;">
  <div style="display:flex;align-items:center;gap:18px;">
    <div class="passport-avatar" style="width:72px;height:86px;border-width:3px;">
      <?php if ($avatarUrl): ?>
        <img src="<?= e($avatarUrl) ?>" alt="Passport Photograph">
      <?php else: ?>
        <span><?= e(swl_initials($authUser['full_name'])) ?></span>
      <?php endif; ?>
    </div>
    <div>
      <span class="badge badge-confirmed" style="margin-bottom:6px;">VERIFIED CUSTOMER PORTAL</span>
      <h2 style="color:#fff;margin:0 0 4px;font-size:1.45rem;"><?= e($authUser['full_name']) ?></h2>
      <div style="font-size:0.86rem;opacity:0.85;">
        <?= e($authUser['email']) ?> <?= !empty($authUser['phone']) ? ' &bull; ' . e($authUser['phone']) : '' ?>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="<?= e(swl_base_url('visitor/book.php')) ?>" class="btn btn-primary">
      <?= swl_icon('sparkles', 16) ?> Book New Laundry Order
    </a>
    <a href="<?= e(swl_base_url('visitor/profile.php')) ?>" class="btn btn-outline" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.25);color:#fff;">
      <?= swl_icon('camera', 16) ?> Upload Passport Photo
    </a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Laundry Orders</div>
    <div class="stat-value"><?= (int)$summary['cnt'] ?></div>
    <div class="stat-sub">Lifetime bookings</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Active Garment Care</div>
    <div class="stat-value"><?= $activeCount ?></div>
    <div class="stat-sub">In progress / scheduled</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Awaiting Payment</div>
    <div class="stat-value"><?= $unpaidCount ?></div>
    <div class="stat-sub">Payable online via Paystack</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Value</div>
    <div class="stat-value"><?= swl_money($summary['spend']) ?></div>
    <div class="stat-sub">All recorded orders</div>
  </div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <h3 style="margin:0;">Recent Laundry Orders</h3>
    <a href="<?= e(swl_base_url('visitor/orders.php')) ?>" class="btn btn-outline btn-sm">View Full History &rarr;</a>
  </div>

  <?php if (empty($recentOrders)): ?>
    <p>You have not booked a laundry pickup yet. <a href="<?= e(swl_base_url('visitor/book.php')) ?>">Schedule your first laundry order now &rarr;</a></p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="swl-table">
        <thead>
          <tr>
            <th>Order Ref</th>
            <th>Pickup Date</th>
            <th>Amount</th>
            <th>Payment</th>
            <th>Order Stage</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $ord): ?>
            <tr>
              <td><strong><?= e($ord['order_number']) ?></strong></td>
              <td><?= e(date('d M Y', strtotime($ord['preferred_date']))) ?></td>
              <td><strong><?= swl_money($ord['total_amount_ngn']) ?></strong></td>
              <td><span class="badge badge-<?= e($ord['payment_status']) ?>"><?= e(strtoupper($ord['payment_status'])) ?></span></td>
              <td><span class="badge badge-<?= e($ord['order_status']) ?>"><?= e(strtoupper(str_replace('_', ' ', $ord['order_status']))) ?></span></td>
              <td style="display:flex;gap:6px;flex-wrap:wrap;">
                <?php if ($ord['payment_status'] !== 'paid' && $ord['order_status'] !== 'cancelled'): ?>
                  <a href="<?= e(swl_base_url('paystack_checkout.php?order=' . urlencode($ord['order_number']))) ?>" class="btn btn-primary btn-sm">
                    Pay via Paystack
                  </a>
                <?php endif; ?>
                <a href="<?= e(swl_base_url('receipt.php?order=' . urlencode($ord['order_number']))) ?>" class="btn btn-outline btn-sm">Receipt</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
