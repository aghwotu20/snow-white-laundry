<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/order_service.php';
swl_enforce_public_maintenance();

$pdo      = swl_db();
$currUser = swl_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $res = swl_create_laundry_order($pdo, $_POST, $currUser);
    if (!$res['ok']) {
        swl_flash('error', $res['error']);
        swl_redirect('pricing.php#book-now');
    }

    $_SESSION['last_guest_order_id'] = $res['order_id'];
    if ($res['payment_method'] === 'paystack') {
        swl_redirect('paystack_checkout.php?order=' . urlencode($res['order_number']));
    }
    swl_flash('success', 'Order ' . $res['order_number'] . ' booked successfully! Our team will contact you shortly.');
    swl_redirect('receipt.php?order=' . urlencode($res['order_number']));
}

$services     = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$preselectId  = (int)($_GET['select'] ?? 0);
$pickupFee    = (float)swl_setting('pickup_delivery_fee', '1500');
$expressPct   = (float)swl_setting('express_surcharge_percent', '40');
$paystackLive = swl_setting_raw('paystack_enabled', '1') === '1';

$pageTitle = 'Transparent Pricing, Instant Calculator & Online Booking';
$activeNav = 'pricing';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?= swl_icon('credit-card', 14) ?> Transparent Pricing &amp; Online Booking</span>
    <h1>Simple Garment Pricing &amp; Instant Laundry Booking</h1>
    <p style="max-width:700px;font-size:1.05rem;margin-bottom:0;">
      Calculate your laundry cost in real time, schedule doorstep pickup or branch drop-off, and pay securely online with <strong>Paystack</strong> or on pickup. Need a bulk quote instead? <a href="<?= e(swl_base_url('contact.php?type=quote_request#enquiry-form')) ?>">Request a Custom Quote &rarr;</a>
    </p>
  </div>
</section>

