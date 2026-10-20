<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('staff');
$pdo      = swl_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $enqId  = (int)($_POST['enquiry_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['new', 'in_review', 'responded', 'closed'], true) ? $_POST['status'] : 'responded';
    $reply  = trim($_POST['staff_reply_note'] ?? '');

    if ($enqId > 0) {
        $pdo->prepare('UPDATE enquiries SET status = :st, staff_reply_note = :rp, handled_by_staff_id = :sid WHERE id = :id')
            ->execute([':st' => $status, ':rp' => $reply, ':sid' => $authUser['id'], ':id' => $enqId]);
        swl_log_activity((int)$authUser['id'], 'staff', 'enquiry.replied', $authUser['full_name'] . ' updated enquiry #' . $enqId);
        swl_flash('success', 'Customer enquiry response saved!');
    }
    swl_redirect('staff/enquiries.php');
}

$enquiries = $pdo->query('SELECT * FROM enquiries ORDER BY id DESC')->fetchAll();

$dashTitle = 'Customer Enquiries & Quote Requests';
$activeTab = 'enquiries';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="card">
  <h3>Respond to Service Enquiries &amp; Commercial Quotes</h3>
  <div style="display:grid;gap:16px;margin-top:18px;">
    <?php foreach ($enquiries as $enq): ?>
      <form method="post" style="padding:18px;border:1px solid var(--border-subtle);border-radius:14px;background:var(--off-white);">
        <?= swl_csrf_field() ?>
        <input type="hidden" name="enquiry_id" value="<?= (int)$enq['id'] ?>">

        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:8px;">
          <div>
            <strong style="color:var(--deep-navy);"><?= e($enq['full_name']) ?></strong>
            &bull; <a href="tel:<?= e($enq['phone']) ?>"><?= e($enq['phone']) ?></a>
            &bull; <?= e($enq['email']) ?>
            <div style="font-size:0.8rem;color:var(--text-subtle);">
              Service: <strong><?= e($enq['requested_service'] ?: 'General') ?></strong>
              <?= !empty($enq['estimated_items']) ? ' &bull; Items: ' . e($enq['estimated_items']) : '' ?>
            </div>
          </div>
          <span class="badge badge-<?= e($enq['status']) ?>"><?= e(strtoupper(str_replace('_', ' ', $enq['status']))) ?></span>
        </div>

        <p style="font-size:0.9rem;background:#fff;padding:12px;border-radius:9px;border:1px solid var(--border-subtle);">
          <?= nl2br(e($enq['message'])) ?>
        </p>

        <div class="form-row" style="grid-template-columns:1.5fr 0.6fr auto;align-items:end;">
          <div class="form-group" style="margin:0;">
            <label>Reply Note (Visible in Customer Portal)</label>
            <input type="text" name="staff_reply_note" class="form-control" value="<?= e($enq['staff_reply_note']) ?>" placeholder="Write quote estimate or pickup confirmation...">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Status</label>
            <select name="status" class="form-control">
              <?php foreach (['new','in_review','responded','closed'] as $st): ?>
                <option value="<?= $st ?>" <?= $enq['status'] === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $st)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Save Reply</button>
        </div>
      </form>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
