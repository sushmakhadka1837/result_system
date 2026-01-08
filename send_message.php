<?php
session_start();
require 'db_config.php';
error_reporting(E_ALL); ini_set('display_errors',1);

if(!isset($_SESSION['student_id'])) die("SESSION missing");
if(!isset($_POST['receiver_id'],$_POST['message'])) die("POST missing");

$sender_id = intval($_SESSION['student_id']);
$receiver_id = intval($_POST['receiver_id']);
$message = trim($_POST['message']);
if($message=='') die("Empty message");

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sender_type, is_read) VALUES (?,?,?, 'student',0)");
$stmt->bind_param("iis",$sender_id,$receiver_id,$message);
$stmt->execute();
echo "SUCCESS";
