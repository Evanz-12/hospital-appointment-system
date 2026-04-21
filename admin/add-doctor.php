<?php
session_start();
require_once '../config.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

$page_title = 'Add Doctor';
$extra_css  = ['forms.css'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request.';
    } else {
        $full_name     = trim($_POST['full_name'] ?? '');
        $email         = trim(strtolower($_POST['email'] ?? ''));
        $phone         = trim($_POST['phone'] ?? '');
        $password      = $_POST['password'] ?? '';
        $dept_id       = (int)($_POST['department_id'] ?? 0);
        $specialisation= trim($_POST['specialisation'] ?? '');
        $bio           = trim($_POST['bio'] ?? '');
        $avail_days    = $_POST['available_days'] ?? [];
        $slot_duration = (int)($_POST['slot_duration'] ?? 30);

        if (empty($full_name))  $errors[] = 'Full name is required.';
        if (empty($email))      $errors[] = 'Email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if (!$dept_id)          $errors[] = 'Please select a department.';
        if (empty($avail_days)) $errors[] = 'Please select at least one available day.';

        if (empty($errors)) {
            // Check email unique
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($exists) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $hashed       = password_hash($password, PASSWORD_BCRYPT);
                $avail_str    = implode(',', $avail_days);

                // Insert user
                $stmt2 = mysqli_prepare($conn,
                    "INSERT INTO users (full_name, email, password, phone, role) VALUES (?,?,?,?,'doctor')");
                mysqli_stmt_bind_param($stmt2, 'ssss', $full_name, $email, $hashed, $phone);
                mysqli_stmt_execute($stmt2);
                $new_user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt2);

                // Insert doctor profile
                $stmt3 = mysqli_prepare($conn,
                    "INSERT INTO doctors (user_id, department_id, specialisation, bio, available_days, slot_duration)
                     VALUES (?,?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt3, 'iisssi',
                    $new_user_id, $dept_id, $specialisation, $bio, $avail_str, $slot_duration);
                if (mysqli_stmt_execute($stmt3)) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => "Doctor $full_name added successfully."];
                    header("Location: " . BASE_URL . "/admin/doctors.php");
                    exit();
                } else {
                    $errors[] = 'Failed to save doctor profile.';
                    // Rollback user insert
                    $stmt_del = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
                    mysqli_stmt_bind_param($stmt_del, 'i', $new_user_id);
                    mysqli_stmt_execute($stmt_del);
                    mysqli_stmt_close($stmt_del);
                }
                mysqli_stmt_close($stmt3);
            }
        }
    }
}

$departments = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$days_of_week = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<div class="page-header">
  <h1>Add New Doctor</h1>
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

    <p class="form-section-title">Account Credentials</p>
    <div class="fields-row">
      <div class="field">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Email Address <span class="required">*</span></label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
    </div>
    <div class="fields-row">
      <div class="field">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="08012345678">
      </div>
      <div class="field">
        <label>Login Password <span class="required">*</span></label>
        <input type="password" name="password" placeholder="Min. 6 characters" required>
      </div>
    </div>

    <p class="form-section-title">Professional Profile</p>
    <div class="fields-row">
      <div class="field">
        <label>Department <span class="required">*</span></label>
        <select name="department_id" required>
          <option value="">Select department…</option>
          <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($_POST['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Specialisation</label>
        <input type="text" name="specialisation" value="<?= htmlspecialchars($_POST['specialisation'] ?? '') ?>" placeholder="e.g. General Practitioner">
      </div>
    </div>
    <div class="field">
      <label>Bio</label>
      <textarea name="bio" rows="3" placeholder="Brief professional biography…"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label>Available Days <span class="required">*</span></label>
      <div class="check-group">
        <?php
        $selected_days = $_POST['available_days'] ?? ['Mon','Tue','Wed','Thu','Fri'];
        foreach ($days_of_week as $day):
        ?>
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
        <option value="<?= $dur ?>" <?= ($_POST['slot_duration'] ?? 30) == $dur ? 'selected' : '' ?>><?= $dur ?> min</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Create Doctor Account</button>
      <a href="<?= BASE_URL ?>/admin/doctors.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
