<?php
// includes/mailer.php — PHPMailer wrapper + all system email functions

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

define('MAIL_FROM',        'medibook.hospital@gmail.com');
define('MAIL_FROM_NAME',   'MediBook Hospital');
define('MAIL_PASS',        'xulmgfozwyhaiovu');  // app password (spaces removed)
define('APP_NAME',         'MediBook');
define('APP_URL',          'http://localhost/hospital-appointment-system');
define('DOCTOR_MAIL_DOMAIN', 'medibook.com');

// Doctor work emails (@medibook.com) are display-only — reroute them to the
// project Gmail inbox using Gmail's + alias trick so delivery actually works.
function resolve_doctor_email(string $email): string {
    if (str_ends_with(strtolower($email), '@' . DOCTOR_MAIL_DOMAIN)) {
        $local = explode('@', $email)[0];
        return 'medibook.hospital+' . $local . '@gmail.com';
    }
    return $email;
}

function make_mailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_FROM;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    return $mail;
}

function email_template(string $title, string $body): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>
      body{font-family:\'DM Sans\',Arial,sans-serif;background:#F9FAFB;margin:0;padding:0;}
      .wrap{max-width:560px;margin:32px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);}
      .header{background:linear-gradient(135deg,#114E66,#1A6B8A);padding:28px 32px;text-align:center;}
      .header h1{color:#fff;margin:0;font-size:1.3rem;font-family:Georgia,serif;}
      .header p{color:rgba(255,255,255,.75);margin:6px 0 0;font-size:.85rem;}
      .body{padding:32px;}
      .body h2{color:#1C1C1E;font-size:1.1rem;margin-top:0;}
      .body p{color:#4B5563;line-height:1.7;font-size:.92rem;}
      .detail-box{background:#F3F8FB;border-left:4px solid #1A6B8A;border-radius:6px;padding:16px 20px;margin:18px 0;}
      .detail-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #E5EDF2;font-size:.88rem;}
      .detail-row:last-child{border-bottom:none;}
      .detail-row span:first-child{color:#6B7280;font-weight:500;}
      .detail-row span:last-child{font-weight:600;color:#1C1C1E;}
      .btn{display:inline-block;padding:12px 28px;background:#1A6B8A;color:#fff!important;border-radius:8px;text-decoration:none;font-weight:600;font-size:.9rem;margin:18px 0;}
      .badge{display:inline-block;padding:3px 12px;border-radius:50px;font-size:.78rem;font-weight:700;}
      .badge-pending{background:#FFF7ED;color:#F57F17;}
      .badge-approved{background:#ECFDF5;color:#2E7D32;}
      .badge-declined{background:#FEF2F2;color:#C62828;}
      .badge-cancelled{background:#F3F4F6;color:#6B7280;}
      .footer{background:#F3F4F6;padding:16px 32px;text-align:center;font-size:.78rem;color:#9CA3AF;}
    </style></head><body>
    <div class="wrap">
      <div class="header">
        <h1>&#x2665; ' . APP_NAME . '</h1>
        <p>Hospital Appointment Booking System</p>
      </div>
      <div class="body">' . $body . '</div>
      <div class="footer">&copy; ' . date('Y') . ' ' . APP_NAME . ' &mdash; Crawford University Hospital &mdash; This is an automated message, please do not reply.</div>
    </div></body></html>';
}

// ── 1. Booking confirmation to patient ──────────────────────────────────────
function email_booking_confirmation(string $to_email, string $to_name, array $appt): bool {
    try {
        $mail = make_mailer();
        $mail->addAddress($to_email, $to_name);
        $mail->Subject = APP_NAME . ' — Appointment Booking Confirmed';
        $body = '
        <h2>Booking Received!</h2>
        <p>Hi <strong>' . htmlspecialchars($to_name) . '</strong>, your appointment request has been submitted and is awaiting admin approval.</p>
        <div class="detail-box">
          <div class="detail-row"><span>Doctor</span><span>' . htmlspecialchars($appt['doctor_name']) . '</span></div>
          <div class="detail-row"><span>Department</span><span>' . htmlspecialchars($appt['department']) . '</span></div>
          <div class="detail-row"><span>Date</span><span>' . htmlspecialchars(date('D, d M Y', strtotime($appt['date']))) . '</span></div>
          <div class="detail-row"><span>Time</span><span>' . htmlspecialchars(date('g:i A', strtotime($appt['time']))) . '</span></div>
          <div class="detail-row"><span>Status</span><span><span class="badge badge-pending">Pending Approval</span></span></div>
        </div>
        <p>You will receive another email once an admin approves or declines your appointment.</p>
        <a href="' . APP_URL . '/patient/appointments.php" class="btn">View My Appointments</a>';
        $mail->Body = email_template('Booking Confirmation', $body);
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// ── 2. New booking alert to doctor ──────────────────────────────────────────
function email_doctor_new_booking(string $to_email, string $doctor_name, array $appt): bool {
    try {
        $mail = make_mailer();
        $mail->addAddress(resolve_doctor_email($to_email), $doctor_name);
        $mail->Subject = APP_NAME . ' — New Appointment Request';
        $body = '
        <h2>New Appointment Request</h2>
        <p>Hi <strong>' . htmlspecialchars($doctor_name) . '</strong>, a patient has requested an appointment with you.</p>
        <div class="detail-box">
          <div class="detail-row"><span>Patient</span><span>' . htmlspecialchars($appt['patient_name']) . '</span></div>
          <div class="detail-row"><span>Date</span><span>' . htmlspecialchars(date('D, d M Y', strtotime($appt['date']))) . '</span></div>
          <div class="detail-row"><span>Time</span><span>' . htmlspecialchars(date('g:i A', strtotime($appt['time']))) . '</span></div>
          <div class="detail-row"><span>Reason</span><span>' . htmlspecialchars($appt['reason'] ?: 'Not specified') . '</span></div>
          <div class="detail-row"><span>Status</span><span><span class="badge badge-pending">Pending Admin Approval</span></span></div>
        </div>
        <p>The appointment is pending admin approval. You will be notified once it is confirmed.</p>
        <a href="' . APP_URL . '/doctor/schedule.php" class="btn">View My Schedule</a>';
        $mail->Body = email_template('New Appointment', $body);
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// ── 3. Approval / Decline notification to patient ───────────────────────────
function email_appointment_status(string $to_email, string $to_name, array $appt, string $status): bool {
    try {
        $mail = make_mailer();
        $mail->addAddress($to_email, $to_name);
        $is_approved = $status === 'approved';
        $mail->Subject = APP_NAME . ' — Appointment ' . ucfirst($status);
        $body = '
        <h2>Appointment ' . ucfirst($status) . '</h2>
        <p>Hi <strong>' . htmlspecialchars($to_name) . '</strong>, your appointment has been <strong>' . $status . '</strong>.</p>
        <div class="detail-box">
          <div class="detail-row"><span>Doctor</span><span>' . htmlspecialchars($appt['doctor_name']) . '</span></div>
          <div class="detail-row"><span>Department</span><span>' . htmlspecialchars($appt['department']) . '</span></div>
          <div class="detail-row"><span>Date</span><span>' . htmlspecialchars(date('D, d M Y', strtotime($appt['date']))) . '</span></div>
          <div class="detail-row"><span>Time</span><span>' . htmlspecialchars(date('g:i A', strtotime($appt['time']))) . '</span></div>
          <div class="detail-row"><span>Status</span><span><span class="badge badge-' . $status . '">' . ucfirst($status) . '</span></span></div>
        </div>' .
        ($is_approved
            ? '<p>Please arrive <strong>10 minutes early</strong> with any relevant medical records.</p><a href="' . APP_URL . '/patient/appointments.php" class="btn">View Appointment</a>'
            : ($appt['notes'] ? '<p><strong>Reason:</strong> ' . htmlspecialchars($appt['notes']) . '</p>' : '') .
              '<p>You may book another appointment at a different time.</p><a href="' . APP_URL . '/patient/book.php" class="btn">Book Again</a>');
        $mail->Body = email_template('Appointment ' . ucfirst($status), $body);
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// ── 4. Cancellation notice to doctor ────────────────────────────────────────
function email_doctor_cancellation(string $to_email, string $doctor_name, array $appt): bool {
    try {
        $mail = make_mailer();
        $mail->addAddress(resolve_doctor_email($to_email), $doctor_name);
        $mail->Subject = APP_NAME . ' — Appointment Cancelled by Patient';
        $body = '
        <h2>Appointment Cancelled</h2>
        <p>Hi <strong>' . htmlspecialchars($doctor_name) . '</strong>, a patient has cancelled their appointment with you.</p>
        <div class="detail-box">
          <div class="detail-row"><span>Patient</span><span>' . htmlspecialchars($appt['patient_name']) . '</span></div>
          <div class="detail-row"><span>Date</span><span>' . htmlspecialchars(date('D, d M Y', strtotime($appt['date']))) . '</span></div>
          <div class="detail-row"><span>Time</span><span>' . htmlspecialchars(date('g:i A', strtotime($appt['time']))) . '</span></div>
          <div class="detail-row"><span>Status</span><span><span class="badge badge-cancelled">Cancelled</span></span></div>
        </div>
        <p>That time slot is now free for other patients to book.</p>
        <a href="' . APP_URL . '/doctor/schedule.php" class="btn">View My Schedule</a>';
        $mail->Body = email_template('Appointment Cancelled', $body);
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// ── 5. Password reset email ──────────────────────────────────────────────────
function email_password_reset(string $to_email, string $to_name, string $token): bool {
    try {
        $mail = make_mailer();
        $mail->addAddress($to_email, $to_name);
        $mail->Subject = APP_NAME . ' — Password Reset Request';
        $reset_link = APP_URL . '/auth/reset-password.php?token=' . urlencode($token);
        $body = '
        <h2>Reset Your Password</h2>
        <p>Hi <strong>' . htmlspecialchars($to_name) . '</strong>, we received a request to reset your password.</p>
        <p>Click the button below to set a new password. This link expires in <strong>1 hour</strong>.</p>
        <a href="' . $reset_link . '" class="btn">Reset My Password</a>
        <p style="margin-top:20px;font-size:.82rem;color:#9CA3AF;">If you did not request this, you can safely ignore this email. Your password will not change.</p>
        <p style="font-size:.82rem;color:#9CA3AF;">Or copy this link into your browser:<br><a href="' . $reset_link . '">' . $reset_link . '</a></p>';
        $mail->Body = email_template('Password Reset', $body);
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}
