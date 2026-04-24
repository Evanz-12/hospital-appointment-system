<?php
// includes/mailer.php — PHPMailer wrapper + all system email functions

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

define('MAIL_FROM',          'medibook.hospital@gmail.com');
define('MAIL_FROM_NAME',     'MediBook Hospital');
define('MAIL_PASS',          'xulmgfozwyhaiovu');  // app password (spaces removed)
define('APP_NAME',           'MediBook');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/hospital-appointment-system');
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
    if (getenv('MAIL_ENABLED') === 'false') {
        throw new Exception('Mail disabled');
    }
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_FROM;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Timeout    = 5;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    return $mail;
}

// ── Email building blocks (table-based; fully inline-styled for Gmail) ───────

function _erow(string $label, string $value, bool $last = false): string {
    $sep = $last ? '' : 'border-bottom:1px solid #DBEAFE;';
    return '
    <tr>
      <td style="padding:10px 20px;font-size:13px;color:#6B7280;font-weight:500;width:38%;' . $sep . 'font-family:Arial,sans-serif;">' . $label . ':</td>
      <td style="padding:10px 20px;font-size:13px;font-weight:600;color:#0A1628;' . $sep . 'font-family:Arial,sans-serif;">' . $value . '</td>
    </tr>';
}

function _ebox(string $rows): string {
    return '
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0;">
      <tr>
        <td style="background:#F0F6FF;border-left:4px solid #00B4A6;border-radius:0 8px 8px 0;padding:4px 0;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0">' . $rows . '
          </table>
        </td>
      </tr>
    </table>';
}

function _ebtn(string $url, string $label): string {
    return '
    <table cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 8px;">
      <tr>
        <td style="background:#0066CC;border-radius:10px;">
          <a href="' . $url . '" style="display:block;padding:13px 30px;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;font-family:Arial,sans-serif;white-space:nowrap;">' . $label . '</a>
        </td>
      </tr>
    </table>';
}

function _ebadge(string $status, string $label = ''): string {
    $map = [
        'pending'   => 'background:#FFF7ED;color:#C05A00;',
        'approved'  => 'background:#ECFDF5;color:#166534;',
        'declined'  => 'background:#FEF2F2;color:#991B1B;',
        'cancelled' => 'background:#F3F4F6;color:#4B5563;',
        'completed' => 'background:#EFF6FF;color:#1D4ED8;',
    ];
    $s = $map[$status] ?? 'background:#F3F4F6;color:#6B7280;';
    $text = $label ?: ucfirst($status);
    return '<span style="display:inline-block;padding:3px 12px;border-radius:50px;font-size:12px;font-weight:700;' . $s . 'font-family:Arial,sans-serif;">' . $text . '</span>';
}

function _enotice(string $html, bool $warning = false): string {
    if ($warning) {
        return '
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0;">
      <tr><td style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400E;font-family:Arial,sans-serif;">' . $html . '</td></tr>
    </table>';
    }
    return '
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0;">
      <tr><td style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#166534;font-family:Arial,sans-serif;">' . $html . '</td></tr>
    </table>';
}

// Main email wrapper — fully table-based so it renders correctly in all clients
function email_template(string $title, string $body): string {
    $year = date('Y');
    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin:0;padding:0;background:#EEF2F7;font-family:Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td align="center" style="padding:32px 16px;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;">

    <!-- HEADER -->
    <tr>
      <td style="background:linear-gradient(160deg,#004E9A 0%,#0066CC 55%,#0080F0 100%);padding:32px 40px 28px;text-align:center;border-radius:16px 16px 0 0;">
        <table cellpadding="0" cellspacing="0" border="0" align="center" style="margin-bottom:10px;">
          <tr>
            <td align="center" valign="middle"
                style="width:42px;height:42px;background:rgba(255,255,255,0.2);border-radius:10px;
                       font-size:22px;font-weight:900;color:#ffffff;text-align:center;
                       vertical-align:middle;font-family:Arial,sans-serif;line-height:42px;">&#x271A;</td>
            <td valign="middle"
                style="padding-left:10px;font-size:20px;font-weight:700;color:#ffffff;
                       font-family:Arial,sans-serif;white-space:nowrap;vertical-align:middle;
                       letter-spacing:-0.3px;">MediBook</td>
          </tr>
        </table>
        <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.72);letter-spacing:0.6px;
                  text-transform:uppercase;font-family:Arial,sans-serif;">Hospital Appointment System</p>
      </td>
    </tr>

    <!-- BODY -->
    <tr>
      <td style="background:#ffffff;padding:36px 40px;">' . $body . '</td>
    </tr>

    <!-- FOOTER -->
    <tr>
      <td style="background:#0F1724;padding:20px 40px;text-align:center;border-radius:0 0 16px 16px;">
        <p style="margin:0;font-size:12px;color:#6B7280;font-family:Arial,sans-serif;">
          &copy; ' . $year . ' MediBook &nbsp;&bull;&nbsp; Crawford University Hospital
        </p>
        <p style="margin:4px 0 0;font-size:12px;color:#4B5563;font-family:Arial,sans-serif;">
          This is an automated message &mdash; please do not reply directly to this email.
        </p>
      </td>
    </tr>

  </table>

</td></tr>
</table>

</body>
</html>';
}

