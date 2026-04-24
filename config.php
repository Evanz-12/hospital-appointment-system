<?php
define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'hospital_db');

define('BASE_URL', getenv('APP_URL') ?: '/hospital-appointment-system');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
