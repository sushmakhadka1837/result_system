<?php
session_start();
require 'db_config.php';

$msg_id = intval($_POST['msg_id']);
$new_msg = trim($_POST['message']);
$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("
UPDATE messages 
SET message=? 
WHERE id=? AND sender_id=? AND sender_type='student'
");
$stmt->bind_param("sii", $new_msg, $msg_id, $student_id);
$stmt->execute();
