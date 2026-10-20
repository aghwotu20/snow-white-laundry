<?php
/**
 * Public Website Footer & Floating Quick Contact WhatsApp Icon
 */
$siteName   = swl_setting('site_name', 'Snow White Laundry');
$footerText = swl_setting('footer_about', 'Clean, reliable garment care, gentle dry cleaning, steam pressing, and scheduled pickup & delivery.');
$phone      = swl_setting('contact_phone', '07062300865');
$email      = swl_setting('contact_email', 'info@snowwhitelaundry.com.ng');
$address    = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street');
$hours      = swl_setting('business_hours', 'Mon – Sat: 8:00 AM – 7:00 PM | Sun: Pickup by Appointment');
$whatsapp   = preg_replace('/\D+/', '', swl_setting('whatsapp_number', '2347062300865'));
if ($whatsapp === '' && $phone !== '') {
    $digits = preg_replace('/\D+/', '', $phone);
    $whatsapp = str_starts_with($digits, '0') ? '234' . substr($digits, 1) : $digits;
}
?>
<footer class="site-footer no-print">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand-box">
          <img src="<?= e(swl_logo_url()) ?>" alt="<?= e($siteName) ?>">
        </div>
        <p style="color:rgba(255,255,255,0.82);font-size:0.9rem;"><?= e($footerText) ?></p>
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
            <span style="color:#ff8b66;margin-top:3px;"><?= swl_icon('map-pin', 16) ?></span>
            <span><?= e($address) ?></span>
          </li>
          <li style="display:flex;gap:9px;align-items:center;">
            <span style="color:#ff8b66;"><?= swl_icon('phone', 16) ?></span>
            <a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a>
          </li>
          <li style="display:flex;gap:9px;align-items:center;">
            <span style="color:#ff8b66;"><?= swl_icon('mail', 16) ?></span>
            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
          </li>
          <li style="display:flex;gap:9px;align-items:flex-start;">
            <span style="color:#ff8b66;margin-top:3px;"><?= swl_icon('clock', 16) ?></span>
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

<?php if ($whatsapp !== ''): ?>
  <!-- Floating WhatsApp Quick-Chat Button -->
  <a
    href="https://wa.me/<?= e($whatsapp) ?>?text=<?= urlencode('Hello ' . $siteName . ', I would like to book a laundry pickup or make an enquiry.') ?>"
    target="_blank"
    rel="noopener noreferrer"
    id="swlFloatingWhatsapp"
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

<script src="<?= e(swl_base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
