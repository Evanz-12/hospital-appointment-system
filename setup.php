<?php
require_once 'config.php';

$admin_email    = 'admin@medibook.com';
$admin_pass     = password_hash('Admin@1234', PASSWORD_BCRYPT);
$doctor_email   = 'doctor@medibook.com';
$doctor_pass    = password_hash('Doctor@1234', PASSWORD_BCRYPT);

// Insert admin
$stmt = mysqli_prepare($conn,
    "INSERT IGNORE INTO users (full_name, email, password, role, is_active)
     VALUES ('Administrator', ?, ?, 'admin', 1)");
mysqli_stmt_bind_param($stmt, 'ss', $admin_email, $admin_pass);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Insert doctor user
$stmt = mysqli_prepare($conn,
    "INSERT IGNORE INTO users (full_name, email, password, role, is_active)
     VALUES ('Dr. Demo Doctor', ?, ?, 'doctor', 1)");
mysqli_stmt_bind_param($stmt, 'ss', $doctor_email, $doctor_pass);
mysqli_stmt_execute($stmt);
$doctor_user_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Get first department
$res  = mysqli_query($conn, "SELECT id FROM departments LIMIT 1");
$dept = mysqli_fetch_assoc($res);
$dept_id = $dept ? $dept['id'] : null;

if ($doctor_user_id && $dept_id) {
    $stmt = mysqli_prepare($conn,
        "INSERT IGNORE INTO doctors (user_id, department_id, specialisation, available_days, available_time_start, available_time_end)
         VALUES (?, ?, 'General Practice', 'Mon,Tue,Wed,Thu,Fri', '08:00:00', '17:00:00')");
    mysqli_stmt_bind_param($stmt, 'ii', $doctor_user_id, $dept_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

echo "<h2>Setup complete</h2>";
echo "<p><strong>Admin:</strong> admin@medibook.com / Admin@1234</p>";
echo "<p><strong>Doctor:</strong> doctor@medibook.com / Doctor@1234</p>";
echo "<p style='color:red'>Delete setup.php now!</p>";
