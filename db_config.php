<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Your database username
define('DB_PASS', '');         // Your database password (empty by default for XAMPP/WAMPP)
define('DB_NAME', 'result_system'); // Your database name


$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");

// Error reporting (for development, disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>