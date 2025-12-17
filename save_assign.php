<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit();
}

$teacher_id = $_POST['teacher_id'];
$subjects = $_POST['subjects'] ?? [];

// Clear old assignments
$conn->query("DELETE FROM teacher_subjects WHERE teacher_id=$teacher_id");

// Insert new
if(!empty($subjects)){
    $stmt = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
    foreach($subjects as $sub_id){
        $stmt->bind_param("ii", $teacher_id, $sub_id);
        $stmt->execute();
    }
}

header("Location: manage_teachers.php");
exit();
