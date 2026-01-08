<?php
session_start();
require 'db_config.php';

if(isset($_SESSION['student_id'])){
    $student_id = $_SESSION['student_id'];

    // Record logout activity
    $conn->query("INSERT INTO student_activity (student_id, activity_type) VALUES ($student_id, 'logout')");
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Optional: clear session cookie (extra security)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect user back to index.php after logout
header("Location: index.php");
exit;
?>
