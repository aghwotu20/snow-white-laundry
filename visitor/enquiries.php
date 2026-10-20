<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('visitor');
$pdo      = swl_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $service = trim($_POST['requested_service'] ?? 'General Laundry');
    $items   = trim($_POST['estimated_items'] ?? '');
    $msg     = trim($_POST['message'] ?? '');

    if ($msg === '') {
        swl_flash('error', 'Please enter your question or quote details.');
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO enquiries (user_id, enquiry_type, full_name, email, phone, requested_service, estimated_items, message, status)
             VALUES (:uid, "quote_request", :fn, :em, :ph, :srv, :it, :msg, "new")'
        );
        $ins->execute([
            ':uid' => $authUser['id'],
            ':fn'  => $authUser['full_name'],
            ':em'  => $authUser['email'],
            ':ph'  => $authUser['phone'] ?: 'N/A',
            ':srv' => $service,
            ':it'  => $items,
            ':msg' => $msg,
        ]);
        swl_flash('success', 'Your quote/enquiry has been submitted! Watch below for staff responses.');
    }
    swl_redirect('visitor/enquiries.php');
}

$stmt = $pdo->prepare('SELECT * FROM enquiries WHERE user_id = :u OR email = :e ORDER BY id DESC');
$stmt->execute([':u' => $authUser['id'], ':e' => $authUser['email']]);
$enquiries = $stmt->fetchAll();

$dashTitle = 'My Quotes & Service Enquiries';
$activeTab = 'enquiries';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="grid-2" style="grid-template-columns:0.85fr 1.15fr;align-items:start;">
  <div class="card">
    <h3>Send a Quote Request / Question</h3>
    <form method="post">
      <?= swl_csrf_field() ?>
      <div class="form-group">
        <label>Service Category</label>
        <input type="text" name="requested_service" class="form-control" placeholder="e.g. Bulk Event Linen / Wedding Attire" required>
      </div>
      <div class="form-group">
        <label>Estimated Items</label>
        <input type="text" name="estimated_items" class="form-control" placeholder="e.g. 4 Agbada sets & 12 shirts">
      </div>
      <div class="form-group">
        <label>Your Message *</label>
        <textarea name="message" class="form-control" required placeholder="Tell our staff how we can assist..."></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Submit Enquiry</button>
    </form>
  </div>

  <div class="card">
    <h3>Submitted Enquiries &amp; Staff Responses</h3>
    <?php if (empty($enquiries)): ?>
      <p>No enquiries submitted yet.</p>
    <?php else: ?>
      <div style="display:grid;gap:14px;">
        <?php foreach ($enquiries as $enq): ?>
          <div style="padding:16px;border:1px solid var(--border-subtle);border-radius:12px;background:var(--off-white);">
            <div style="display:flex;justify-content:space-between;gap:8px;margin-bottom:6px;">
              <strong><?= e($enq['requested_service'] ?: 'Service Enquiry') ?></strong>
              <span class="badge badge-<?= e($enq['status']) ?>"><?= e(strtoupper(str_replace('_', ' ', $enq['status']))) ?></span>
            </div>
            <p style="font-size:0.88rem;margin-bottom:8px;"><?= nl2br(e($enq['message'])) ?></p>
            <?php if (!empty($enq['staff_reply_note'])): ?>
              <div style="background:#e0f2fe;padding:10px 13px;border-radius:9px;font-size:0.85rem;color:var(--deep-navy);">
                <strong>Snow White Staff Reply:</strong> <?= nl2br(e($enq['staff_reply_note'])) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
