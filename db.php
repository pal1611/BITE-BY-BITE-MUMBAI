<?php
// ─── Database Configuration ───────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Your MySQL username (XAMPP default: root)
define('DB_PASS', '');           // Your MySQL password (XAMPP default: empty)
define('DB_NAME', 'bitebybite');

// ─── Create Connection ────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ─── Check Connection ─────────────────────────────────────────
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
