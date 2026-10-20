<?php
/**
 * Public Website Header & Navigation Bar
 */
$siteName   = swl_setting('site_name', 'Snow White Laundry');
$tagline    = swl_setting('site_tagline', 'Clean, Fresh & Reliable Garment Care');
$phone      = swl_setting('contact_phone', '07062300865');
$email      = swl_setting('contact_email', 'info@snowwhitelaundry.com.ng');
$address    = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street');
$whatsapp   = preg_replace('/\D+/', '', swl_setting('whatsapp_number', '2347062300865'));
$pageTitle  = isset($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName . ' — ' . $tagline;
$pageDesc   = $pageDesc ?? 'Snow White Laundry at ' . $address . ' — clean, modern laundry, dry cleaning, garment pressing, pickup & delivery services.';
$activeNav  = $activeNav ?? '';
$currUser   = swl_current_user();
$flashes    = swl_get_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>">
  <link rel="icon" href="<?= e(swl_favicon_url()) ?>">
  <link rel="apple-touch-icon" href="<?= e(swl_favicon_url()) ?>">
  <link rel="stylesheet" href="<?= e(swl_base_url('assets/css/style.css')) ?>?v=<?= filemtime(SWL_ROOT . '/assets/css/style.css') ?>">
</head>
<body>
<?php if (swl_is_maintenance() && $currUser && $currUser['role'] === 'admin'): ?>
  <div class="maint-admin-ribbon no-print">
    <?= swl_icon('alert', 16) ?>
    <span><strong>Maintenance Mode is Currently ON:</strong> Public visitors see the maintenance screen while you preview the live site as Admin.</span>
    <a href="<?= e(swl_base_url('admin/settings.php#maintenance')) ?>">Turn Off in Settings &rarr;</a>
  </div>
<?php endif; ?>

<div class="top-strip no-print">
  <div class="container top-strip-inner">
    <div class="top-strip-links">
      <span><?= swl_icon('map-pin', 14) ?> <?= e($address) ?></span>
      <a href="tel:<?= e($phone) ?>"><?= swl_icon('phone', 14) ?> <?= e($phone) ?></a>
      <a href="mailto:<?= e($email) ?>"><?= swl_icon('mail', 14) ?> <?= e($email) ?></a>
    </div>
    <div class="top-strip-links">
      <a href="<?= e(swl_base_url('track.php')) ?>"><?= swl_icon('truck', 14) ?> Track Laundry Order</a>
      <?php if ($whatsapp !== ''): ?>
        <a href="https://wa.me/<?= e($whatsapp) ?>?text=<?= urlencode('Hello ' . $siteName . ', I would like to book a laundry service.') ?>" target="_blank" rel="noopener">
          <?= swl_icon('whatsapp', 14) ?> WhatsApp Quick Chat
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<header class="site-header no-print">
  <div class="container nav-inner">
    <a href="<?= e(swl_base_url()) ?>" class="brand-logo" aria-label="<?= e($siteName) ?> Home">
      <img src="<?= e(swl_logo_url()) ?>" alt="<?= e($siteName) ?>">
    </a>

    <nav class="nav-links" id="swlNavMenu">
      <a href="<?= e(swl_base_url()) ?>" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Home</a>
      <a href="<?= e(swl_base_url('services.php')) ?>" class="<?= $activeNav === 'services' ? 'active' : '' ?>">Services</a>
      <a href="<?= e(swl_base_url('pricing.php')) ?>" class="<?= $activeNav === 'pricing' ? 'active' : '' ?>">Pricing &amp; Book</a>
      <a href="<?= e(swl_base_url('about.php')) ?>" class="<?= $activeNav === 'about' ? 'active' : '' ?>">About Us</a>
      <a href="<?= e(swl_base_url('faq.php')) ?>" class="<?= $activeNav === 'faq' ? 'active' : '' ?>">FAQs</a>
      <a href="<?= e(swl_base_url('contact.php')) ?>" class="<?= $activeNav === 'contact' ? 'active' : '' ?>">Contact</a>
    </nav>

    <div class="nav-actions">
      <?php if ($currUser): ?>
        <?php $avatar = swl_avatar_url($currUser); ?>
        <a href="<?= e(swl_base_url(swl_dashboard_path($currUser['role']))) ?>" class="btn btn-outline btn-sm" style="padding:5px 13px 5px 6px;">
          <?php if ($avatar): ?>
            <img src="<?= e($avatar) ?>" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
          <?php else: ?>
            <span style="width:28px;height:28px;border-radius:50%;background:var(--ice-blue-mid);display:grid;place-items:center;font-size:0.74rem;font-weight:800;"><?= e(swl_initials($currUser['full_name'])) ?></span>
          <?php endif; ?>
          <span><?= e(ucfirst($currUser['role'])) ?> Dashboard</span>
        </a>
      <?php else: ?>
        <a href="<?= e(swl_base_url('login.php')) ?>" class="btn btn-outline btn-sm">
          <?= swl_icon('user', 16) ?> Login
        </a>
        <a href="<?= e(swl_base_url('pricing.php#book-now')) ?>" class="btn btn-primary btn-sm">
          Book Laundry
        </a>
      <?php endif; ?>
      <button type="button" class="mobile-toggle" id="swlNavToggle" aria-label="Menu">&#9776;</button>
    </div>
  </div>
</header>

<?php if (!empty($flashes)): ?>
  <div class="container" style="padding-top:18px;">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
