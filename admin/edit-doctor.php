<?php
session_start();
require_once '../config.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

$page_title = 'Edit Doctor';
$extra_css  = ['forms.css'];
$errors     = [];

$doctor_id = (int)($_GET['id'] ?? $_POST['doctor_id'] ?? 0);
if (!$doctor_id) { header("Location: " . BASE_URL . "/admin/doctors.php"); exit(); }

// Fetch doctor
$stmt = mysqli_prepare($conn,
    "SELECT u.id AS user_id, u.full_name, u.email, u.phone, u.is_active,
            dr.id, dr.department_id, dr.specialisation, dr.bio, dr.available_days, dr.slot_duration
     FROM doctors dr JOIN users u ON dr.user_id=u.id WHERE dr.id=?");
mysqli_stmt_bind_param($stmt, 'i', $doctor_id);
mysqli_stmt_execute($stmt);
$doc = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$doc) { header("Location: " . BASE_URL . "/admin/doctors.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request.';
    } else {
        $full_name      = trim($_POST['full_name'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $dept_id        = (int)($_POST['department_id'] ?? 0);
        $specialisation = trim($_POST['specialisation'] ?? '');
        $bio            = trim($_POST['bio'] ?? '');
        $avail_days     = $_POST['available_days'] ?? [];
        $slot_duration  = (int)($_POST['slot_duration'] ?? 30);
        $new_password   = $_POST['new_password'] ?? '';

        if (empty($full_name)) $errors[] = 'Full name is required.';
        if (!$dept_id)         $errors[] = 'Department is required.';
        if (empty($avail_days))$errors[] = 'Select at least one available day.';

        if (empty($errors)) {
            $avail_str = implode(',', $avail_days);

            // Update user
            if (!empty($new_password) && strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $s = mysqli_prepare($conn, "UPDATE users SET full_name=?, phone=?, password=? WHERE id=?");
                mysqli_stmt_bind_param($s, 'sssi', $full_name, $phone, $hashed, $doc['user_id']);
            } else {
                $s = mysqli_prepare($conn, "UPDATE users SET full_name=?, phone=? WHERE id=?");
                mysqli_stmt_bind_param($s, 'ssi', $full_name, $phone, $doc['user_id']);
            }
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);

            // Update doctor
            $s2 = mysqli_prepare($conn,
                "UPDATE doctors SET department_id=?, specialisation=?, bio=?, available_days=?, slot_duration=? WHERE id=?");
            mysqli_stmt_bind_param($s2, 'isssii', $dept_id, $specialisation, $bio, $avail_str, $slot_duration, $doctor_id);
            if (mysqli_stmt_execute($s2)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Doctor updated successfully.'];
                header("Location: " . BASE_URL . "/admin/doctors.php");
                exit();
            } else {
                $errors[] = 'Update failed.';
            }
            mysqli_stmt_close($s2);

            // Refresh for display
            $doc['full_name'] = $full_name; $doc['phone'] = $phone;
            $doc['department_id'] = $dept_id; $doc['specialisation'] = $specialisation;
            $doc['bio'] = $bio; $doc['available_days'] = $avail_str; $doc['slot_duration'] = $slot_duration;
        }
    }
}

$departments  = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$days_of_week = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$selected_days = array_map('trim', explode(',', $doc['available_days']));
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<div class="page-header">
  <h1>Edit Doctor</h1>
  <a href="<?= BASE_URL ?>/admin/doctors.php" class="btn btn-outline btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<div class="form-card">
  <?php if ($errors): ?>
    <div class="error-msg" style="margin-bottom:18px;"><i class="fa fa-exclamation-circle"></i>
      <ul style="margin:0;padding-left:16px;"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="doctor_id"  value="<?= $doctor_id ?>">

    <p class="form-section-title">Account Details</p>
    <div class="fields-row">
      <div class="field">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($doc['full_name']) ?>" required>
      </div>
      <div class="field">
        <label>Email (read-only)</label>
        <input type="email" value="<?= htmlspecialchars($doc['email']) ?>" disabled>
      </div>
    </div>
    <div class="fields-row">
      <div class="field">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($doc['phone'] ?? '') ?>">
      </div>
      <div class="field">
        <label>New Password <span style="color:var(--text-muted);font-weight:400">(leave blank to keep)</span></label>
        <input type="password" name="new_password" placeholder="Min. 6 characters">
      </div>
    </div>

    <p class="form-section-title">Professional Profile</p>
    <div class="fields-row">
      <div class="field">
        <label>Department <span class="required">*</span></label>
        <select name="department_id" required>
          <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $doc['department_id'] == $d['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Specialisation</label>
        <input type="text" name="specialisation" value="<?= htmlspecialchars($doc['specialisation'] ?? '') ?>">
      </div>
    </div>
    <div class="field">
      <label>Bio</label>
      <textarea name="bio" rows="3"><?= htmlspecialchars($doc['bio'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label>Available Days <span class="required">*</span></label>
      <div class="check-group">
        <?php foreach ($days_of_week as $day): ?>
        <label class="check-label">
          <input type="checkbox" name="available_days[]" value="<?= $day ?>"
                 <?= in_array($day, $selected_days) ? 'checked' : '' ?>>
          <?= $day ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field" style="max-width:200px;">
      <label>Slot Duration (minutes)</label>
      <select name="slot_duration">
        <?php foreach ([15,20,30,45,60] as $dur): ?>
        <option value="<?= $dur ?>" <?= $doc['slot_duration'] == $dur ? 'selected' : '' ?>><?= $dur ?> min</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
      <a href="<?= BASE_URL ?>/admin/doctors.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
