<?php
/**
 * Branded Maintenance Mode View (with Floating WhatsApp Quick-Chat Icon)
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
  <link rel="stylesheet" href="<?= e(swl_base_url('assets/css/style.css')) ?>?v=<?= filemtime(SWL_ROOT . '/assets/css/style.css') ?>">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at 85% 20%, rgba(255,90,31,0.16), transparent 45%), linear-gradient(155deg,#eef4ff 0%,#ffffff 55%,#dbeafe 100%);padding:24px;">
  <main class="card" style="max-width:640px;width:100%;padding:44px 36px;text-align:center;box-shadow:var(--shadow-md);">
    <div style="display:flex;justify-content:center;margin-bottom:24px;">
      <img src="<?= e(swl_logo_url()) ?>" alt="<?= e($siteName) ?>" style="height:56px;width:auto;">
    </div>

    <span class="eyebrow">
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

  <?php if ($whatsapp !== ''): ?>
    <a
      href="https://wa.me/<?= e($whatsapp) ?>?text=<?= urlencode('Hello ' . $siteName . ', I have an urgent laundry enquiry.') ?>"
      target="_blank"
      rel="noopener noreferrer"
      class="whatsapp-float-wrap no-print"
      aria-label="Chat with <?= e($siteName) ?> on WhatsApp"
    >
      <span class="whatsapp-float-label">
        <span class="whatsapp-live-dot"></span>
        <span>Chat on WhatsApp</span>
      </span>
      <span class="whatsapp-float-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="31" height="31" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
          <path d="M16.02 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.26.59 4.46 1.71 6.4L3.1 28.8l6.58-1.73a12.74 12.74 0 0 0 6.34 1.69h.01c7.05 0 12.79-5.74 12.79-12.8 0-3.42-1.33-6.64-3.75-9.05A12.72 12.72 0 0 0 16.02 3.2zm0 23.4h-.01a10.58 10.58 0 0 1-5.4-1.48l-.39-.23-3.9.1.02-.05.99-3.77-.25-.4a10.57 10.57 0 0 1-1.63-5.64c0-5.85 4.76-10.61 10.62-10.61 2.83 0 5.5 1.1 7.5 3.11a10.54 10.54 0 0 1 3.11 7.5c0 5.86-4.77 10.62-10.66 10.47zm5.82-7.95c-.32-.16-1.88-.93-2.18-1.03-.29-.11-.5-.16-.72.16-.21.32-.82 1.03-1.01 1.25-.18.21-.37.24-.69.08-.32-.16-1.35-.5-2.57-1.59-.95-.85-1.59-1.89-1.78-2.21-.18-.32-.02-.49.14-.65.14-.14.32-.37.48-.56.16-.18.21-.32.32-.53.1-.21.05-.4-.03-.56-.08-.16-.72-1.73-.98-2.37-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.66s1.15 3.08 1.31 3.29c.16.21 2.25 3.44 5.46 4.82.76.33 1.36.53 1.82.68.77.24 1.47.21 2.02.13.62-.09 1.88-.77 2.15-1.52.27-.74.27-1.38.19-1.52-.08-.13-.29-.21-.61-.37z"/>
        </svg>
      </span>
    </a>
  <?php endif; ?>
</body>
</html>
