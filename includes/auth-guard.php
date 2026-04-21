<?php
// Usage: set $required_role before including this file (after session_start())
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}
