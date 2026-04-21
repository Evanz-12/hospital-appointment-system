<div class="sidebar">
  <div class="sidebar-brand">
    <i class="fa fa-hospital-o"></i>
    <span>MediBook</span>
  </div>
  <nav class="sidebar-nav">
    <a href="<?= BASE_URL ?>/doctor/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa fa-home"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>/doctor/schedule.php" class="<?= basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'active' : '' ?>">
      <i class="fa fa-calendar-alt"></i> My Schedule
    </a>
    <a href="<?= BASE_URL ?>/doctor/availability.php" class="<?= basename($_SERVER['PHP_SELF']) === 'availability.php' ? 'active' : '' ?>">
      <i class="fa fa-calendar-times"></i> Availability
    </a>
    <a href="<?= BASE_URL ?>/doctor/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
      <i class="fa fa-user-md"></i> My Profile
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
      <i class="fa fa-user-md"></i>
      <span><?= htmlspecialchars($_SESSION['name'] ?? 'Doctor') ?></span>
    </div>
  </header>
  <div class="main-content">
