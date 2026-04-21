<?php
session_start();
require_once '../config.php';
$required_role = 'doctor';
require_once '../includes/auth-guard.php';

$user_id    = $_SESSION['user_id'];
$page_title = 'Doctor Dashboard';
$extra_css  = ['dashboard.css'];

// Get doctor record
$stmt = mysqli_prepare($conn, "SELECT id, department_id, specialisation FROM doctors WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$doctor) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Doctor profile not found.'];
    header("Location: " . BASE_URL . "/auth/logout.php");
    exit();
}
$doctor_id = $doctor['id'];
$today = date('Y-m-d');

// Today's appointments
$stmt2 = mysqli_prepare($conn,
    "SELECT a.id, a.appointment_time, a.status, a.reason, u.full_name AS patient_name, u.phone
     FROM appointments a JOIN users u ON a.patient_id = u.id
     WHERE a.doctor_id=? AND a.appointment_date=? AND a.status IN ('approved','pending')
     ORDER BY a.appointment_time");
mysqli_stmt_bind_param($stmt2, 'is', $doctor_id, $today);
mysqli_stmt_execute($stmt2);
$today_appts = mysqli_stmt_get_result($stmt2)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

// Stats
$stmt3 = mysqli_prepare($conn,
    "SELECT
       SUM(appointment_date = ?) AS today_count,
       SUM(status='pending')  AS pending_count,
       SUM(status='approved' AND appointment_date >= ?) AS upcoming_count,
       SUM(status='completed') AS completed_count
     FROM appointments WHERE doctor_id=?");
mysqli_stmt_bind_param($stmt3, 'ssi', $today, $today, $doctor_id);
mysqli_stmt_execute($stmt3);
$stats = mysqli_stmt_get_result($stmt3)->fetch_assoc();
mysqli_stmt_close($stmt3);

include '../includes/header.php';
include '../includes/sidebar-doctor.php';
?>

<div class="welcome-banner">
  <div>
    <h2>Good day, <?= htmlspecialchars($_SESSION['name']) ?></h2>
    <p>Here is your schedule overview for today — <?= date('l, d F Y') ?>.</p>
  </div>
  <i class="fa fa-user-md banner-icon"></i>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon teal"><i class="fa fa-calendar-day"></i></div>
    <div class="stat-info"><h3><?= (int)$stats['today_count'] ?></h3><p>Today's Appointments</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa fa-clock"></i></div>
    <div class="stat-info"><h3><?= (int)$stats['pending_count'] ?></h3><p>Pending</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-calendar-check"></i></div>
    <div class="stat-info"><h3><?= (int)$stats['upcoming_count'] ?></h3><p>Upcoming (Approved)</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa fa-check-double"></i></div>
    <div class="stat-info"><h3><?= (int)$stats['completed_count'] ?></h3><p>Completed</p></div>
  </div>
</div>

<div class="card">
  <div class="card-title"><i class="fa fa-calendar-day"></i> Today's Schedule
    <a href="<?= BASE_URL ?>/doctor/schedule.php" style="margin-left:auto;font-size:.82rem;font-weight:500;">Full schedule &rarr;</a>
  </div>
  <?php if (empty($today_appts)): ?>
    <div class="empty-state">
      <i class="fa fa-calendar-check"></i>
      <p>No appointments scheduled for today.</p>
    </div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Time</th><th>Patient</th><th>Phone</th><th>Reason</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($today_appts as $a): ?>
        <tr>
          <td><strong><?= htmlspecialchars(date('g:i A', strtotime($a['appointment_time']))) ?></strong></td>
          <td><?= htmlspecialchars($a['patient_name']) ?></td>
          <td><?= htmlspecialchars($a['phone'] ?? '—') ?></td>
          <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= $a['reason'] ? htmlspecialchars(substr($a['reason'], 0, 60)) : '<span style="color:var(--text-muted)">—</span>' ?>
          </td>
          <td><span class="badge badge-<?= $a['status'] ?>"><?= $a['status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
