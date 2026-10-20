<?php
/**
 * Public Website Footer & Floating Quick Contact Actions
 */
$siteName   = swl_setting('site_name', 'Snow White Laundry');
$footerText = swl_setting('footer_about', 'Clean, reliable garment care, gentle dry cleaning, steam pressing, and scheduled pickup & delivery.');
$phone      = swl_setting('contact_phone', '07062300865');
$email      = swl_setting('contact_email', 'info@snowwhitelaundry.com.ng');
$address    = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street');
$hours      = swl_setting('business_hours', 'Mon – Sat: 8:00 AM – 7:00 PM | Sun: Pickup by Appointment');
$whatsapp   = preg_replace('/\D+/', '', swl_setting('whatsapp_number', '2347062300865'));
?>
<footer class="site-footer no-print">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand-box">
          <img src="<?= e(swl_logo_url()) ?>" alt="<?= e($siteName) ?>">
        </div>
        <p style="color:rgba(255,255,255,0.78);font-size:0.9rem;"><?= e($footerText) ?></p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
          <a href="tel:<?= e($phone) ?>" class="btn btn-primary btn-sm"><?= swl_icon('phone', 15) ?> Call Now</a>
          <?php if ($whatsapp !== ''): ?>
            <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp btn-sm">
              <?= swl_icon('whatsapp', 15) ?> WhatsApp Us
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <h4>Quick Navigation</h4>
        <ul class="footer-links">
          <li><a href="<?= e(swl_base_url()) ?>">Home</a></li>
          <li><a href="<?= e(swl_base_url('services.php')) ?>">Our Laundry Services</a></li>
          <li><a href="<?= e(swl_base_url('pricing.php')) ?>">Pricing &amp; Book Pickup</a></li>
          <li><a href="<?= e(swl_base_url('track.php')) ?>">Track Laundry Order</a></li>
          <li><a href="<?= e(swl_base_url('about.php')) ?>">About Snow White Laundry</a></li>
          <li><a href="<?= e(swl_base_url('faq.php')) ?>">Customer FAQs</a></li>
        </ul>
      </div>

      <div>
        <h4>Account Portals</h4>
        <ul class="footer-links">
          <li><a href="<?= e(swl_base_url('login.php?role=visitor')) ?>">Visitor / Customer Login</a></li>
          <li><a href="<?= e(swl_base_url('register.php')) ?>">Create Customer Account</a></li>
          <li><a href="<?= e(swl_base_url('login.php?role=staff')) ?>">Staff Operations Portal</a></li>
          <li><a href="<?= e(swl_base_url('login.php?role=admin')) ?>">Administrator Login</a></li>
          <li><a href="<?= e(swl_base_url('contact.php')) ?>">Request Commercial Quote</a></li>
        </ul>
      </div>

      <div>
        <h4>Visit or Contact Us</h4>
        <ul class="footer-links" style="gap:12px;">
          <li style="display:flex;gap:9px;align-items:flex-start;">
            <span style="color:#7dd3fc;margin-top:3px;"><?= swl_icon('map-pin', 16) ?></span>
            <span><?= e($address) ?></span>
          </li>
          <li style="display:flex;gap:9px;align-items:center;">
            <span style="color:#7dd3fc;"><?= swl_icon('phone', 16) ?></span>
            <a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a>
          </li>
          <li style="display:flex;gap:9px;align-items:center;">
            <span style="color:#7dd3fc;"><?= swl_icon('mail', 16) ?></span>
            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
          </li>
          <li style="display:flex;gap:9px;align-items:flex-start;">
            <span style="color:#7dd3fc;margin-top:3px;"><?= swl_icon('clock', 16) ?></span>
            <span style="font-size:0.86rem;"><?= e($hours) ?></span>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
      <span>Secure Online Payments powered by <strong>Paystack</strong></span>
    </div>
  </div>
</footer>

<script src="<?= e(swl_base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
