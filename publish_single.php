<?php
session_start();
require 'db_config.php';

$dept_id = $_GET['dept_id'] ?? 0;
$sem_id  = $_GET['sem_id'] ?? 0;

if(!$dept_id || !$sem_id){
    die("Invalid request!");
}

// Update results_publish_status table
$check = $conn->query("SELECT * FROM results_publish_status WHERE department_id=$dept_id AND semester_id=$sem_id");
if($check->num_rows > 0){
    // Update published status
    $conn->query("UPDATE results_publish_status SET published=1, published_at=NOW() WHERE department_id=$dept_id AND semester_id=$sem_id");
} else {
    // Insert new
    $conn->query("INSERT INTO results_publish_status (department_id, semester_id, published, published_at) VALUES ($dept_id, $sem_id, 1, NOW())");
}

// Update all student results of that department and semester as published
$conn->query("UPDATE results SET published=1 WHERE student_id IN (SELECT id FROM students WHERE department_id=$dept_id AND semester=$sem_id)");

// Redirect back to department page
header("Location: publish_department.php?dept_id=$dept_id");
exit;
