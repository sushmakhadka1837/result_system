<?php
session_start();
require 'db_config.php';
$student_id = (int)($_SESSION['student_id'] ?? 0);

if ($student_id <= 0) {
	echo 0;
	exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM messages WHERE receiver_id = ? AND sender_type = 'teacher' AND is_read = 0");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
echo (int)($row['cnt'] ?? 0);
