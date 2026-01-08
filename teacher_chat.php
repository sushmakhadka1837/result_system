<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['teacher_id']) || $_SESSION['user_type']!='teacher'){
    header("Location: index.php"); exit();
}

$teacher_id = $_SESSION['teacher_id'];

/* ---------- Fetch students with last message and unread count ---------- */
$students = [];
$sql = "
SELECT s.id, s.full_name,
       MAX(m.created_at) AS last_msg_time,
       SUM(CASE WHEN m.receiver_id=? AND m.is_read=0 AND m.sender_type='student' THEN 1 ELSE 0 END) AS unread_count
FROM students s
LEFT JOIN messages m ON (
    (m.sender_id=s.id AND m.sender_type='student' AND m.receiver_id=?)
 OR (m.sender_id=? AND m.sender_type='teacher' AND m.receiver_id=s.id)
)
GROUP BY s.id
ORDER BY last_msg_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii",$teacher_id,$teacher_id,$teacher_id);
$stmt->execute();
$res = $stmt->get_result();
while($row=$res->fetch_assoc()) $students[]=$row;
$stmt->close();

/* ---------- Selected student ---------- */
$selected_student_id = intval($_GET['student_id'] ?? 0);
$student_name = "Student";
foreach($students as $s){
    if($s['id']==$selected_student_id){
        $student_name = $s['full_name'];
        break;
    }
}

/* ---------- Unread count ---------- */
$stmt2 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND sender_type='student' AND is_read=0");
$stmt2->bind_param("i",$teacher_id);
$stmt2->execute();
$unread_count = $stmt2->get_result()->fetch_assoc()['unread_count'];
$stmt2->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Chat</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body{font-family:Arial;margin:0;background:#f4f6f8;}
.navbar{display:flex;justify-content:space-between;background:#1a73e8;color:#fff;padding:15px 20px;}
.navbar a{color:#fff;text-decoration:none;margin-left:15px;font-weight:bold;position:relative;}
.navbar span{background:red;font-size:12px;padding:2px 6px;border-radius:50%;position:absolute;top:-8px;right:-10px;}
.container{display:flex;height:90vh;}
.left-panel{width:260px;border-right:1px solid #ccc;display:flex;flex-direction:column;}
.right-panel{flex:1;display:flex;flex-direction:column;padding:10px;}
.search-bar{padding:8px;border-bottom:1px solid #ccc;}
.search-bar input{width:100%;padding:6px;border-radius:5px;border:1px solid #ccc;}
.student-list{flex:1;overflow-y:auto;}
.student-list div{padding:8px;border-bottom:1px solid #eee;cursor:pointer;}
.student-list .active{background:#d0e7ff;}
.student-list .unread{font-weight:bold;}
.chat-box{flex:1;overflow-y:auto;background:#f5f5f5;padding:10px;border:1px solid #ccc;border-radius:6px;display:flex;flex-direction:column;}
.message{max-width:65%;padding:8px 12px;border-radius:12px;margin-bottom:6px;font-size:0.85rem;position:relative;word-wrap:break-word;}
.teacher-message{background:#007bff;color:#fff;align-self:flex-end;border-bottom-right-radius:0;}
.student-message{background:#e9ecef;color:#000;align-self:flex-start;border-bottom-left-radius:0;}
.time{font-size:10px;opacity:0.6;margin-top:2px;text-align:right;}
textarea{width:100%;height:45px;padding:6px;border-radius:5px;border:1px solid #ccc;resize:none;}
button{background:#28a745;color:#fff;border:none;padding:6px 12px;border-radius:5px;cursor:pointer;float:right;margin-top:4px;}
</style>
</head>
<body>
<div class="navbar">
    <div>Teacher Dashboard</div>
    <div>
        <a href="teacher_dashboard.php">Dashboard</a>
        <a href="#">Messages <?php if($unread_count>0): ?><span><?=($unread_count>9?'9+':$unread_count)?></span><?php endif; ?></a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
<div class="left-panel">
<div class="search-bar"><input type="text" placeholder="Search Student..."></div>
<div class="student-list">
<?php foreach($students as $s): ?>
<div class="<?=($selected_student_id==$s['id']?'active':'')?> <?=($s['unread_count']>0?'unread':'')?>" data-id="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?></div>
<?php endforeach; ?>
</div>
</div>

<div class="right-panel">
<?php if($selected_student_id): ?>
<strong>Chat with <?=htmlspecialchars($student_name)?></strong>
<div class="chat-box" id="chat-box"></div>
<form id="chat-form" method="post">
<input type="hidden" name="receiver_id" value="<?=$selected_student_id?>">
<textarea name="message" required placeholder="Type message..."></textarea>
<button type="submit">Send</button>
</form>
<?php else: ?>
<p>Select a student to start chat</p>
<?php endif; ?>
</div>
</div>

<script>
$('.student-list div').click(function(){
    window.location.href='teacher_chat.php?student_id='+$(this).data('id');
});

function fetchMessages(){
    let student_id = <?=$selected_student_id?>;
    if(!student_id) return;
    $.get('fetch_messages_teacher.php',{receiver_id:student_id},function(data){
        $('#chat-box').html(data);
        $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
    });
}

$('#chat-form').submit(function(e){
    e.preventDefault();
    $.post('send_teacher_message.php',$(this).serialize(),function(res){
        $('textarea[name="message"]').val('');
        fetchMessages();
    });
});

setInterval(fetchMessages,2000);
$(document).ready(fetchMessages);
</script>
</body>
</html>
