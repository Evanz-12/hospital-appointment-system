<?php
$name = $_SESSION['name'] ?? 'Admin';
$words = explode(' ', trim($name));
$initials = '';
foreach ($words as $w) { if (!empty($w)) { $initials .= strtoupper($w[0]); } if (strlen($initials) >= 2) break; }
if (empty($initials)) $initials = 'A';
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><svg viewBox="0 0 24 24" fill="none" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="2" width="4" height="20" rx="2" fill="white"/><rect x="2" y="10" width="20" height="4" rx="2" fill="white"/></svg></div>
    <div>
      <span class="brand-name">MediBook</span>
      <span class="brand-sub">Hospital System</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Main Menu</div>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>/admin/appointments.php" class="<?= basename($_SERVER['PHP_SELF']) === 'appointments.php' ? 'active' : '' ?>">
      <i class="fa fa-calendar-check"></i> Appointments
    </a>
    <div class="sidebar-section-label">Management</div>
    <a href="<?= BASE_URL ?>/admin/doctors.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['doctors.php','add-doctor.php','edit-doctor.php']) ? 'active' : '' ?>">
      <i class="fa fa-user-md"></i> Doctors
    </a>
    <a href="<?= BASE_URL ?>/admin/patients.php" class="<?= basename($_SERVER['PHP_SELF']) === 'patients.php' ? 'active' : '' ?>">
      <i class="fa fa-users"></i> Patients
    </a>
    <a href="<?= BASE_URL ?>/admin/departments.php" class="<?= basename($_SERVER['PHP_SELF']) === 'departments.php' ? 'active' : '' ?>">
      <i class="fa fa-hospital"></i> Departments
    </a>
    <div class="sidebar-section-label">Analytics</div>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
      <i class="fa fa-chart-bar"></i> Reports
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