// ── 1. Booking confirmation to patient ──────────────────────────────────────
function email_booking_confirmation(string $to_email, string $to_name, array $appt): bool {
    try {
        $mail = make_mailer();
        $mail->addAddress($to_email, $to_name);
        $mail->Subject = APP_NAME . ' — Appointment Booking Confirmed';

        $body =
            '<h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0A1628;font-family:Arial,sans-serif;">Booking Received!</h2>
            <p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              Hi <strong>' . htmlspecialchars($to_name) . '</strong>, your appointment request has been submitted and is awaiting admin approval.
            </p>'
            . _ebox(
                _erow('Doctor',     htmlspecialchars($appt['doctor_name'])) .
                _erow('Department', htmlspecialchars($appt['department'])) .
                _erow('Date',       htmlspecialchars(date('D, d M Y', strtotime($appt['date'])))) .
                _erow('Time',       htmlspecialchars(date('g:i A',    strtotime($appt['time'])))) .
                _erow('Status',     _ebadge('pending', 'Pending Approval'), true)
            ) .
            '<p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              You will receive another email once an admin approves or declines your appointment.
            </p>'
            . _ebtn(APP_URL . '/patient/appointments.php', 'View My Appointments');

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

        $body =
            '<h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0A1628;font-family:Arial,sans-serif;">New Appointment Request</h2>
            <p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              Hi <strong>' . htmlspecialchars($doctor_name) . '</strong>, a patient has requested an appointment with you.
            </p>'
            . _ebox(
                _erow('Patient', htmlspecialchars($appt['patient_name'])) .
                _erow('Date',    htmlspecialchars(date('D, d M Y', strtotime($appt['date'])))) .
                _erow('Time',    htmlspecialchars(date('g:i A',    strtotime($appt['time'])))) .
                _erow('Reason',  htmlspecialchars($appt['reason'] ?: 'Not specified')) .
                _erow('Status',  _ebadge('pending', 'Pending Admin Approval'), true)
            ) .
            '<p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              The appointment is pending admin approval. You will be notified once it is confirmed.
            </p>'
            . _ebtn(APP_URL . '/doctor/schedule.php', 'View My Schedule');

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

        $body =
            '<h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0A1628;font-family:Arial,sans-serif;">Appointment ' . ucfirst($status) . '</h2>
            <p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              Hi <strong>' . htmlspecialchars($to_name) . '</strong>, your appointment has been <strong>' . htmlspecialchars($status) . '</strong>.
            </p>'
            . _ebox(
                _erow('Doctor',     htmlspecialchars($appt['doctor_name'])) .
                _erow('Department', htmlspecialchars($appt['department'])) .
                _erow('Date',       htmlspecialchars(date('D, d M Y', strtotime($appt['date'])))) .
                _erow('Time',       htmlspecialchars(date('g:i A',    strtotime($appt['time'])))) .
                _erow('Status',     _ebadge($status), true)
            ) .
            ($is_approved
                ? _enotice('&#10003;&nbsp; Please arrive <strong>10 minutes early</strong> with any relevant medical records.')
                  . _ebtn(APP_URL . '/patient/appointments.php', 'View Appointment')
                : ($appt['notes']
                    ? _enotice('<strong>Admin note:</strong> ' . htmlspecialchars($appt['notes']), true)
                    : '') .
                  '<p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
                    You may book another appointment at a different time.
                  </p>'
                  . _ebtn(APP_URL . '/patient/book.php', 'Book Again'));

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

        $body =
            '<h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0A1628;font-family:Arial,sans-serif;">Appointment Cancelled</h2>
            <p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              Hi <strong>' . htmlspecialchars($doctor_name) . '</strong>, a patient has cancelled their appointment with you.
            </p>'
            . _ebox(
                _erow('Patient', htmlspecialchars($appt['patient_name'])) .
                _erow('Date',    htmlspecialchars(date('D, d M Y', strtotime($appt['date'])))) .
                _erow('Time',    htmlspecialchars(date('g:i A',    strtotime($appt['time'])))) .
                _erow('Status',  _ebadge('cancelled'), true)
            ) .
            '<p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              That time slot is now free for other patients to book.
            </p>'
            . _ebtn(APP_URL . '/doctor/schedule.php', 'View My Schedule');

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

        $body =
            '<h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0A1628;font-family:Arial,sans-serif;">Reset Your Password</h2>
            <p style="margin:0 0 12px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              Hi <strong>' . htmlspecialchars($to_name) . '</strong>, we received a request to reset the password for your MediBook account.
            </p>
            <p style="margin:0 0 4px;font-size:14px;color:#4B5563;line-height:1.7;font-family:Arial,sans-serif;">
              Click the button below to set a new password. This link expires in <strong>1 hour</strong>.
            </p>'
            . _ebtn($reset_link, 'Reset My Password') .
            '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0 0;">
               <tr><td style="border-top:1px solid #E5E7EB;padding-top:16px;">
                 <p style="margin:0 0 8px;font-size:12px;color:#9CA3AF;font-family:Arial,sans-serif;">
                   If you did not request a password reset, you can safely ignore this email &mdash; your password will remain unchanged.
                 </p>
                 <p style="margin:0;font-size:12px;color:#9CA3AF;font-family:Arial,sans-serif;">
                   Or paste this link into your browser:<br>
                   <a href="' . $reset_link . '" style="color:#3B82F6;word-break:break-all;">' . $reset_link . '</a>
                 </p>
               </td></tr>
             </table>';

        $mail->Body = email_template('Password Reset', $body);
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}
