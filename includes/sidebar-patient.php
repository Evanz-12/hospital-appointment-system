<div class="sidebar">
  <div class="sidebar-brand">
    <i class="fa fa-hospital-o"></i>
    <span>MediBook</span>
  </div>
  <nav class="sidebar-nav">
    <a href="<?= BASE_URL ?>/patient/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa fa-home"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>/patient/book.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['book.php','book-doctor.php','book-slot.php','book-confirm.php']) ? 'active' : '' ?>">
      <i class="fa fa-calendar-plus"></i> Book Appointment
    </a>
    <a href="<?= BASE_URL ?>/patient/appointments.php" class="<?= basename($_SERVER['PHP_SELF']) === 'appointments.php' ? 'active' : '' ?>">
      <i class="fa fa-calendar-alt"></i> My Appointments
    </a>
    <a href="<?= BASE_URL ?>/patient/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
      <i class="fa fa-user"></i> My Profile
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-logout">
      <i class="fa fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<div class="app-wrapper">
  <header class="topbar">
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fa fa-bars"></i></button>
    <div class="topbar-title"><?= htmlspecialchars($page_title ?? '') ?></div>
    <div class="topbar-user">
      <i class="fa fa-user-circle"></i>
      <span><?= htmlspecialchars($_SESSION['name'] ?? 'Patient') ?></span>
    </div>
  </header>
  <div class="main-content">
