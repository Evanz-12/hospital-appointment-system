<?php
require_once 'config.php';

$new_pass = password_hash('Doctor@1234', PASSWORD_BCRYPT);

$stmt = mysqli_prepare($conn,
    "UPDATE users SET password = ? WHERE role = 'doctor'");
mysqli_stmt_bind_param($stmt, 's', $new_pass);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

echo "<h2>Done</h2>";
echo "<p>Reset password for <strong>$affected</strong> doctor account(s).</p>";
echo "<p>All doctors can now log in with password: <strong>Doctor@1234</strong></p>";
echo "<p style='color:red'>Delete this file now!</p>";
