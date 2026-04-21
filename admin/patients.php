<?php
session_start();
require_once '../config.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

$page_title = 'Registered Patients';
$extra_css  = ['dashboard.css'];

$search = trim($_GET['search'] ?? '');

if ($search) {
    $like  = '%' . $search . '%';
    $stmt  = mysqli_prepare($conn,
        "SELECT u.id, u.full_name, u.email, u.phone, u.created_at, u.is_active,
                COUNT(a.id) AS appt_count
         FROM users u LEFT JOIN appointments a ON a.patient_id = u.id
         WHERE u.role='patient' AND (u.full_name LIKE ? OR u.email LIKE ?)
         GROUP BY u.id ORDER BY u.full_name");
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $patients = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $patients = mysqli_query($conn,
        "SELECT u.id, u.full_name, u.email, u.phone, u.created_at, u.is_active,
                COUNT(a.id) AS appt_count
         FROM users u LEFT JOIN appointments a ON a.patient_id = u.id
         WHERE u.role='patient'
         GROUP BY u.id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<div class="page-header">
  <h1>Patients</h1>
  <span style="font-size:.85rem;color:var(--text-muted);"><?= count($patients) ?> registered</span>
</div>

<form method="GET" class="filter-bar">
  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email…">
  <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
  <?php if ($search): ?>
  <a href="?" class="btn btn-outline btn-sm">Clear</a>
  <?php endif; ?>
</form>

<div class="card">
  <?php if (empty($patients)): ?>
    <div class="empty-state"><div class="empty-icon"><i class="fa fa-users"></i></div><h3>No patients found</h3><p>Patients will appear here once they register.</p></div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Appointments</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($patients as $p): ?>
        <tr>
          <td><strong><?= htmlspecialchars($p['full_name']) ?></strong></td>
          <td><?= htmlspecialchars($p['email']) ?></td>
          <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
          <td style="font-size:.82rem;color:var(--text-muted);"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
          <td><?= $p['appt_count'] ?></td>
          <td><span class="badge <?= $p['is_active'] ? 'badge-approved' : 'badge-cancelled' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
