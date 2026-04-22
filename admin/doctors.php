<?php
session_start();
require_once '../config.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

$page_title = 'Manage Doctors';
$extra_css  = ['dashboard.css'];

// Toggle active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
        header("Location: " . BASE_URL . "/admin/doctors.php"); exit();
    }
    $uid    = (int)$_POST['user_id'];
    $active = (int)$_POST['current_active'] === 1 ? 0 : 1;
    $stmt = mysqli_prepare($conn, "UPDATE users SET is_active=? WHERE id=? AND role='doctor'");
    mysqli_stmt_bind_param($stmt, 'ii', $active, $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Doctor status updated.'];
    header("Location: " . BASE_URL . "/admin/doctors.php"); exit();
}

$doctors = mysqli_query($conn,
    "SELECT u.id AS user_id, u.full_name, u.email, u.phone, u.is_active,
            dr.id AS doctor_id, dr.specialisation, dr.available_days, dep.name AS department
     FROM users u
     JOIN doctors dr ON dr.user_id = u.id
     JOIN departments dep ON dep.id = dr.department_id
     ORDER BY u.full_name")->fetch_all(MYSQLI_ASSOC);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<div class="page-header">
  <h1>Doctors</h1>
  <a href="<?= BASE_URL ?>/admin/add-doctor.php" class="btn btn-primary btn-sm">
    <i class="fa fa-user-plus"></i> Add Doctor
  </a>
</div>

<div class="card">
  <?php if (empty($doctors)): ?>
    <div class="empty-state"><div class="empty-icon"><i class="fa fa-user-md"></i></div><h3>No doctors yet</h3><p><a href="<?= BASE_URL ?>/admin/add-doctor.php">Add your first doctor</a> to get started.</p></div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Doctor</th>
          <th>Department</th>
          <th>Available Days</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($doctors as $d):
          $days = array_map('trim', explode(',', $d['available_days']));
          $days_str = implode(' &middot; ', array_map('htmlspecialchars', $days));
        ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($d['full_name']) ?></strong>
            <span style="display:block;font-size:.78rem;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($d['email']) ?></span>
          </td>
          <td>
            <?= htmlspecialchars($d['department']) ?>
            <span style="display:block;font-size:.78rem;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($d['specialisation'] ?? '—') ?></span>
          </td>
          <td style="font-size:.8rem;color:var(--text-muted);"><?= $days_str ?></td>
          <td>
            <span class="badge <?= $d['is_active'] ? 'badge-approved' : 'badge-cancelled' ?>">
              <?= $d['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td style="white-space:nowrap;">
            <a href="<?= BASE_URL ?>/admin/edit-doctor.php?id=<?= $d['doctor_id'] ?>" class="btn btn-sm btn-outline">
              <i class="fa fa-edit"></i> Edit
            </a>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="user_id"         value="<?= $d['user_id'] ?>">
              <input type="hidden" name="current_active"  value="<?= $d['is_active'] ?>">
              <input type="hidden" name="toggle_active"   value="1">
              <button class="btn btn-sm <?= $d['is_active'] ? 'btn-danger' : 'btn-success' ?>" type="submit">
                <i class="fa <?= $d['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                <?= $d['is_active'] ? 'Deactivate' : 'Activate' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
