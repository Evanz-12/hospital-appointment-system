<?php
session_start();
require_once '../config.php';
$required_role = 'patient';
require_once '../includes/auth-guard.php';

$patient_id = $_SESSION['user_id'];
$page_title = 'Patient Dashboard';
$extra_css  = ['dashboard.css'];

// Upcoming appointments (approved/pending)
$stmt = mysqli_prepare($conn,
    "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason,
            u.full_name AS doctor_name, d.specialisation, dep.name AS department
     FROM appointments a
     JOIN doctors dr ON a.doctor_id = dr.id
     JOIN users u    ON dr.user_id  = u.id
     JOIN departments dep ON dr.department_id = dep.id
     LEFT JOIN doctors d ON d.id = dr.id
     WHERE a.patient_id = ? AND a.status IN ('pending','approved')
       AND a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date, a.appointment_time
     LIMIT 5");
mysqli_stmt_bind_param($stmt, 'i', $patient_id);
mysqli_stmt_execute($stmt);
$upcoming = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Stats
$stmt2 = mysqli_prepare($conn, "SELECT status, COUNT(*) as cnt FROM appointments WHERE patient_id = ? GROUP BY status");
mysqli_stmt_bind_param($stmt2, 'i', $patient_id);
mysqli_stmt_execute($stmt2);
$stats_raw = mysqli_stmt_get_result($stmt2)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);
$stats = [];
foreach ($stats_raw as $r) { $stats[$r['status']] = $r['cnt']; }

include '../includes/header.php';
include '../includes/sidebar-patient.php';
?>

<div class="welcome-banner">
  <div>
    <h2>Hello, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h2>
    <p>Welcome to your health dashboard. Manage your appointments and stay on track.</p>
    <a href="<?= BASE_URL ?>/patient/book.php" class="btn btn-primary" style="margin-top:14px">
      <i class="fa fa-calendar-plus"></i> Book New Appointment
    </a>
  </div>
  <i class="fa fa-stethoscope banner-icon"></i>
</div>

<!-- Stats row -->
<div class="stats-grid" style="margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon teal"><i class="fa fa-calendar-check"></i></div>
    <div class="stat-info"><h3><?= ($stats['approved'] ?? 0) + ($stats['pending'] ?? 0) ?></h3><p>Upcoming</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa fa-clock"></i></div>
    <div class="stat-info"><h3><?= $stats['pending'] ?? 0 ?></h3><p>Pending</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-check-circle"></i></div>
    <div class="stat-info"><h3><?= $stats['completed'] ?? 0 ?></h3><p>Completed</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa fa-times-circle"></i></div>
    <div class="stat-info"><h3><?= $stats['cancelled'] ?? 0 ?></h3><p>Cancelled</p></div>
  </div>
</div>

<!-- Upcoming appointments -->
<div class="card">
  <div class="card-title"><i class="fa fa-calendar-alt"></i> Upcoming Appointments
    <a href="<?= BASE_URL ?>/patient/appointments.php" style="margin-left:auto;font-size:.82rem;font-weight:500;">View all &rarr;</a>
  </div>
  <?php if (empty($upcoming)): ?>
    <div class="empty-state">
      <i class="fa fa-calendar-times"></i>
      <p>No upcoming appointments. <a href="<?= BASE_URL ?>/patient/book.php">Book one now.</a></p>
    </div>
  <?php else: ?>
    <div class="appt-mini-list">
      <?php foreach ($upcoming as $a):
        $date = new DateTime($a['appointment_date']);
      ?>
      <div class="appt-mini-item status-<?= $a['status'] ?>">
        <div class="appt-mini-date">
          <div class="day"><?= $date->format('d') ?></div>
          <div class="mon"><?= $date->format('M') ?></div>
        </div>
        <div class="appt-mini-info" style="flex:1">
          <h4><?= htmlspecialchars($a['doctor_name']) ?></h4>
          <p><?= htmlspecialchars($a['department']) ?> &mdash; <?= date('g:i A', strtotime($a['appointment_time'])) ?></p>
        </div>
        <span class="badge badge-<?= $a['status'] ?>"><?= $a['status'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
