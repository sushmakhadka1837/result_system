<?php
session_start();
require 'db_config.php';

$type = $_GET['type'] ?? ''; // 'student' or 'teacher'

if($type=='teacher' && isset($_GET['receiver_id'])) {
    $student_id = (int)$_GET['receiver_id'];
    $teacher_id = $_SESSION['teacher_id'];

    // Fetch messages between teacher and student
    $stmt = $conn->prepare("
        SELECT * FROM messages 
        WHERE (sender_type='student' AND sender_id=? AND receiver_id=?) 
           OR (sender_type='teacher' AND sender_id=? AND receiver_id=?)
        ORDER BY timestamp ASC
    ");
    $stmt->bind_param("iiii", $student_id, $teacher_id, $teacher_id, $student_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Mark student messages as read
    $stmt2 = $conn->prepare("UPDATE messages SET is_read=1 WHERE sender_type='student' AND sender_id=? AND receiver_id=?");
    $stmt2->bind_param("ii", $student_id, $teacher_id);
    $stmt2->execute();
    $stmt2->close();

    // Output messages
    foreach($messages as $msg){
        $class = ($msg['sender_type']=='teacher') ? 'teacher-message' : 'student-message';
        echo '<div class="message '.$class.'">';
        echo '<span class="msg-text">'.htmlspecialchars($msg['message']).'</span>';
        echo '<div class="time">'.htmlspecialchars($msg['timestamp']).'</div>';
        echo '</div>';
    }
}
?>
