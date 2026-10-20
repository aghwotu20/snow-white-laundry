<?php
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$pdo       = swl_db();
$code      = strtoupper(trim($_GET['order'] ?? ''));
$order     = null;
$items     = [];

if ($code !== '') {
    $stmt = $pdo->prepare(
        'SELECT o.*, u.full_name AS staff_name
         FROM orders o
         LEFT JOIN users u ON u.id = o.assigned_staff_id
         WHERE o.order_number = :code LIMIT 1'
    );
    $stmt->execute([':code' => $code]);
    $order = $stmt->fetch();
    if ($order) {
        $itStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :oid');
        $itStmt->execute([':oid' => $order['id']]);
        $items = $itStmt->fetchAll();
    }
}

$stages = [
    'pending'    => 'Order Received',
    'confirmed'  => 'Confirmed & Scheduled',
    'picked_up'  => 'Garments Collected',
    'processing' => 'Washing & Steam Pressing',
    'ready'      => 'Fresh & Ready for Delivery',
    'delivered'  => 'Completed & Delivered',
];

$pageTitle = 'Track Your Laundry Order';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?= swl_icon('truck', 14) ?> Real-Time Garment Status</span>
    <h1>Track Your Laundry Order</h1>
    <p style="max-width:640px;margin-bottom:0;">Enter your order reference number (for example <strong>SWL-2026-1001</strong>) to view live progress and payment status.</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:820px;">
    <form method="get" class="card" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:28px;">
      <input
        type="text"
        name="order"
        value="<?= e($code) ?>"
        placeholder="Enter Order Number e.g. SWL-2026-1001"
        class="form-control"
        style="flex:1;min-width:220px;"
        required
      >
      <button type="submit" class="btn btn-primary"><?= swl_icon('search', 17) ?> Track Order</button>
    </form>

    <?php if ($code !== '' && !$order): ?>
      <div class="alert alert-error">
        No order found matching reference <strong><?= e($code) ?></strong>. Please double-check your receipt code or try demo order <a href="?order=SWL-2026-1001"><strong>SWL-2026-1001</strong></a>.
      </div>
    <?php elseif ($order): ?>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;padding-bottom:18px;border-bottom:1px solid var(--border-subtle);">
          <div>
            <span class="eyebrow" style="margin-bottom:6px;"><?= e($order['order_number']) ?></span>
            <h3 style="margin:0;">Customer: <?= e($order['customer_name']) ?></h3>
            <span style="font-size:0.85rem;color:var(--text-subtle);">
              Scheduled Date: <?= e(date('d M Y', strtotime($order['preferred_date']))) ?> &bull; <?= e($order['preferred_time_slot']) ?>
            </span>
          </div>
          <div style="text-align:right;">
            <span class="badge badge-<?= e($order['payment_status']) ?>">Payment: <?= e(strtoupper($order['payment_status'])) ?></span>
            <span class="badge badge-<?= e($order['order_status']) ?>"><?= e(strtoupper(str_replace('_', ' ', $order['order_status']))) ?></span>
            <div style="font-size:1.35rem;font-weight:800;color:var(--deep-navy);margin-top:6px;"><?= swl_money($order['total_amount_ngn']) ?></div>
          </div>
        </div>

        <!-- Stage Stepper -->
        <?php
          $stageKeys  = array_keys($stages);
          $currentIdx = array_search($order['order_status'], $stageKeys, true);
        ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(115px,1fr));gap:10px;margin:24px 0;">
          <?php foreach ($stages as $sKey => $sLabel): ?>
            <?php
              $idx  = array_search($sKey, $stageKeys, true);
              $done = ($currentIdx !== false && $idx <= $currentIdx);
            ?>
            <div style="padding:12px;border-radius:10px;text-align:center;border:1.5px solid <?= $done ? 'var(--blue-accent)' : 'var(--border-subtle)' ?>;background:<?= $done ? 'var(--ice-blue)' : 'var(--off-white)' ?>;">
              <div style="font-weight:800;font-size:0.78rem;color:<?= $done ? 'var(--blue-hover)' : 'var(--text-subtle)' ?>;">
                <?= $done ? '&#10003; ' : '' ?><?= e($sLabel) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($order['staff_notes'])): ?>
          <div class="alert alert-info">
            <span><strong>Garment Care Update:</strong> <?= e($order['staff_notes']) ?></span>
          </div>
        <?php endif; ?>

        <div class="table-wrap">
          <table class="swl-table">
            <thead>
              <tr>
                <th>Service</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><strong><?= e($it['service_name']) ?></strong></td>
                  <td><?= (int)$it['quantity'] ?></td>
                  <td><?= swl_money($it['unit_price_ngn']) ?></td>
                  <td><strong><?= swl_money($it['line_total_ngn']) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
          <?php if ($order['payment_status'] !== 'paid'): ?>
            <a href="<?= e(swl_base_url('paystack_checkout.php?order=' . urlencode($order['order_number']))) ?>" class="btn btn-primary">
              <?= swl_icon('credit-card', 16) ?> Pay Online Now via Paystack
            </a>
          <?php endif; ?>
          <a href="<?= e(swl_base_url('receipt.php?order=' . urlencode($order['order_number']))) ?>" class="btn btn-outline">
            View Printable Invoice / Receipt
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
