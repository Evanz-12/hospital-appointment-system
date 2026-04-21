<?php
// AJAX endpoint — returns HTML slot buttons for a given doctor + date
session_start();
require_once '../config.php';

if ($_SESSION['role'] !== 'patient') { http_response_code(403); exit(); }

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$date      = $_GET['date'] ?? '';

if (!$doctor_id || !$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo '<p style="color:var(--danger);font-size:.85rem;">Invalid request.</p>';
    exit();
}

// Validate date is not in the past
if ($date < date('Y-m-d')) {
    echo '<p style="color:var(--danger);font-size:.85rem;">Please choose a future date.</p>';
    exit();
}

// Get doctor info
$stmt = mysqli_prepare($conn, "SELECT available_days, slot_duration FROM doctors WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $doctor_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$doctor) { echo '<p style="color:var(--danger);font-size:.85rem;">Doctor not found.</p>'; exit(); }

// Check if day of week is available
$day_abbr     = date('D', strtotime($date)); // Mon, Tue…
$avail_days   = array_map('trim', explode(',', $doctor['available_days']));
if (!in_array($day_abbr, $avail_days)) {
    echo '<p style="color:var(--warning);font-size:.85rem;"><i class="fa fa-exclamation-triangle"></i> Doctor is not available on ' . htmlspecialchars($day_abbr) . 's. Please pick another date.</p>';
    exit();
}

// Check doctor_unavailability
$stmt2 = mysqli_prepare($conn, "SELECT id FROM doctor_unavailability WHERE doctor_id = ? AND unavail_date = ?");
mysqli_stmt_bind_param($stmt2, 'is', $doctor_id, $date);
mysqli_stmt_execute($stmt2);
mysqli_stmt_store_result($stmt2);
$is_unavail = mysqli_stmt_num_rows($stmt2) > 0;
mysqli_stmt_close($stmt2);

if ($is_unavail) {
    echo '<p style="color:var(--danger);font-size:.85rem;"><i class="fa fa-ban"></i> Doctor has marked this date as unavailable.</p>';
    exit();
}

// Fetch already-booked slots for this doctor+date
$stmt3 = mysqli_prepare($conn,
    "SELECT appointment_time FROM appointments
     WHERE doctor_id = ? AND appointment_date = ? AND status IN ('pending','approved')");
mysqli_stmt_bind_param($stmt3, 'is', $doctor_id, $date);
mysqli_stmt_execute($stmt3);
$booked_rows = mysqli_stmt_get_result($stmt3)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt3);

$booked_times = array_column($booked_rows, 'appointment_time');

// Generate slots 08:00 – 17:00
$slot_duration = (int)$doctor['slot_duration'];
$start = strtotime('08:00');
$end   = strtotime('17:00');
$slots = [];
for ($t = $start; $t < $end; $t += $slot_duration * 60) {
    $slots[] = date('H:i', $t);
}

if (empty($slots)) {
    echo '<p style="color:var(--text-muted);font-size:.85rem;">No slots configured for this doctor.</p>';
    exit();
}

$has_available = false;
foreach ($slots as $slot) {
    $is_taken = in_array($slot . ':00', $booked_times) || in_array($slot, $booked_times);
    if (!$is_taken) { $has_available = true; }
    $class = $is_taken ? 'slot-btn taken' : 'slot-btn';
    $label = date('g:i A', strtotime($slot));
    $taken_text = $is_taken ? ' (Taken)' : '';
    echo '<button type="button" class="' . $class . '" data-time="' . $slot . '">'
         . htmlspecialchars($label . $taken_text) . '</button>';
}

if (!$has_available) {
    echo '<p style="color:var(--warning);font-size:.82rem;margin-top:8px;"><i class="fa fa-exclamation-triangle"></i> All slots are booked for this date. Please try another date.</p>';
}
