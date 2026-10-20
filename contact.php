<?php
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$pdo      = swl_db();
$currUser = swl_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $type      = in_array($_POST['enquiry_type'] ?? '', ['service_enquiry', 'quote_request', 'general_contact'], true)
        ? $_POST['enquiry_type']
        : 'service_enquiry';
    $fullName  = trim($_POST['full_name'] ?? '');
    $emailIn   = strtolower(trim($_POST['email'] ?? ''));
    $phoneIn   = trim($_POST['phone'] ?? '');
    $serviceIn = trim($_POST['requested_service'] ?? '');
    $prefDate  = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null;
    $estItems  = trim($_POST['estimated_items'] ?? '');
    $message   = trim($_POST['message'] ?? '');

    if ($fullName === '' || !filter_var($emailIn, FILTER_VALIDATE_EMAIL) || $phoneIn === '' || $message === '') {
        swl_flash('error', 'Please fill in your full name, valid email address, phone number, and message.');
        swl_redirect('contact.php#enquiry-form');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO enquiries (user_id, enquiry_type, full_name, email, phone, requested_service, preferred_date, estimated_items, message, status)
         VALUES (:uid, :type, :fn, :em, :ph, :srv, :pdate, :items, :msg, "new")'
    );
    $stmt->execute([
        ':uid'   => $currUser ? (int)$currUser['id'] : null,
        ':type'  => $type,
        ':fn'    => $fullName,
        ':em'    => $emailIn,
        ':ph'    => $phoneIn,
        ':srv'   => $serviceIn,
        ':pdate' => $prefDate,
        ':items' => $estItems,
        ':msg'   => $message,
    ]);

    swl_log_activity($currUser ? (int)$currUser['id'] : null, $currUser['role'] ?? 'visitor', 'enquiry.submitted', 'New ' . $type . ' received from ' . $fullName);
    swl_flash('success', 'Thank you, ' . $fullName . '! Your service enquiry has been received. Our team at Snow White Laundry will respond promptly.');
    swl_redirect('contact.php');
}

