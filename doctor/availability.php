<?php
session_start();
require_once '../config.php';
$required_role = 'doctor';
require_once '../includes/auth-guard.php';

$user_id = $_SESSION['user_id'];
$page_title = 'Manage Availability';
$extra_css  = ['dashboard.css'];
$extra_js   = ['calendar.js'];

$stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$doctor) { header("Location: " . BASE_URL . "/auth/logout.php"); exit(); }
$doctor_id = $doctor['id'];

// Fetch all future unavailability dates
$stmt2 = mysqli_prepare($conn,
    "SELECT unavail_date, reason FROM doctor_unavailability
     WHERE doctor_id=? AND unavail_date >= CURDATE() ORDER BY unavail_date");
mysqli_stmt_bind_param($stmt2, 'i', $doctor_id);
mysqli_stmt_execute($stmt2);
$unavail_rows = mysqli_stmt_get_result($stmt2)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

$unavail_dates = array_column($unavail_rows, 'unavail_date');

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../includes/header.php';
include '../includes/sidebar-doctor.php';
?>

<script>var BASE_URL = '<?= BASE_URL ?>';</script>

<div class="page-header">
  <h1>Manage Availability</h1>
</div>

<div class="dash-grid">
  <!-- Calendar -->
  <div class="card">
    <div class="card-title"><i class="fa fa-calendar-times"></i> Mark Unavailable Dates</div>
    <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:18px;">
      Click a date to toggle it as unavailable (shown in red). Patients will not be able to book those dates.
    </p>

    <input type="hidden" id="csrf_token_cal" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
      <button id="calPrev" class="btn btn-outline btn-sm"><i class="fa fa-chevron-left"></i></button>
      <span id="calMonthLabel" style="font-weight:700;font-size:.95rem;"></span>
      <button id="calNext" class="btn btn-outline btn-sm"><i class="fa fa-chevron-right"></i></button>
    </div>

    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:8px;">
      <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
      <div class="avail-day-header"><?= $d ?></div>
      <?php endforeach; ?>
    </div>
    <div id="availCalendar"
         data-unavail='<?= json_encode($unavail_dates) ?>'
         data-doctor="<?= $doctor_id ?>">
      <div id="calGrid" class="avail-calendar"></div>
    </div>

    <div style="margin-top:14px;display:flex;gap:14px;font-size:.78rem;color:var(--text-muted);">
      <span style="display:flex;align-items:center;gap:6px;"><span style="width:14px;height:14px;background:#FEF2F2;border:1px solid #FECACA;border-radius:3px;display:inline-block;"></span> Unavailable</span>
      <span style="display:flex;align-items:center;gap:6px;"><span style="width:14px;height:14px;border:1.5px solid var(--primary);border-radius:3px;display:inline-block;"></span> Today</span>
    </div>
  </div>

  <!-- Unavailability list -->
  <div class="card">
    <div class="card-title"><i class="fa fa-list"></i> Upcoming Unavailable Dates</div>
    <?php if (empty($unavail_rows)): ?>
      <div class="empty-state">
        <i class="fa fa-calendar-check"></i>
        <p>No dates marked as unavailable.</p>
      </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Date</th><th>Day</th><th>Reason</th></tr></thead>
        <tbody>
          <?php foreach ($unavail_rows as $u): ?>
          <tr>
            <td><?= htmlspecialchars(date('d M Y', strtotime($u['unavail_date']))) ?></td>
            <td><?= htmlspecialchars(date('l', strtotime($u['unavail_date']))) ?></td>
            <td><?= $u['reason'] ? htmlspecialchars($u['reason']) : '<span style="color:var(--text-muted)">—</span>' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
