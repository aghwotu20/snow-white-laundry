<?php
/**
 * Admin Website Settings (`admin/settings.php`)
 * 1. Website Logo & Favicon Upload (with live preview + reset to default)
 * 2. Business Details (Address, Click-to-Call Phone, Email, WhatsApp, Hours, Pickup Fee)
 * 3. Paystack Integration & Credentials (Enable Toggle, Test/Live Mode, Public Key, Secret Key, Currency, Callback & Webhook URLs)
 * 4. Maintenance Mode Toggle Button & Custom Visitor Notice
 */
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $action = $_POST['form_action'] ?? '';

    // 1. One-Click Quick Maintenance Toggle (from Topbar, Overview, or Settings Card)
    if ($action === 'quick_toggle_maintenance') {
        $nextState = swl_is_maintenance() ? '0' : '1';
        swl_save_setting('maintenance_mode', $nextState, 'maintenance');
        swl_log_activity(
            (int)$authUser['id'],
            'admin',
            'settings.maintenance_toggled',
            'Administrator switched Maintenance Mode ' . ($nextState === '1' ? 'ON' : 'OFF')
        );
        swl_flash(
            $nextState === '1' ? 'warning' : 'success',
            $nextState === '1'
                ? 'Maintenance Mode is now ON. Public visitors will see the maintenance screen while you stay signed in as Admin.'
                : 'Maintenance Mode is now OFF. Snow White Laundry is live to all public visitors!'
        );
        $back = $_SERVER['HTTP_REFERER'] ?? swl_base_url('admin/settings.php#maintenance');
        header('Location: ' . $back);
        exit;
    }

    // 2. Upload Website Logo and/or Favicon + Save Brand & Contact Settings
    if ($action === 'save_branding') {
        if (!empty($_POST['reset_default_logo'])) {
            swl_save_setting('site_logo', 'uploads/branding/default-logo.svg', 'branding');
            swl_flash('info', 'Website Logo restored to default Snow White Laundry emblem.');
        } elseif (!empty($_FILES['site_logo_file']['name'] ?? '')) {
            $upLogo = swl_upload_image($_FILES['site_logo_file'], 'branding', 'site-logo', 5242880, true);
            if (!$upLogo['ok']) {
                swl_flash('error', 'Logo Upload: ' . $upLogo['error']);
                swl_redirect('admin/settings.php#branding');
            }
            swl_save_setting('site_logo', $upLogo['path'], 'branding');
            swl_log_activity((int)$authUser['id'], 'admin', 'settings.logo_uploaded', 'Uploaded a new Website Logo.');
        }

        if (!empty($_POST['reset_default_favicon'])) {
            swl_save_setting('site_favicon', 'uploads/branding/default-favicon.svg', 'branding');
            swl_flash('info', 'Website Favicon restored to default SW icon.');
        } elseif (!empty($_FILES['site_favicon_file']['name'] ?? '')) {
            $upFav = swl_upload_image($_FILES['site_favicon_file'], 'branding', 'site-favicon', 2097152, true);
            if (!$upFav['ok']) {
                swl_flash('error', 'Favicon Upload: ' . $upFav['error']);
                swl_redirect('admin/settings.php#branding');
            }
            swl_save_setting('site_favicon', $upFav['path'], 'branding');
            swl_log_activity((int)$authUser['id'], 'admin', 'settings.favicon_uploaded', 'Uploaded a new Website Favicon.');
        }

        swl_save_setting('site_name', trim($_POST['site_name'] ?? 'Snow White Laundry'), 'branding');
        swl_save_setting('site_tagline', trim($_POST['site_tagline'] ?? ''), 'branding');
        swl_save_setting('footer_about', trim($_POST['footer_about'] ?? ''), 'branding');
        swl_save_setting('contact_address', trim($_POST['contact_address'] ?? ''), 'contact');
        swl_save_setting('contact_phone', trim($_POST['contact_phone'] ?? ''), 'contact');
        swl_save_setting('contact_email', trim($_POST['contact_email'] ?? ''), 'contact');
        swl_save_setting('whatsapp_number', trim($_POST['whatsapp_number'] ?? ''), 'contact');
        swl_save_setting('business_hours', trim($_POST['business_hours'] ?? ''), 'contact');
        swl_save_setting('pickup_delivery_fee', (string)max(0, (float)($_POST['pickup_delivery_fee'] ?? 1500)), 'pricing');
        swl_save_setting('express_surcharge_percent', (string)max(0, (float)($_POST['express_surcharge_percent'] ?? 40)), 'pricing');

        swl_log_activity((int)$authUser['id'], 'admin', 'settings.branding_saved', 'Updated website branding, logo/favicon, and business contact details.');
        swl_flash('success', 'Brand identity, Logo/Favicon, and business settings saved successfully!');
        swl_redirect('admin/settings.php#branding');
    }

    // 3. Save Paystack Payment Gateway Credentials
    if ($action === 'save_paystack') {
        $enabled  = !empty($_POST['paystack_enabled']) ? '1' : '0';
        $mode     = ($_POST['paystack_mode'] ?? 'test') === 'live' ? 'live' : 'test';
        $pubKey   = trim($_POST['paystack_public_key'] ?? '');
        $secKey   = trim($_POST['paystack_secret_key'] ?? '');
        $currency = strtoupper(trim($_POST['paystack_currency'] ?? 'NGN'));

        swl_save_setting('paystack_enabled', $enabled, 'paystack');
        swl_save_setting('paystack_mode', $mode, 'paystack');
        swl_save_setting('paystack_public_key', $pubKey, 'paystack');
        swl_save_setting('paystack_secret_key', $secKey, 'paystack');
        swl_save_setting('paystack_currency', $currency ?: 'NGN', 'paystack');

        swl_log_activity((int)$authUser['id'], 'admin', 'settings.paystack_saved', 'Updated Paystack integration credentials (' . strtoupper($mode) . ' mode).');
        swl_flash('success', 'Paystack integration settings & API credentials saved!');
        swl_redirect('admin/settings.php#paystack');
    }

    // 4. Save Full Maintenance Mode Configuration
    if ($action === 'save_maintenance') {
        $maintOn    = !empty($_POST['maintenance_mode']) ? '1' : '0';
        $maintTitle = trim($_POST['maintenance_title'] ?? 'Scheduled Website & Service Upgrade in Progress');
        $maintMsg   = trim($_POST['maintenance_message'] ?? '');

        swl_save_setting('maintenance_mode', $maintOn, 'maintenance');
        swl_save_setting('maintenance_title', $maintTitle, 'maintenance');
        swl_save_setting('maintenance_message', $maintMsg, 'maintenance');

        swl_log_activity((int)$authUser['id'], 'admin', 'settings.maintenance_saved', 'Saved Maintenance Mode configuration (Active: ' . ($maintOn === '1' ? 'YES' : 'NO') . ').');
        swl_flash('success', 'Maintenance Mode configuration updated!');
        swl_redirect('admin/settings.php#maintenance');
    }
}

