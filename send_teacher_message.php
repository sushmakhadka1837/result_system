<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['teacher_id']) || $_SESSION['user_type']!='teacher'){
    die("Teacher not logged in");
}

if(empty($_POST['receiver_id']) || empty($_POST['message'])){
    die("Missing data");
}

$teacher_id = intval($_SESSION['teacher_id']);
$student_id = intval($_POST['receiver_id']);
$message = trim($_POST['message']);

if($message=='') die("Empty message");

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sender_type, is_read) VALUES (?, ?, ?, 'teacher', 0)");
$stmt->bind_param("iis", $teacher_id, $student_id, $message);

if($stmt->execute()){
    echo "SUCCESS";
}else{
    echo "ERROR: ".$conn->error;
}

$stmt->close();
