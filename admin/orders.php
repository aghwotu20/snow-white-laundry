<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('admin');
$pdo      = swl_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $staffId   = !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : null;
    $status    = $_POST['order_status'] ?? 'confirmed';
    $payStatus = $_POST['payment_status'] ?? 'unpaid';

    $pdo->prepare('UPDATE orders SET assigned_staff_id = :sid, order_status = :ost, payment_status = :pst WHERE id = :id')
        ->execute([':sid' => $staffId, ':ost' => $status, ':pst' => $payStatus, ':id' => $orderId]);

    swl_log_activity((int)$authUser['id'], 'admin', 'order.admin_updated', 'Admin updated order #' . $orderId . ' (' . $status . ' / ' . $payStatus . ').');
    swl_flash('success', 'Order #' . $orderId . ' assignment and status updated.');
    swl_redirect('admin/orders.php');
}

$activeStaff = $pdo->query("SELECT id, full_name, staff_code FROM users WHERE role = 'staff' AND status = 'active' ORDER BY full_name ASC")->fetchAll();
$orders      = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();
$payments    = $pdo->query(
    'SELECT p.*, o.order_number, o.customer_name
     FROM payments p
     JOIN orders o ON o.id = p.order_id
     ORDER BY p.id DESC LIMIT 12'
)->fetchAll();

$dashTitle = 'Orders, Staff Assignment & Paystack Ledger';
$activeTab = 'orders';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="card" style="margin-bottom:26px;">
  <h3>Customer Laundry Orders &amp; Staff Assignment</h3>
  <div class="table-wrap" style="margin-top:14px;">
    <table class="swl-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Assign Active Staff</th>
          <th>Payment</th>
          <th>Order Stage</th>
          <th>Save</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $ord): ?>
          <tr>
            <form method="post">
              <?= swl_csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
              <td>
                <strong><a href="<?= e(swl_base_url('receipt.php?order=' . urlencode($ord['order_number']))) ?>" target="_blank"><?= e($ord['order_number']) ?></a></strong>
                <br><small><?= e(date('d M Y', strtotime($ord['preferred_date']))) ?></small>
              </td>
              <td><?= e($ord['customer_name']) ?><br><small><?= e($ord['customer_phone']) ?></small></td>
              <td><strong><?= swl_money($ord['total_amount_ngn']) ?></strong></td>
              <td>
                <select name="assigned_staff_id" class="form-control" style="padding:7px 9px;font-size:0.83rem;">
                  <option value="">-- Unassigned --</option>
                  <?php foreach ($activeStaff as $st): ?>
                    <option value="<?= (int)$st['id'] ?>" <?= (int)$ord['assigned_staff_id'] === (int)$st['id'] ? 'selected' : '' ?>>
                      <?= e($st['full_name']) ?> (<?= e($st['staff_code']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <select name="payment_status" class="form-control" style="padding:7px 9px;font-size:0.83rem;">
                  <?php foreach (['unpaid','paid','failed','refunded'] as $ps): ?>
                    <option value="<?= $ps ?>" <?= $ord['payment_status'] === $ps ? 'selected' : '' ?>><?= strtoupper($ps) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <select name="order_status" class="form-control" style="padding:7px 9px;font-size:0.83rem;">
                  <?php foreach (['pending','confirmed','picked_up','processing','ready','delivered','cancelled'] as $os): ?>
                    <option value="<?= $os ?>" <?= $ord['order_status'] === $os ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $os)) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><button type="submit" class="btn btn-primary btn-sm">Update</button></td>
            </form>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h3>Paystack Payment Transactions Ledger</h3>
  <div class="table-wrap" style="margin-top:12px;">
    <table class="swl-table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Order #</th>
          <th>Customer</th>
          <th>Mode</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><code><?= e($p['reference']) ?></code></td>
            <td><strong><?= e($p['order_number']) ?></strong></td>
            <td><?= e($p['customer_name']) ?></td>
            <td><?= e(strtoupper($p['mode'])) ?></td>
            <td><strong><?= swl_money($p['amount_ngn'], $p['currency']) ?></strong></td>
            <td><span class="badge badge-paid"><?= e(strtoupper($p['status'])) ?></span></td>
            <td><?= e(date('d M Y, g:i A', strtotime($p['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