$maintOn     = swl_is_maintenance();
$pskEnabled  = swl_setting_raw('paystack_enabled', '1') === '1';
$pskMode     = swl_setting('paystack_mode', 'test');
$pskPub      = swl_setting_raw('paystack_public_key', '');
$pskSec      = swl_setting_raw('paystack_secret_key', '');
$pskCurr     = swl_setting('paystack_currency', 'NGN');
$callbackUrl = swl_full_url('paystack_callback.php');
$webhookUrl  = swl_full_url('paystack_webhook.php');

$dashTitle = 'Website Settings — Logo, Favicon, Paystack & Maintenance Mode';
$activeTab = 'settings';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<!-- =====================================================================
     CARD 1: MAINTENANCE MODE TOGGLE & VISITOR MESSAGE
     ===================================================================== -->
<section class="card" id="maintenance" style="margin-bottom:28px;border:2px solid <?= $maintOn ? '#f59e0b' : 'var(--border-subtle)' ?>;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:18px;">
    <div>
      <span class="eyebrow"><?= swl_icon('power', 14) ?> Site Availability Control</span>
      <h3 style="margin-bottom:4px;">Maintenance Mode Toggle</h3>
      <p style="font-size:0.88rem;margin:0;">When enabled, public visitors see your branded maintenance page while Administrators continue browsing normally.</p>
    </div>

    <form method="post" style="margin:0;">
      <?= swl_csrf_field() ?>
      <input type="hidden" name="form_action" value="quick_toggle_maintenance">
      <button type="submit" class="btn <?= $maintOn ? 'btn-success' : 'btn-warning' ?>">
        <?= swl_icon('power', 16) ?>
        <?= $maintOn ? 'Maintenance ON — Click to Put Website Live' : 'Website Live — Click to Enable Maintenance Mode' ?>
      </button>
    </form>
  </div>

  <form method="post">
    <?= swl_csrf_field() ?>
    <input type="hidden" name="form_action" value="save_maintenance">

    <div class="toggle-row" style="margin-bottom:18px;">
      <div>
        <strong style="color:var(--deep-navy);display:block;">Maintenance Mode Switch</strong>
        <span style="font-size:0.83rem;color:var(--text-subtle);">Toggle ON during scheduled updates or facility holidays.</span>
      </div>
      <label class="switch" aria-label="Toggle Maintenance Mode">
        <input type="checkbox" name="maintenance_mode" value="1" <?= $maintOn ? 'checked' : '' ?>>
        <span class="slider slider-amber"></span>
      </label>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Maintenance Screen Headline</label>
        <input type="text" name="maintenance_title" class="form-control" value="<?= e(swl_setting('maintenance_title')) ?>" required>
      </div>
      <div class="form-group">
        <label>Maintenance Visitor Message</label>
        <input type="text" name="maintenance_message" class="form-control" value="<?= e(swl_setting('maintenance_message')) ?>" required>
      </div>
    </div>

    <button type="submit" class="btn btn-navy">Save Maintenance Settings</button>
  </form>
