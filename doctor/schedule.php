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
$today_str = date('Y-m-d');
$day       = clone $monday;
$has_any   = false;
for ($i = 0; $i < 7; $i++):
  $d_str    = $day->format('Y-m-d');
  $d_full   = $day->format('l');
  $d_short  = $day->format('d M Y');
  $slots    = $by_date[$d_str] ?? [];
  $is_today = ($d_str === $today_str);
  $has_appts = !empty($slots);
  if ($has_appts) { $has_any = true; }
?>
<div class="schedule-day <?= $is_today ? 'today' : '' ?> <?= !$has_appts ? 'empty-day' : '' ?>">

  <div class="schedule-day-hd">
    <div class="schedule-day-name">
      <?php if ($is_today): ?>
        <span class="today-pill">Today</span>
      <?php endif; ?>
      <strong><?= htmlspecialchars($d_full) ?></strong>
      <span class="schedule-day-date"><?= htmlspecialchars($d_short) ?></span>
    </div>
    <?php if ($has_appts): ?>
      <span class="schedule-day-count"><?= count($slots) ?> appointment<?= count($slots) > 1 ? 's' : '' ?></span>
    <?php endif; ?>
  </div>

  <?php if (!$has_appts): ?>
    <p class="schedule-no-appts">No appointments scheduled</p>
  <?php else: ?>
  <div class="schedule-slots">
    <?php foreach ($slots as $s): ?>
    <div class="schedule-slot status-<?= $s['status'] ?>">
      <div class="slot-time">
        <?= date('g:i', strtotime($s['appointment_time'])) ?>
        <span class="slot-ampm"><?= date('A', strtotime($s['appointment_time'])) ?></span>
      </div>
      <div class="slot-divider"></div>
      <div class="slot-body">
        <span class="slot-patient"><?= htmlspecialchars($s['patient_name']) ?></span>
        <span class="slot-sep">&middot;</span>
        <span class="slot-reason"><?= $s['reason'] ? htmlspecialchars(substr($s['reason'], 0, 80)) : 'No reason given' ?></span>
      </div>
      <span class="badge badge-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
<?php
  $day->modify('+1 day');
endfor;

if (!$has_any): ?>
<div class="empty-state">
  <div class="empty-icon"><i class="fa fa-calendar-week"></i></div>
  <h3>Clear week ahead</h3>
  <p>No appointments scheduled for this week.</p>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
