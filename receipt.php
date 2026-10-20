<?php
/**
 * Printable Official Laundry Invoice & Payment Receipt (`receipt.php`)
 */
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$pdo       = swl_db();
$orderCode = strtoupper(trim($_GET['order'] ?? ''));

$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = :num LIMIT 1');
$stmt->execute([':num' => $orderCode]);
$order = $stmt->fetch();

if (!$order) {
    swl_flash('error', 'Receipt not found.');
    swl_redirect('index.php');
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :oid');
$itemsStmt->execute([':oid' => $order['id']]);
$items = $itemsStmt->fetchAll();

$payStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :oid AND status = "success" ORDER BY id DESC LIMIT 1');
$payStmt->execute([':oid' => $order['id']]);
$paidTx = $payStmt->fetch();

$siteName = swl_setting('site_name', 'Snow White Laundry');
$address  = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street');
$phone    = swl_setting('contact_phone', '07062300865');
$email    = swl_setting('contact_email', 'info@snowwhitelaundry.com.ng');

$pageTitle = 'Official Receipt ' . $order['order_number'];
require __DIR__ . '/includes/header.php';
?>

<section class="section section-soft">
  <div class="container" style="max-width:780px;">
    <div class="card" style="padding:40px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:20px;padding-bottom:24px;border-bottom:2px solid var(--ice-blue-mid);">
        <div>
          <img src="<?= e(swl_logo_url()) ?>" alt="<?= e($siteName) ?>" style="height:48px;width:auto;margin-bottom:10px;">
          <div style="font-size:0.84rem;color:var(--text-body);">
            <?= e($address) ?><br>
            Tel: <?= e($phone) ?> &bull; <?= e($email) ?>
          </div>
        </div>
        <div style="text-align:right;">
          <span class="badge badge-<?= e($order['payment_status']) ?>" style="font-size:0.85rem;padding:6px 14px;">
            <?= $order['payment_status'] === 'paid' ? 'PAID OFFICIAL RECEIPT' : 'ORDER INVOICE (' . strtoupper($order['payment_status']) . ')' ?>
          </span>
          <h2 style="margin:8px 0 2px;font-size:1.35rem;"><?= e($order['order_number']) ?></h2>
          <div style="font-size:0.82rem;color:var(--text-subtle);">
            Issued: <?= e(date('d M Y, g:i A', strtotime($order['created_at']))) ?>
          </div>
        </div>
      </div>

      <div class="grid-2" style="margin:24px 0;gap:18px;font-size:0.9rem;">
        <div style="background:var(--off-white);padding:16px;border-radius:12px;">
          <strong style="display:block;color:var(--deep-navy);margin-bottom:4px;">Billed To</strong>
          <?= e($order['customer_name']) ?><br>
          <?= e($order['customer_phone']) ?><br>
          <?= e($order['customer_email']) ?>
        </div>
        <div style="background:var(--off-white);padding:16px;border-radius:12px;">
          <strong style="display:block;color:var(--deep-navy);margin-bottom:4px;">Schedule &amp; Handling</strong>
          <?= $order['delivery_option'] === 'pickup_delivery' ? 'Doorstep Pickup & Delivery' : 'Branch Drop-Off' ?>
          (<?= ucfirst($order['service_speed']) ?>)<br>
          Date: <?= e(date('d M Y', strtotime($order['preferred_date']))) ?><br>
          Address: <?= e($order['pickup_address']) ?>
        </div>
      </div>

      <div class="table-wrap">
        <table class="swl-table">
          <thead>
            <tr>
              <th>Description</th>
              <th>Unit</th>
              <th>Qty</th>
              <th>Rate</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><strong><?= e($it['service_name']) ?></strong></td>
                <td><?= e($it['unit_label']) ?></td>
                <td><?= (int)$it['quantity'] ?></td>
                <td><?= swl_money($it['unit_price_ngn']) ?></td>
                <td><strong><?= swl_money($it['line_total_ngn']) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="max-width:340px;margin-left:auto;margin-top:20px;display:grid;gap:8px;font-size:0.92rem;">
        <div style="display:flex;justify-content:space-between;">
          <span>Subtotal:</span><strong><?= swl_money($order['subtotal_ngn']) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span>Pickup &amp; Delivery:</span><strong><?= swl_money($order['delivery_fee_ngn']) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span>Express Surcharge:</span><strong><?= swl_money($order['express_fee_ngn']) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:1.25rem;padding-top:10px;border-top:2px solid var(--deep-navy);color:var(--deep-navy);">
          <strong>Total Amount:</strong><strong><?= swl_money($order['total_amount_ngn']) ?></strong>
        </div>
      </div>

      <?php if ($paidTx): ?>
        <div style="margin-top:22px;padding:12px 16px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;font-size:0.84rem;color:#065f46;">
          <strong>Paystack Reference:</strong> <?= e($paidTx['reference']) ?> &bull;
          <strong>Channel:</strong> <?= e(strtoupper($paidTx['channel'] ?? 'CARD')) ?> &bull;
          <strong>Paid At:</strong> <?= e(date('d M Y, g:i A', strtotime($paidTx['paid_at']))) ?>
        </div>
      <?php endif; ?>

      <div class="no-print" style="margin-top:28px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <button type="button" onclick="window.print()" class="btn btn-navy">
          Print / Save Receipt as PDF
        </button>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <?php if ($order['payment_status'] !== 'paid'): ?>
            <a href="<?= e(swl_base_url('paystack_checkout.php?order=' . urlencode($order['order_number']))) ?>" class="btn btn-primary">
              Pay Now with Paystack
            </a>
          <?php endif; ?>
          <a href="<?= e(swl_base_url('track.php?order=' . urlencode($order['order_number']))) ?>" class="btn btn-outline">
            Track Live Order Status &rarr;
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
