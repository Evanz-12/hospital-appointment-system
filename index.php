<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin')   { header("Location: " . BASE_URL . "/admin/dashboard.php");   exit(); }
    if ($role === 'doctor')  { header("Location: " . BASE_URL . "/doctor/dashboard.php");  exit(); }
    if ($role === 'patient') { header("Location: " . BASE_URL . "/patient/dashboard.php"); exit(); }
}

// Fetch departments for the landing hero
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <style>
    body { display: block; background: var(--bg); }
    /* --- Navbar --- */
    .landing-nav {
      background: var(--white);
      border-bottom: 1px solid var(--border);
      padding: 0 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 64px;
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .landing-nav .brand { display:flex; align-items:center; gap:10px; font-size:1.2rem; font-weight:700; color:var(--primary); font-family:'Playfair Display',serif; }
    .landing-nav .brand i { font-size:1.5rem; }
    .nav-links { display:flex; gap:8px; }
    .nav-links a { padding:8px 18px; border-radius:var(--radius); font-size:.88rem; font-weight:600; color:var(--primary); transition:background .18s; }
    .nav-links a:hover { background:var(--accent); }
    .nav-links .btn-nav { background:var(--primary); color:var(--white); }
    .nav-links .btn-nav:hover { background:var(--primary-dark); }
    /* --- Hero --- */
    .hero {
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, #2A8FAF 100%);
      color: var(--white);
      padding: 80px 40px;
      text-align: center;
    }
    .hero h1 { font-size: 2.8rem; color:var(--white); margin-bottom:16px; }
    .hero p  { font-size: 1.05rem; opacity:.85; max-width:560px; margin:0 auto 32px; }
    .hero-btns { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
    .hero-btns a { padding:13px 30px; border-radius:var(--radius); font-size:.95rem; font-weight:600; transition:all .2s; }
    .hero-btns .btn-white { background:var(--white); color:var(--primary); }
    .hero-btns .btn-white:hover { background:var(--accent); }
    .hero-btns .btn-outline-w { background:transparent; border:2px solid rgba(255,255,255,.7); color:var(--white); }
    .hero-btns .btn-outline-w:hover { background:rgba(255,255,255,.12); }
    /* --- Features --- */
    .features-section { padding: 64px 40px; text-align:center; }
    .features-section h2 { font-size:1.7rem; margin-bottom:8px; }
    .features-section .sub { color:var(--text-muted); margin-bottom:40px; }
    .features-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; max-width:900px; margin:0 auto; }
    .feature-card { background:var(--white); border-radius:12px; padding:28px 22px; box-shadow:var(--shadow); }
    .feature-card i { font-size:2rem; color:var(--primary); margin-bottom:14px; }
    .feature-card h3 { font-size:1rem; margin-bottom:8px; font-family:'DM Sans',sans-serif; }
    .feature-card p { font-size:.83rem; color:var(--text-muted); }
    /* --- Departments --- */
    .depts-section { padding:48px 40px; background:var(--accent); }
    .depts-section h2 { text-align:center; font-size:1.5rem; margin-bottom:28px; }
    .depts-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; max-width:1000px; margin:0 auto; }
    .dept-pill { background:var(--white); border-radius:var(--radius); padding:14px 16px; font-size:.85rem; font-weight:600; color:var(--primary); box-shadow:var(--shadow); display:flex; align-items:center; gap:10px; }
    .dept-pill i { opacity:.7; }
    /* --- CTA --- */
    .cta-section { padding:60px 40px; text-align:center; }
    .cta-section h2 { font-size:1.6rem; margin-bottom:12px; }
    .cta-section p  { color:var(--text-muted); margin-bottom:28px; }
    /* --- Footer --- */
    .landing-footer { background:var(--primary-dark); color:rgba(255,255,255,.6); text-align:center; padding:22px; font-size:.82rem; }
    @media(max-width:768px){
      .hero h1{font-size:2rem;} .features-grid{grid-template-columns:1fr;} .landing-nav{padding:0 20px;}
      .features-section,.depts-section,.cta-section{padding:40px 20px;}
    }
  </style>
</head>
<body>

<nav class="landing-nav">
  <div class="brand"><i class="fa fa-hospital-o"></i> MediBook</div>
  <div class="nav-links">
    <a href="<?= BASE_URL ?>/auth/login.php">Sign In</a>
    <a href="<?= BASE_URL ?>/auth/register.php" class="btn-nav">Register</a>
  </div>
</nav>

<section class="hero">
  <h1>Book Your Hospital<br>Appointment Online</h1>
  <p>Skip the queue. Choose your doctor, pick a convenient time, and confirm your booking — all from the comfort of your home.</p>
  <div class="hero-btns">
    <a href="<?= BASE_URL ?>/auth/register.php" class="btn-white"><i class="fa fa-calendar-plus"></i> Book an Appointment</a>
    <a href="<?= BASE_URL ?>/auth/login.php"    class="btn-outline-w"><i class="fa fa-sign-in-alt"></i> Sign In</a>
  </div>
</section>

<section class="features-section">
  <h2>Why MediBook?</h2>
  <p class="sub">Efficient. Reliable. Patient-centred.</p>
  <div class="features-grid">
    <div class="feature-card">
      <i class="fa fa-user-md"></i>
      <h3>Expert Doctors</h3>
      <p>Browse verified specialist doctors across all departments and read their profiles.</p>
    </div>
    <div class="feature-card">
      <i class="fa fa-clock"></i>
      <h3>Real-Time Slots</h3>
      <p>Only available time slots are shown. No double-bookings, no wasted trips.</p>
    </div>
    <div class="feature-card">
      <i class="fa fa-shield-alt"></i>
      <h3>Secure & Private</h3>
      <p>Your health data is protected with industry-standard security and encrypted passwords.</p>
    </div>
  </div>
</section>

<section class="depts-section">
  <h2>Our Departments</h2>
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
    <div class="dept-pill"><i class="fa <?= $icon ?>"></i> <?= htmlspecialchars($d['name']) ?></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="cta-section">
  <h2>Ready to get started?</h2>
  <p>Create a free patient account and book your first appointment today.</p>
  <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg">
    <i class="fa fa-user-plus"></i> Register Now — It's Free
  </a>
</section>

<footer class="landing-footer">
  &copy; <?= date('Y') ?> MediBook &mdash; Crawford University Hospital Appointment System
</footer>

</body>
</html>
