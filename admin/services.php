<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$authUser = swl_require_auth('admin');
$pdo      = swl_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    swl_verify_csrf();
    $action = $_POST['srv_action'] ?? '';

    if ($action === 'add_service') {
        $name  = trim($_POST['name'] ?? '');
        $cat   = trim($_POST['category'] ?? 'Laundry Care');
        $unit  = trim($_POST['unit_label'] ?? 'Per Item');
        $price = max(0, (float)($_POST['price_ngn'] ?? 0));
        $turn  = trim($_POST['turnaround_hours'] ?? '24 – 48 Hours');
        $short = trim($_POST['short_description'] ?? '');
        $full  = trim($_POST['full_description'] ?? $short);

        if ($name !== '' && $short !== '') {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')) . '-' . random_int(10, 99);
            $ins = $pdo->prepare(
                'INSERT INTO services (name, slug, category, short_description, full_description, unit_label, price_ngn, turnaround_hours, icon_name, is_featured, is_active)
                 VALUES (:n, :s, :c, :sd, :fd, :u, :p, :t, "shirt", 1, 1)'
            );
            $ins->execute([
                ':n'  => $name,
                ':s'  => $slug,
                ':c'  => $cat,
                ':sd' => $short,
                ':fd' => $full ?: $short,
                ':u'  => $unit,
                ':p'  => $price,
                ':t'  => $turn,
            ]);
            swl_log_activity((int)$authUser['id'], 'admin', 'service.created', 'Added new service: ' . $name);
            swl_flash('success', 'Service "' . $name . '" published to website!');
        }
        swl_redirect('admin/services.php');
    }

    if ($action === 'update_service') {
        $sid    = (int)($_POST['service_id'] ?? 0);
        $price  = max(0, (float)($_POST['price_ngn'] ?? 0));
        $active = !empty($_POST['is_active']) ? 1 : 0;
        $pdo->prepare('UPDATE services SET price_ngn = :p, is_active = :a WHERE id = :id')
            ->execute([':p' => $price, ':a' => $active, ':id' => $sid]);
        swl_flash('success', 'Service pricing/visibility updated!');
        swl_redirect('admin/services.php');
    }
}

$services  = $pdo->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC')->fetchAll();
$dashTitle = 'Laundry Services & Approved NGN Pricing';
$activeTab = 'services';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="grid-2" style="grid-template-columns:0.85fr 1.15fr;align-items:start;">
  <div class="card">
    <h3>Add New Laundry Service</h3>
    <form method="post">
      <?= swl_csrf_field() ?>
      <input type="hidden" name="srv_action" value="add_service">
      <div class="form-group">
        <label>Service Title *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Wedding Gown Preservation" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Category</label>
          <input type="text" name="category" class="form-control" value="Special Care">
        </div>
        <div class="form-group">
          <label>Price (NGN ₦) *</label>
          <input type="number" name="price_ngn" class="form-control" value="5000" min="0" step="50" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Unit Label</label>
          <input type="text" name="unit_label" class="form-control" value="Per Garment">
        </div>
        <div class="form-group">
          <label>Turnaround</label>
          <input type="text" name="turnaround_hours" class="form-control" value="48 Hours">
        </div>
      </div>
      <div class="form-group">
        <label>Short Website Description *</label>
        <textarea name="short_description" class="form-control" style="min-height:80px;" required placeholder="Clear customer summary..."></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Publish Service</button>
    </form>
  </div>

  <div class="card">
    <h3>Published Service Catalogue</h3>
    <div style="display:grid;gap:12px;margin-top:14px;">
      <?php foreach ($services as $s): ?>
        <form method="post" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 15px;border:1px solid var(--border-subtle);border-radius:11px;background:var(--off-white);flex-wrap:wrap;">
          <?= swl_csrf_field() ?>
          <input type="hidden" name="srv_action" value="update_service">
          <input type="hidden" name="service_id" value="<?= (int)$s['id'] ?>">

          <div style="flex:1;min-width:190px;">
            <strong style="color:var(--deep-navy);"><?= e($s['name']) ?></strong>
            <span style="display:block;font-size:0.78rem;color:var(--text-subtle);"><?= e($s['unit_label']) ?> &bull; <?= e($s['turnaround_hours']) ?></span>
          </div>

          <div style="display:flex;align-items:center;gap:10px;">
            <input type="number" name="price_ngn" value="<?= e((string)(int)$s['price_ngn']) ?>" class="form-control" style="width:110px;padding:7px 10px;font-weight:700;">
            <label style="display:flex;align-items:center;gap:5px;margin:0;font-size:0.8rem;">
              <input type="checkbox" name="is_active" value="1" <?= (int)$s['is_active'] === 1 ? 'checked' : '' ?>> Active
            </label>
            <button type="submit" class="btn btn-outline btn-sm">Save</button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
