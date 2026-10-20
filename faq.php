<?php
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$pdo       = swl_db();
$faqs      = $pdo->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$pageTitle = 'Frequently Asked Questions';
$activeNav = 'faq';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Customer Support &amp; Policies</span>
    <h1>Frequently Asked Questions</h1>
    <p style="max-width:680px;font-size:1.05rem;margin-bottom:0;">
      Everything you need to know about turnaround times, garment handling, pickup &amp; delivery, and secure online payments at Snow White Laundry.
    </p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="faq-list">
      <?php foreach ($faqs as $idx => $faq): ?>
        <div class="faq-item <?= $idx < 2 ? 'open' : '' ?>">
          <button type="button" class="faq-question">
            <span>
              <small style="display:block;font-size:0.73rem;text-transform:uppercase;color:var(--blue-accent);letter-spacing:0.06em;margin-bottom:3px;">
                <?= e($faq['category']) ?>
              </small>
              <?= e($faq['question']) ?>
            </span>
            <span class="faq-chevron">&#9662;</span>
          </button>
          <div class="faq-answer">
            <?= nl2br(e($faq['answer'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card" style="max-width:860px;margin:40px auto 0;background:var(--ice-blue);display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;">
      <div>
        <h3 style="margin-bottom:4px;">Still have a specific fabric or bulk laundry question?</h3>
        <p style="margin:0;font-size:0.9rem;">Send us a quick note or call our front desk directly on <strong><?= e(swl_setting('contact_phone')) ?></strong>.</p>
      </div>
      <a href="<?= e(swl_base_url('contact.php#enquiry-form')) ?>" class="btn btn-primary">Ask Our Team &rarr;</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
