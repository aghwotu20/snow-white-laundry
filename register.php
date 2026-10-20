<?php
/**
 * Website Visitor / Customer Registration (with Optional Immediate Passport Photograph Upload)
 */
require_once __DIR__ . '/includes/bootstrap.php';
swl_enforce_public_maintenance();

$pdo = swl_db();
if (swl_current_user()) {
    swl_redirect(swl_dashboard_path(swl_current_user()['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
        swl_flash('error', 'Please fill in your full name, valid email address, and phone number.');
        swl_redirect('register.php');
    }
    if (strlen($password) < 6 || $password !== $confirm) {
        swl_flash('error', 'Passwords must match and be at least 6 characters long.');
        swl_redirect('register.php');
    }

    $chk = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
    $chk->execute([':e' => $email]);
    if ($chk->fetch()) {
        swl_flash('error', 'An account with this email already exists. Please sign in instead.');
        swl_redirect('login.php?role=visitor');
    }

    $passportPath = null;
    if (!empty($_FILES['passport_photo']['name'] ?? '')) {
        $up = swl_upload_image($_FILES['passport_photo'], 'passports', 'visitor-signup', 5242880, false);
        if (!$up['ok']) {
            swl_flash('error', $up['error']);
            swl_redirect('register.php');
        }
        $passportPath = $up['path'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = $pdo->prepare(
        'INSERT INTO users (full_name, email, phone, password_hash, role, status, address, passport_photo, last_login_at)
         VALUES (:fn, :em, :ph, :pw, "visitor", "active", :ad, :pp, NOW())'
    );
    $ins->execute([
        ':fn' => $fullName,
        ':em' => $email,
        ':ph' => $phone,
        ':pw' => $hash,
        ':ad' => $address,
        ':pp' => $passportPath,
    ]);
    $newId = (int)$pdo->lastInsertId();

    // Link any previous guest orders placed under this email address!
    $pdo->prepare('UPDATE orders SET user_id = :uid WHERE customer_email = :em AND user_id IS NULL')
        ->execute([':uid' => $newId, ':em' => $email]);

    session_regenerate_id(true);
    $_SESSION['user_id']   = $newId;
    $_SESSION['user_role'] = 'visitor';

    swl_log_activity($newId, 'visitor', 'auth.registered', 'New customer account registered: ' . $fullName . ' (' . $email . ')');
    swl_flash('success', 'Welcome to Snow White Laundry, ' . $fullName . '! Your customer portal is ready.');
    swl_redirect('visitor/dashboard.php');
}

$pageTitle = 'Create Visitor / Customer Account';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
  <div class="container" style="max-width:740px;">
    <div class="card" style="padding:38px;">
      <span class="eyebrow"><?= swl_icon('user', 14) ?> Free Customer Portal</span>
      <h2 style="margin-bottom:6px;">Create Your Snow White Laundry Account</h2>
      <p style="font-size:0.9rem;margin-bottom:24px;">
        Track laundry progress live, pay securely via Paystack, save pickup addresses, and upload your profile passport photograph anytime.
      </p>

      <form method="post" enctype="multipart/form-data">
        <?= swl_csrf_field() ?>

        <div class="form-row">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" class="form-control" placeholder="e.g. Chidinma Okafor" required>
          </div>
          <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Phone / WhatsApp Number *</label>
            <input type="text" name="phone" class="form-control" placeholder="e.g. 08031234567" required>
          </div>
          <div class="form-group">
            <label>Pickup / Delivery Address</label>
            <input type="text" name="address" class="form-control" placeholder="Street & area">
          </div>
        </div>

        <div class="form-group">
          <label>Passport Photograph (Optional now — can also be uploaded inside your Dashboard)</label>
          <input type="file" name="passport_photo" accept="image/jpeg,image/png,image/webp" class="form-control">
          <span class="form-hint">JPG, PNG, or WEBP up to 5 MB.</span>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Password (min. 6 characters) *</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
          </div>
          <div class="form-group">
            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:6px;">
          Create Account &amp; Enter Dashboard &rarr;
        </button>
      </form>

      <div style="text-align:center;margin-top:20px;font-size:0.9rem;">
        Already registered? <a href="<?= e(swl_base_url('login.php?role=visitor')) ?>" style="font-weight:700;">Sign In Here</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
