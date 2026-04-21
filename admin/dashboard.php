<?php
session_start();
require_once '../config.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

$page_title = 'Admin Dashboard';
$extra_css  = ['dashboard.css'];

$today = date('Y-m-d');

// Stats
$total_patients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='patient'"))['c'];
$total_doctors  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='doctor' AND is_active=1"))['c'];
$today_appts    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$today'"))['c'];
$pending_appts  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='pending'"))['c'];

// Recent appointments
$recent = mysqli_query($conn,
    "SELECT a.id, a.appointment_date, a.appointment_time, a.status,
            pu.full_name AS patient_name, du.full_name AS doctor_name
     FROM appointments a
     JOIN users pu ON a.patient_id = pu.id
     JOIN doctors dr ON a.doctor_id = dr.id
     JOIN users du ON dr.user_id = du.id
     ORDER BY a.created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<div class="welcome-banner">
  <div>
    <h2>Admin Dashboard</h2>
    <p>Overview of the hospital appointment system — <?= date('l, d F Y') ?>.</p>
  </div>
  <i class="fa fa-hospital-o banner-icon"></i>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon teal"><i class="fa fa-users"></i></div>
    <div class="stat-info"><h3><?= $total_patients ?></h3><p>Total Patients</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa fa-user-md"></i></div>
    <div class="stat-info"><h3><?= $total_doctors ?></h3><p>Active Doctors</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-calendar-day"></i></div>
    <div class="stat-info"><h3><?= $today_appts ?></h3><p>Appointments Today</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa fa-clock"></i></div>
    <div class="stat-info"><h3><?= $pending_appts ?></h3><p>Pending Requests</p></div>
  </div>
</div>

<div class="card">
  <div class="card-title"><i class="fa fa-calendar-alt"></i> Recent Appointments
    <a href="<?= BASE_URL ?>/admin/appointments.php" style="margin-left:auto;font-size:.82rem;font-weight:500;">View all &rarr;</a>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $a): ?>
        <tr>
          <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
          <td><?= htmlspecialchars(date('g:i A', strtotime($a['appointment_time']))) ?></td>
          <td><?= htmlspecialchars($a['patient_name']) ?></td>
          <td><?= htmlspecialchars($a['doctor_name']) ?></td>
          <td><span class="badge badge-<?= $a['status'] ?>"><?= $a['status'] ?></span></td>
          <td>
            <?php if ($a['status'] === 'pending'): ?>
            <a href="<?= BASE_URL ?>/admin/appointments.php#appt-<?= $a['id'] ?>" class="btn btn-sm btn-primary">Review</a>
            <?php else: ?>
            <span style="color:var(--text-muted);font-size:.8rem;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
