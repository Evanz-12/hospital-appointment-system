<?php
$name = $_SESSION['name'] ?? 'Patient';
$words = explode(' ', trim($name));
$initials = '';
foreach ($words as $w) { if (!empty($w)) { $initials .= strtoupper($w[0]); } if (strlen($initials) >= 2) break; }
if (empty($initials)) $initials = 'P';
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fa fa-hospital-o"></i></div>
    <div>
      <span class="brand-name">MediBook</span>
      <span class="brand-sub">Patient Portal</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Navigation</div>
    <a href="<?= BASE_URL ?>/patient/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa fa-home"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>/patient/book.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['book.php','book-doctor.php','book-slot.php','book-confirm.php']) ? 'active' : '' ?>">
      <i class="fa fa-calendar-plus"></i> Book Appointment
    </a>
    <a href="<?= BASE_URL ?>/patient/appointments.php" class="<?= basename($_SERVER['PHP_SELF']) === 'appointments.php' ? 'active' : '' ?>">
      <i class="fa fa-calendar-alt"></i> My Appointments
    </a>
    <div class="sidebar-section-label">Account</div>
    <a href="<?= BASE_URL ?>/patient/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
      <i class="fa fa-user"></i> My Profile
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-logout">
      <i class="fa fa-sign-out-alt"></i> Sign Out
    </a>
  </div>
</div>

<div class="app-wrapper">
  <header class="topbar">
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fa fa-bars"></i></button>
    <div class="topbar-title"><?= htmlspecialchars($page_title ?? '') ?></div>
    <div class="topbar-user">
      <span class="topbar-user-name"><?= htmlspecialchars($name) ?></span>
      <div class="topbar-avatar"><?= htmlspecialchars($initials) ?></div>
    </div>
  </header>
  <div class="main-content">
