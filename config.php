<?php
$_db_url = getenv('MYSQL_PUBLIC_URL');
if ($_db_url) {
    $_p = parse_url($_db_url);
    define('DB_HOST', $_p['host']);
    define('DB_USER', $_p['user']);
    define('DB_PASS', $_p['pass']);
    define('DB_NAME', ltrim($_p['path'], '/'));
    define('DB_PORT', $_p['port'] ?? 3306);
} else {
    define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
    define('DB_USER', getenv('MYSQL_USER') ?: 'root');
    define('DB_PASS', getenv('MYSQL_PASSWORD') ?: '');
    define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'hospital_db');
    define('DB_PORT', (int)(getenv('MYSQL_PORT') ?: 3306));
}

define('BASE_URL', getenv('APP_URL') ?: '/hospital-appointment-system');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
