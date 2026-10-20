<?php
/**
 * Paystack Payment Checkout Page
 * - Integrates genuinely with Paystack Inline V1 + Server-side REST API (`api.paystack.co`) whenever
 *   Paystack Public & Secret Keys are configured under Admin -> Website Settings.
 * - Includes an interactive Sandbox Payment Simulator when keys have not yet been pasted in Admin Settings,
 *   allowing full end-to-end checkout testing out of the box!
 */
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$pdo       = swl_db();
$currUser  = swl_current_user();
$orderCode = strtoupper(trim($_GET['order'] ?? ''));

$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = :num LIMIT 1');
$stmt->execute([':num' => $orderCode]);
$order = $stmt->fetch();

if (!$order) {
    swl_flash('error', 'Order not found.');
    swl_redirect('pricing.php');
}

if ($order['payment_status'] === 'paid') {
    swl_flash('info', 'This order has already been paid. Viewing official receipt.');
    swl_redirect('receipt.php?order=' . urlencode($order['order_number']));
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :oid');
$itemsStmt->execute([':oid' => $order['id']]);
$items = $itemsStmt->fetchAll();

$paystackEnabled = swl_setting_raw('paystack_enabled', '1') === '1';
$paystackMode    = swl_setting('paystack_mode', 'test');
$publicKey       = trim(swl_setting_raw('paystack_public_key', ''));
$currency        = strtoupper(swl_setting('paystack_currency', 'NGN'));
$hasRealKeys     = swl_paystack_has_live_keys();

$reference = 'SWL-PSK-' . $order['id'] . '-' . strtoupper(bin2hex(random_bytes(4)));

// Record initialized payment record in MySQL `payments` table
$insPay = $pdo->prepare(
    'INSERT INTO payments (order_id, user_id, reference, gateway, mode, amount_ngn, currency, status)
     VALUES (:oid, :uid, :ref, "paystack", :mode, :amt, :curr, "initialized")'
);
$insPay->execute([
    ':oid'  => $order['id'],
    ':uid'  => $currUser ? (int)$currUser['id'] : ($order['user_id'] ? (int)$order['user_id'] : null),
    ':ref'  => $reference,
    ':mode' => $hasRealKeys ? $paystackMode : 'sandbox_demo',
    ':amt'  => $order['total_amount_ngn'],
    ':curr' => $currency,
]);

$callbackUrl = swl_full_url('paystack_callback.php?reference=' . urlencode($reference));
$amountKobo  = (int)round(((float)$order['total_amount_ngn']) * 100);

// Optional server-side Paystack Hosted Checkout URL fallback if real keys are set
$hostedAuthUrl = null;
if ($hasRealKeys && isset($_POST['use_hosted_redirect'])) {
    swl_verify_csrf();
    $init = swl_paystack_request('POST', '/transaction/initialize', [
        'email'        => $order['customer_email'],
        'amount'       => (string)$amountKobo,
        'currency'     => $currency,
        'reference'    => $reference,
        'callback_url' => $callbackUrl,
        'metadata'     => [
            'order_number'  => $order['order_number'],
            'customer_name' => $order['customer_name'],
        ],
    ]);
    if (!empty($init['status']) && !empty($init['data']['authorization_url'])) {
        swl_redirect($init['data']['authorization_url']);
    }
    swl_flash('error', $init['message'] ?? 'Unable to initialize Paystack checkout. Check your Paystack credentials in Admin Settings.');
    swl_redirect('paystack_checkout.php?order=' . urlencode($order['order_number']));
}

$pageTitle = 'Paystack Checkout — ' . $order['order_number'];
require __DIR__ . '/includes/header.php';
?>

<section class="section section-soft">
  <div class="container" style="max-width:860px;">
    <div class="grid-2" style="grid-template-columns:1.1fr 0.9fr;align-items:start;">
      <!-- ORDER BREAKDOWN -->
      <div class="card">
        <span class="eyebrow"><?= e($order['order_number']) ?></span>
        <h3>Laundry Order Checkout</h3>
        <p style="font-size:0.88rem;">Review your itemized laundry order below before completing secure online payment.</p>

        <div class="table-wrap" style="margin:16px 0;">
          <table class="swl-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= e($it['service_name']) ?></td>
                  <td><?= (int)$it['quantity'] ?></td>
                  <td><strong><?= swl_money($it['line_total_ngn'], $currency) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="font-size:0.9rem;display:grid;gap:7px;padding-top:12px;border-top:1px solid var(--border-subtle);">
          <div style="display:flex;justify-content:space-between;">
            <span>Subtotal:</span><strong><?= swl_money($order['subtotal_ngn'], $currency) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span>Pickup &amp; Delivery:</span><strong><?= swl_money($order['delivery_fee_ngn'], $currency) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span>Express Surcharge:</span><strong><?= swl_money($order['express_fee_ngn'], $currency) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:1.2rem;padding-top:8px;border-top:1px solid var(--border-subtle);color:var(--deep-navy);">
            <strong>Amount Payable:</strong><strong><?= swl_money($order['total_amount_ngn'], $currency) ?></strong>
          </div>
        </div>
      </div>

      <!-- PAYSTACK PAYMENT BOX -->
      <div class="card" style="border:2px solid var(--blue-accent);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
          <span class="badge badge-confirmed">PAYSTACK SECURE GATEWAY</span>
          <span style="font-size:0.76rem;font-weight:700;color:var(--text-subtle);text-transform:uppercase;">
            Mode: <?= e($hasRealKeys ? strtoupper($paystackMode) : 'SANDBOX TEST') ?>
          </span>
        </div>

        <h3>Pay <?= swl_money($order['total_amount_ngn'], $currency) ?></h3>
        <p style="font-size:0.86rem;">
          Customer: <strong><?= e($order['customer_name']) ?></strong><br>
          Email: <?= e($order['customer_email']) ?><br>
          Ref: <code><?= e($reference) ?></code>
        </p>

        <?php if (!$paystackEnabled): ?>
          <div class="alert alert-warning">Online Paystack checkout is currently paused by the administrator.</div>
        <?php elseif ($hasRealKeys): ?>
          <!-- REAL LIVE / TEST PAYSTACK INLINE POPUP + SERVER REDIRECT BACKUP -->
          <script src="https://js.paystack.co/v1/inline.js"></script>
          <button type="button" id="paystackPopupBtn" class="btn btn-primary btn-lg" style="width:100%;margin-bottom:12px;">
            <?= swl_icon('credit-card', 18) ?> Pay Now with Paystack
          </button>

          <form method="post">
            <?= swl_csrf_field() ?>
            <input type="hidden" name="use_hosted_redirect" value="1">
            <button type="submit" class="btn btn-outline btn-sm" style="width:100%;">
              Or Open Hosted Paystack Checkout Page &rarr;
            </button>
          </form>

          <script>
          document.getElementById('paystackPopupBtn').addEventListener('click', function () {
            if (typeof PaystackPop === 'undefined') {
              document.querySelector('form button[type="submit"]').click();
              return;
            }
            const handler = PaystackPop.setup({
              key: <?= json_encode($publicKey) ?>,
              email: <?= json_encode($order['customer_email']) ?>,
              amount: <?= $amountKobo ?>,
              currency: <?= json_encode($currency) ?>,
              ref: <?= json_encode($reference) ?>,
              metadata: {
                custom_fields: [
                  { display_name: "Order Number", variable_name: "order_number", value: <?= json_encode($order['order_number']) ?> }
                ]
              },
              callback: function (response) {
                window.location.href = <?= json_encode(swl_base_url('paystack_callback.php')) ?> + '?reference=' + encodeURIComponent(response.reference);
              }
            });
            handler.openIframe();
          });
          </script>
        <?php else: ?>
          <!-- SANDBOX / DEMO SIMULATOR WHEN ADMIN HAS NOT ENTERED LIVE PAYSTACK KEYS YET -->
          <div class="alert alert-info" style="font-size:0.82rem;">
            <span><strong>Sandbox Test Mode:</strong> Add your real <code>pk_live_...</code> &amp; <code>sk_live_...</code> keys anytime in <strong>Admin &rarr; Website Settings</strong>. Click below to test instant simulated Paystack payment &amp; receipt generation now!</span>
          </div>

          <form method="post" action="<?= e(swl_base_url('paystack_callback.php')) ?>">
            <?= swl_csrf_field() ?>
            <input type="hidden" name="reference" value="<?= e($reference) ?>">
            <input type="hidden" name="sandbox_simulate" value="1">

            <div class="form-group">
              <label>Simulated Payment Channel</label>
              <select name="channel" class="form-control">
                <option value="card">Debit Card (Mastercard / Visa / Verve)</option>
                <option value="bank_transfer">Pay with Bank Transfer</option>
                <option value="ussd">Pay with USSD (*737# / *901#)</option>
              </select>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
              <?= swl_icon('check', 18) ?> Complete Test Payment (<?= swl_money($order['total_amount_ngn'], $currency) ?>)
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
