<?php
/**
 * Paystack Payment Verification Callback (`paystack_callback.php`)
 * Verifies transaction status via `api.paystack.co/transaction/verify/{reference}` using Secret Key from Admin Settings,
 * OR processes sandbox demo checkout when live keys are not yet configured.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$pdo       = swl_db();
$reference = trim($_REQUEST['reference'] ?? $_REQUEST['trxref'] ?? '');

if ($reference === '') {
    swl_flash('error', 'Missing payment verification reference.');
    swl_redirect('index.php');
}

$payStmt = $pdo->prepare(
    'SELECT p.*, o.order_number, o.total_amount_ngn
     FROM payments p
     JOIN orders o ON o.id = p.order_id
     WHERE p.reference = :ref LIMIT 1'
);
$payStmt->execute([':ref' => $reference]);
$payment = $payStmt->fetch();

if (!$payment) {
    swl_flash('error', 'Payment transaction reference could not be located.');
    swl_redirect('index.php');
}

if ($payment['status'] === 'success') {
    swl_flash('success', 'Payment verified! Here is your official Snow White Laundry receipt.');
    swl_redirect('receipt.php?order=' . urlencode($payment['order_number']));
}

// Path A: Real Paystack API Verification
if (swl_paystack_has_live_keys() && empty($_POST['sandbox_simulate'])) {
    $verify = swl_paystack_request('GET', '/transaction/verify/' . rawurlencode($reference));
    $data   = $verify['data'] ?? [];
    if (!empty($verify['status']) && ($data['status'] ?? '') === 'success') {
        $channel = $data['channel'] ?? 'card';
        $gwResp  = $data['gateway_response'] ?? 'Approved';

        $pdo->prepare(
            'UPDATE payments
             SET status = "success", channel = :ch, gateway_response = :resp, paid_at = NOW(), raw_payload = :raw
             WHERE id = :id'
        )->execute([
            ':ch'   => $channel,
            ':resp' => $gwResp,
            ':raw'  => json_encode($data),
            ':id'   => $payment['id'],
        ]);

        $pdo->prepare(
            'UPDATE orders
             SET payment_status = "paid",
                 order_status = IF(order_status = "pending", "confirmed", order_status)
             WHERE id = :oid'
        )->execute([':oid' => $payment['order_id']]);

        swl_log_activity($payment['user_id'] ? (int)$payment['user_id'] : null, 'visitor', 'payment.paystack_verified', 'Verified Paystack payment ' . $reference . ' for order ' . $payment['order_number']);
        swl_flash('success', 'Paystack payment verified successfully! Your laundry order is confirmed.');
        swl_redirect('receipt.php?order=' . urlencode($payment['order_number']));
    }

    $pdo->prepare('UPDATE payments SET status = "failed", gateway_response = :resp WHERE id = :id')
        ->execute([':resp' => $verify['message'] ?? 'Verification failed', ':id' => $payment['id']]);
    swl_flash('error', 'Payment could not be verified: ' . ($verify['message'] ?? 'Declined or incomplete.'));
    swl_redirect('paystack_checkout.php?order=' . urlencode($payment['order_number']));
}

// Path B: Sandbox Demo Checkout (only permitted when real Paystack keys have not been configured yet)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['sandbox_simulate']) && !swl_paystack_has_live_keys()) {
    swl_verify_csrf();
    $channel = trim($_POST['channel'] ?? 'card');

    $pdo->prepare(
        'UPDATE payments
         SET status = "success", channel = :ch, gateway_response = "Sandbox Approved", paid_at = NOW()
         WHERE id = :id'
    )->execute([':ch' => $channel, ':id' => $payment['id']]);

    $pdo->prepare(
        'UPDATE orders
         SET payment_status = "paid",
             order_status = IF(order_status = "pending", "confirmed", order_status)
         WHERE id = :oid'
    )->execute([':oid' => $payment['order_id']]);

    swl_log_activity($payment['user_id'] ? (int)$payment['user_id'] : null, 'visitor', 'payment.sandbox_approved', 'Completed Sandbox test payment ' . $reference . ' for ' . $payment['order_number']);
    swl_flash('success', 'Test payment approved! Order ' . $payment['order_number'] . ' is now Paid & Confirmed.');
    swl_redirect('receipt.php?order=' . urlencode($payment['order_number']));
}

swl_redirect('paystack_checkout.php?order=' . urlencode($payment['order_number']));
