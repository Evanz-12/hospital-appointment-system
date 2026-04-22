<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin')   { header("Location: " . BASE_URL . "/admin/dashboard.php");   exit(); }
    if ($role === 'doctor')  { header("Location: " . BASE_URL . "/doctor/dashboard.php");  exit(); }
    if ($role === 'patient') { header("Location: " . BASE_URL . "/patient/dashboard.php"); exit(); }
}

$depts = [];
$res   = mysqli_query($conn, "SELECT name, description FROM departments ORDER BY name");
while ($row = mysqli_fetch_assoc($res)) { $depts[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediBook — Hospital Appointment Booking</title>
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    body { display: block; background: var(--bg-white); }

    /* ── Navbar ── */
    .landing-nav {
      background: var(--bg-white);
      border-bottom: 1px solid var(--border);
      padding: 0 5%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 68px;
      position: sticky; top: 0; z-index: 100;
      box-shadow: var(--shadow-xs);
    }
    .nav-brand {
      display: flex; align-items: center; gap: 10px;
      font-family: var(--font-display);
      font-size: 1.2rem; font-weight: 700;
      color: var(--text-primary);
      letter-spacing: -.02em;
    }
    .nav-brand .brand-mark {
      width: 36px; height: 36px;
      background: var(--primary);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1rem;
    }
    .nav-links { display: flex; gap: 6px; align-items: center; }
    .nav-links a {
      padding: 8px 16px;
      border-radius: var(--radius-sm);
      font-family: var(--font-display);
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-secondary);
      transition: background .15s, color .15s;
    }
    .nav-links a:hover { background: var(--bg); color: var(--text-primary); }
    .nav-links .btn-nav {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 2px 8px rgba(0,102,204,.25);
    }
    .nav-links .btn-nav:hover { background: var(--primary-dark); color: #fff; }

    /* ── Hero ── */
    .hero {
      background: var(--bg-white);
      padding: 96px 5% 80px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 48px;
      max-width: 1200px;
      margin: 0 auto;
    }
    .hero-content { flex: 1; max-width: 580px; }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--primary-light);
      color: var(--primary);
      padding: 5px 12px;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 600;
      margin-bottom: 20px;
      font-family: var(--font-display);
    }
    .hero h1 {
      font-family: var(--font-display);
      font-size: 3rem;
      font-weight: 700;
      color: var(--text-primary);
      letter-spacing: -.04em;
      line-height: 1.15;
      margin-bottom: 20px;
    }
    .hero h1 span { color: var(--primary); }
    .hero p {
      font-size: 1.05rem;
      color: var(--text-secondary);
      line-height: 1.75;
      margin-bottom: 36px;
      max-width: 480px;
    }
    .hero-btns { display: flex; gap: 12px; flex-wrap: wrap; }
    .hero-btns a {
      padding: 13px 26px;
      border-radius: var(--radius-sm);
      font-family: var(--font-display);
      font-size: 0.9rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all .2s;
    }
    .btn-hero-primary {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 4px 14px rgba(0,102,204,.3);
    }
    .btn-hero-primary:hover { background: var(--primary-dark); color: #fff; transform: translateY(-1px); }
    .btn-hero-outline {
      background: transparent;
      border: 1.5px solid var(--border-strong);
      color: var(--text-secondary);
    }
    .btn-hero-outline:hover { border-color: var(--primary); color: var(--primary); }

    .hero-stats {
      display: flex;
      gap: 28px;
      margin-top: 44px;
      padding-top: 36px;
      border-top: 1px solid var(--border);
    }
    .hero-stat-item h4 {
      font-family: var(--font-display);
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-primary);
      letter-spacing: -.03em;
    }
    .hero-stat-item p {
      font-size: 0.78rem;
      color: var(--text-muted);
      margin: 0;
    }

    .hero-visual {
      flex-shrink: 0;
      width: 380px;
      background: var(--bg);
      border-radius: var(--radius-xl);
      border: 1px solid var(--border);
      padding: 24px;
      box-shadow: var(--shadow-md);
    }
    .hero-card-header {
      display: flex; align-items: center; gap: 12px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 16px;
    }
    .hero-doc-avatar {
      width: 44px; height: 44px;
      background: var(--primary-light);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      color: var(--primary);
      font-size: 1rem;
      font-family: var(--font-display);
      font-weight: 700;
    }
    .hero-doc-info h4 { font-family: var(--font-display); font-size: 0.9rem; font-weight: 700; color: var(--text-primary); }
    .hero-doc-info p  { font-size: 0.75rem; color: var(--accent); font-weight: 600; }
    .hero-card-row {
      display: flex; justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
      font-size: 0.82rem;
    }
    .hero-card-row:last-child { border-bottom: none; }
    .hero-card-row span:first-child { color: var(--text-muted); }
    .hero-card-row span:last-child  { font-weight: 600; color: var(--text-primary); }
    .hero-confirm-btn {
      width: 100%;
      height: 40px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      font-family: var(--font-display);
      font-size: 0.85rem;
      font-weight: 600;
      margin-top: 16px;
      cursor: default;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }

    /* ── Features ── */
    .features-section {
      padding: 80px 5%;
      background: var(--bg);
      text-align: center;
    }
    .section-label {
      display: inline-block;
      font-family: var(--font-display);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--primary);
      background: var(--primary-light);
      padding: 4px 12px;
      border-radius: 999px;
      margin-bottom: 16px;
    }
    .section-title {
      font-family: var(--font-display);
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-primary);
      letter-spacing: -.03em;
      margin-bottom: 10px;
    }
    .section-sub {
      font-size: 0.95rem;
      color: var(--text-muted);
      margin-bottom: 52px;
      max-width: 480px;
      margin-left: auto; margin-right: auto;
    }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      max-width: 980px;
      margin: 0 auto;
    }
    .feature-card {
      background: var(--bg-white);
      border-radius: var(--radius-lg);
      padding: 28px 24px;
      border: 1px solid var(--border);
      text-align: left;
      transition: box-shadow .2s, transform .2s;
    }
    .feature-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }
    .feature-icon {
      width: 48px; height: 48px;
      background: var(--primary-light);
      border-radius: var(--radius-md);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.15rem;
      color: var(--primary);
      margin-bottom: 16px;
    }
    .feature-card h3 {
      font-family: var(--font-display);
      font-size: 0.95rem;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--text-primary);
    }
    .feature-card p { font-size: 0.85rem; color: var(--text-muted); line-height: 1.65; }

    /* ── Departments ── */
    .depts-section {
      padding: 80px 5%;
      background: var(--bg-white);
      text-align: center;
    }
    .depts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 14px;
      max-width: 1000px;
      margin: 0 auto;
    }
    .dept-card {
      background: var(--bg-white);
      border-radius: var(--radius-md);
      padding: 16px 18px;
      border: 1px solid var(--border);
      border-left: 4px solid var(--accent);
      display: flex;
      align-items: center;
      gap: 12px;
      text-align: left;
      transition: box-shadow .2s, transform .2s;
    }
    .dept-card:hover { box-shadow: var(--shadow-sm); transform: translateY(-1px); }
    .dept-card i { color: var(--accent); font-size: 1.1rem; flex-shrink: 0; width: 20px; text-align: center; }
    .dept-card-text h4 { font-family: var(--font-display); font-size: 0.85rem; font-weight: 700; color: var(--text-primary); }
    .dept-card-text p  { font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; }

    /* ── CTA ── */
    .cta-section {
      padding: 80px 5%;
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .cta-section::before {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: rgba(255,255,255,.05);
      border-radius: 50%;
      top: -150px; right: -100px;
    }
    .cta-section::after {
      content: '';
      position: absolute;
      width: 250px; height: 250px;
      background: rgba(255,255,255,.04);
      border-radius: 50%;
      bottom: -80px; left: -60px;
    }
    .cta-section h2 {
      font-family: var(--font-display);
      font-size: 2.1rem;
      font-weight: 700;
      color: #fff;
      letter-spacing: -.03em;
      margin-bottom: 14px;
      position: relative; z-index: 1;
    }
    .cta-section p { color: rgba(255,255,255,.7); margin-bottom: 32px; font-size: 0.95rem; position: relative; z-index: 1; }
    .cta-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; position: relative; z-index: 1; }
    .btn-cta-white {
      background: #fff;
      color: var(--primary);
      padding: 13px 28px;
      border-radius: var(--radius-sm);
      font-family: var(--font-display);
      font-size: 0.9rem;
      font-weight: 700;
      display: inline-flex; align-items: center; gap: 8px;
      transition: all .2s;
    }
    .btn-cta-white:hover { background: var(--primary-light); color: var(--primary-dark); transform: translateY(-1px); }
    .btn-cta-outline {
      background: transparent;
      color: rgba(255,255,255,.9);
      border: 1.5px solid rgba(255,255,255,.35);
      padding: 13px 28px;
      border-radius: var(--radius-sm);
      font-family: var(--font-display);
      font-size: 0.9rem;
      font-weight: 600;
      display: inline-flex; align-items: center; gap: 8px;
      transition: all .2s;
    }
    .btn-cta-outline:hover { background: rgba(255,255,255,.1); color: #fff; }

    /* ── Footer ── */
    .landing-footer {
      background: var(--text-primary);
      padding: 40px 5%;
    }
    .footer-inner {
      max-width: 1000px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 32px;
      align-items: start;
    }
    .footer-brand .nav-brand { color: #fff; margin-bottom: 10px; }
    .footer-brand p { font-size: 0.82rem; color: rgba(255,255,255,.45); line-height: 1.6; }
    .footer-copy {
      text-align: right;
      font-size: 0.78rem;
      color: rgba(255,255,255,.35);
      padding-top: 6px;
    }
    .footer-copy a { color: rgba(255,255,255,.5); }
    .footer-copy a:hover { color: #fff; }

    @media(max-width: 960px) {
      .hero { flex-direction: column; padding: 60px 5%; }
      .hero-visual { width: 100%; max-width: 420px; }
      .hero h1 { font-size: 2.2rem; }
    }
    @media(max-width: 768px) {
      .features-grid { grid-template-columns: 1fr; }
      .hero h1 { font-size: 1.9rem; }
      .section-title { font-size: 1.5rem; }
      .footer-inner { grid-template-columns: 1fr; }
      .footer-copy { text-align: left; }
      .hero-stats { gap: 20px; }
    }
    @media(max-width: 480px) {
      .landing-nav { padding: 0 20px; }
      .features-section, .depts-section, .cta-section { padding: 52px 5%; }
    }
  </style>
</head>
<body>

<nav class="landing-nav">
  <div class="nav-brand">
    <div class="brand-mark"><svg viewBox="0 0 24 24" fill="none" width="18" height="18" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="2" width="4" height="20" rx="2" fill="white"/><rect x="2" y="10" width="20" height="4" rx="2" fill="white"/></svg></div>
    MediBook
  </div>
  <div class="nav-links">
    <a href="<?= BASE_URL ?>/auth/login.php">Sign In</a>
    <a href="<?= BASE_URL ?>/auth/register.php" class="btn-nav">Get Started</a>
  </div>
</nav>

<!-- Hero -->
<section style="background:var(--bg-white);border-bottom:1px solid var(--border);">
  <div class="hero">
    <div class="hero-content">
      <div class="hero-badge"><i class="fa fa-shield-alt"></i> Crawford University Hospital</div>
      <h1>Book Your Appointment <span>Online</span></h1>
      <p>Skip the waiting room. Choose your specialist, pick a convenient time slot, and confirm your booking in minutes — all from your phone or laptop.</p>
      <div class="hero-btns">
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn-hero-primary">
          <i class="fa fa-calendar-plus"></i> Book Appointment
        </a>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn-hero-outline">
          <i class="fa fa-sign-in-alt"></i> Sign In
        </a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat-item"><h4>8+</h4><p>Departments</p></div>
        <div class="hero-stat-item"><h4>8</h4><p>Specialists</p></div>
        <div class="hero-stat-item"><h4>24/7</h4><p>Online Access</p></div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-card-header">
        <div class="hero-doc-avatar">JA</div>
        <div class="hero-doc-info">
          <h4>Dr. James Adeyemi</h4>
          <p>Cardiologist</p>
        </div>
      </div>
      <div class="hero-card-row"><span>Date</span><span>Mon, 28 Apr 2026</span></div>
      <div class="hero-card-row"><span>Time</span><span>10:00 AM</span></div>
      <div class="hero-card-row"><span>Department</span><span>Cardiology</span></div>
      <div class="hero-card-row"><span>Status</span><span><span class="badge badge-approved">Approved</span></span></div>
      <button class="hero-confirm-btn"><i class="fa fa-check"></i> Appointment Confirmed</button>
    </div>
  </div>
</section>

<!-- Features -->
<section class="features-section">
  <div class="section-label">Why MediBook</div>
  <h2 class="section-title">Healthcare made simple</h2>
  <p class="section-sub">A seamless digital experience built around patients — fast, secure, and reliable.</p>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon"><i class="fa fa-user-md"></i></div>
      <h3>Verified Specialists</h3>
      <p>Browse qualified doctors across all departments. Read specialisations and choose the right expert for your needs.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon" style="background:var(--accent-light);color:var(--accent);"><i class="fa fa-clock"></i></div>
      <h3>Real-Time Availability</h3>
      <p>Only available time slots are shown. No double-bookings, no wasted trips — book with confidence.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon" style="background:var(--success-light);color:var(--success);"><i class="fa fa-bell"></i></div>
      <h3>Instant Notifications</h3>
      <p>Receive email confirmations the moment you book, and alerts when your appointment is approved or updated.</p>
    </div>
  </div>
</section>

<!-- Departments -->
<section class="depts-section">
  <div class="section-label">Our Services</div>
  <h2 class="section-title">Departments & Specialties</h2>
  <p class="section-sub" style="margin-bottom:40px;">Expert care across a wide range of medical disciplines.</p>
  <div class="depts-grid">
    <?php
    $dept_icons = [
      'General Medicine' => 'fa-stethoscope', 'Cardiology' => 'fa-heartbeat',
      'Paediatrics' => 'fa-baby', 'Gynaecology' => 'fa-venus',
      'Orthopaedics' => 'fa-bone', 'Dermatology' => 'fa-allergies',
      'ENT' => 'fa-ear-deaf', 'Ophthalmology' => 'fa-eye',
    ];
    foreach ($depts as $d):
      $icon = $dept_icons[$d['name']] ?? 'fa-hospital';
    ?>
    <div class="dept-card">
      <i class="fa <?= $icon ?>"></i>
      <div class="dept-card-text">
        <h4><?= htmlspecialchars($d['name']) ?></h4>
        <p><?= htmlspecialchars(substr($d['description'] ?? '', 0, 42)) ?>…</p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <h2>Ready to get started?</h2>
  <p>Create a free patient account and book your first appointment today — it takes under two minutes.</p>
  <div class="cta-btns">
    <a href="<?= BASE_URL ?>/auth/register.php" class="btn-cta-white">
      <i class="fa fa-user-plus"></i> Register — It's Free
    </a>
    <a href="<?= BASE_URL ?>/auth/login.php" class="btn-cta-outline">
      <i class="fa fa-sign-in-alt"></i> Sign In
    </a>
  </div>
</section>

<!-- Footer -->
<footer class="landing-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="nav-brand" style="color:#fff;">
        <div class="brand-mark"><svg viewBox="0 0 24 24" fill="none" width="18" height="18" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="2" width="4" height="20" rx="2" fill="white"/><rect x="2" y="10" width="20" height="4" rx="2" fill="white"/></svg></div>
        MediBook
      </div>
      <p>Crawford University Hospital Appointment Booking System. Secure, fast, and patient-centred digital healthcare access.</p>
    </div>
    <div class="footer-copy">
      <p>&copy; <?= date('Y') ?> MediBook</p>
      <p style="margin-top:6px;">Crawford University Hospital</p>
      <p style="margin-top:6px;">
        <a href="<?= BASE_URL ?>/auth/login.php">Sign In</a> &nbsp;&middot;&nbsp;
        <a href="<?= BASE_URL ?>/auth/register.php">Register</a>
      </p>
    </div>
  </div>
</footer>

</body>
</html>
