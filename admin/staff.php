<?php
/**
 * Admin Staff Management (`admin/staff.php`)
 * - Create new staff accounts (with optional Passport Photograph)
 * - Suspend any active staff member in 1 click (with optional suspension reason)
 * - Reactivate suspended staff members in 1 click
 * - Remove staff accounts
 */
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('admin');
$pdo      = swl_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $action = $_POST['staff_action'] ?? '';

    // 1. Add New Staff Account
    if ($action === 'create_staff') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = trim($_POST['phone'] ?? '');
        $jobTitle = trim($_POST['job_title'] ?? 'Garment Care Staff');
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            swl_flash('error', 'Please provide a full name, valid email address, and password of at least 6 characters.');
            swl_redirect('admin/staff.php');
        }

        $chk = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
        $chk->execute([':e' => $email]);
        if ($chk->fetch()) {
            swl_flash('error', 'A user with that email address already exists.');
            swl_redirect('admin/staff.php');
        }

        $passportPath = null;
        if (!empty($_FILES['passport_photo']['name'] ?? '')) {
            $up = swl_upload_image($_FILES['passport_photo'], 'passports', 'staff-member', 5242880, false);
            if ($up['ok']) {
                $passportPath = $up['path'];
            }
        }

        $nextNum   = ((int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn()) + 1;
        $staffCode = sprintf('SWL-STF-%03d', $nextNum);

        $ins = $pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, role, status, staff_code, job_title, passport_photo)
             VALUES (:fn, :em, :ph, :pw, "staff", "active", :sc, :jt, :pp)'
        );
        $ins->execute([
            ':fn' => $fullName,
            ':em' => $email,
            ':ph' => $phone,
            ':pw' => password_hash($password, PASSWORD_BCRYPT),
            ':sc' => $staffCode,
            ':jt' => $jobTitle,
            ':pp' => $passportPath,
        ]);

        swl_log_activity((int)$authUser['id'], 'admin', 'staff.created', 'Added new staff account ' . $fullName . ' (' . $staffCode . ').');
        swl_flash('success', 'Staff member ' . $fullName . ' (' . $staffCode . ') created successfully!');
        swl_redirect('admin/staff.php');
    }

    // 2. Suspend or Reactivate Staff Member
    if ($action === 'toggle_suspend') {
        $staffId   = (int)($_POST['staff_id'] ?? 0);
        $newStatus = ($_POST['target_status'] ?? 'suspended') === 'suspended' ? 'suspended' : 'active';
        $reason    = trim($_POST['suspension_reason'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'staff' LIMIT 1");
        $stmt->execute([':id' => $staffId]);
        $target = $stmt->fetch();

        if ($target) {
            $pdo->prepare('UPDATE users SET status = :st, suspension_reason = :rs WHERE id = :id')
                ->execute([
                    ':st' => $newStatus,
                    ':rs' => $newStatus === 'suspended' ? ($reason ?: 'Suspended by Management') : null,
                    ':id' => $staffId,
                ]);
            swl_log_activity(
                (int)$authUser['id'],
                'admin',
                'staff.' . $newStatus,
                'Administrator set staff ' . $target['full_name'] . ' status to ' . strtoupper($newStatus)
            );
            swl_flash(
                $newStatus === 'suspended' ? 'warning' : 'success',
                $newStatus === 'suspended'
                    ? $target['full_name'] . ' has been SUSPENDED and can no longer access the Staff Portal.'
                    : $target['full_name'] . ' has been REACTIVATED and granted portal access again.'
            );
        }
        swl_redirect('admin/staff.php');
    }

    // 3. Delete Staff Member
    if ($action === 'delete_staff') {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'staff'")->execute([':id' => $staffId]);
        swl_flash('info', 'Staff account removed.');
        swl_redirect('admin/staff.php');
    }
}

$staffList = $pdo->query(
    "SELECT u.*,
            (SELECT COUNT(*) FROM orders o WHERE o.assigned_staff_id = u.id) AS assigned_count
     FROM users u
     WHERE u.role = 'staff'
     ORDER BY u.id DESC"
)->fetchAll();

$dashTitle = 'Staff Management & Suspension Control';
$activeTab = 'staff';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="grid-2" style="grid-template-columns:0.82fr 1.18fr;align-items:start;">
  <!-- Create Staff Member Form -->
  <div class="card">
    <span class="eyebrow"><?= swl_icon('users', 14) ?> Onboard Team Member</span>
    <h3>Add New Staff Account</h3>
    <p style="font-size:0.86rem;">Staff members receive dedicated login access to process orders, update garment stages, and upload their passport photograph.</p>

    <form method="post" enctype="multipart/form-data">
      <?= swl_csrf_field() ?>
      <input type="hidden" name="staff_action" value="create_staff">

      <div class="form-group">
        <label>Staff Full Name *</label>
        <input type="text" name="full_name" class="form-control" placeholder="e.g. Chinedu Obi" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Staff Email *</label>
          <input type="email" name="email" class="form-control" placeholder="staff2@snowwhitelaundry.com.ng" required>
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="0803...">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Role / Department Title</label>
          <input type="text" name="job_title" class="form-control" placeholder="e.g. Dry Cleaning Specialist" value="Garment Care Specialist">
        </div>
        <div class="form-group">
          <label>Temporary Password *</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 chars" minlength="6" required>
        </div>
      </div>

      <div class="form-group">
        <label>Passport Photograph (Optional — staff can also upload in their dashboard)</label>
        <input type="file" name="passport_photo" accept="image/jpeg,image/png,image/webp" class="form-control">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">Create Staff Account</button>
    </form>
  </div>

  <!-- Staff Directory & Suspend / Reactivate Controls -->
  <div class="card">
    <span class="eyebrow"><?= swl_icon('shield', 14) ?> Access &amp; Disciplinary Control</span>
    <h3>Snow White Laundry Staff Directory (<?= count($staffList) ?>)</h3>
    <p style="font-size:0.86rem;">Suspending a staff member immediately blocks login access and terminates any active session.</p>

    <div style="display:grid;gap:16px;margin-top:18px;">
      <?php foreach ($staffList as $st): ?>
        <?php $sAvatar = swl_avatar_url($st); ?>
        <div style="padding:18px;border:1.5px solid <?= $st['status'] === 'suspended' ? '#fca5a5' : 'var(--border-subtle)' ?>;border-radius:14px;background:<?= $st['status'] === 'suspended' ? '#fff5f5' : 'var(--off-white)' ?>;">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:14px;">
              <div class="passport-avatar">
                <?php if ($sAvatar): ?>
                  <img src="<?= e($sAvatar) ?>" alt="<?= e($st['full_name']) ?>">
                <?php else: ?>
                  <span><?= e(swl_initials($st['full_name'])) ?></span>
                <?php endif; ?>
              </div>
              <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                  <strong style="color:var(--deep-navy);font-size:1rem;"><?= e($st['full_name']) ?></strong>
                  <span class="badge badge-<?= e($st['status']) ?>"><?= e(strtoupper($st['status'])) ?></span>
                  <?php if (!empty($st['staff_code'])): ?>
                    <span class="badge badge-confirmed"><?= e($st['staff_code']) ?></span>
                  <?php endif; ?>
                </div>
                <div style="font-size:0.83rem;color:var(--text-body);margin-top:2px;">
                  <?= e($st['job_title'] ?: 'Staff') ?> &bull; <?= e($st['email']) ?>
                  <?= !empty($st['phone']) ? ' &bull; ' . e($st['phone']) : '' ?>
                </div>
                <div style="font-size:0.78rem;color:var(--text-subtle);margin-top:2px;">
                  Assigned Orders: <strong><?= (int)$st['assigned_count'] ?></strong>
                  <?php if ($st['status'] === 'suspended' && !empty($st['suspension_reason'])): ?>
                    &bull; <span style="color:#b91c1c;">Reason: <?= e($st['suspension_reason']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Suspend / Reactivate Form -->
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <?php if ($st['status'] === 'active'): ?>
                <form method="post" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0;">
                  <?= swl_csrf_field() ?>
                  <input type="hidden" name="staff_action" value="toggle_suspend">
                  <input type="hidden" name="staff_id" value="<?= (int)$st['id'] ?>">
                  <input type="hidden" name="target_status" value="suspended">
                  <input type="text" name="suspension_reason" class="form-control" placeholder="Reason (optional)" style="width:165px;padding:7px 10px;font-size:0.82rem;">
                  <button type="submit" class="btn btn-warning btn-sm">
                    <?= swl_icon('ban', 14) ?> Suspend Staff
                  </button>
                </form>
              <?php else: ?>
                <form method="post" style="margin:0;">
                  <?= swl_csrf_field() ?>
                  <input type="hidden" name="staff_action" value="toggle_suspend">
                  <input type="hidden" name="staff_id" value="<?= (int)$st['id'] ?>">
                  <input type="hidden" name="target_status" value="active">
                  <button type="submit" class="btn btn-success btn-sm">
                    <?= swl_icon('check', 14) ?> Reactivate Staff
                  </button>
                </form>
              <?php endif; ?>

              <form method="post" style="margin:0;" onsubmit="return confirm('Permanently delete this staff account?');">
                <?= swl_csrf_field() ?>
                <input type="hidden" name="staff_action" value="delete_staff">
                <input type="hidden" name="staff_id" value="<?= (int)$st['id'] ?>">
                <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);">Remove</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
