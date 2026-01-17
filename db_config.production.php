<?php
// PRODUCTION DATABASE CONFIGURATION
// ⚠️ Update these values with your hosting provider's credentials

define('DB_HOST', 'localhost');  // Usually 'localhost' on shared hosting
define('DB_USER', 'your_database_username');  // From cPanel MySQL Databases
define('DB_PASS', 'your_strong_password');    // Your database password
define('DB_NAME', 'your_database_name');      // Database name from cPanel

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log error instead of displaying
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("Sorry, we are experiencing technical difficulties. Please try again later.");
}

// Set charset
$conn->set_charset("utf8mb4");

// PRODUCTION MODE - Errors are logged, not displayed
// The .htaccess file handles PHP error display settings
?>