</section>

<!-- =====================================================================
     CARD 2: PAYSTACK PAYMENT GATEWAY INTEGRATION & CREDENTIALS
     ===================================================================== -->
<section class="card" id="paystack" style="margin-bottom:28px;">
  <span class="eyebrow"><?= swl_icon('credit-card', 14) ?> Online Payment Gateway</span>
  <h3 style="margin-bottom:6px;">Paystack Integration &amp; API Credentials</h3>
  <p style="font-size:0.88rem;margin-bottom:20px;">
    Paste your Paystack keys from your <strong>Paystack Dashboard &rarr; Settings &rarr; API Keys &amp; Webhooks</strong> to accept live Card, Bank Transfer, and USSD payments in Naira.
  </p>

  <form method="post">
    <?= swl_csrf_field() ?>
    <input type="hidden" name="form_action" value="save_paystack">

    <div class="toggle-row" style="margin-bottom:20px;">
      <div>
        <strong style="color:var(--deep-navy);display:block;">Enable Paystack Online Payments</strong>
        <span style="font-size:0.83rem;color:var(--text-subtle);">Allow website visitors and registered customers to pay online during booking or from their dashboard.</span>
      </div>
      <label class="switch" aria-label="Enable Paystack">
        <input type="checkbox" name="paystack_enabled" value="1" <?= $pskEnabled ? 'checked' : '' ?>>
        <span class="slider"></span>
      </label>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Gateway Environment Mode</label>
        <select name="paystack_mode" class="form-control">
          <option value="test" <?= $pskMode === 'test' ? 'selected' : '' ?>>Test Mode (Use pk_test_... &amp; sk_test_...)</option>
          <option value="live" <?= $pskMode === 'live' ? 'selected' : '' ?>>Live Production Mode (Use pk_live_... &amp; sk_live_...)</option>
        </select>
      </div>
      <div class="form-group">
        <label>Settlement Currency</label>
        <select name="paystack_currency" class="form-control">
          <?php foreach (['NGN' => 'Nigerian Naira (NGN ₦)', 'GHS' => 'Ghanaian Cedi (GHS)', 'USD' => 'US Dollar (USD)', 'ZAR' => 'South African Rand (ZAR)', 'KES' => 'Kenyan Shilling (KES)'] as $cCode => $cName): ?>
            <option value="<?= $cCode ?>" <?= $pskCurr === $cCode ? 'selected' : '' ?>><?= e($cName) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="pskPublicKey">Paystack Public Key (<code>pk_test_...</code> or <code>pk_live_...</code>)</label>
        <input type="text" id="pskPublicKey" name="paystack_public_key" class="form-control" value="<?= e($pskPub) ?>" placeholder="Enter your Paystack Public Key">
      </div>

      <div class="form-group">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <label for="pskSecretKey">Paystack Secret Key (<code>sk_test_...</code> or <code>sk_live_...</code>)</label>
          <button type="button" onclick="swlToggleSecret('pskSecretKey', this)" style="background:none;border:none;color:var(--blue-accent);font-weight:700;font-size:0.8rem;cursor:pointer;">Show</button>
        </div>
        <input type="password" id="pskSecretKey" name="paystack_secret_key" class="form-control" value="<?= e($pskSec) ?>" placeholder="Enter your Paystack Secret Key">
      </div>
    </div>

    <!-- Auto-Detected Callback & Webhook URLs for easy cPanel setup -->
    <div style="background:var(--ice-blue);border:1px solid var(--ice-border);border-radius:12px;padding:16px;margin-bottom:20px;font-size:0.85rem;">
      <strong style="color:var(--deep-navy);display:block;margin-bottom:8px;">Your Automatic cPanel Paystack Endpoints (Copy into Paystack Dashboard):</strong>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
        <span><strong>Callback URL:</strong> <code><?= e($callbackUrl) ?></code></span>
        <button type="button" class="btn btn-outline btn-sm" data-copy-value="<?= e($callbackUrl) ?>">Copy URL</button>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <span><strong>Webhook URL:</strong> <code><?= e($webhookUrl) ?></code></span>
        <button type="button" class="btn btn-outline btn-sm" data-copy-value="<?= e($webhookUrl) ?>">Copy URL</button>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">
      <?= swl_icon('check', 18) ?> Save Paystack Credentials
    </button>
  </form>
