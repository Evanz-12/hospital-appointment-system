<?php
$name = $_SESSION['name'] ?? 'Doctor';
$words = explode(' ', trim($name));
$initials = '';
foreach ($words as $w) { if (!empty($w)) { $initials .= strtoupper($w[0]); } if (strlen($initials) >= 2) break; }
if (empty($initials)) $initials = 'D';
?>
<div class="sidebar" data-role="doctor">
  <div class="sidebar-brand">
    <div class="brand-icon" style="background:var(--accent);"><svg viewBox="0 0 24 24" fill="none" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="2" width="4" height="20" rx="2" fill="white"/><rect x="2" y="10" width="20" height="4" rx="2" fill="white"/></svg></div>
    <div>
      <span class="brand-name">MediBook</span>
      <span class="brand-sub">Doctor Portal</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Navigation</div>
    <a href="<?= BASE_URL ?>/doctor/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa fa-home"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>/doctor/schedule.php" class="<?= basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'active' : '' ?>">
      <i class="fa fa-calendar-alt"></i> My Schedule
    </a>
    <a href="<?= BASE_URL ?>/doctor/availability.php" class="<?= basename($_SERVER['PHP_SELF']) === 'availability.php' ? 'active' : '' ?>">
      <i class="fa fa-calendar-times"></i> Availability
    </a>
    <div class="sidebar-section-label">Account</div>
    <a href="<?= BASE_URL ?>/doctor/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
      <i class="fa fa-user-md"></i> My Profile
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
      <div class="topbar-avatar accent"><?= htmlspecialchars($initials) ?></div>
    </div>
  </header>
  <div class="main-content">