$services   = $pdo->query('SELECT name FROM services WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_COLUMN);
$address    = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street');
$phone      = swl_setting('contact_phone', '07062300865');
$email      = swl_setting('contact_email', 'info@snowwhitelaundry.com.ng');
$hours      = swl_setting('business_hours', 'Mon – Sat: 8:00 AM – 7:00 PM | Sun: Pickup by Appointment');
$whatsapp   = preg_replace('/\D+/', '', swl_setting('whatsapp_number', '2347062300865'));
$presetType = $_GET['type'] ?? 'service_enquiry';
$presetSrv  = $_GET['service'] ?? '';

$pageTitle = 'Contact Us & Request a Quote';
$activeNav = 'contact';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?= swl_icon('phone', 14) ?> Get in Touch</span>
    <h1>Contact Snow White Laundry or Request a Service Quote</h1>
    <p style="max-width:680px;font-size:1.05rem;margin-bottom:0;">
      Call, email, WhatsApp, visit our reception counter at <strong><?= e($address) ?></strong>, or complete the service enquiry form below.
    </p>
  </div>
</section>

<section class="section" id="enquiry-form">
  <div class="container">
    <div class="grid-2" style="grid-template-columns:0.92fr 1.08fr;align-items:start;">
      <!-- DIRECT CONTACT DETAILS + EMBEDDED MAP -->
      <div style="display:grid;gap:20px;">
        <div class="card">
          <h3>Direct Business Contacts</h3>
          <div style="display:grid;gap:16px;margin-top:18px;">
            <div style="display:flex;gap:13px;align-items:flex-start;">
              <div class="service-icon" style="width:42px;height:42px;margin:0;flex-shrink:0;"><?= swl_icon('map-pin', 20) ?></div>
              <div>
                <strong style="display:block;color:var(--deep-navy);">Branch Address</strong>
                <span style="font-size:0.92rem;color:var(--text-body);"><?= e($address) ?></span>
              </div>
            </div>

            <div style="display:flex;gap:13px;align-items:flex-start;">
              <div class="service-icon" style="width:42px;height:42px;margin:0;flex-shrink:0;"><?= swl_icon('phone', 20) ?></div>
              <div>
                <strong style="display:block;color:var(--deep-navy);">Click-to-Call Phone</strong>
                <a href="tel:<?= e($phone) ?>" style="font-size:1rem;font-weight:700;"><?= e($phone) ?></a>
              </div>
            </div>

            <div style="display:flex;gap:13px;align-items:flex-start;">
              <div class="service-icon" style="width:42px;height:42px;margin:0;flex-shrink:0;"><?= swl_icon('mail', 20) ?></div>
              <div>
                <strong style="display:block;color:var(--deep-navy);">Official Email</strong>
                <a href="mailto:<?= e($email) ?>" style="font-size:0.95rem;font-weight:600;"><?= e($email) ?></a>
              </div>
            </div>

            <div style="display:flex;gap:13px;align-items:flex-start;">
              <div class="service-icon" style="width:42px;height:42px;margin:0;flex-shrink:0;"><?= swl_icon('clock', 20) ?></div>
              <div>
                <strong style="display:block;color:var(--deep-navy);">Operating Hours</strong>
                <span style="font-size:0.9rem;color:var(--text-body);"><?= e($hours) ?></span>
              </div>
            </div>
          </div>

          <?php if ($whatsapp !== ''): ?>
            <div style="margin-top:22px;padding-top:18px;border-top:1px solid var(--border-subtle);">
              <a href="https://wa.me/<?= e($whatsapp) ?>?text=<?= urlencode('Hello Snow White Laundry, I have an enquiry about laundry services.') ?>" target="_blank" rel="noopener" class="btn btn-whatsapp" style="width:100%;">
                <?= swl_icon('whatsapp', 18) ?> Start Instant WhatsApp Chat (<?= e($phone) ?>)
              </a>
            </div>
          <?php endif; ?>
        </div>

        <div class="card" style="padding:14px;">
          <iframe
            title="Snow White Laundry Location Map"
            src="https://maps.google.com/maps?q=<?= urlencode($address . ', Nigeria') ?>&t=&z=14&ie=UTF8&iwloc=&output=embed"
            style="width:100%;height:250px;border:0;border-radius:10px;background:var(--ice-blue);"
            loading="lazy"
          ></iframe>
        </div>
      </div>

      <!-- SERVICE ENQUIRY & QUOTE REQUEST FORM -->
      <div class="card">
        <span class="eyebrow">Fast Response Guaranteed</span>
        <h3>Service Enquiry &amp; Quote Request Form</h3>
        <p style="font-size:0.9rem;">Tell us what garments or linens you need cleaned, your preferred date, and any special instructions.</p>

        <form method="post">
          <?= swl_csrf_field() ?>

          <div class="form-row">
            <div class="form-group">
              <label>Enquiry Type</label>
              <select name="enquiry_type" class="form-control">
                <option value="service_enquiry" <?= $presetType === 'service_enquiry' ? 'selected' : '' ?>>Service Pickup / Laundry Enquiry</option>
                <option value="quote_request" <?= $presetType === 'quote_request' ? 'selected' : '' ?>>Request Custom / Bulk Pricing Quote</option>
                <option value="general_contact" <?= $presetType === 'general_contact' ? 'selected' : '' ?>>General Question or Feedback</option>
              </select>
            </div>
            <div class="form-group">
              <label>Requested Service</label>
              <select name="requested_service" class="form-control">
                <option value="General Garment Care">Select a Laundry Service...</option>
                <?php foreach ($services as $sName): ?>
                  <option value="<?= e($sName) ?>" <?= $presetSrv === $sName ? 'selected' : '' ?>><?= e($sName) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Your Full Name *</label>
              <input type="text" name="full_name" class="form-control" value="<?= e($currUser['full_name'] ?? '') ?>" placeholder="Full Name" required>
            </div>
            <div class="form-group">
              <label>Phone Number *</label>
              <input type="text" name="phone" class="form-control" value="<?= e($currUser['phone'] ?? '') ?>" placeholder="e.g. 07062300865" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Email Address *</label>
              <input type="email" name="email" class="form-control" value="<?= e($currUser['email'] ?? '') ?>" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
              <label>Preferred Service Date</label>
              <input type="date" name="preferred_date" class="form-control" min="<?= e(date('Y-m-d')) ?>" value="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Approximate Number / Type of Items (Optional)</label>
            <input type="text" name="estimated_items" class="form-control" placeholder="e.g. 10 corporate shirts, 2 suits & 1 king duvet">
          </div>

          <div class="form-group">
            <label>Notes, Address &amp; Care Instructions *</label>
            <textarea name="message" class="form-control" placeholder="Share your pickup area, fabric questions, or quote details..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
            Submit Service Enquiry
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