</section>

<!-- =====================================================================
     CARD 3: WEBSITE LOGO, FAVICON UPLOAD & BUSINESS PROFILE
     ===================================================================== -->
<section class="card" id="branding">
  <span class="eyebrow"><?= swl_icon('camera', 14) ?> Brand Assets &amp; Business Details</span>
  <h3 style="margin-bottom:6px;">Website Logo, Favicon &amp; Contact Settings</h3>
  <p style="font-size:0.88rem;margin-bottom:22px;">Upload a custom Website Logo or Favicon anytime—changes update live across the entire public website, receipts, and all user dashboards.</p>

  <form method="post" enctype="multipart/form-data">
    <?= swl_csrf_field() ?>
    <input type="hidden" name="form_action" value="save_branding">

    <div class="grid-2" style="margin-bottom:24px;">
      <!-- Website Logo Upload Box -->
      <div style="padding:20px;border:1.5px dashed var(--ice-border);border-radius:14px;background:var(--off-white);">
        <label style="font-size:0.95rem;">Website Logo Upload</label>
        <div style="height:84px;background:#fff;border:1px solid var(--border-subtle);border-radius:10px;display:grid;place-items:center;padding:10px;margin:12px 0;">
          <img id="logoPreviewImg" src="<?= e(swl_logo_url()) ?>" alt="Current Website Logo" style="max-height:60px;width:auto;">
        </div>
        <input type="file" name="site_logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="form-control" data-preview-target="logoPreviewImg">
        <span class="form-hint">Recommended: Transparent PNG, SVG, or WEBP (approx. 360 &times; 88 px).</span>
        <label style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-weight:500;font-size:0.8rem;cursor:pointer;">
          <input type="checkbox" name="reset_default_logo" value="1"> Reset to default Snow White logo
        </label>
      </div>

      <!-- Website Favicon Upload Box -->
      <div style="padding:20px;border:1.5px dashed var(--ice-border);border-radius:14px;background:var(--off-white);">
        <label style="font-size:0.95rem;">Website Favicon Upload</label>
        <div style="height:84px;background:#fff;border:1px solid var(--border-subtle);border-radius:10px;display:grid;place-items:center;padding:10px;margin:12px 0;">
          <img id="faviconPreviewImg" src="<?= e(swl_favicon_url()) ?>" alt="Current Website Favicon" style="width:52px;height:52px;object-fit:contain;">
        </div>
        <input type="file" name="site_favicon_file" accept="image/png,image/x-icon,image/svg+xml,image/webp" class="form-control" data-preview-target="faviconPreviewImg">
        <span class="form-hint">Recommended: Square PNG, ICO, or SVG (128 &times; 128 px or 256 &times; 256 px).</span>
        <label style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-weight:500;font-size:0.8rem;cursor:pointer;">
          <input type="checkbox" name="reset_default_favicon" value="1"> Reset to default SW favicon
        </label>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Business / Website Name *</label>
        <input type="text" name="site_name" class="form-control" value="<?= e(swl_setting('site_name')) ?>" required>
      </div>
      <div class="form-group">
        <label>Brand Tagline</label>
        <input type="text" name="site_tagline" class="form-control" value="<?= e(swl_setting('site_tagline')) ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Business Street Address</label>
        <input type="text" name="contact_address" class="form-control" value="<?= e(swl_setting('contact_address')) ?>">
      </div>
      <div class="form-group">
        <label>Business Operating Hours</label>
        <input type="text" name="business_hours" class="form-control" value="<?= e(swl_setting('business_hours')) ?>">
      </div>
    </div>

    <div class="grid-3">
      <div class="form-group">
        <label>Click-to-Call Phone</label>
        <input type="text" name="contact_phone" class="form-control" value="<?= e(swl_setting('contact_phone')) ?>">
      </div>
      <div class="form-group">
        <label>Official Email</label>
        <input type="email" name="contact_email" class="form-control" value="<?= e(swl_setting('contact_email')) ?>">
      </div>
      <div class="form-group">
        <label>WhatsApp Number (Intl Format)</label>
        <input type="text" name="whatsapp_number" class="form-control" value="<?= e(swl_setting('whatsapp_number')) ?>" placeholder="2347062300865">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Doorstep Pickup &amp; Delivery Fee (NGN ₦)</label>
        <input type="number" name="pickup_delivery_fee" class="form-control" value="<?= e(swl_setting('pickup_delivery_fee', '1500')) ?>" min="0" step="50">
      </div>
      <div class="form-group">
        <label>Express Same-Day Surcharge (%)</label>
        <input type="number" name="express_surcharge_percent" class="form-control" value="<?= e(swl_setting('express_surcharge_percent', '40')) ?>" min="0" max="200">
      </div>
    </div>

    <div class="form-group">
      <label>Footer Summary Text</label>
      <textarea name="footer_about" class="form-control" style="min-height:76px;"><?= e(swl_setting('footer_about')) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">
      <?= swl_icon('check', 18) ?> Upload Branding &amp; Save Website Settings
    </button>
  </form>
</section>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