<section class="section" id="book-now">
  <div class="container">
    <form method="post" id="swlOrderBuilder" data-pickup-fee="<?= e((string)$pickupFee) ?>" data-express-pct="<?= e((string)$expressPct) ?>">
      <?= swl_csrf_field() ?>

      <div class="grid-2" style="grid-template-columns:1.35fr 0.85fr;align-items:start;">
        <!-- STEP 1: SELECT GARMENTS & QUANTITIES -->
        <div class="card">
          <h3 style="margin-bottom:6px;">1. Choose Services &amp; Garment Quantities</h3>
          <p style="font-size:0.88rem;margin-bottom:20px;">Adjust quantities below to see your live total update immediately.</p>

          <div style="display:grid;gap:12px;">
            <?php foreach ($services as $idx => $srv): ?>
              <?php
                $sid = (int)$srv['id'];
                $defaultQty = ($preselectId === $sid) ? 1 : ($preselectId === 0 && $idx === 0 ? 1 : 0);
              ?>
              <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 16px;border:1px solid var(--border-subtle);border-radius:12px;background:var(--off-white);flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                  <strong style="display:block;color:var(--deep-navy);font-size:0.96rem;"><?= e($srv['name']) ?></strong>
                  <span style="font-size:0.8rem;color:var(--text-subtle);">
                    <?= swl_money($srv['price_ngn']) ?> &bull; <?= e($srv['unit_label']) ?> &bull; <?= e($srv['turnaround_hours']) ?>
                  </span>
                </div>

                <div style="display:flex;align-items:center;gap:6px;">
                  <button type="button" class="btn btn-outline btn-sm js-qty-btn" data-target="qty_<?= $sid ?>" data-delta="-1" style="padding:6px 11px;">&minus;</button>
                  <input
                    type="number"
                    id="qty_<?= $sid ?>"
                    name="qty[<?= $sid ?>]"
                    value="<?= $defaultQty ?>"
                    min="0"
                    max="500"
                    data-price="<?= e((string)$srv['price_ngn']) ?>"
                    class="form-control js-qty-input"
                    style="width:70px;text-align:center;padding:7px 8px;font-weight:800;"
                  >
                  <button type="button" class="btn btn-outline btn-sm js-qty-btn" data-target="qty_<?= $sid ?>" data-delta="1" style="padding:6px 11px;">+</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <hr style="border:none;border-top:1px solid var(--border-subtle);margin:26px 0;">

          <h3 style="margin-bottom:14px;">2. Customer &amp; Pickup Details</h3>

          <div class="form-row">
            <div class="form-group">
              <label>Your Full Name *</label>
              <input type="text" name="customer_name" class="form-control" value="<?= e($currUser['full_name'] ?? '') ?>" placeholder="e.g. Oluwaseun Balogun" required>
            </div>
            <div class="form-group">
              <label>Email Address (for Receipt &amp; Updates) *</label>
              <input type="email" name="customer_email" class="form-control" value="<?= e($currUser['email'] ?? '') ?>" placeholder="you@example.com" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Phone / WhatsApp Number *</label>
              <input type="text" name="customer_phone" class="form-control" value="<?= e($currUser['phone'] ?? '') ?>" placeholder="e.g. 08031234567" required>
            </div>
            <div class="form-group">
              <label>Preferred Pickup / Drop-Off Date *</label>
              <input type="date" name="preferred_date" class="form-control" value="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" min="<?= e(date('Y-m-d')) ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Collection Method</label>
              <label style="display:flex;align-items:center;gap:8px;font-weight:600;padding:10px 12px;border:1px solid var(--border-subtle);border-radius:10px;cursor:pointer;margin-bottom:8px;">
                <input type="radio" name="delivery_option" value="pickup_delivery" checked>
                <span>Doorstep Pickup &amp; Delivery (+<?= swl_money($pickupFee) ?>)</span>
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-weight:600;padding:10px 12px;border:1px solid var(--border-subtle);border-radius:10px;cursor:pointer;">
                <input type="radio" name="delivery_option" value="drop_off">
                <span>Branch Drop-Off (Free — <?= e(swl_setting('contact_address')) ?>)</span>
              </label>
            </div>

            <div class="form-group">
              <label>Turnaround Speed</label>
              <label style="display:flex;align-items:center;gap:8px;font-weight:600;padding:10px 12px;border:1px solid var(--border-subtle);border-radius:10px;cursor:pointer;margin-bottom:8px;">
                <input type="radio" name="service_speed" value="standard" checked>
                <span>Standard Care (24–48 hrs &bull; Regular Rate)</span>
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-weight:600;padding:10px 12px;border:1px solid var(--border-subtle);border-radius:10px;cursor:pointer;">
                <input type="radio" name="service_speed" value="express">
                <span>Express Priority Care (+<?= e((string)$expressPct) ?>% Same/Next Day)</span>
              </label>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Pickup / Delivery Address</label>
              <input type="text" name="pickup_address" class="form-control" value="<?= e($currUser['address'] ?? '') ?>" placeholder="House number, street & landmark">
            </div>
            <div class="form-group">
              <label>Preferred Time Window</label>
              <select name="preferred_time_slot" class="form-control">
                <option value="Morning (8:00 AM - 12:00 PM)">Morning (8:00 AM – 12:00 PM)</option>
                <option value="Afternoon (12:00 PM - 4:00 PM)">Afternoon (12:00 PM – 4:00 PM)</option>
                <option value="Evening (4:00 PM - 7:00 PM)">Evening (4:00 PM – 7:00 PM)</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Special Care Instructions (Starch preference, stains, hangers vs folded)</label>
            <textarea name="special_notes" class="form-control" style="min-height:82px;" placeholder="e.g. Light starch on white shirts, fold casual t-shirts..."></textarea>
          </div>
        </div>

        <!-- STEP 3: LIVE SUMMARY & PAYSTACK CHECKOUT BOX -->
        <aside class="card" style="position:sticky;top:98px;background:var(--deep-navy);color:#fff;border-color:var(--deep-navy);">
          <span class="eyebrow" style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.2);color:#7dd3fc;">
            Instant Cost Estimate
          </span>
          <h3 style="color:#fff;margin-bottom:18px;">Order Summary</h3>

          <div style="display:grid;gap:12px;font-size:0.93rem;padding-bottom:18px;border-bottom:1px solid rgba(255,255,255,0.14);">
            <div style="display:flex;justify-content:space-between;">
              <span style="opacity:0.8;">Selected Items:</span>
              <strong id="sumItems">1 item/bag</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
              <span style="opacity:0.8;">Garments Subtotal:</span>
              <strong id="sumSubtotal">₦0</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
              <span style="opacity:0.8;">Pickup &amp; Delivery:</span>
              <strong id="sumDelivery">₦1,500</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
              <span style="opacity:0.8;">Express Surcharge:</span>
              <strong id="sumExpress">₦0</strong>
            </div>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:baseline;margin:18px 0 22px;">
            <span style="font-size:1rem;font-weight:700;">Total Estimate</span>
            <span id="sumTotal" style="font-size:2rem;font-weight:800;color:#7dd3fc;">₦0</span>
          </div>

          <div style="margin-bottom:20px;">
            <label style="color:#fff;margin-bottom:8px;">Choose Payment Method</label>
            <?php if ($paystackLive): ?>
              <label style="display:flex;align-items:center;gap:9px;padding:11px 13px;background:rgba(255,255,255,0.1);border-radius:10px;color:#fff;cursor:pointer;margin-bottom:8px;">
                <input type="radio" name="payment_method" value="paystack" checked>
                <span><strong>Pay Online Now via Paystack</strong><br><small style="opacity:0.8;">Card, Bank Transfer, USSD &bull; Instant Receipt</small></span>
              </label>
            <?php endif; ?>
            <label style="display:flex;align-items:center;gap:9px;padding:11px 13px;background:rgba(255,255,255,0.07);border-radius:10px;color:#fff;cursor:pointer;">
              <input type="radio" name="payment_method" value="pay_on_pickup" <?= !$paystackLive ? 'checked' : '' ?>>
              <span><strong>Pay on Pickup / Drop-Off</strong><br><small style="opacity:0.8;">POS, Transfer, or Cash at Inspection</small></span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
            <?= swl_icon('credit-card', 18) ?> Confirm Booking &amp; Proceed
          </button>

          <p style="font-size:0.78rem;color:rgba(255,255,255,0.72);margin:14px 0 0;text-align:center;">
            Not sure how many items yet? <a href="<?= e(swl_base_url('contact.php?type=quote_request#enquiry-form')) ?>" style="color:#7dd3fc;text-decoration:underline;">Request a free quote instead</a>.
          </p>
        </aside>
      </div>
    </form>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
