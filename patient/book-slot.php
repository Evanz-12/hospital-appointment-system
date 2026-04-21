<?php
session_start();
require_once '../config.php';
$required_role = 'patient';
require_once '../includes/auth-guard.php';

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
if (!$doctor_id) {
    header("Location: " . BASE_URL . "/patient/book.php");
    exit();
}

// Fetch doctor details
$stmt = mysqli_prepare($conn,
    "SELECT dr.id, dr.available_days, dr.slot_duration, dr.department_id,
            u.full_name, dr.specialisation, dep.name AS department
     FROM doctors dr
     JOIN users u ON dr.user_id = u.id
     JOIN departments dep ON dr.department_id = dep.id
     WHERE dr.id = ? AND u.is_active = 1");
mysqli_stmt_bind_param($stmt, 'i', $doctor_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$doctor) {
    header("Location: " . BASE_URL . "/patient/book.php");
    exit();
}

$page_title = 'Book Appointment — Step 3';
$extra_css  = ['dashboard.css', 'forms.css'];
$extra_js   = ['booking.js'];

include '../includes/header.php';
include '../includes/sidebar-patient.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="step-indicator">
  <div class="step completed"><div class="step-circle"><i class="fa fa-check"></i></div><div class="step-label">Department</div></div>
  <div class="step completed"><div class="step-circle"><i class="fa fa-check"></i></div><div class="step-label">Doctor</div></div>
  <div class="step active"><div class="step-circle">3</div><div class="step-label">Date & Time</div></div>
  <div class="step"><div class="step-circle">4</div><div class="step-label">Confirm</div></div>
</div>

<div style="margin-bottom:16px;">
  <a href="<?= BASE_URL ?>/patient/book-doctor.php?dept_id=<?= $doctor['department_id'] ?>" style="font-size:.85rem;color:var(--text-muted);">
    <i class="fa fa-chevron-left"></i> Back to Doctors
  </a>
</div>

<div class="card" style="max-width:600px;">
  <!-- Doctor summary -->
  <div style="display:flex;align-items:center;gap:14px;padding-bottom:18px;border-bottom:1px solid var(--border);margin-bottom:20px;">
    <div style="width:48px;height:48px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.3rem;">
      <i class="fa fa-user-md"></i>
    </div>
    <div>
      <h3 style="font-size:.95rem;font-weight:700;font-family:'DM Sans',sans-serif;"><?= htmlspecialchars($doctor['full_name']) ?></h3>
      <p style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($doctor['department']) ?> &mdash; <?= htmlspecialchars($doctor['specialisation'] ?? '') ?></p>
      <p style="font-size:.78rem;color:var(--primary);margin-top:3px;">Available: <?= htmlspecialchars($doctor['available_days']) ?></p>
    </div>
  </div>

  <form method="POST" action="<?= BASE_URL ?>/patient/book-confirm.php" id="slotForm">
    <?php
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="doctor_id"  value="<?= $doctor_id ?>">
    <input type="hidden" id="selected_slot" name="appointment_time" value="">
    <input type="hidden" id="doctor_id_value" value="<?= $doctor_id ?>">

    <div class="field">
      <label for="appointment_date">Select Date <span class="required">*</span></label>
      <input type="date" id="appointment_date" name="appointment_date" required>
      <p class="field-hint">Available days: <?= htmlspecialchars($doctor['available_days']) ?></p>
    </div>

    <div class="field">
      <label>Select Time Slot <span class="required">*</span></label>
      <div class="slot-grid" id="slotGrid">
        <p style="color:var(--text-muted);font-size:.85rem;">Pick a date above to see available slots.</p>
      </div>
    </div>

    <div class="field">
      <label for="reason">Reason for Visit <span style="color:var(--text-muted);font-weight:400">(optional)</span></label>
      <textarea id="reason" name="reason" rows="3" placeholder="Briefly describe your symptoms or reason for visit…"></textarea>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        Continue to Review <i class="fa fa-chevron-right"></i>
      </button>
    </div>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
