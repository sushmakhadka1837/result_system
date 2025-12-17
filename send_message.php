<?php
session_start();
require 'db_config.php';
$student_id = $_SESSION['student_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];

$stmt = $conn->prepare("INSERT INTO messages(sender_id,receiver_id,message,sender_type) VALUES(?,?,?, 'student')");
$stmt->bind_param("iis",$student_id,$receiver_id,$message);
$stmt->execute();
