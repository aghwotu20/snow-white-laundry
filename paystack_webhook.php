<?php
/**
 * Official Paystack Server Webhook Receiver (`paystack_webhook.php`)
 * Validates `x-paystack-signature` using HMAC SHA-512 with the Secret Key configured in Admin Settings.
 */
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$secretKey = trim(swl_setting_raw('paystack_secret_key', ''));
$rawBody   = (string)file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if ($secretKey === '' || $sigHeader === '' || !hash_equals(hash_hmac('sha512', $rawBody, $secretKey), $sigHeader)) {
    http_response_code(401);
    exit('Invalid signature');
}

http_response_code(200);
$event = json_decode($rawBody, true);
if (($event['event'] ?? '') === 'charge.success') {
    $data = $event['data'] ?? [];
    $ref  = $data['reference'] ?? '';
    if ($ref !== '') {
        $pdo  = swl_db();
        $stmt = $pdo->prepare('SELECT id, order_id FROM payments WHERE reference = :ref LIMIT 1');
        $stmt->execute([':ref' => $ref]);
        if ($pay = $stmt->fetch()) {
            $pdo->prepare(
                'UPDATE payments SET status = "success", channel = :ch, gateway_response = :resp, paid_at = NOW(), raw_payload = :raw WHERE id = :id'
            )->execute([
                ':ch'   => $data['channel'] ?? 'card',
                ':resp' => $data['gateway_response'] ?? 'Approved via Webhook',
                ':raw'  => $rawBody,
                ':id'   => $pay['id'],
            ]);
            $pdo->prepare('UPDATE orders SET payment_status = "paid", order_status = IF(order_status = "pending", "confirmed", order_status) WHERE id = :oid')
                ->execute([':oid' => $pay['order_id']]);
        }
    }
}
echo 'OK';
