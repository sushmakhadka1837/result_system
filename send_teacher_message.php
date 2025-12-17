<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['teacher_id'])){
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$student_id = $_POST['student_id'] ?? 0;
$message = $_POST['message'] ?? '';

if($student_id && $message){
    $stmt = $conn->prepare("INSERT INTO messages (sender_type, sender_id, receiver_id, message) VALUES ('teacher', ?, ?, ?)");
    $stmt->bind_param("iis", $teacher_id, $student_id, $message);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the same chat
header("Location: teacher_chat.php?student_id=".$student_id);
exit();
