<?php
session_start();
require_once '../config.php';
$required_role = 'patient';
require_once '../includes/auth-guard.php';

$patient_id = $_SESSION['user_id'];
$page_title = 'My Profile';
$extra_css  = ['forms.css'];

// Fetch current data
$stmt = mysqli_prepare($conn, "SELECT full_name, email, phone FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, 'i', $patient_id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request.';
    } else {
        $full_name   = trim($_POST['full_name'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $new_pass    = $_POST['new_password'] ?? '';
        $confirm     = $_POST['confirm_password'] ?? '';
        $current     = $_POST['current_password'] ?? '';

        if (empty($full_name)) $errors[] = 'Full name is required.';

        if (!empty($new_pass)) {
            if (strlen($new_pass) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($new_pass !== $confirm) {
                $errors[] = 'New passwords do not match.';
            } else {
                // Verify current password
                $stmt2 = mysqli_prepare($conn, "SELECT password FROM users WHERE id=?");
                mysqli_stmt_bind_param($stmt2, 'i', $patient_id);
                mysqli_stmt_execute($stmt2);
                $pw_row = mysqli_stmt_get_result($stmt2)->fetch_assoc();
                mysqli_stmt_close($stmt2);
                if (!password_verify($current, $pw_row['password'])) {
                    $errors[] = 'Current password is incorrect.';
                }
            }
        }

        if (empty($errors)) {
            if (!empty($new_pass)) {
                $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt3  = mysqli_prepare($conn, "UPDATE users SET full_name=?, phone=?, password=? WHERE id=?");
                mysqli_stmt_bind_param($stmt3, 'sssi', $full_name, $phone, $hashed, $patient_id);
            } else {
                $stmt3 = mysqli_prepare($conn, "UPDATE users SET full_name=?, phone=? WHERE id=?");
                mysqli_stmt_bind_param($stmt3, 'ssi', $full_name, $phone, $patient_id);
            }
            if (mysqli_stmt_execute($stmt3)) {
                $_SESSION['name'] = $full_name;
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
                header("Location: " . BASE_URL . "/patient/profile.php");
                exit();
            } else {
                $errors[] = 'Update failed. Please try again.';
            }
            mysqli_stmt_close($stmt3);
        }

        // Update local var for redisplay
        $user['full_name'] = $full_name;
        $user['phone']     = $phone;
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
include '../includes/header.php';
include '../includes/sidebar-patient.php';
?>

<div class="page-header"><h1>My Profile</h1></div>

<div class="form-card">
  <?php if ($errors): ?>
    <div class="error-msg" style="margin-bottom:18px;border-radius:var(--radius);padding:12px 14px;">
      <i class="fa fa-exclamation-circle"></i>
      <ul style="margin:0;padding-left:16px;">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="avatar-placeholder"><i class="fa fa-user"></i></div>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <p class="form-section-title">Personal Information</p>
    <div class="field">
      <label>Full Name <span class="required">*</span></label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
    </div>
    <div class="fields-row">
      <div class="field">
        <label>Email Address</label>
        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        <p class="field-hint">Email cannot be changed.</p>
      </div>
      <div class="field">
        <label>Phone Number</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08012345678">
      </div>
    </div>

    <p class="form-section-title">Change Password <span style="font-size:.75rem;font-weight:400;color:var(--text-muted)">(leave blank to keep current)</span></p>
    <div class="field">
      <label>Current Password</label>
      <input type="password" name="current_password" placeholder="Enter current password">
    </div>
    <div class="fields-row">
      <div class="field">
        <label>New Password</label>
        <input type="password" name="new_password" placeholder="Min. 6 characters">
      </div>
      <div class="field">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" placeholder="Repeat new password">
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
    </div>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
