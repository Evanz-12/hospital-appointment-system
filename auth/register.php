<?php
session_start();
require_once '../config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/patient/dashboard.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        $full_name        = trim($_POST['full_name'] ?? '');
        $email            = trim(strtolower($_POST['email'] ?? ''));
        $phone            = trim($_POST['phone'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($full_name) || empty($email) || empty($password)) {
            $error = 'Full name, email, and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($exists) {
                $error = 'An account with that email already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt2  = mysqli_prepare($conn,
                    "INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'patient')");
                mysqli_stmt_bind_param($stmt2, 'ssss', $full_name, $email, $hashed, $phone);
                if (mysqli_stmt_execute($stmt2)) {
                    $success = 'Account created successfully! You can now log in.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                mysqli_stmt_close($stmt2);
            }
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
  <title>Create Account — MediBook</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">

<!-- Left Panel -->
<div class="auth-left">
  <div class="auth-deco-circle"></div>
  <div class="auth-brand-panel">
    <div class="logo-mark"><i class="fa fa-hospital-o"></i></div>
    <h1>MediBook</h1>
    <p>Crawford University Hospital<br>Appointment Booking System</p>
  </div>
  <div class="auth-quote">
    <blockquote>"Wherever the art of medicine is loved, there is also a love of humanity."</blockquote>
    <cite>— Hippocrates</cite>
  </div>
  <div class="auth-features">
    <div class="auth-feature-item">
      <i class="fa fa-user-md"></i>
      Access specialist doctors
    </div>
    <div class="auth-feature-item">
      <i class="fa fa-clock"></i>
      Choose convenient time slots
    </div>
    <div class="auth-feature-item">
      <i class="fa fa-envelope"></i>
      Get email confirmations
    </div>
  </div>
</div>

<!-- Right Panel -->
<div class="auth-right">
  <div class="auth-form-wrap">
    <div class="auth-form-header">
      <h2>Create your account</h2>
      <p>Register as a patient to book appointments online</p>
    </div>

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
        <label for="full_name">Full Name</label>
        <div class="input-wrap">
          <i class="fa fa-user"></i>
          <input type="text" id="full_name" name="full_name" placeholder="e.g. John Adeyemi"
                 value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required autocomplete="name">
        </div>
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrap">
          <i class="fa fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label for="phone">Phone Number <span style="color:var(--text-muted);font-weight:400;font-size:.78rem">(optional)</span></label>
        <div class="input-wrap">
          <i class="fa fa-phone"></i>
          <input type="tel" id="phone" name="phone" placeholder="08012345678"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" autocomplete="tel">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <i class="fa fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="Min. 6 characters" required>
          </div>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-wrap">
            <i class="fa fa-lock"></i>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
          </div>
        </div>
      </div>

      <button type="submit" class="auth-btn">
        <i class="fa fa-user-plus"></i> Create Account
      </button>
    </form>
    <?php endif; ?>

    <?php if ($success): ?>
      <a href="<?= BASE_URL ?>/auth/login.php" class="auth-btn" style="margin-top:16px;">
        <i class="fa fa-sign-in-alt"></i> Go to Sign In
      </a>
    <?php endif; ?>

    <div class="auth-footer">
      Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Sign in</a>
    </div>
  </div>
</div>

</body>
</html>
