<?php
/**
 * Shared Profile & Passport Photograph Upload Module
 * Used inside Visitor Dashboard (`visitor/profile.php`), Staff Dashboard (`staff/profile.php`),
 * and Admin Dashboard (`admin/profile.php`) so EVERY logged-in user can upload their Passport Photograph
 * and manage their complete profile & password.
 */
$authUser = swl_require_auth();
$pdo      = swl_db();
$role     = $authUser['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $action = $_POST['profile_action'] ?? '';

    // 1. Passport Photograph Upload
    if ($action === 'upload_passport') {
        $res = swl_upload_image($_FILES['passport_photo'] ?? [], 'passports', $role . '-user-' . $authUser['id'], 5242880, false);
        if (!$res['ok']) {
            swl_flash('error', $res['error']);
        } else {
            // Delete previous custom uploaded photo if not shared demo seed image
            $old = (string)($authUser['passport_photo'] ?? '');
            if ($old !== '' && !in_array(basename($old), ['admin-passport.jpg', 'staff-passport.jpg', 'visitor-passport.jpg'], true)) {
                $oldFull = SWL_ROOT . '/' . ltrim($old, '/');
                if (is_file($oldFull)) {
                    @unlink($oldFull);
                }
            }
            $stmt = $pdo->prepare('UPDATE users SET passport_photo = :p WHERE id = :id');
            $stmt->execute([':p' => $res['path'], ':id' => $authUser['id']]);
            swl_log_activity((int)$authUser['id'], $role, 'profile.passport_uploaded', $authUser['full_name'] . ' uploaded a new passport photograph.');
            swl_flash('success', 'Your passport photograph has been uploaded and updated across your portal!');
        }
        swl_redirect($role . '/profile.php');
    }

    // 2. Profile Information Update
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = trim($_POST['phone'] ?? '');
        $jobTitle = trim($_POST['job_title'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $cityArea = trim($_POST['city_area'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            swl_flash('error', 'Please enter a valid full name and email address.');
            swl_redirect($role . '/profile.php');
        }

        // Check email uniqueness
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1');
        $chk->execute([':e' => $email, ':id' => $authUser['id']]);
        if ($chk->fetch()) {
            swl_flash('error', 'That email address is already registered to another user.');
            swl_redirect($role . '/profile.php');
        }

        $upd = $pdo->prepare(
            'UPDATE users
             SET full_name = :fn, email = :em, phone = :ph, job_title = :jt, address = :ad, city_area = :ca, bio = :bio
             WHERE id = :id'
        );
        $upd->execute([
            ':fn'  => $fullName,
            ':em'  => $email,
            ':ph'  => $phone,
            ':jt'  => $jobTitle,
            ':ad'  => $address,
            ':ca'  => $cityArea,
            ':bio' => $bio,
            ':id'  => $authUser['id'],
        ]);
        swl_log_activity((int)$authUser['id'], $role, 'profile.updated', $fullName . ' updated their profile details.');
        swl_flash('success', 'Your profile details have been saved successfully.');
        swl_redirect($role . '/profile.php');
    }

    // 3. Password Update
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $authUser['password_hash'])) {
            swl_flash('error', 'Your current password is incorrect.');
            swl_redirect($role . '/profile.php');
        }
        if (strlen($newPass) < 6) {
            swl_flash('error', 'New password must be at least 6 characters long.');
            swl_redirect($role . '/profile.php');
        }
        if ($newPass !== $confirm) {
            swl_flash('error', 'New password and confirmation do not match.');
            swl_redirect($role . '/profile.php');
        }

        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')->execute([
            ':h'  => $hash,
            ':id' => $authUser['id'],
        ]);
        swl_log_activity((int)$authUser['id'], $role, 'profile.password_changed', $authUser['full_name'] . ' changed their account password.');
        swl_flash('success', 'Your password has been updated securely.');
        swl_redirect($role . '/profile.php');
    }
}

$authUser  = swl_current_user(true);
$avatarUrl = swl_avatar_url($authUser);
$dashTitle = 'My Profile & Passport Photograph';
$activeTab = 'profile';
require SWL_ROOT . '/includes/dashboard_header.php';
?>

