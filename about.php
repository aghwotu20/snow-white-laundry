<?php
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$address   = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street');
$phone     = swl_setting('contact_phone', '07062300865');
$email     = swl_setting('contact_email', 'info@snowwhitelaundry.com.ng');
$pageTitle = 'About Us & Garment Care Standards';
$activeNav = 'about';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?= swl_icon('shield', 14) ?> About Snow White Laundry</span>
    <h1>Clean Standards, Honest Care &amp; Fresh Garments Every Single Time</h1>
    <p style="max-width:680px;font-size:1.05rem;margin-bottom:0;">
      Rooted at <strong><?= e($address) ?></strong>, Snow White Laundry was established to remove stress from wardrobe care while treating every customer's clothes with genuine respect.
    </p>
  </div>
</section>

<section class="section">
  <div class="container split-grid">
    <div>
      <span class="eyebrow">Our Story &amp; Promise</span>
      <h2>Why Discerning Homes &amp; Professionals Choose Us</h2>
      <p>
        Anyone who has ever had a favourite white shirt dulled in a mixed wash or delicate embroidery damaged by harsh chemicals knows why proper fabric care matters. At Snow White Laundry, we combine disciplined garment sorting, fabric-matched cleaning cycles, and crisp steam pressing so your wardrobe always looks sharp and feels fresh against your skin.
      </p>
      <p>
        Whether you drop off a single business suit on your morning commute or schedule weekly household pickup and delivery through our online portal, you receive clear communication, transparent Naira pricing, and dependable turnaround times.
      </p>

      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:22px;">
        <a href="<?= e(swl_base_url('pricing.php#book-now')) ?>" class="btn btn-primary">Book Your First Pickup</a>
        <a href="<?= e(swl_base_url('contact.php')) ?>" class="btn btn-outline">Contact Our Team</a>
      </div>
    </div>

    <div class="split-photo">
      <img src="<?= e(swl_base_url('assets/images/studio-interior.jpg')) ?>" alt="Snow White Laundry Reception and Organized Garment Studio">
    </div>
  </div>
</section>

<section class="section section-ice">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Quality Pillars</span>
      <h2>Our Four Non-Negotiable Hygiene &amp; Care Standards</h2>
    </div>

    <div class="grid-4">
      <div class="card">
        <div class="service-icon"><?= swl_icon('shield', 24) ?></div>
        <h4>100% Dedicated Loads</h4>
        <p style="font-size:0.88rem;margin:0;">Your clothes are washed strictly in dedicated loads—never mingled with another household's garments.</p>
      </div>
      <div class="card">
        <div class="service-icon"><?= swl_icon('sparkles', 24) ?></div>
        <h4>True Bright Whites</h4>
        <p style="font-size:0.88rem;margin:0;">Whites, pastels, darks, and native fabrics are separated with oxygen-brightening formulas that protect fibres.</p>
      </div>
      <div class="card">
        <div class="service-icon"><?= swl_icon('zap', 24) ?></div>
        <h4>Zero-Scorch Steam Press</h4>
        <p style="font-size:0.88rem;margin:0;">Regulated industrial steam finishing prevents shine marks on dark suits, trousers, and delicate silk/lace.</p>
      </div>
      <div class="card">
        <div class="service-icon"><?= swl_icon('clock', 24) ?></div>
        <h4>Accountable Turnaround</h4>
        <p style="font-size:0.88rem;margin:0;">Every order carries a unique tracking ID (`SWL-...`) monitored by our staff from reception to doorstep delivery.</p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
