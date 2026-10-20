<?php
/**
 * Transactional Order Creation Helper (Used by Public Booking & Visitor Dashboard Booking)
 */
function swl_create_laundry_order(PDO $pdo, array $post, ?array $user = null): array
{
    $customerName  = trim($post['customer_name'] ?? ($user['full_name'] ?? ''));
    $customerEmail = strtolower(trim($post['customer_email'] ?? ($user['email'] ?? '')));
    $customerPhone = trim($post['customer_phone'] ?? ($user['phone'] ?? ''));
    $pickupAddress = trim($post['pickup_address'] ?? ($user['address'] ?? ''));
    $deliveryOpt   = ($post['delivery_option'] ?? 'pickup_delivery') === 'drop_off' ? 'drop_off' : 'pickup_delivery';
    $serviceSpeed  = ($post['service_speed'] ?? 'standard') === 'express' ? 'express' : 'standard';
    $preferredDate = trim($post['preferred_date'] ?? date('Y-m-d', strtotime('+1 day')));
    $timeSlot      = trim($post['preferred_time_slot'] ?? 'Morning (8:00 AM - 12:00 PM)');
    $specialNotes  = trim($post['special_notes'] ?? '');
    $paymentMethod = ($post['payment_method'] ?? 'paystack') === 'pay_on_pickup' ? 'pay_on_pickup' : 'paystack';

    if ($customerName === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || $customerPhone === '') {
        return ['ok' => false, 'error' => 'Please enter your full name, valid email address, and phone number.'];
    }

    if ($deliveryOpt === 'drop_off' && $pickupAddress === '') {
        $pickupAddress = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street') . ' (Branch Drop-off)';
    } elseif ($pickupAddress === '') {
        return ['ok' => false, 'error' => 'Please enter your pickup & delivery street address.'];
    }

    $quantities = $post['qty'] ?? [];
    if (!is_array($quantities)) {
        return ['ok' => false, 'error' => 'Please choose at least one laundry service item.'];
    }

    $servicesStmt = $pdo->query('SELECT * FROM services WHERE is_active = 1');
    $catalog = [];
    foreach ($servicesStmt->fetchAll() as $srv) {
        $catalog[(int)$srv['id']] = $srv;
    }

    $selectedItems = [];
    $subtotal = 0.0;
    foreach ($quantities as $srvId => $rawQty) {
        $sid = (int)$srvId;
        $qty = max(0, min(500, (int)$rawQty));
        if ($qty > 0 && isset($catalog[$sid])) {
            $srv = $catalog[$sid];
            $line = round((float)$srv['price_ngn'] * $qty, 2);
            $subtotal += $line;
            $selectedItems[] = [
                'service_id'   => $sid,
                'service_name' => $srv['name'],
                'unit_label'   => $srv['unit_label'],
                'unit_price'   => (float)$srv['price_ngn'],
                'quantity'     => $qty,
                'line_total'   => $line,
            ];
        }
    }

    if (empty($selectedItems)) {
        return ['ok' => false, 'error' => 'Please select a quantity of at least 1 for one or more laundry services.'];
    }

    $pickupFeeSetting = (float)swl_setting('pickup_delivery_fee', '1500');
    $expressPct       = (float)swl_setting('express_surcharge_percent', '40');

    $deliveryFee = $deliveryOpt === 'pickup_delivery' ? $pickupFeeSetting : 0.0;
    $expressFee  = $serviceSpeed === 'express' ? round(($subtotal * $expressPct) / 100, 2) : 0.0;
    $totalAmount = round($subtotal + $deliveryFee + $expressFee, 2);

    $pdo->beginTransaction();
    try {
        $tempOrderNum = 'SWL-' . date('Y') . '-TMP' . random_int(1000, 9999);
        $ins = $pdo->prepare(
            'INSERT INTO orders (
                order_number, user_id, customer_name, customer_email, customer_phone,
                pickup_address, delivery_option, service_speed, preferred_date, preferred_time_slot,
                special_notes, subtotal_ngn, delivery_fee_ngn, express_fee_ngn, total_amount_ngn,
                payment_method, payment_status, order_status
             ) VALUES (
                :onum, :uid, :cname, :cemail, :cphone,
                :addr, :dopt, :speed, :pdate, :slot,
                :notes, :sub, :dfee, :efee, :tot,
                :pmeth, "unpaid", "pending"
             )'
        );
        $ins->execute([
            ':onum'   => $tempOrderNum,
            ':uid'    => $user ? (int)$user['id'] : null,
            ':cname'  => $customerName,
            ':cemail' => $customerEmail,
            ':cphone' => $customerPhone,
            ':addr'   => $pickupAddress,
            ':dopt'   => $deliveryOpt,
            ':speed'  => $serviceSpeed,
            ':pdate'  => $preferredDate ?: date('Y-m-d'),
            ':slot'   => $timeSlot,
            ':notes'  => $specialNotes,
            ':sub'    => $subtotal,
            ':dfee'   => $deliveryFee,
            ':efee'   => $expressFee,
            ':tot'    => $totalAmount,
            ':pmeth'  => $paymentMethod,
        ]);

        $orderId = (int)$pdo->lastInsertId();
        $finalOrderNumber = sprintf('SWL-%s-%04d', date('Y'), 1000 + $orderId);
        $pdo->prepare('UPDATE orders SET order_number = :num WHERE id = :id')
            ->execute([':num' => $finalOrderNumber, ':id' => $orderId]);

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, service_id, service_name, unit_label, unit_price_ngn, quantity, line_total_ngn)
             VALUES (:oid, :sid, :sname, :ulab, :uprice, :qty, :ltot)'
        );
        foreach ($selectedItems as $it) {
            $itemStmt->execute([
                ':oid'    => $orderId,
                ':sid'    => $it['service_id'],
                ':sname'  => $it['service_name'],
                ':ulab'   => $it['unit_label'],
                ':uprice' => $it['unit_price'],
                ':qty'    => $it['quantity'],
                ':ltot'   => $it['line_total'],
            ]);
        }

        $pdo->commit();
        swl_log_activity(
            $user ? (int)$user['id'] : null,
            $user['role'] ?? 'visitor',
            'order.created',
            'Laundry order ' . $finalOrderNumber . ' created (' . swl_money($totalAmount) . ').'
        );

        return [
            'ok'             => true,
            'order_id'       => $orderId,
            'order_number'   => $finalOrderNumber,
            'payment_method' => $paymentMethod,
            'total_amount'   => $totalAmount,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'Could not save order right now. Please try again.'];
    }
}
