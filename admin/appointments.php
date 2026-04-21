<?php
session_start();
require_once '../config.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

$page_title = 'Manage Appointments';
$extra_css  = ['dashboard.css'];

$filter_status = $_GET['status'] ?? 'all';
$filter_date   = $_GET['date']   ?? '';
$filter_doctor = (int)($_GET['doctor'] ?? 0);

$where  = ['1=1'];
$params = [];
$types  = '';

if ($filter_status !== 'all') {
    $where[]  = 'a.status = ?';
    $params[] = $filter_status;
    $types   .= 's';
}
if ($filter_date) {
    $where[]  = 'a.appointment_date = ?';
    $params[] = $filter_date;
    $types   .= 's';
}
if ($filter_doctor) {
    $where[]  = 'a.doctor_id = ?';
    $params[] = $filter_doctor;
    $types   .= 'i';
}

$where_sql = implode(' AND ', $where);

$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason, a.notes,
               pu.full_name AS patient_name, du.full_name AS doctor_name, dep.name AS department
        FROM appointments a
        JOIN users pu ON a.patient_id = pu.id
        JOIN doctors dr ON a.doctor_id = dr.id
        JOIN users du ON dr.user_id = du.id
        JOIN departments dep ON dr.department_id = dep.id
        WHERE $where_sql
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $appointments = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $appointments = mysqli_query($conn, $sql)->fetch_all(MYSQLI_ASSOC);
}

// Doctors list for filter
$doctors = mysqli_query($conn,
    "SELECT dr.id, u.full_name FROM doctors dr JOIN users u ON dr.user_id=u.id ORDER BY u.full_name")
    ->fetch_all(MYSQLI_ASSOC);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<div class="page-header">
  <h1>All Appointments</h1>
</div>

<form method="GET" class="filter-bar">
  <select name="status">
    <option value="all" <?= $filter_status==='all'?'selected':'' ?>>All Statuses</option>
    <?php foreach(['pending','approved','declined','completed','cancelled'] as $s): ?>
    <option value="<?= $s ?>" <?= $filter_status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
  <select name="doctor">
    <option value="0">All Doctors</option>
    <?php foreach ($doctors as $d): ?>
    <option value="<?= $d['id'] ?>" <?= $filter_doctor===$d['id']?'selected':'' ?>><?= htmlspecialchars($d['full_name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filter</button>
  <a href="?" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="card">
  <?php if (empty($appointments)): ?>
    <div class="empty-state"><i class="fa fa-calendar-times"></i><p>No appointments found matching your filters.</p></div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>ID</th><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Dept</th><th>Reason</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($appointments as $a): ?>
        <tr id="appt-<?= $a['id'] ?>">
          <td style="color:var(--text-muted);font-size:.8rem;">#<?= $a['id'] ?></td>
          <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
          <td><?= htmlspecialchars(date('g:i A', strtotime($a['appointment_time']))) ?></td>
          <td><?= htmlspecialchars($a['patient_name']) ?></td>
          <td><?= htmlspecialchars($a['doctor_name']) ?></td>
          <td><?= htmlspecialchars($a['department']) ?></td>
          <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.8rem;">
            <?= $a['reason'] ? htmlspecialchars(substr($a['reason'], 0, 50)) : '—' ?>
          </td>
          <td><span class="badge badge-<?= $a['status'] ?>"><?= $a['status'] ?></span></td>
          <td style="white-space:nowrap;">
            <?php if ($a['status'] === 'pending'): ?>
            <form method="POST" action="<?= BASE_URL ?>/admin/approve.php" style="display:inline;">
              <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="appointment_id"  value="<?= $a['id'] ?>">
              <input type="hidden" name="action"          value="approve">
              <button class="btn btn-success btn-sm" type="submit"><i class="fa fa-check"></i> Approve</button>
            </form>
            <form method="POST" action="<?= BASE_URL ?>/admin/approve.php" style="display:inline;">
              <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="appointment_id"  value="<?= $a['id'] ?>">
              <input type="hidden" name="action"          value="decline">
              <button class="btn btn-danger btn-sm" type="submit"><i class="fa fa-times"></i> Decline</button>
            </form>
            <?php elseif ($a['status'] === 'approved'): ?>
            <form method="POST" action="<?= BASE_URL ?>/admin/approve.php" style="display:inline;">
              <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="appointment_id"  value="<?= $a['id'] ?>">
              <input type="hidden" name="action"          value="complete">
              <button class="btn btn-sm btn-outline" type="submit"><i class="fa fa-check-double"></i> Complete</button>
            </form>
            <?php else: ?>
            <span style="color:var(--text-muted);font-size:.8rem;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
