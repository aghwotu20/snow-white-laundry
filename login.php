<?php
/**
 * Unified Portal Login Page for Website Visitors, Staff Members, and Administrators
 * Note: Accessible even when Maintenance Mode is enabled so Administrators can sign in to turn Maintenance Mode OFF!
 */
require_once __DIR__ . '/includes/bootstrap.php';

$pdo      = swl_db();
$currUser = swl_current_user();
if ($currUser && !(swl_is_maintenance() && $currUser['role'] !== 'admin')) {
    swl_redirect(swl_dashboard_path($currUser['role']));
}

$selectedRole = in_array($_GET['role'] ?? '', ['visitor', 'staff', 'admin'], true) ? $_GET['role'] : 'visitor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $portalRole = in_array($_POST['portal_role'] ?? '', ['visitor', 'staff', 'admin'], true) ? $_POST['portal_role'] : 'visitor';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        swl_flash('error', 'Invalid email address or password. Please try again.');
        swl_redirect('login.php?role=' . urlencode($portalRole));
    }

    if ($user['role'] !== $portalRole) {
        swl_flash(
            'warning',
            'Your account belongs to the ' . strtoupper($user['role']) . ' portal. Please sign in under the ' . ucfirst($user['role']) . ' tab.'
        );
        swl_redirect('login.php?role=' . urlencode($user['role']));
    }

    // Enforce Staff / User Suspension immediately at login!
    if ($user['status'] === 'suspended') {
        $reason = !empty($user['suspension_reason']) ? ' Reason: ' . $user['suspension_reason'] : '';
        swl_log_activity((int)$user['id'], $user['role'], 'auth.suspended_blocked', 'Blocked login attempt by suspended user ' . $user['email']);
        swl_flash('error', 'Access Denied: Your ' . $user['role'] . ' account has been suspended by the Administrator.' . $reason);
        swl_redirect('login.php?role=' . urlencode($portalRole));
    }

    // If Maintenance Mode is ON, only allow Administrators in!
    if (swl_is_maintenance() && $user['role'] !== 'admin') {
        swl_flash('warning', 'The website is currently in Maintenance Mode. Only Administrators may sign in until maintenance concludes.');
        swl_redirect('login.php?role=admin');
    }

    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];

    $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute([':id' => $user['id']]);
    swl_log_activity((int)$user['id'], $user['role'], 'auth.login', $user['full_name'] . ' signed in to ' . ucfirst($user['role']) . ' Dashboard.');
    swl_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
    swl_redirect(swl_dashboard_path($user['role']));
}

$pageTitle = 'Portal Sign In — Visitor, Staff & Admin';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
  <div class="container">
    <div class="auth-wrapper">
      <!-- LEFT PANEL: PORTAL GUIDE + QUICK DEMO CREDENTIALS -->
      <div class="auth-promo">
        <div>
          <span class="eyebrow" style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.2);color:#7dd3fc;">
            Unified Multi-Role Access
          </span>
          <h2>Sign In to Your Snow White Laundry Portal</h2>
          <p>
            Manage laundry bookings, upload your official passport photograph, pay online via Paystack, handle operations, or configure website settings—all in one secure portal.
          </p>

          <?php if (swl_is_maintenance()): ?>
            <div class="alert alert-warning" style="margin-top:16px;">
              <?= swl_icon('alert', 18) ?>
              <span><strong>Maintenance Mode Active:</strong> Sign in under the <strong>Admin</strong> tab to manage site settings or put the website back live.</span>
            </div>
          <?php endif; ?>
        </div>

        <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.14);">
          <div style="font-size:0.76rem;text-transform:uppercase;letter-spacing:0.06em;color:#7dd3fc;font-weight:800;">
            Quick Test Credentials (Click Any Card to Auto-Fill)
          </div>
          <div class="demo-pill-grid">
            <button type="button" class="demo-pill" onclick="fillDemoLogin('visitor','customer@snowwhitelaundry.com.ng','Customer@123')">
              <strong>1. Website Visitor / Customer Portal</strong>
              <span>customer@snowwhitelaundry.com.ng &bull; Customer@123</span>
            </button>
            <button type="button" class="demo-pill" onclick="fillDemoLogin('staff','staff@snowwhitelaundry.com.ng','Staff@123')">
              <strong>2. Staff Operations Portal</strong>
              <span>staff@snowwhitelaundry.com.ng &bull; Staff@123</span>
            </button>
            <button type="button" class="demo-pill" onclick="fillDemoLogin('admin','admin@snowwhitelaundry.com.ng','Admin@123')">
              <strong>3. Super Admin Control Dashboard</strong>
              <span>admin@snowwhitelaundry.com.ng &bull; Admin@123</span>
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT PANEL: ROLE-SELECTOR LOGIN FORM -->
      <div class="card" style="padding:36px;">
        <h3 style="font-size:1.45rem;margin-bottom:6px;">Account Sign In</h3>
        <p style="font-size:0.88rem;margin-bottom:18px;">Select your portal role below and enter your login credentials.</p>

        <div class="role-tabs" role="tablist">
          <button type="button" class="role-tab <?= $selectedRole === 'visitor' ? 'active' : '' ?>" data-role="visitor" onclick="selectPortalRole('visitor')">
            Visitor / Client
          </button>
          <button type="button" class="role-tab <?= $selectedRole === 'staff' ? 'active' : '' ?>" data-role="staff" onclick="selectPortalRole('staff')">
            Staff Member
          </button>
          <button type="button" class="role-tab <?= $selectedRole === 'admin' ? 'active' : '' ?>" data-role="admin" onclick="selectPortalRole('admin')">
            Administrator
          </button>
        </div>

        <form method="post">
          <?= swl_csrf_field() ?>
          <input type="hidden" name="portal_role" id="portalRoleInput" value="<?= e($selectedRole) ?>">

          <div class="form-group">
            <label for="loginEmail">Email Address</label>
            <input type="email" id="loginEmail" name="email" class="form-control" placeholder="name@example.com" required autofocus>
          </div>

          <div class="form-group">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <label for="loginPassword">Password</label>
              <button type="button" onclick="swlToggleSecret('loginPassword', this)" style="background:none;border:none;color:var(--blue-accent);font-size:0.8rem;font-weight:700;cursor:pointer;">Show</button>
            </div>
            <input type="password" id="loginPassword" name="password" class="form-control" placeholder="Enter your password" required>
          </div>

          <button type="submit" id="loginSubmitBtn" class="btn btn-primary btn-lg" style="width:100%;margin-top:8px;">
            Sign In to <?= e(ucfirst($selectedRole)) ?> Dashboard &rarr;
          </button>
        </form>

        <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--border-subtle);text-align:center;font-size:0.9rem;">
          New to Snow White Laundry?
          <a href="<?= e(swl_base_url('register.php')) ?>" style="font-weight:700;">Create a Free Visitor Account</a>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
function selectPortalRole(role) {
  document.getElementById('portalRoleInput').value = role;
  document.querySelectorAll('.role-tab').forEach((btn) => {
    btn.classList.toggle('active', btn.dataset.role === role);
  });
  const labels = { visitor: 'Visitor', staff: 'Staff', admin: 'Admin' };
  document.getElementById('loginSubmitBtn').innerHTML = 'Sign In to ' + (labels[role] || 'Portal') + ' Dashboard &rarr;';
}
function fillDemoLogin(role, email, pass) {
  selectPortalRole(role);
  document.getElementById('loginEmail').value = email;
  document.getElementById('loginPassword').value = pass;
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
