<?php
session_start();
require_once '../config.php';
require_once '../includes/mailer.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php"); exit();
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim(strtolower($_POST['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, full_name FROM users WHERE email=? AND is_active=1");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $user = mysqli_stmt_get_result($stmt)->fetch_assoc();
            mysqli_stmt_close($stmt);

            // Always show success to avoid email enumeration
            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt2 = mysqli_prepare($conn,
                    "UPDATE users SET password_reset_token=?, password_reset_expires=? WHERE id=?");
                mysqli_stmt_bind_param($stmt2, 'ssi', $token, $expires, $user['id']);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);

                email_password_reset($email, $user['full_name'], $token);
            }
            $success = 'If an account exists with that email, a reset link has been sent. Check your inbox (and spam folder).';
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
  <title>Forgot Password — MediBook</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
  <div class="auth-brand">
    <i class="fa fa-hospital-o"></i>
    <h1>MediBook</h1>
    <p>Hospital Appointment Booking System</p>
  </div>

  <div class="auth-card">
    <h2>Forgot Password?</h2>
    <p class="auth-subtitle">Enter your email and we'll send you a reset link.</p>

    <?php if ($error): ?>
      <div class="error-msg"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success-msg"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrap">
          <i class="fa fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
      </div>
      <button type="submit" class="auth-btn">
        <i class="fa fa-paper-plane"></i> Send Reset Link
      </button>
    </form>
    <?php endif; ?>

    <div class="auth-footer">
      <a href="<?= BASE_URL ?>/auth/login.php"><i class="fa fa-arrow-left"></i> Back to Login</a>
    </div>
  </div>
</div>

</body>
</html>
