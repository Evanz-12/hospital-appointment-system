<?php
session_start();
require_once '../config.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

$page_title = 'Reports';
$extra_css  = ['dashboard.css'];

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) { $month = date('Y-m'); }
[$year_sel, $mon_sel] = explode('-', $month);
$start_of_month = "$year_sel-$mon_sel-01";
$end_of_month   = date('Y-m-t', strtotime($start_of_month));

// 1. Total this month
$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS c FROM appointments WHERE appointment_date BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, 'ss', $start_of_month, $end_of_month);
mysqli_stmt_execute($stmt);
$total_month = mysqli_stmt_get_result($stmt)->fetch_assoc()['c'];
mysqli_stmt_close($stmt);

// 2. Breakdown by status (this month)
$stmt2 = mysqli_prepare($conn,
    "SELECT status, COUNT(*) AS cnt FROM appointments
     WHERE appointment_date BETWEEN ? AND ? GROUP BY status");
mysqli_stmt_bind_param($stmt2, 'ss', $start_of_month, $end_of_month);
mysqli_stmt_execute($stmt2);
$by_status = mysqli_stmt_get_result($stmt2)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

// 3. Top 3 most-booked doctors (all time)
$top_doctors = mysqli_query($conn,
    "SELECT u.full_name, dep.name AS department, COUNT(a.id) AS total
     FROM appointments a
     JOIN doctors dr ON a.doctor_id = dr.id
     JOIN users u ON dr.user_id = u.id
     JOIN departments dep ON dr.department_id = dep.id
     WHERE a.status NOT IN ('cancelled','declined')
     GROUP BY a.doctor_id ORDER BY total DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// 4. Total patients
$total_patients = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='patient'"))['c'];

// 5. Monthly trend (last 6 months)
$trend = mysqli_query($conn,
    "SELECT DATE_FORMAT(appointment_date,'%Y-%m') AS mo, COUNT(*) AS cnt
     FROM appointments
     WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY mo ORDER BY mo")->fetch_all(MYSQLI_ASSOC);

// 6. Department breakdown (all time)
$dept_stats = mysqli_query($conn,
    "SELECT dep.name, COUNT(a.id) AS total
     FROM appointments a
     JOIN doctors dr ON a.doctor_id = dr.id
     JOIN departments dep ON dr.department_id = dep.id
     WHERE a.status NOT IN ('cancelled','declined')
     GROUP BY dep.id ORDER BY total DESC")->fetch_all(MYSQLI_ASSOC);

// Build month options (last 12 months)
$month_options = [];
for ($i = 0; $i < 12; $i++) {
    $m = date('Y-m', strtotime("-$i months"));
    $month_options[$m] = date('F Y', strtotime($m . '-01'));
}

$status_colours = [
    'pending' => 'badge-pending', 'approved' => 'badge-approved',
    'declined' => 'badge-declined', 'completed' => 'badge-completed', 'cancelled' => 'badge-cancelled'
];

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<div class="page-header">
  <h1>Reports & Statistics</h1>
  <form method="GET" style="display:flex;gap:8px;align-items:center;">
    <label style="font-size:.84rem;font-weight:600;color:var(--text-muted)">Month:</label>
    <select name="month" onchange="this.form.submit()" style="padding:7px 12px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:.88rem;">
      <?php foreach ($month_options as $val => $label): ?>
      <option value="<?= $val ?>" <?= $val === $month ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<!-- Top stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon teal"><i class="fa fa-calendar-alt"></i></div>
    <div class="stat-info"><h3><?= $total_month ?></h3><p>Appointments This Month</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa fa-users"></i></div>
    <div class="stat-info"><h3><?= $total_patients ?></h3><p>Total Registered Patients</p></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-user-md"></i></div>
    <div class="stat-info">
      <h3><?= count($top_doctors) > 0 ? htmlspecialchars($top_doctors[0]['full_name']) : 'N/A' ?></h3>
      <p>Top Doctor (All Time)</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa fa-hospital"></i></div>
    <div class="stat-info">
      <h3><?= count($dept_stats) > 0 ? htmlspecialchars($dept_stats[0]['name']) : 'N/A' ?></h3>
      <p>Busiest Department</p>
    </div>
  </div>
</div>

<div class="dash-grid">
  <!-- Status breakdown -->
  <div class="card">
    <div class="card-title"><i class="fa fa-chart-pie"></i> Appointments by Status — <?= date('F Y', strtotime($month . '-01')) ?></div>
    <?php if (empty($by_status)): ?>
      <div class="empty-state"><i class="fa fa-chart-bar"></i><p>No data for this month.</p></div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Status</th><th>Count</th><th>% of Total</th></tr></thead>
        <tbody>
          <?php foreach ($by_status as $row): $pct = $total_month > 0 ? round($row['cnt']/$total_month*100,1) : 0; ?>
          <tr>
            <td><span class="badge <?= $status_colours[$row['status']] ?? '' ?>"><?= $row['status'] ?></span></td>
            <td><strong><?= $row['cnt'] ?></strong></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <?= $pct ?>%
                <div style="flex:1;height:6px;background:var(--border);border-radius:3px;max-width:100px;">
                  <div style="height:6px;background:var(--primary);border-radius:3px;width:<?= $pct ?>%;"></div>
                </div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Top doctors -->
  <div class="card">
    <div class="card-title"><i class="fa fa-trophy"></i> Top 3 Most-Booked Doctors (All Time)</div>
    <?php if (empty($top_doctors)): ?>
      <div class="empty-state"><i class="fa fa-user-md"></i><p>No booking data yet.</p></div>
    <?php else: ?>
    <?php foreach ($top_doctors as $i => $doc): ?>
    <div style="display:flex;align-items:center;gap:14px;padding:12px 0;<?= $i < count($top_doctors)-1 ? 'border-bottom:1px solid var(--border);' : '' ?>">
      <div style="width:32px;height:32px;border-radius:50%;background:<?= ['#FFF7ED','#EFF6FF','#ECFDF5'][$i] ?>;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;color:<?= ['var(--warning)','#2563EB','var(--success)'][$i] ?>;">
        #<?= $i+1 ?>
      </div>
      <div style="flex:1;">
        <div style="font-weight:700;font-size:.9rem;"><?= htmlspecialchars($doc['full_name']) ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);"><?= htmlspecialchars($doc['department']) ?></div>
      </div>
      <strong style="font-size:1.1rem;"><?= $doc['total'] ?></strong>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="dash-grid">
  <!-- Monthly trend -->
  <div class="card">
    <div class="card-title"><i class="fa fa-chart-line"></i> Monthly Trend (Last 6 Months)</div>
    <?php if (empty($trend)): ?>
      <div class="empty-state"><i class="fa fa-chart-bar"></i><p>No trend data available.</p></div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Month</th><th>Total Appointments</th><th>Bar</th></tr></thead>
        <tbody>
          <?php
          $max_trend = max(array_column($trend, 'cnt'));
          foreach ($trend as $t): $w = $max_trend > 0 ? round($t['cnt']/$max_trend*100) : 0; ?>
          <tr>
            <td><?= htmlspecialchars(date('F Y', strtotime($t['mo'] . '-01'))) ?></td>
            <td><strong><?= $t['cnt'] ?></strong></td>
            <td>
              <div style="height:8px;background:var(--border);border-radius:4px;width:160px;">
                <div style="height:8px;background:var(--primary);border-radius:4px;width:<?= $w ?>%;"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Department breakdown -->
  <div class="card">
    <div class="card-title"><i class="fa fa-hospital"></i> Appointments by Department (All Time)</div>
    <?php if (empty($dept_stats)): ?>
      <div class="empty-state"><i class="fa fa-hospital"></i><p>No data yet.</p></div>
    <?php else: ?>
    <?php $max_d = max(array_column($dept_stats, 'total')); ?>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Department</th><th>Total</th><th>Bar</th></tr></thead>
        <tbody>
          <?php foreach ($dept_stats as $ds): $w = $max_d > 0 ? round($ds['total']/$max_d*100) : 0; ?>
          <tr>
            <td><?= htmlspecialchars($ds['name']) ?></td>
            <td><strong><?= $ds['total'] ?></strong></td>
            <td>
              <div style="height:8px;background:var(--border);border-radius:4px;width:140px;">
                <div style="height:8px;background:var(--primary);border-radius:4px;width:<?= $w ?>%;"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
