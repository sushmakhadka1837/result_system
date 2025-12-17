<?php
session_start();
require 'db_config.php';
$student_id = $_SESSION['student_id'];
$res = $conn->query("SELECT COUNT(*) AS cnt FROM messages WHERE receiver_id=$student_id AND is_read=0");
echo $res->fetch_assoc()['cnt'];
