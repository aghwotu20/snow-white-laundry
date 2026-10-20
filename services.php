<?php
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$pdo       = swl_db();
$services  = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$pageTitle = 'Our Laundry & Dry Cleaning Services';
$activeNav = 'services';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?= swl_icon('shirt', 14) ?> Complete Garment &amp; Linen Care</span>
    <h1>Professional Laundry, Dry Cleaning &amp; Pressing Services</h1>
    <p style="max-width:680px;font-size:1.05rem;margin-bottom:0;">
      Every fabric requires the right water temperature, detergent balance, and pressing technique. Explore our full menu below and book online or request a tailored commercial quote.
    </p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="grid-2">
      <?php foreach ($services as $srv): ?>
        <article class="card card-hover" style="display:flex;flex-direction:column;justify-content:space-between;">
          <div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px;">
              <div class="service-icon" style="margin-bottom:0;"><?= swl_icon($srv['icon_name'], 24) ?></div>
              <span class="badge badge-confirmed"><?= e($srv['category']) ?> &bull; <?= e($srv['turnaround_hours']) ?></span>
            </div>

            <h3 style="font-size:1.28rem;"><?= e($srv['name']) ?></h3>
            <p style="font-weight:600;color:var(--navy-muted);margin-bottom:10px;"><?= e($srv['short_description']) ?></p>
            <p style="font-size:0.9rem;margin-bottom:0;"><?= e($srv['full_description']) ?></p>
          </div>

          <div class="service-meta">
            <div>
              <span class="price-tag"><?= swl_money($srv['price_ngn']) ?></span>
              <span class="price-unit"><?= e($srv['unit_label']) ?></span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <a href="<?= e(swl_base_url('pricing.php?select=' . (int)$srv['id'] . '#book-now')) ?>" class="btn btn-primary btn-sm">
                Book Service
              </a>
              <a href="<?= e(swl_base_url('contact.php?service=' . urlencode($srv['name']) . '#enquiry-form')) ?>" class="btn btn-outline btn-sm">
                Ask Question
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-ice">
  <div class="container" style="text-align:center;max-width:740px;">
    <h2>Need Recurring Hotel, Clinic, Corporate, or Event Linen Care?</h2>
    <p>We prepare customized weekly and monthly laundry plans with scheduled pickups and priority turnaround.</p>
    <a href="<?= e(swl_base_url('contact.php?type=quote_request#enquiry-form')) ?>" class="btn btn-navy btn-lg">
      Request a Commercial Quote
    </a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
