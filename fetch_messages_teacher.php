<?php
session_start();
require 'db_config.php';

$teacher_id = $_SESSION['teacher_id'];
$student_id = intval($_GET['receiver_id']);

// ---------- Mark all student → teacher messages as read ----------
$conn->query("UPDATE messages 
              SET is_read=1 
              WHERE receiver_id=$teacher_id 
                AND sender_id=$student_id 
                AND sender_type='student'");

// ---------- Fetch messages ----------
$stmt = $conn->prepare("
SELECT * FROM messages
WHERE 
(sender_id=? AND receiver_id=? AND sender_type='teacher')
OR (sender_id=? AND receiver_id=? AND sender_type='student')
ORDER BY created_at ASC
");
$stmt->bind_param("iiii",$teacher_id,$student_id,$student_id,$teacher_id);
$stmt->execute();
$res = $stmt->get_result();

while($m=$res->fetch_assoc()){
    $cls = ($m['sender_type']=='teacher')?'teacher-message':'student-message';
    $time = date('h:i A', strtotime($m['created_at']));
    echo "<div class='message $cls'>".htmlspecialchars($m['message'])."<div class='time'>$time</div></div>";
}
