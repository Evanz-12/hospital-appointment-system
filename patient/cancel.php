<?php
session_start();
require_once '../config.php';
require_once '../includes/mailer.php';
$required_role = 'patient';
require_once '../includes/auth-guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/patient/appointments.php"); exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token_cancel']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request. Please try again.'];
    header("Location: " . BASE_URL . "/patient/appointments.php"); exit();
}

$appointment_id = (int)($_POST['appointment_id'] ?? 0);
$patient_id     = $_SESSION['user_id'];

if (!$appointment_id) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid appointment.'];
    header("Location: " . BASE_URL . "/patient/appointments.php"); exit();
}

// Fetch full details before cancelling (for email)
$stmt = mysqli_prepare($conn,
    "SELECT a.appointment_date, a.appointment_time,
            pu.full_name AS patient_name,
            du.full_name AS doctor_name, du.email AS doctor_email
     FROM appointments a
     JOIN users pu ON a.patient_id = pu.id
     JOIN doctors dr ON a.doctor_id = dr.id
     JOIN users du ON dr.user_id = du.id
     WHERE a.id = ? AND a.patient_id = ? AND a.status IN ('pending','approved') AND a.appointment_date >= CURDATE()");
mysqli_stmt_bind_param($stmt, 'ii', $appointment_id, $patient_id);
mysqli_stmt_execute($stmt);
$appt = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$appt) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Appointment cannot be cancelled.'];
    header("Location: " . BASE_URL . "/patient/appointments.php"); exit();
}

$stmt2 = mysqli_prepare($conn, "UPDATE appointments SET status='cancelled' WHERE id=?");
mysqli_stmt_bind_param($stmt2, 'i', $appointment_id);
if (mysqli_stmt_execute($stmt2)) {
    // Notify the doctor
    email_doctor_cancellation(
        $appt['doctor_email'],
        $appt['doctor_name'],
        [
            'patient_name' => $appt['patient_name'],
            'date'         => $appt['appointment_date'],
            'time'         => $appt['appointment_time'],
        ]
    );
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Appointment cancelled successfully.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to cancel appointment. Please try again.'];
}
mysqli_stmt_close($stmt2);

header("Location: " . BASE_URL . "/patient/appointments.php");
exit();
