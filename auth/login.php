<?php
session_start();
require_once '../config.php';

// Already logged in — redirect to their dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin')   { header("Location: " . BASE_URL . "/admin/dashboard.php");   exit(); }
    if ($role === 'doctor')  { header("Location: " . BASE_URL . "/doctor/dashboard.php");  exit(); }
    if ($role === 'patient') { header("Location: " . BASE_URL . "/patient/dashboard.php"); exit(); }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, full_name, password, role, is_active FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user   = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$user) {
                $error = 'No account found with that email address.';
            } elseif (!$user['is_active']) {
                $error = 'Your account has been deactivated. Please contact the hospital.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Incorrect password. Please try again.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['name']    = $user['full_name'];

                if ($user['role'] === 'admin')   { header("Location: " . BASE_URL . "/admin/dashboard.php");   exit(); }
                if ($user['role'] === 'doctor')  { header("Location: " . BASE_URL . "/doctor/dashboard.php");  exit(); }
                if ($user['role'] === 'patient') { header("Location: " . BASE_URL . "/patient/dashboard.php"); exit(); }
            }
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Hospital Appointment System</title>
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
    <h2>Welcome back</h2>
    <p class="auth-subtitle">Sign in to your account to continue</p>

    <?php if ($error): ?>
      <div class="error-msg"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrap">
          <i class="fa fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <i class="fa fa-lock"></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
        </div>
      </div>

      <div style="text-align:right;margin-bottom:10px;">
        <a href="<?= BASE_URL ?>/auth/forgot-password.php" style="font-size:.82rem;color:var(--text-muted);">Forgot password?</a>
      </div>

      <button type="submit" class="auth-btn">
        <i class="fa fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Register as Patient</a>
    </div>
  </div>
</div>

</body>
</html>