<div class="grid-2" style="align-items:start;">
  <!-- Left Card: Passport Photograph Upload & Official ID Card Preview -->
  <div class="card">
    <span class="eyebrow"><?= swl_icon('camera', 14) ?> Official Identification</span>
    <h3>Passport Photograph</h3>
    <p style="font-size:0.88rem;">Upload a clear front-facing studio or portrait passport photograph (JPG, PNG, or WEBP up to 5 MB).</p>

    <div style="background:var(--off-white);border:1px dashed var(--ice-border);border-radius:16px;padding:24px;text-align:center;margin:18px 0;">
      <div class="passport-frame-lg">
        <?php if ($avatarUrl): ?>
          <img id="passportLivePreview" src="<?= e($avatarUrl) ?>" alt="Passport Photograph of <?= e($authUser['full_name']) ?>">
        <?php else: ?>
          <img id="passportLivePreview" src="" alt="" style="display:none;">
          <div data-preview-placeholder style="text-align:center;padding:12px;">
            <div style="font-size:2.2rem;font-weight:800;color:var(--blue-accent);"><?= e(swl_initials($authUser['full_name'])) ?></div>
            <div style="font-size:0.75rem;color:var(--text-subtle);">No Passport Uploaded Yet</div>
          </div>
        <?php endif; ?>
      </div>

      <div style="font-weight:800;color:var(--deep-navy);font-size:1.05rem;"><?= e($authUser['full_name']) ?></div>
      <div style="margin-top:4px;display:flex;justify-content:center;gap:8px;flex-wrap:wrap;">
        <span class="badge badge-active"><?= e(strtoupper($role)) ?> PORTAL</span>
        <?php if (!empty($authUser['staff_code'])): ?>
          <span class="badge badge-confirmed"><?= e($authUser['staff_code']) ?></span>
        <?php endif; ?>
      </div>
      <div style="font-size:0.8rem;color:var(--text-subtle);margin-top:8px;">
        Member since <?= e(date('d M Y', strtotime($authUser['created_at']))) ?>
      </div>
    </div>

    <form method="post" enctype="multipart/form-data">
      <?= swl_csrf_field() ?>
      <input type="hidden" name="profile_action" value="upload_passport">
      <div class="form-group">
        <label for="passportFileInput">Select New Passport Photograph</label>
        <input
          type="file"
          id="passportFileInput"
          name="passport_photo"
          accept="image/jpeg,image/png,image/webp"
          class="form-control"
          data-preview-target="passportLivePreview"
          required
        >
        <span class="form-hint">Recommended: Clear portrait headshot (35mm &times; 45mm passport style). Instant preview shown above before saving.</span>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">
        <?= swl_icon('camera', 17) ?> Upload &amp; Save Passport Photograph
      </button>
    </form>

    <hr style="border:none;border-top:1px solid var(--border-subtle);margin:28px 0;">

    <h3>Change Account Password</h3>
    <form method="post">
      <?= swl_csrf_field() ?>
      <input type="hidden" name="profile_action" value="change_password">
      <div class="form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" class="form-control" minlength="6" required>
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" minlength="6" required>
        </div>
      </div>
      <button type="submit" class="btn btn-navy">Update Password</button>
    </form>
  </div>

  <!-- Right Card: Complete Personal & Contact Details -->
  <div class="card">
    <span class="eyebrow"><?= swl_icon('user', 14) ?> Personal &amp; Delivery Profile</span>
    <h3>Account Information</h3>
    <p style="font-size:0.88rem;">Keep your contact phone number and address current for smooth pickup, delivery, and order notifications.</p>

    <form method="post">
      <?= swl_csrf_field() ?>
      <input type="hidden" name="profile_action" value="update_profile">

      <div class="form-row">
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="<?= e($authUser['full_name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Email Address *</label>
          <input type="email" name="email" class="form-control" value="<?= e($authUser['email']) ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Phone / WhatsApp Number</label>
          <input type="text" name="phone" class="form-control" value="<?= e($authUser['phone']) ?>" placeholder="e.g. 08031234567">
        </div>
        <div class="form-group">
          <label><?= $role === 'visitor' ? 'Occupation / Title (Optional)' : 'Designation / Role Title' ?></label>
          <input type="text" name="job_title" class="form-control" value="<?= e($authUser['job_title']) ?>" placeholder="e.g. Senior Garment Specialist">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label><?= $role === 'visitor' ? 'Default Pickup / Delivery Street Address' : 'Residential / Branch Address' ?></label>
          <input type="text" name="address" class="form-control" value="<?= e($authUser['address']) ?>" placeholder="House number & street name">
        </div>
        <div class="form-group">
          <label>Area / City / Department</label>
          <input type="text" name="city_area" class="form-control" value="<?= e($authUser['city_area']) ?>" placeholder="e.g. Victoria Island / Operations">
        </div>
      </div>

      <div class="form-group">
        <label><?= $role === 'visitor' ? 'Fabric Preferences & Care Notes' : 'Profile Bio & Responsibilities' ?></label>
        <textarea name="bio" class="form-control" placeholder="Add starch preference, detergent sensitivity, or staff bio..."><?= e($authUser['bio']) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-lg">
        <?= swl_icon('check', 18) ?> Save Profile Changes
      </button>
    </form>
  </div>
</div>

<?php require SWL_ROOT . '/includes/dashboard_footer.php'; ?>
