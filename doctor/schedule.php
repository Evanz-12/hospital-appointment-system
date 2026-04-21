<?php
session_start();
require_once '../config.php';
$required_role = 'doctor';
require_once '../includes/auth-guard.php';

$user_id    = $_SESSION['user_id'];
$page_title = 'My Schedule';
$extra_css  = ['dashboard.css'];

$stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$doctor) { header("Location: " . BASE_URL . "/auth/logout.php"); exit(); }
$doctor_id = $doctor['id'];

// Date range: this week Mon–Sun, or a custom week via GET
$week_offset = (int)($_GET['week'] ?? 0);
$monday = new DateTime();
$day_of_week = (int)$monday->format('N'); // 1=Mon … 7=Sun
$monday->modify('-' . ($day_of_week - 1) . ' days');
$monday->modify(($week_offset >= 0 ? '+' : '') . $week_offset . ' weeks');
$sunday = clone $monday;
$sunday->modify('+6 days');

$start = $monday->format('Y-m-d');
$end   = $sunday->format('Y-m-d');

$stmt2 = mysqli_prepare($conn,
    "SELECT a.appointment_date, a.appointment_time, a.status, a.reason,
            u.full_name AS patient_name, u.phone
     FROM appointments a JOIN users u ON a.patient_id = u.id
     WHERE a.doctor_id=? AND a.appointment_date BETWEEN ? AND ?
       AND a.status NOT IN ('cancelled','declined')
     ORDER BY a.appointment_date, a.appointment_time");
mysqli_stmt_bind_param($stmt2, 'iss', $doctor_id, $start, $end);
mysqli_stmt_execute($stmt2);
$rows = mysqli_stmt_get_result($stmt2)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

// Group by date
$by_date = [];
foreach ($rows as $r) { $by_date[$r['appointment_date']][] = $r; }

include '../includes/header.php';
include '../includes/sidebar-doctor.php';
?>

<div class="page-header">
  <h1>Weekly Schedule</h1>
  <div style="display:flex;gap:8px;">
    <a href="?week=<?= $week_offset - 1 ?>" class="btn btn-outline btn-sm"><i class="fa fa-chevron-left"></i> Prev</a>
    <span style="padding:6px 14px;font-size:.88rem;font-weight:600;color:var(--text-muted);">
      <?= $monday->format('d M') ?> – <?= $sunday->format('d M Y') ?>
    </span>
    <a href="?week=<?= $week_offset + 1 ?>" class="btn btn-outline btn-sm">Next <i class="fa fa-chevron-right"></i></a>
    <?php if ($week_offset !== 0): ?>
    <a href="?week=0" class="btn btn-primary btn-sm">Today</a>
    <?php endif; ?>
  </div>
</div>

<?php
$day = clone $monday;
$has_any = false;
for ($i = 0; $i < 7; $i++):
  $d_str = $day->format('Y-m-d');
  $d_name = $day->format('l, d M Y');
  $slots = $by_date[$d_str] ?? [];
  if (!empty($slots)) { $has_any = true; }
?>
<div class="schedule-day">
  <h3><?= htmlspecialchars($d_name) ?></h3>
  <?php if (empty($slots)): ?>
    <p style="font-size:.82rem;color:var(--text-muted);padding:4px 0;">No appointments</p>
  <?php else: ?>
    <?php foreach ($slots as $s): ?>
    <div class="schedule-slot">
      <span class="schedule-slot-time"><?= date('g:i A', strtotime($s['appointment_time'])) ?></span>
      <div class="schedule-slot-info" style="flex:1;">
        <h4><?= htmlspecialchars($s['patient_name']) ?></h4>
        <p><?= $s['reason'] ? htmlspecialchars(substr($s['reason'], 0, 80)) : 'No reason given' ?>
          <?php if ($s['phone']): ?> &mdash; <?= htmlspecialchars($s['phone']) ?><?php endif; ?>
        </p>
      </div>
      <span class="badge badge-<?= $s['status'] ?>"><?= $s['status'] ?></span>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php
  $day->modify('+1 day');
endfor;

if (!$has_any): ?>
<div class="empty-state">
  <i class="fa fa-calendar-week"></i>
  <p>No appointments scheduled for this week.</p>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
