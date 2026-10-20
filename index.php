<?php
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$pdo       = swl_db();
$services  = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC LIMIT 6')->fetchAll();
$faqs      = $pdo->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 4')->fetchAll();
$phone     = swl_setting('contact_phone', '07062300865');
$email     = swl_setting('contact_email', 'info@snowwhitelaundry.com.ng');
$address   = swl_setting('contact_address', 'No 3 Kess Rubber Factory Street');
$whatsapp  = preg_replace('/\D+/', '', swl_setting('whatsapp_number', '2347062300865'));
$activeNav = 'home';

require __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION -->
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="eyebrow"><?= swl_icon('sparkles', 14) ?> Fresh, Crisp &amp; Dependable Garment Care</span>
      <h1>Spotless Laundry &amp; Dry Cleaning <span>Made Effortless.</span></h1>
      <p class="hero-lead">
        At <strong>Snow White Laundry</strong>, every shirt, traditional outfit, suit, and household linen is inspected individually, washed in dedicated hygienic loads, and returned crisp, fresh, and right on time.
      </p>

      <div class="hero-cta-group">
        <a href="<?= e(swl_base_url('pricing.php#book-now')) ?>" class="btn btn-primary btn-lg">
          <?= swl_icon('basket', 19) ?> Schedule Laundry Pickup
        </a>
        <a href="<?= e(swl_base_url('contact.php#enquiry-form')) ?>" class="btn btn-outline btn-lg">
          Request a Quote
        </a>
        <?php if ($whatsapp !== ''): ?>
          <a href="https://wa.me/<?= e($whatsapp) ?>?text=<?= urlencode('Hello Snow White Laundry, I want to book a pickup/drop-off.') ?>" target="_blank" rel="noopener" class="btn btn-whatsapp btn-lg">
            <?= swl_icon('whatsapp', 18) ?> Chat on WhatsApp
          </a>
        <?php endif; ?>
      </div>

      <div class="hero-trust-row">
        <div class="trust-stat">
          <strong>24–48 hrs</strong>
          <span>Fast Reliable Turnaround</span>
        </div>
        <div class="trust-stat">
          <strong>100% Separate</strong>
          <span>Never Mixed With Others' Loads</span>
        </div>
        <div class="trust-stat">
          <strong>Paystack Ready</strong>
          <span>Instant Online or Doorstep Pay</span>
        </div>
      </div>
    </div>

    <div class="hero-visual">
      <div class="hero-photo-frame">
        <img src="<?= e(swl_base_url('assets/images/hero-laundry.jpg')) ?>" alt="Crisply folded white cotton linens and dress shirts at Snow White Laundry">
      </div>
      <div class="hero-floating-card">
        <div class="hero-floating-icon"><?= swl_icon('shield', 24) ?></div>
        <div>
          <strong style="display:block;font-size:0.9rem;color:var(--deep-navy);">Fabric-Safe Guarantee</strong>
          <span style="font-size:0.8rem;color:var(--text-body);"><?= e($address) ?></span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CORE LAUNDRY & DRY CLEANING SERVICES -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Our Laundry Services</span>
      <h2>Tailored Care for Every Fabric in Your Wardrobe</h2>
      <p>From everyday wash-and-fold bags to executive suits, delicate lace, embroidered Agbada, and king-size duvets.</p>
    </div>

    <div class="grid-3">
      <?php foreach ($services as $srv): ?>
        <article class="card card-hover" style="display:flex;flex-direction:column;justify-content:space-between;">
          <div>
            <div class="service-icon"><?= swl_icon($srv['icon_name'], 24) ?></div>
            <span style="font-size:0.74rem;font-weight:700;text-transform:uppercase;color:var(--blue-accent);letter-spacing:0.05em;">
              <?= e($srv['category']) ?> &bull; <?= e($srv['turnaround_hours']) ?>
            </span>
            <h3 style="margin-top:6px;font-size:1.18rem;"><?= e($srv['name']) ?></h3>
            <p style="font-size:0.9rem;margin-bottom:0;"><?= e($srv['short_description']) ?></p>
          </div>

          <div class="service-meta">
            <div>
              <span class="price-tag"><?= swl_money($srv['price_ngn']) ?></span>
              <span class="price-unit"><?= e($srv['unit_label']) ?></span>
            </div>
            <a href="<?= e(swl_base_url('pricing.php?select=' . (int)$srv['id'] . '#book-now')) ?>" class="btn btn-outline btn-sm">
              Book Now &rarr;
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:38px;">
      <a href="<?= e(swl_base_url('services.php')) ?>" class="btn btn-navy">
        Explore Full Service Menu &amp; Fabric Guide
      </a>
    </div>
  </div>
</section>

