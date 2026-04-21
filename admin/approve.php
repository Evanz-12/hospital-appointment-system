<?php
session_start();
require_once '../config.php';
require_once '../includes/mailer.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/appointments.php"); exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
    header("Location: " . BASE_URL . "/admin/appointments.php"); exit();
}

$appointment_id = (int)($_POST['appointment_id'] ?? 0);
$action         = $_POST['action'] ?? '';

$allowed = ['approve' => 'approved', 'decline' => 'declined', 'complete' => 'completed'];
if (!$appointment_id || !isset($allowed[$action])) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid action.'];
    header("Location: " . BASE_URL . "/admin/appointments.php"); exit();
}

$new_status = $allowed[$action];

// Fetch appointment details for email before updating
$stmt_info = mysqli_prepare($conn,
    "SELECT a.appointment_date, a.appointment_time, a.notes,
            pu.full_name AS patient_name, pu.email AS patient_email,
            du.full_name AS doctor_name, dep.name AS department
     FROM appointments a
     JOIN users pu ON a.patient_id = pu.id
     JOIN doctors dr ON a.doctor_id = dr.id
     JOIN users du ON dr.user_id = du.id
     JOIN departments dep ON dr.department_id = dep.id
     WHERE a.id = ?");
mysqli_stmt_bind_param($stmt_info, 'i', $appointment_id);
mysqli_stmt_execute($stmt_info);
$appt = mysqli_stmt_get_result($stmt_info)->fetch_assoc();
mysqli_stmt_close($stmt_info);

// Update status
$stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=?");
mysqli_stmt_bind_param($stmt, 'si', $new_status, $appointment_id);
if (mysqli_stmt_execute($stmt)) {
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Appointment ' . $new_status . ' successfully.'];

    // Send email to patient for approve/decline only
    if ($appt && in_array($new_status, ['approved', 'declined'])) {
        email_appointment_status(
            $appt['patient_email'],
            $appt['patient_name'],
            [
                'doctor_name' => $appt['doctor_name'],
                'department'  => $appt['department'],
                'date'        => $appt['appointment_date'],
                'time'        => $appt['appointment_time'],
                'notes'       => $appt['notes'],
            ],
            $new_status
        );
    }
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Action failed. Please try again.'];
}
mysqli_stmt_close($stmt);

header("Location: " . BASE_URL . "/admin/appointments.php");
exit();
