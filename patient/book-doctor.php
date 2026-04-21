<?php
session_start();
require_once '../config.php';
$required_role = 'patient';
require_once '../includes/auth-guard.php';

$dept_id = (int)($_GET['dept_id'] ?? 0);
if (!$dept_id) {
    header("Location: " . BASE_URL . "/patient/book.php");
    exit();
}

// Fetch department name
$stmt = mysqli_prepare($conn, "SELECT name FROM departments WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $dept_id);
mysqli_stmt_execute($stmt);
$dept = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$dept) {
    header("Location: " . BASE_URL . "/patient/book.php");
    exit();
}

// Fetch doctors in this department
$stmt2 = mysqli_prepare($conn,
    "SELECT dr.id, u.full_name, dr.specialisation, dr.bio, dr.available_days, dr.slot_duration
     FROM doctors dr
     JOIN users u ON dr.user_id = u.id
     WHERE dr.department_id = ? AND u.is_active = 1
     ORDER BY u.full_name");
mysqli_stmt_bind_param($stmt2, 'i', $dept_id);
mysqli_stmt_execute($stmt2);
$doctors = mysqli_stmt_get_result($stmt2)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

$page_title = 'Book Appointment — Step 2';
$extra_css  = ['dashboard.css', 'forms.css'];

include '../includes/header.php';
include '../includes/sidebar-patient.php';
?>

<div class="step-indicator">
  <div class="step completed"><div class="step-circle"><i class="fa fa-check"></i></div><div class="step-label">Department</div></div>
  <div class="step active"><div class="step-circle">2</div><div class="step-label">Doctor</div></div>
  <div class="step"><div class="step-circle">3</div><div class="step-label">Date & Time</div></div>
  <div class="step"><div class="step-circle">4</div><div class="step-label">Confirm</div></div>
</div>

<div style="margin-bottom:16px;">
  <a href="<?= BASE_URL ?>/patient/book.php" style="font-size:.85rem;color:var(--text-muted);">
    <i class="fa fa-chevron-left"></i> Back to Departments
  </a>
</div>

<div class="card">
  <div class="card-title"><i class="fa fa-user-md"></i> Choose a Doctor
    <span style="font-size:.8rem;font-weight:400;color:var(--text-muted);margin-left:8px;">in <?= htmlspecialchars($dept['name']) ?></span>
  </div>

  <?php if (empty($doctors)): ?>
    <div class="empty-state">
      <i class="fa fa-user-slash"></i>
      <p>No doctors available in this department right now. Please try another department.</p>
    </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:14px;">
    <?php foreach ($doctors as $doc): ?>
    <div style="background:var(--bg);border-radius:var(--radius);padding:18px 20px;display:flex;align-items:center;gap:18px;border:1.5px solid var(--border);">
      <div style="width:52px;height:52px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--primary);flex-shrink:0;">
        <i class="fa fa-user-md"></i>
      </div>
      <div style="flex:1;">
        <h3 style="font-size:.95rem;font-weight:700;font-family:'DM Sans',sans-serif;"><?= htmlspecialchars($doc['full_name']) ?></h3>
        <p style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($doc['specialisation'] ?? '') ?></p>
        <?php if ($doc['bio']): ?>
          <p style="font-size:.8rem;color:var(--text-muted);margin-top:4px;"><?= htmlspecialchars(substr($doc['bio'], 0, 120)) ?>…</p>
        <?php endif; ?>
        <p style="font-size:.78rem;color:var(--primary);margin-top:5px;">
          <i class="fa fa-calendar-week"></i> <?= htmlspecialchars($doc['available_days']) ?> &nbsp;
          <i class="fa fa-clock"></i> <?= $doc['slot_duration'] ?>-min slots
        </p>
      </div>
      <a href="<?= BASE_URL ?>/patient/book-slot.php?doctor_id=<?= $doc['id'] ?>" class="btn btn-primary btn-sm">
        Select <i class="fa fa-chevron-right"></i>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
