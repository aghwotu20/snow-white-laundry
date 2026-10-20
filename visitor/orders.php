<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('visitor');
$pdo      = swl_db();

$stmt = $pdo->prepare(
    'SELECT o.*, s.full_name AS staff_name
     FROM orders o
     LEFT JOIN users s ON s.id = o.assigned_staff_id
     WHERE o.user_id = :u OR o.customer_email = :e
     ORDER BY o.id DESC'
);
$stmt->execute([':u' => $authUser['id'], ':e' => $authUser['email']]);
$orders = $stmt->fetchAll();

$dashTitle = 'My Laundry Orders & Paystack Payments';
$activeTab = 'orders';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
    <h3 style="margin:0;">All My Laundry Orders</h3>
    <a href="<?= e(swl_base_url('visitor/book.php')) ?>" class="btn btn-primary btn-sm">+ Book New Pickup</a>
  </div>

  <?php if (empty($orders)): ?>
    <p>No laundry orders yet. Click <strong>Book New Pickup</strong> to place your first order.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="swl-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Schedule</th>
            <th>Assigned Staff</th>
            <th>Total</th>
            <th>Payment Status</th>
            <th>Garment Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>
                <strong><?= e($o['order_number']) ?></strong>
                <?php if (!empty($o['staff_notes'])): ?>
                  <small style="display:block;color:var(--text-subtle);max-width:220px;"><?= e($o['staff_notes']) ?></small>
                <?php endif; ?>
              </td>
              <td><?= e(date('d M Y', strtotime($o['preferred_date']))) ?><br><small><?= e($o['preferred_time_slot']) ?></small></td>
              <td><?= e($o['staff_name'] ?? 'Assigning shortly') ?></td>
              <td><strong><?= swl_money($o['total_amount_ngn']) ?></strong></td>
              <td><span class="badge badge-<?= e($o['payment_status']) ?>"><?= e(strtoupper($o['payment_status'])) ?></span></td>
              <td><span class="badge badge-<?= e($o['order_status']) ?>"><?= e(strtoupper(str_replace('_', ' ', $o['order_status']))) ?></span></td>
              <td style="display:flex;gap:6px;flex-wrap:wrap;">
                <?php if ($o['payment_status'] !== 'paid' && $o['order_status'] !== 'cancelled'): ?>
                  <a href="<?= e(swl_base_url('paystack_checkout.php?order=' . urlencode($o['order_number']))) ?>" class="btn btn-primary btn-sm">Pay Now</a>
                <?php endif; ?>
                <a href="<?= e(swl_base_url('track.php?order=' . urlencode($o['order_number']))) ?>" class="btn btn-outline btn-sm">Track</a>
                <a href="<?= e(swl_base_url('receipt.php?order=' . urlencode($o['order_number']))) ?>" class="btn btn-outline btn-sm">Receipt</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
