<?php
/**
 * Branded Maintenance Mode View
 * Displayed when Maintenance Mode is enabled by the Administrator.
 * Includes direct phone/WhatsApp/email contact links + Administrator Sign-In button so Admin is never locked out!
 */
$siteName   = swl_setting('site_name', 'Snow White Laundry');
$maintTitle = swl_setting('maintenance_title', 'Scheduled Website & Service Upgrade in Progress');
$maintMsg   = swl_setting('maintenance_message', 'Snow White Laundry is briefly performing scheduled updates to serve you better.');
$phone      = swl_setting('contact_phone', '07062300865');
$email      = swl_setting('contact_email', 'info@snowwhitelaundry.com.ng');
$address    = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street');
$whatsapp   = preg_replace('/\D+/', '', swl_setting('whatsapp_number', '2347062300865'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($maintTitle) ?> | <?= e($siteName) ?></title>
  <link rel="icon" href="<?= e(swl_favicon_url()) ?>">
  <link rel="stylesheet" href="<?= e(swl_base_url('assets/css/style.css')) ?>">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:linear-gradient(155deg,#ebf5fb 0%,#ffffff 55%,#dceffb 100%);padding:24px;">
  <main class="card" style="max-width:640px;width:100%;padding:44px 36px;text-align:center;box-shadow:var(--shadow-md);">
    <div style="display:flex;justify-content:center;margin-bottom:24px;">
      <img src="<?= e(swl_logo_url()) ?>" alt="<?= e($siteName) ?>" style="height:56px;width:auto;">
    </div>

    <span class="eyebrow" style="background:#fef3c7;border-color:#fde68a;color:#92400e;">
      <?= swl_icon('settings', 14) ?> Maintenance Mode Active
    </span>

    <h1 style="font-size:1.85rem;margin-bottom:14px;"><?= e($maintTitle) ?></h1>
    <p style="font-size:1rem;max-width:520px;margin:0 auto 28px;"><?= nl2br(e($maintMsg)) ?></p>

    <div style="background:var(--off-white);border:1px solid var(--border-subtle);border-radius:14px;padding:20px;margin-bottom:26px;text-align:left;">
      <div style="font-size:0.8rem;font-weight:800;text-transform:uppercase;color:var(--text-subtle);margin-bottom:10px;">
        Direct Walk-In &amp; Express Pickup Desk (Open Now)
      </div>
      <div style="display:grid;gap:8px;font-size:0.92rem;color:var(--deep-navy);">
        <div><?= swl_icon('map-pin', 15) ?> <strong>Address:</strong> <?= e($address) ?></div>
        <div><?= swl_icon('phone', 15) ?> <strong>Call Desk:</strong> <a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a></div>
        <div><?= swl_icon('mail', 15) ?> <strong>Email:</strong> <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div>
      </div>
    </div>

    <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
      <a href="tel:<?= e($phone) ?>" class="btn btn-primary"><?= swl_icon('phone', 16) ?> Call <?= e($phone) ?></a>
      <?php if ($whatsapp !== ''): ?>
        <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp">
          <?= swl_icon('whatsapp', 16) ?> Chat on WhatsApp
        </a>
      <?php endif; ?>
      <a href="<?= e(swl_base_url('login.php?role=admin')) ?>" class="btn btn-outline">
        <?= swl_icon('shield', 16) ?> Admin Sign In
      </a>
    </div>
  </main>
</body>
</html>