<!-- 4-STEP GARMENT CARE WORKFLOW -->
<section class="section section-ice">
  <div class="container split-grid">
    <div class="split-photo">
      <img src="<?= e(swl_base_url('assets/images/garment-care.jpg')) ?>" alt="Garment specialist steam pressing white executive dress shirt">
    </div>

    <div>
      <span class="eyebrow">How Snow White Works</span>
      <h2 style="font-size:clamp(1.7rem,3vw,2.3rem);">Neat, Transparent Care from Pickup to Wardrobe</h2>
      <p>Every garment follows a strict four-stage quality protocol at our facility so your favourites look newer for longer.</p>

      <div class="step-list">
        <div class="step-item">
          <div class="step-num">1</div>
          <div>
            <h4 style="margin-bottom:3px;">Easy Online Booking or Drop-Off</h4>
            <p style="margin:0;font-size:0.88rem;">Schedule doorstep pickup online, message us on WhatsApp, or walk into <?= e($address) ?>.</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-num">2</div>
          <div>
            <h4 style="margin-bottom:3px;">Pocket Check, Tagging &amp; Stain Pre-Treatment</h4>
            <p style="margin:0;font-size:0.88rem;">Clothes are itemized by order ID, checked for delicate trims, and separated strictly whites-from-colours.</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-num">3</div>
          <div>
            <h4 style="margin-bottom:3px;">Gentle Wash, Dry Cleaning &amp; Steam Finish</h4>
            <p style="margin:0;font-size:0.88rem;">Cleaned using fabric-safe formulas and finished on temperature-controlled steam presses with your preferred starch level.</p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-num">4</div>
          <div>
            <h4 style="margin-bottom:3px;">Protective Packaging &amp; Prompt Delivery</h4>
            <p style="margin:0;font-size:0.88rem;">Track live status inside your Customer Portal, pay seamlessly via Paystack, and receive fresh garments on time.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WHY CHOOSE SNOW WHITE LAUNDRY + STUDIO PREVIEW -->
<section class="section">
  <div class="container split-grid">
    <div>
      <span class="eyebrow">Why Customers Trust Us</span>
      <h2 style="font-size:clamp(1.7rem,3vw,2.3rem);">Built Around Cleanliness, Reliability &amp; Respect for Your Time</h2>
      <p>Whether you are a busy professional preparing Monday-to-Friday corporate shirts, a family refreshing household bedding, or a business needing dependable linen care, Snow White Laundry delivers consistent results without guesswork.</p>

      <div class="grid-2" style="gap:16px;margin:24px 0;">
        <div class="card" style="padding:18px;background:var(--off-white);">
          <strong style="color:var(--deep-navy);display:block;margin-bottom:4px;">Dedicated Portal Access</strong>
          <span style="font-size:0.86rem;color:var(--text-body);">Create your personal account, upload your profile photo, manage orders, and download instant receipts.</span>
        </div>
        <div class="card" style="padding:18px;background:var(--off-white);">
          <strong style="color:var(--deep-navy);display:block;margin-bottom:4px;">Direct Human Support</strong>
          <span style="font-size:0.86rem;color:var(--text-body);">Tap-to-call <a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a> or email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> anytime.</span>
        </div>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="<?= e(swl_base_url('register.php')) ?>" class="btn btn-primary">Create Free Customer Account</a>
        <a href="<?= e(swl_base_url('about.php')) ?>" class="btn btn-outline">Read Our Standards</a>
      </div>
    </div>

    <div class="split-photo">
      <img src="<?= e(swl_base_url('assets/images/studio-interior.jpg')) ?>" alt="Organized Snow White Laundry reception and clean garment racks">
    </div>
  </div>
</section>

<!-- FREQUENTLY ASKED QUESTIONS PREVIEW -->
<section class="section section-soft">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Common Questions</span>
      <h2>Helpful Answers Before You Book</h2>
      <p>Clear policies on turnaround hours, fabric care, doorstep pickup, and online payment.</p>
    </div>

    <div class="faq-list">
      <?php foreach ($faqs as $idx => $faq): ?>
        <div class="faq-item <?= $idx === 0 ? 'open' : '' ?>">
          <button type="button" class="faq-question">
            <span><?= e($faq['question']) ?></span>
            <span class="faq-chevron">&#9662;</span>
          </button>
          <div class="faq-answer">
            <?= nl2br(e($faq['answer'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:28px;">
      <a href="<?= e(swl_base_url('faq.php')) ?>" class="btn btn-outline">View All FAQs &rarr;</a>
    </div>
  </div>
</section>

<!-- BOTTOM CALL TO ACTION -->
<section class="section section-navy">
  <div class="container" style="text-align:center;max-width:780px;">
    <span class="eyebrow" style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.22);color:#7dd3fc;">
      Ready When You Are
    </span>
    <h2 style="font-size:clamp(1.85rem,3.4vw,2.6rem);margin-bottom:14px;">Give Your Clothes the Snow White Finish Today</h2>
    <p style="font-size:1.05rem;margin-bottom:30px;">
      Drop off at <strong><?= e($address) ?></strong>, book doorstep pickup online in under two minutes, or speak directly with our team on <strong><?= e($phone) ?></strong>.
    </p>
    <div style="display:flex;justify-content:center;gap:14px;flex-wrap:wrap;">
      <a href="<?= e(swl_base_url('pricing.php#book-now')) ?>" class="btn btn-primary btn-lg">Book Laundry &amp; Pay Online</a>
      <a href="tel:<?= e($phone) ?>" class="btn btn-outline btn-lg" style="background:transparent;color:#fff;border-color:rgba(255,255,255,0.35);">
        <?= swl_icon('phone', 18) ?> Call <?= e($phone) ?>
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
