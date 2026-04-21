<?php
session_start();
require_once '../config.php';
$required_role = 'patient';
require_once '../includes/auth-guard.php';

$page_title = 'Book Appointment — Step 1';
$extra_css  = ['dashboard.css', 'forms.css'];

// Clear any leftover booking session data
unset($_SESSION['booking']);

$stmt = mysqli_prepare($conn, "SELECT d.id, d.name, d.description, COUNT(dr.id) AS doctor_count
    FROM departments d LEFT JOIN doctors dr ON dr.department_id = d.id
    LEFT JOIN users u ON dr.user_id = u.id AND u.is_active = 1
    GROUP BY d.id ORDER BY d.name");
mysqli_stmt_execute($stmt);
$departments = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$dept_icons = [
    'General Medicine' => 'fa-stethoscope', 'Cardiology' => 'fa-heartbeat',
    'Paediatrics' => 'fa-baby', 'Gynaecology' => 'fa-venus',
    'Orthopaedics' => 'fa-bone', 'Dermatology' => 'fa-allergies',
    'ENT' => 'fa-ear-deaf', 'Ophthalmology' => 'fa-eye',
];

include '../includes/header.php';
include '../includes/sidebar-patient.php';
?>

<!-- Step indicator -->
<div class="step-indicator">
  <div class="step active"><div class="step-circle">1</div><div class="step-label">Department</div></div>
  <div class="step"><div class="step-circle">2</div><div class="step-label">Doctor</div></div>
  <div class="step"><div class="step-circle">3</div><div class="step-label">Date & Time</div></div>
  <div class="step"><div class="step-circle">4</div><div class="step-label">Confirm</div></div>
</div>

<div class="card">
  <div class="card-title"><i class="fa fa-hospital"></i> Choose a Department</div>
  <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:20px;">Select the medical department that matches your health needs.</p>

  <div class="select-grid">
    <?php foreach ($departments as $dept):
      $icon = $dept_icons[$dept['name']] ?? 'fa-hospital';
    ?>
    <a href="<?= BASE_URL ?>/patient/book-doctor.php?dept_id=<?= $dept['id'] ?>" class="select-card">
      <i class="fa <?= $icon ?>"></i>
      <h3><?= htmlspecialchars($dept['name']) ?></h3>
      <p><?= (int)$dept['doctor_count'] ?> doctor<?= $dept['doctor_count'] != 1 ? 's' : '' ?> available</p>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
