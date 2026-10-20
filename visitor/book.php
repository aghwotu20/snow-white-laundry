<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/order_service.php';
$authUser = swl_require_auth('visitor');
$pdo      = swl_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $res = swl_create_laundry_order($pdo, $_POST, $authUser);
    if (!$res['ok']) {
        swl_flash('error', $res['error']);
        swl_redirect('visitor/book.php');
    }
    if ($res['payment_method'] === 'paystack') {
        swl_redirect('paystack_checkout.php?order=' . urlencode($res['order_number']));
    }
    swl_flash('success', 'Laundry order ' . $res['order_number'] . ' booked successfully!');
    swl_redirect('visitor/orders.php');
}

$services   = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();
$pickupFee  = (float)swl_setting('pickup_delivery_fee', '1500');
$expressPct = (float)swl_setting('express_surcharge_percent', '40');

$dashTitle = 'Book New Laundry Pickup or Drop-Off';
$activeTab = 'book';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<form method="post" id="swlOrderBuilder" data-pickup-fee="<?= e((string)$pickupFee) ?>" data-express-pct="<?= e((string)$expressPct) ?>">
  <?= swl_csrf_field() ?>

  <div class="grid-2" style="grid-template-columns:1.35fr 0.85fr;align-items:start;">
    <div class="card">
      <h3>Select Garments &amp; Schedule</h3>
      <div style="display:grid;gap:10px;margin:16px 0 24px;">
        <?php foreach ($services as $idx => $srv): ?>
          <?php $sid = (int)$srv['id']; ?>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 15px;border:1px solid var(--border-subtle);border-radius:10px;background:var(--off-white);flex-wrap:wrap;">
            <div>
              <strong style="color:var(--deep-navy);"><?= e($srv['name']) ?></strong>
              <span style="display:block;font-size:0.8rem;color:var(--text-subtle);">
                <?= swl_money($srv['price_ngn']) ?> &bull; <?= e($srv['unit_label']) ?>
              </span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
              <button type="button" class="btn btn-outline btn-sm js-qty-btn" data-target="vqty_<?= $sid ?>" data-delta="-1">&minus;</button>
              <input type="number" id="vqty_<?= $sid ?>" name="qty[<?= $sid ?>]" value="<?= $idx === 0 ? 1 : 0 ?>" min="0" max="500" data-price="<?= e((string)$srv['price_ngn']) ?>" class="form-control js-qty-input" style="width:68px;text-align:center;font-weight:800;">
              <button type="button" class="btn btn-outline btn-sm js-qty-btn" data-target="vqty_<?= $sid ?>" data-delta="1">+</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <input type="hidden" name="customer_name" value="<?= e($authUser['full_name']) ?>">
      <input type="hidden" name="customer_email" value="<?= e($authUser['email']) ?>">

      <div class="form-row">
        <div class="form-group">
          <label>Contact Phone *</label>
          <input type="text" name="customer_phone" class="form-control" value="<?= e($authUser['phone']) ?>" required>
        </div>
        <div class="form-group">
          <label>Preferred Date *</label>
          <input type="date" name="preferred_date" class="form-control" value="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" min="<?= e(date('Y-m-d')) ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Collection Option</label>
          <label style="display:flex;gap:8px;align-items:center;margin-bottom:6px;cursor:pointer;">
            <input type="radio" name="delivery_option" value="pickup_delivery" checked> Doorstep Pickup &amp; Delivery (+<?= swl_money($pickupFee) ?>)
          </label>
          <label style="display:flex;gap:8px;align-items:center;cursor:pointer;">
            <input type="radio" name="delivery_option" value="drop_off"> Branch Drop-Off (Free)
          </label>
        </div>
        <div class="form-group">
          <label>Turnaround Speed</label>
          <label style="display:flex;gap:8px;align-items:center;margin-bottom:6px;cursor:pointer;">
            <input type="radio" name="service_speed" value="standard" checked> Standard (24–48 hrs)
          </label>
          <label style="display:flex;gap:8px;align-items:center;cursor:pointer;">
            <input type="radio" name="service_speed" value="express"> Express (+<?= e((string)$expressPct) ?>%)
          </label>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Pickup Address *</label>
          <input type="text" name="pickup_address" class="form-control" value="<?= e($authUser['address']) ?>" required>
        </div>
        <div class="form-group">
          <label>Time Slot</label>
          <select name="preferred_time_slot" class="form-control">
            <option value="Morning (8:00 AM - 12:00 PM)">Morning (8:00 AM – 12:00 PM)</option>
            <option value="Afternoon (12:00 PM - 4:00 PM)">Afternoon (12:00 PM – 4:00 PM)</option>
            <option value="Evening (4:00 PM - 7:00 PM)">Evening (4:00 PM – 7:00 PM)</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Special Fabric / Starch Notes</label>
        <textarea name="special_notes" class="form-control" style="min-height:78px;" placeholder="e.g. Medium starch on native outfits, no starch on shirts..."><?= e($authUser['bio']) ?></textarea>
      </div>
    </div>

    <div class="card" style="background:var(--deep-navy);color:#fff;">
      <h3 style="color:#fff;">Payment &amp; Summary</h3>
      <div style="display:grid;gap:10px;font-size:0.9rem;margin:16px 0;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,0.14);">
        <div style="display:flex;justify-content:space-between;"><span>Items:</span><strong id="sumItems">1 item</strong></div>
        <div style="display:flex;justify-content:space-between;"><span>Subtotal:</span><strong id="sumSubtotal">₦0</strong></div>
        <div style="display:flex;justify-content:space-between;"><span>Pickup Fee:</span><strong id="sumDelivery">₦1,500</strong></div>
        <div style="display:flex;justify-content:space-between;"><span>Express Fee:</span><strong id="sumExpress">₦0</strong></div>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:20px;">
        <span>Total Payable</span>
        <strong id="sumTotal" style="font-size:1.85rem;color:#7dd3fc;">₦0</strong>
      </div>

      <label style="display:flex;gap:8px;align-items:center;padding:10px;background:rgba(255,255,255,0.1);border-radius:10px;color:#fff;margin-bottom:8px;cursor:pointer;">
        <input type="radio" name="payment_method" value="paystack" checked>
        <span>Pay Now via <strong>Paystack</strong></span>
      </label>
      <label style="display:flex;gap:8px;align-items:center;padding:10px;background:rgba(255,255,255,0.06);border-radius:10px;color:#fff;margin-bottom:18px;cursor:pointer;">
        <input type="radio" name="payment_method" value="pay_on_pickup">
        <span>Pay on Pickup / Drop-Off</span>
      </label>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Submit Laundry Order &rarr;</button>
    </div>
  </div>
</form>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
