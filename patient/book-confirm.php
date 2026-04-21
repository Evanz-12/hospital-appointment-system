<?php
session_start();
require_once '../config.php';
require_once '../includes/mailer.php';
$required_role = 'patient';
require_once '../includes/auth-guard.php';

$patient_id = $_SESSION['user_id'];

// Accept data from POST (coming from book-slot.php)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/patient/book.php");
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request. Please try again.'];
    header("Location: " . BASE_URL . "/patient/book.php");
    exit();
}

$doctor_id        = (int)($_POST['doctor_id'] ?? 0);
$appointment_date = trim($_POST['appointment_date'] ?? '');
$appointment_time = trim($_POST['appointment_time'] ?? '');
$reason           = trim($_POST['reason'] ?? '');

// Stash in session so confirm page can show a summary and re-submit
$_SESSION['booking'] = [
    'doctor_id'        => $doctor_id,
    'appointment_date' => $appointment_date,
    'appointment_time' => $appointment_time,
    'reason'           => $reason,
];

// Handle final confirmation POST (hidden action=confirm)
if (isset($_POST['action']) && $_POST['action'] === 'confirm') {
    $b = $_SESSION['booking'];

    // Re-validate
    $errors = [];
    if (!$b['doctor_id'])        $errors[] = 'Invalid doctor.';
    if (!$b['appointment_date']) $errors[] = 'Date is required.';
    if (!$b['appointment_time']) $errors[] = 'Time slot is required.';
    if ($b['appointment_date'] < date('Y-m-d')) $errors[] = 'Appointment date must be in the future.';

    if (empty($errors)) {
        // Check slot still available
        $stmt = mysqli_prepare($conn,
            "SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status IN('pending','approved')");
        $time_full = strlen($b['appointment_time']) === 5 ? $b['appointment_time'] . ':00' : $b['appointment_time'];
        mysqli_stmt_bind_param($stmt, 'iss', $b['doctor_id'], $b['appointment_date'], $time_full);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $taken = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($taken) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'That time slot was just taken. Please choose another.'];
            header("Location: " . BASE_URL . "/patient/book-slot.php?doctor_id=" . $b['doctor_id']);
            exit();
        }

        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
             VALUES (?, ?, ?, ?, ?, 'pending')");
        $t = strlen($b['appointment_time']) === 5 ? $b['appointment_time'] . ':00' : $b['appointment_time'];
        mysqli_stmt_bind_param($stmt2, 'iisss', $patient_id, $b['doctor_id'], $b['appointment_date'], $t, $b['reason']);
        if (mysqli_stmt_execute($stmt2)) {
            unset($_SESSION['booking']);

            // Fetch details for emails
            $eq = mysqli_prepare($conn,
                "SELECT u.full_name AS doctor_name, u.email AS doctor_email,
                        dep.name AS department,
                        pu.full_name AS patient_name, pu.email AS patient_email
                 FROM doctors dr
                 JOIN users u  ON dr.user_id = u.id
                 JOIN departments dep ON dr.department_id = dep.id
                 JOIN users pu ON pu.id = ?
                 WHERE dr.id = ?");
            mysqli_stmt_bind_param($eq, 'ii', $patient_id, $b['doctor_id']);
            mysqli_stmt_execute($eq);
            $eq_data = mysqli_stmt_get_result($eq)->fetch_assoc();
            mysqli_stmt_close($eq);

            if ($eq_data) {
                $appt_info = [
                    'doctor_name'  => $eq_data['doctor_name'],
                    'department'   => $eq_data['department'],
                    'patient_name' => $eq_data['patient_name'],
                    'date'         => $b['appointment_date'],
                    'time'         => $b['appointment_time'],
                    'reason'       => $b['reason'],
                ];
                email_booking_confirmation($eq_data['patient_email'], $eq_data['patient_name'], $appt_info);
                email_doctor_new_booking($eq_data['doctor_email'], $eq_data['doctor_name'], $appt_info);
            }

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Appointment booked successfully! A confirmation email has been sent.'];
            header("Location: " . BASE_URL . "/patient/appointments.php");
            exit();
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to book appointment. Please try again.'];
        }
        mysqli_stmt_close($stmt2);
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' ', $errors)];
    }
}

// Fetch doctor info for display
$b = $_SESSION['booking'];
$stmt = mysqli_prepare($conn,
    "SELECT u.full_name, dr.specialisation, dep.name AS department
     FROM doctors dr JOIN users u ON dr.user_id=u.id JOIN departments dep ON dr.department_id=dep.id
     WHERE dr.id=?");
mysqli_stmt_bind_param($stmt, 'i', $b['doctor_id']);
mysqli_stmt_execute($stmt);
$doctor = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

$page_title = 'Book Appointment — Step 4';
$extra_css  = ['dashboard.css', 'forms.css'];

// New CSRF for the confirm form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../includes/header.php';
include '../includes/sidebar-patient.php';
?>

<div class="step-indicator">
  <div class="step completed"><div class="step-circle"><i class="fa fa-check"></i></div><div class="step-label">Department</div></div>
  <div class="step completed"><div class="step-circle"><i class="fa fa-check"></i></div><div class="step-label">Doctor</div></div>
  <div class="step completed"><div class="step-circle"><i class="fa fa-check"></i></div><div class="step-label">Date & Time</div></div>
  <div class="step active"><div class="step-circle">4</div><div class="step-label">Confirm</div></div>
</div>

<div class="confirm-card">
  <h2><i class="fa fa-calendar-check" style="color:var(--primary)"></i> Review & Confirm</h2>
  <p style="color:var(--text-muted);font-size:.87rem;margin-bottom:20px;">Please review your appointment details before confirming.</p>

  <div class="confirm-row"><span>Doctor</span><span><?= htmlspecialchars($doctor['full_name'] ?? 'N/A') ?></span></div>
  <div class="confirm-row"><span>Department</span><span><?= htmlspecialchars($doctor['department'] ?? 'N/A') ?></span></div>
  <div class="confirm-row"><span>Specialisation</span><span><?= htmlspecialchars($doctor['specialisation'] ?? 'N/A') ?></span></div>
  <div class="confirm-row"><span>Date</span><span><?= htmlspecialchars(date('D, d M Y', strtotime($b['appointment_date']))) ?></span></div>
  <div class="confirm-row"><span>Time</span><span><?= htmlspecialchars(date('g:i A', strtotime($b['appointment_time']))) ?></span></div>
  <div class="confirm-row"><span>Reason</span><span><?= $b['reason'] ? htmlspecialchars(substr($b['reason'], 0, 80)) : '<em style="color:var(--text-muted)">Not specified</em>' ?></span></div>
  <div class="confirm-row" style="border-bottom:none;">
    <span>Status after booking</span>
    <span class="badge badge-pending">Pending Approval</span>
  </div>

  <form method="POST" action="" style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
    <input type="hidden" name="csrf_token"        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="doctor_id"          value="<?= htmlspecialchars($b['doctor_id']) ?>">
    <input type="hidden" name="appointment_date"   value="<?= htmlspecialchars($b['appointment_date']) ?>">
    <input type="hidden" name="appointment_time"   value="<?= htmlspecialchars($b['appointment_time']) ?>">
    <input type="hidden" name="reason"             value="<?= htmlspecialchars($b['reason']) ?>">
    <input type="hidden" name="action"             value="confirm">
    <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Confirm Booking</button>
    <a href="<?= BASE_URL ?>/patient/book-slot.php?doctor_id=<?= $b['doctor_id'] ?>" class="btn btn-outline">
      <i class="fa fa-arrow-left"></i> Go Back
    </a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
