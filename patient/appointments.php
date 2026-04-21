<?php
session_start();
require_once '../config.php';
$required_role = 'patient';
require_once '../includes/auth-guard.php';

$patient_id = $_SESSION['user_id'];
$page_title = 'My Appointments';
$extra_css  = ['dashboard.css'];

$filter_status = $_GET['status'] ?? 'all';

$where = "a.patient_id = ?";
$params = [$patient_id];
$types  = 'i';

if ($filter_status !== 'all') {
    $where .= " AND a.status = ?";
    $params[] = $filter_status;
    $types   .= 's';
}

$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason, a.notes,
               u.full_name AS doctor_name, dep.name AS department, dr.specialisation
        FROM appointments a
        JOIN doctors dr ON a.doctor_id = dr.id
        JOIN users u    ON dr.user_id  = u.id
        JOIN departments dep ON dr.department_id = dep.id
        WHERE $where
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

include '../includes/header.php';
include '../includes/sidebar-patient.php';
?>

<div class="page-header">
  <h1>My Appointments</h1>
  <a href="<?= BASE_URL ?>/patient/book.php" class="btn btn-primary btn-sm">
    <i class="fa fa-plus"></i> Book New
  </a>
</div>

<!-- Filter bar -->
<div class="filter-bar">
  <label style="font-size:.84rem;font-weight:600;color:var(--text-muted)">Filter by Status:</label>
  <?php
  $statuses = ['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved',
               'declined' => 'Declined', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
  foreach ($statuses as $val => $label):
    $active = ($filter_status === $val) ? 'btn-primary' : 'btn-outline';
  ?>
  <a href="?status=<?= $val ?>" class="btn btn-sm <?= $active ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <?php if (empty($appointments)): ?>
    <div class="empty-state">
      <i class="fa fa-calendar-times"></i>
      <p>No appointments found. <a href="<?= BASE_URL ?>/patient/book.php">Book your first appointment.</a></p>
    </div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Date</th><th>Time</th><th>Doctor</th><th>Department</th>
          <th>Reason</th><th>Status</th><th>Notes</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($appointments as $a): ?>
        <tr>
          <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
          <td><?= htmlspecialchars(date('g:i A', strtotime($a['appointment_time']))) ?></td>
          <td><?= htmlspecialchars($a['doctor_name']) ?></td>
          <td><?= htmlspecialchars($a['department']) ?></td>
          <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= $a['reason'] ? htmlspecialchars(substr($a['reason'], 0, 60)) : '<span style="color:var(--text-muted)">—</span>' ?>
          </td>
          <td><span class="badge badge-<?= $a['status'] ?>"><?= $a['status'] ?></span></td>
          <td style="max-width:140px;font-size:.8rem;color:var(--text-muted);">
            <?= $a['notes'] ? htmlspecialchars(substr($a['notes'], 0, 60)) : '—' ?>
          </td>
          <td>
            <?php if (in_array($a['status'], ['pending', 'approved'])
                      && $a['appointment_date'] >= date('Y-m-d')): ?>
            <button class="btn btn-danger btn-sm" data-confirm-form="cancelForm<?= $a['id'] ?>">
              <i class="fa fa-times"></i> Cancel
            </button>
            <form id="cancelForm<?= $a['id'] ?>" method="POST" action="<?= BASE_URL ?>/patient/cancel.php" style="display:none;">
              <?php $_SESSION['csrf_token_cancel'] = bin2hex(random_bytes(32)); ?>
              <input type="hidden" name="csrf_token"      value="<?= htmlspecialchars($_SESSION['csrf_token_cancel']) ?>">
              <input type="hidden" name="appointment_id"  value="<?= $a['id'] ?>">
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

<!-- Confirm cancel modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal">
    <h3>Cancel Appointment?</h3>
    <p>This action cannot be undone. The time slot will be released back for other patients.</p>
    <div class="modal-actions">
      <button class="btn btn-danger" id="confirmModalOk">Yes, Cancel It</button>
      <button class="btn btn-outline" onclick="closeModal('confirmModal')">Keep It</button>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
