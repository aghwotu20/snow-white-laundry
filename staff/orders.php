<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('staff');
$pdo      = swl_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $orderId    = (int)($_POST['order_id'] ?? 0);
    $newStatus  = $_POST['order_status'] ?? 'processing';
    $staffNotes = trim($_POST['staff_notes'] ?? '');
    $markPaid   = !empty($_POST['mark_paid_pickup']);

    $allowed = ['pending', 'confirmed', 'picked_up', 'processing', 'ready', 'delivered'];
    if ($orderId > 0 && in_array($newStatus, $allowed, true)) {
        $sql = 'UPDATE orders SET order_status = :st, staff_notes = :notes, assigned_staff_id = COALESCE(assigned_staff_id, :sid)';
        if ($markPaid) {
            $sql .= ', payment_status = "paid"';
        }
        $sql .= ' WHERE id = :id';
        $pdo->prepare($sql)->execute([
            ':st'    => $newStatus,
            ':notes' => $staffNotes,
            ':sid'   => $authUser['id'],
            ':id'    => $orderId,
        ]);
        swl_log_activity((int)$authUser['id'], 'staff', 'order.updated', $authUser['full_name'] . ' updated order #' . $orderId . ' to ' . $newStatus);
        swl_flash('success', 'Order #' . $orderId . ' stage & garment care notes updated!');
    }
    swl_redirect('staff/orders.php');
}

$orders = $pdo->query(
    'SELECT o.*, u.full_name AS staff_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.assigned_staff_id
     ORDER BY o.id DESC'
)->fetchAll();

$dashTitle = 'Staff Laundry Orders & Garment Processing';
$activeTab = 'orders';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="card">
  <h3>Update Order Stages, Inspection Notes &amp; Doorstep Payments</h3>
  <p style="font-size:0.88rem;">Update garment care progress so customers can track their clothes live from their Visitor Dashboard.</p>

  <div style="display:grid;gap:16px;margin-top:18px;">
    <?php foreach ($orders as $ord): ?>
      <form method="post" style="padding:18px;border:1px solid var(--border-subtle);border-radius:14px;background:var(--off-white);">
        <?= swl_csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
          <div>
            <strong style="font-size:1.05rem;color:var(--deep-navy);"><?= e($ord['order_number']) ?></strong>
            &bull; <strong><?= e($ord['customer_name']) ?></strong> (<?= e($ord['customer_phone']) ?>)
            <div style="font-size:0.82rem;color:var(--text-body);margin-top:3px;">
              Address: <?= e($ord['pickup_address']) ?> &bull; Total: <strong><?= swl_money($ord['total_amount_ngn']) ?></strong>
              <?php if (!empty($ord['special_notes'])): ?>
                <br><em>Customer Note: <?= e($ord['special_notes']) ?></em>
              <?php endif; ?>
            </div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;">
            <span class="badge badge-<?= e($ord['payment_status']) ?>">PAY: <?= e(strtoupper($ord['payment_status'])) ?></span>
            <span class="badge badge-<?= e($ord['order_status']) ?>"><?= e(strtoupper(str_replace('_', ' ', $ord['order_status']))) ?></span>
            <a href="<?= e(swl_base_url('receipt.php?order=' . urlencode($ord['order_number']))) ?>" target="_blank" class="btn btn-outline btn-sm">Slip</a>
          </div>
        </div>

        <div class="form-row" style="grid-template-columns:0.8fr 1.4fr auto;align-items:end;">
          <div class="form-group" style="margin:0;">
            <label>Garment Stage</label>
            <select name="order_status" class="form-control">
              <?php foreach (['pending','confirmed','picked_up','processing','ready','delivered'] as $st): ?>
                <option value="<?= $st ?>" <?= $ord['order_status'] === $st ? 'selected' : '' ?>>
                  <?= ucwords(str_replace('_', ' ', $st)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin:0;">
            <label>Staff Inspection / Delivery Note</label>
            <input type="text" name="staff_notes" class="form-control" value="<?= e($ord['staff_notes']) ?>" placeholder="e.g. Checked pockets, steam pressed, ready on Rack B2...">
          </div>

          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <?php if ($ord['payment_status'] !== 'paid'): ?>
              <label style="display:flex;align-items:center;gap:6px;margin:0;font-size:0.8rem;cursor:pointer;">
                <input type="checkbox" name="mark_paid_pickup" value="1"> Paid Cash/POS
              </label>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-sm">Save Update</button>
          </div>
        </div>
      </form>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
