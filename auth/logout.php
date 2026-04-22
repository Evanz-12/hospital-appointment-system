<?php
session_start();
require_once '../config.php';

session_unset();
session_destroy();

// Fresh session to carry the flash through to login
session_start();
$_SESSION['flash'] = ['type' => 'success', 'message' => 'You have been signed out successfully.'];

header("Location: " . BASE_URL . "/auth/login.php");
exit();
