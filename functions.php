<?php
// functions.php

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Add 'active' class for navigation
function is_active_link($page_name) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page === $page_name) ? 'active' : '';
}

// Fetch admin by ID
function getAdminById($admin_id, $conn) {
    $stmt = $conn->prepare("SELECT id, email, role, last_login_at FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fetch all admins
function getAllAdmins($conn) {
    $stmt = $conn->prepare("SELECT id, email, role, last_login_at FROM admins ORDER BY id ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get total count from any table
function getTotalCount($table_name, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM " . $table_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

// Get pending result publications
function getPendingResultsCount($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM results WHERE published = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}
