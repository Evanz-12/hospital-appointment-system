<?php
// AJAX endpoint for calendar toggle
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if ($_SESSION['role'] !== 'doctor') { echo json_encode(['success' => false]); exit(); }

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit();
}

$user_id = $_SESSION['user_id'];
$date    = $_POST['date']   ?? '';
$action  = $_POST['action'] ?? '';

if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false]);
    exit();
}

if ($date < date('Y-m-d')) {
    echo json_encode(['success' => false, 'error' => 'Cannot modify past dates']);
    exit();
}

// Get doctor id
$stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$doctor) { echo json_encode(['success' => false]); exit(); }
$doctor_id = $doctor['id'];

if ($action === 'add') {
    $stmt2 = mysqli_prepare($conn,
        "INSERT IGNORE INTO doctor_unavailability (doctor_id, unavail_date) VALUES (?,?)");
    mysqli_stmt_bind_param($stmt2, 'is', $doctor_id, $date);
    $ok = mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
} else {
    $stmt2 = mysqli_prepare($conn,
        "DELETE FROM doctor_unavailability WHERE doctor_id=? AND unavail_date=?");
    mysqli_stmt_bind_param($stmt2, 'is', $doctor_id, $date);
    $ok = mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
}

echo json_encode(['success' => (bool)$ok]);
