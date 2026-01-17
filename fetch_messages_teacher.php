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
    $msg_text = htmlspecialchars($m['message']);
    
    echo "<div class='message $cls'>";
    if($msg_text) echo $msg_text;
    
    // Display attachment
    if(!empty($m['attachment'])){
        $file_path = $m['attachment'];
        $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $file_name = basename($file_path);
        
        echo "<div class='attachment'>";
        if(in_array($file_ext, ['jpg','jpeg','png','gif'])){
            echo "<a href='$file_path' target='_blank'><img src='$file_path' alt='Image'></a>";
        }else{
            echo "<a href='$file_path' download='$file_name'>📎 $file_name</a>";
        }
        echo "</div>";
    }
    
    echo "<div class='time'>$time</div></div>";
}
