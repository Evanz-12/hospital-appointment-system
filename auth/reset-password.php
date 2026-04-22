<?php
session_start();
require_once '../config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php"); exit();
}

$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$valid = false;
$user    = null;

if (empty($token)) {
    header("Location: " . BASE_URL . "/auth/forgot-password.php"); exit();
}

// Validate token
$stmt = mysqli_prepare($conn,
    "SELECT id, full_name, email FROM users
     WHERE password_reset_token=? AND password_reset_expires > NOW() AND is_active=1");
mysqli_stmt_bind_param($stmt, 's', $token);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$user) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
} else {
    $valid = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request.';
    } else {
        $new_pass = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (strlen($new_pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($new_pass !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt2  = mysqli_prepare($conn,
                "UPDATE users SET password=?, password_reset_token=NULL, password_reset_expires=NULL WHERE id=?");
            mysqli_stmt_bind_param($stmt2, 'si', $hashed, $user['id']);
            if (mysqli_stmt_execute($stmt2)) {
                mysqli_stmt_close($stmt2);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password updated! You can now sign in with your new password.'];
                header("Location: " . BASE_URL . "/auth/login.php"); exit();
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
            mysqli_stmt_close($stmt2);
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — MediBook</title>
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-left">
  <div class="auth-deco-circle"></div>
  <div class="auth-brand-panel">
    <div class="logo-mark"><svg viewBox="0 0 24 24" fill="none" width="32" height="32" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="2" width="4" height="20" rx="2" fill="white"/><rect x="2" y="10" width="20" height="4" rx="2" fill="white"/></svg></div>
    <h1>MediBook</h1>
    <p>Crawford University Hospital<br>Appointment Booking System</p>
  </div>
  <div class="auth-quote">
    <blockquote>"The good physician treats the disease; the great physician treats the patient who has the disease."</blockquote>
    <cite>— William Osler</cite>
  </div>
</div>

<div class="auth-right">
  <div class="auth-form-wrap">
    <div class="auth-form-header">
      <h2>Set New Password</h2>
      <p>Choose a strong password for your account.</p>
    </div>

    <?php if ($error): ?>
      <div class="error-msg"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($valid): ?>
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="token"      value="<?= htmlspecialchars($token) ?>">

      <div class="form-group">
        <label for="new_password">New Password</label>
        <div class="input-wrap">
          <i class="fa fa-lock"></i>
          <input type="password" id="new_password" name="new_password" placeholder="Min. 6 characters" required>
        </div>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm New Password</label>
        <div class="input-wrap">
          <i class="fa fa-lock"></i>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
        </div>
      </div>
      <button type="submit" class="auth-btn">
        <i class="fa fa-save"></i> Reset Password
      </button>
    </form>
    <?php endif; ?>

    <div class="auth-footer">
      <a href="<?= BASE_URL ?>/auth/forgot-password.php">Request a new reset link</a>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
