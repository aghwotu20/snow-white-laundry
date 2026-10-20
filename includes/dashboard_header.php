<?php
/**
 * Shared Dashboard Shell Header (Visitor, Staff, and Admin Portals)
 */
$authUser  = $authUser ?? swl_require_auth();
$role      = $authUser['role'];
$siteName  = swl_setting('site_name', 'Snow White Laundry');
$dashTitle = $dashTitle ?? ucfirst($role) . ' Dashboard';
$activeTab = $activeTab ?? 'dashboard';
$avatarUrl = swl_avatar_url($authUser);
$flashes   = swl_get_flashes();

$navItems = match ($role) {
    'admin' => [
        ['key' => 'dashboard', 'label' => 'Admin Overview',      'icon' => 'grid',        'url' => 'admin/dashboard.php'],
        ['key' => 'orders',    'label' => 'Orders & Payments',   'icon' => 'basket',      'url' => 'admin/orders.php'],
        ['key' => 'enquiries', 'label' => 'Quotes & Enquiries',  'icon' => 'file-text',   'url' => 'admin/enquiries.php'],
        ['key' => 'staff',     'label' => 'Staff Management',    'icon' => 'users',       'url' => 'admin/staff.php'],
        ['key' => 'visitors',  'label' => 'Website Visitors',    'icon' => 'user',        'url' => 'admin/visitors.php'],
        ['key' => 'services',  'label' => 'Services & Pricing',  'icon' => 'shirt',       'url' => 'admin/services.php'],
        ['key' => 'settings',  'label' => 'Website Settings',    'icon' => 'settings',    'url' => 'admin/settings.php'],
        ['key' => 'profile',   'label' => 'My Profile & Photo',  'icon' => 'camera',      'url' => 'admin/profile.php'],
    ],
    'staff' => [
        ['key' => 'dashboard', 'label' => 'Staff Workspace',     'icon' => 'grid',        'url' => 'staff/dashboard.php'],
        ['key' => 'orders',    'label' => 'Laundry Orders Queue','icon' => 'basket',      'url' => 'staff/orders.php'],
        ['key' => 'enquiries', 'label' => 'Customer Enquiries',  'icon' => 'file-text',   'url' => 'staff/enquiries.php'],
        ['key' => 'profile',   'label' => 'My Profile & Photo',  'icon' => 'camera',      'url' => 'staff/profile.php'],
    ],
    default => [
        ['key' => 'dashboard', 'label' => 'My Dashboard',        'icon' => 'grid',        'url' => 'visitor/dashboard.php'],
        ['key' => 'book',      'label' => 'Book Laundry Service','icon' => 'sparkles',    'url' => 'visitor/book.php'],
        ['key' => 'orders',    'label' => 'My Orders & Paystack','icon' => 'credit-card', 'url' => 'visitor/orders.php'],
        ['key' => 'enquiries', 'label' => 'My Quotes & Messages','icon' => 'file-text',   'url' => 'visitor/enquiries.php'],
        ['key' => 'profile',   'label' => 'My Profile & Photo',  'icon' => 'camera',      'url' => 'visitor/profile.php'],
    ],
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($dashTitle) ?> | <?= e($siteName) ?></title>
  <link rel="icon" href="<?= e(swl_favicon_url()) ?>">
  <link rel="stylesheet" href="<?= e(swl_base_url('assets/css/style.css')) ?>">
</head>
<body class="dash-body">
<div class="dash-layout">
  <aside class="dash-sidebar no-print" id="swlDashSidebar">
    <a href="<?= e(swl_base_url()) ?>" class="dash-brand" title="Visit Live Website">
      <img src="<?= e(swl_logo_url()) ?>" alt="<?= e($siteName) ?>">
    </a>

    <div class="dash-user-box">
      <div class="passport-avatar">
        <?php if ($avatarUrl): ?>
          <img src="<?= e($avatarUrl) ?>" alt="<?= e($authUser['full_name']) ?>">
        <?php else: ?>
          <span><?= e(swl_initials($authUser['full_name'])) ?></span>
        <?php endif; ?>
      </div>
      <div style="min-width:0;">
        <div style="font-weight:800;font-size:0.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?= e($authUser['full_name']) ?>
        </div>
        <div style="font-size:0.75rem;color:#7dd3fc;text-transform:uppercase;letter-spacing:0.06em;font-weight:700;">
          <?= e($role === 'visitor' ? 'Customer Account' : ucfirst($role) . ' Portal') ?>
          <?php if (!empty($authUser['staff_code'])): ?>
            &bull; <?= e($authUser['staff_code']) ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <nav class="dash-nav">
      <?php foreach ($navItems as $item): ?>
        <a href="<?= e(swl_base_url($item['url'])) ?>" class="<?= $activeTab === $item['key'] ? 'active' : '' ?>">
          <?= swl_icon($item['icon'], 18) ?>
          <span><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div style="margin-top:24px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.12);display:grid;gap:8px;">
      <a href="<?= e(swl_base_url()) ?>" class="btn btn-outline btn-sm" style="background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.18);color:#fff;">
        &larr; Public Website
      </a>
      <a href="<?= e(swl_base_url('logout.php')) ?>" class="btn btn-danger btn-sm">
        <?= swl_icon('logout', 15) ?> Sign Out
      </a>
    </div>
  </aside>

  <div class="dash-main">
    <header class="dash-topbar no-print">
      <div style="display:flex;align-items:center;gap:12px;">
        <button type="button" class="mobile-toggle" id="swlDashToggle" aria-label="Toggle Sidebar">&#9776;</button>
        <div>
          <h1 style="font-size:1.3rem;margin:0;"><?= e($dashTitle) ?></h1>
          <div style="font-size:0.8rem;color:var(--text-subtle);">
            Welcome back, <?= e($authUser['full_name']) ?>
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <?php if ($role === 'admin'): ?>
          <form method="post" action="<?= e(swl_base_url('admin/settings.php')) ?>" style="margin:0;">
            <?= swl_csrf_field() ?>
            <input type="hidden" name="form_action" value="quick_toggle_maintenance">
            <?php if (swl_is_maintenance()): ?>
              <button type="submit" class="btn btn-warning btn-sm" title="Click to disable Maintenance Mode and put website live">
                <?= swl_icon('power', 15) ?> Maintenance ON (Click to Go Live)
              </button>
            <?php else: ?>
              <button type="submit" class="btn btn-outline btn-sm" title="Click to enable Maintenance Mode">
                <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block;"></span>
                Site Live &bull; Toggle Maintenance
              </button>
            <?php endif; ?>
          </form>
        <?php endif; ?>

        <a href="<?= e(swl_base_url($role . '/profile.php')) ?>" class="btn btn-outline btn-sm">
          <?= swl_icon('camera', 15) ?> Passport &amp; Profile
        </a>
      </div>
    </header>

    <main class="dash-content">
      <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
