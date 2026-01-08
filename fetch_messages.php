<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id']) || $_SESSION['user_type']!='student'){
    http_response_code(403);
    echo json_encode(['error'=>'Unauthorized']); 
    exit();
}

$student_id = $_SESSION['student_id'];
$teacher_id = intval($_GET['receiver_id'] ?? 0);

if(!$teacher_id){
    echo json_encode(['messages'=>[], 'unread_count'=>0]);
    exit();
}

/* ---------- Mark teacher messages as read ---------- */
$stmt = $conn->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND sender_type='teacher' AND is_read=0");
$stmt->bind_param("ii", $teacher_id, $student_id);
$stmt->execute();
$stmt->close();

/* ---------- Fetch all messages between student and selected teacher ---------- */
$sql = "SELECT * FROM messages 
        WHERE (sender_id=? AND sender_type='student' AND receiver_id=?) 
           OR (sender_id=? AND sender_type='teacher' AND receiver_id=?) 
        ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $student_id, $teacher_id, $teacher_id, $student_id);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while($row = $res->fetch_assoc()){
    $messages[] = [
        'id' => $row['id'],
        'sender_type' => $row['sender_type'],
        'message' => htmlspecialchars($row['message']),
        'created_at' => date('h:i A, M d', strtotime($row['created_at']))
    ];
}
$stmt->close();

/* ---------- Count unread messages (all teachers) ---------- */
$stmt2 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND sender_type='teacher' AND is_read=0");
$stmt2->bind_param("i",$student_id);
$stmt2->execute();
$unread_count = $stmt2->get_result()->fetch_assoc()['unread_count'];
$stmt2->close();

/* ---------- Return JSON ---------- */
header('Content-Type: application/json');
echo json_encode(['messages'=>$messages, 'unread_count'=>$unread_count]);
exit();
