<?php
session_start();
require_once '../config.php';
$required_role = 'doctor';
require_once '../includes/auth-guard.php';

$user_id    = $_SESSION['user_id'];
$page_title = 'My Profile';
$extra_css  = ['forms.css'];

// Fetch user + doctor data
$stmt = mysqli_prepare($conn,
    "SELECT u.full_name, u.email, u.phone, dr.specialisation, dr.bio, dr.available_days, dr.slot_duration, dep.name AS department
     FROM users u
     JOIN doctors dr ON dr.user_id = u.id
     JOIN departments dep ON dep.id = dr.department_id
     WHERE u.id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$profile = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

include '../includes/header.php';
include '../includes/sidebar-doctor.php';
?>

<div class="page-header"><h1>My Profile</h1></div>

<div class="form-card">
  <div class="avatar-placeholder"><i class="fa fa-user-md"></i></div>
  <h2 style="font-size:1.1rem;font-weight:700;font-family:'DM Sans',sans-serif;margin-bottom:4px;">
    <?= htmlspecialchars($profile['full_name']) ?>
  </h2>
  <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:20px;">
    <?= htmlspecialchars($profile['department']) ?> — <?= htmlspecialchars($profile['specialisation'] ?? '') ?>
  </p>

  <p class="form-section-title">Contact Information</p>
  <div class="fields-row">
    <div class="field">
      <label>Email</label>
      <input type="email" value="<?= htmlspecialchars($profile['email']) ?>" disabled>
    </div>
    <div class="field">
      <label>Phone</label>
      <input type="tel" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" disabled>
    </div>
  </div>

  <p class="form-section-title">Professional Details</p>
  <div class="fields-row">
    <div class="field">
      <label>Department</label>
      <input type="text" value="<?= htmlspecialchars($profile['department']) ?>" disabled>
    </div>
    <div class="field">
      <label>Specialisation</label>
      <input type="text" value="<?= htmlspecialchars($profile['specialisation'] ?? '') ?>" disabled>
    </div>
  </div>
  <div class="fields-row">
    <div class="field">
      <label>Available Days</label>
      <input type="text" value="<?= htmlspecialchars($profile['available_days']) ?>" disabled>
    </div>
    <div class="field">
      <label>Slot Duration</label>
      <input type="text" value="<?= htmlspecialchars($profile['slot_duration']) ?> minutes" disabled>
    </div>
  </div>
  <div class="field">
    <label>Bio</label>
    <textarea rows="4" disabled><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
  </div>

  <p style="font-size:.82rem;color:var(--text-muted);margin-top:10px;">
    <i class="fa fa-info-circle"></i> To update your professional details, please contact the hospital administrator.
  </p>
</div>

<?php include '../includes/footer.php'; ?>
