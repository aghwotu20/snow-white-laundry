<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('admin');
$pdo      = swl_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $vid       = (int)($_POST['visitor_id'] ?? 0);
    $newStatus = ($_POST['target_status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';
    $pdo->prepare("UPDATE users SET status = :st WHERE id = :id AND role = 'visitor'")
        ->execute([':st' => $newStatus, ':id' => $vid]);
    swl_flash('success', 'Customer account status updated.');
    swl_redirect('admin/visitors.php');
}

$visitors = $pdo->query(
    "SELECT u.*,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id OR o.customer_email = u.email) AS orders_count,
            (SELECT COALESCE(SUM(o.total_amount_ngn),0) FROM orders o WHERE (o.user_id = u.id OR o.customer_email = u.email) AND o.payment_status = 'paid') AS total_paid
     FROM users u
     WHERE u.role = 'visitor'
     ORDER BY u.id DESC"
)->fetchAll();

$dashTitle = 'Registered Website Visitors & Customers';
$activeTab = 'visitors';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="card">
  <h3>Website Visitors Directory (<?= count($visitors) ?>)</h3>
  <p style="font-size:0.88rem;">View customer profiles, passport photographs, order counts, and verified Naira payments.</p>

  <div class="table-wrap" style="margin-top:16px;">
    <table class="swl-table">
      <thead>
        <tr>
          <th>Passport</th>
          <th>Customer Name</th>
          <th>Contact</th>
          <th>Address</th>
          <th>Orders</th>
          <th>Paid Spend</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($visitors as $v): ?>
          <?php $vAva = swl_avatar_url($v); ?>
          <tr>
            <td>
              <div class="passport-avatar" style="width:44px;height:52px;">
                <?php if ($vAva): ?>
                  <img src="<?= e($vAva) ?>" alt="<?= e($v['full_name']) ?>">
                <?php else: ?>
                  <span><?= e(swl_initials($v['full_name'])) ?></span>
                <?php endif; ?>
              </div>
            </td>
            <td><strong><?= e($v['full_name']) ?></strong></td>
            <td><?= e($v['email']) ?><br><small><?= e($v['phone']) ?></small></td>
            <td><?= e($v['address'] ?: '—') ?></td>
            <td><strong><?= (int)$v['orders_count'] ?></strong></td>
            <td><strong><?= swl_money($v['total_paid']) ?></strong></td>
            <td><span class="badge badge-<?= e($v['status']) ?>"><?= e(strtoupper($v['status'])) ?></span></td>
            <td>
              <form method="post" style="margin:0;">
                <?= swl_csrf_field() ?>
                <input type="hidden" name="visitor_id" value="<?= (int)$v['id'] ?>">
                <input type="hidden" name="target_status" value="<?= $v['status'] === 'active' ? 'suspended' : 'active' ?>">
                <button type="submit" class="btn btn-outline btn-sm">
                  <?= $v['status'] === 'active' ? 'Suspend' : 'Reactivate' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
