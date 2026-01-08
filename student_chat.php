<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id']) || $_SESSION['user_type']!='student'){
    header("Location: index.php"); exit();
}

$student_id = $_SESSION['student_id'];

/* ---------- Fetch teachers with last message and unread count ---------- */
$teachers = [];
$sql = "
SELECT t.id, t.full_name,
       MAX(m.created_at) AS last_msg_time,
       SUM(CASE WHEN m.receiver_id=? AND m.is_read=0 AND m.sender_type='teacher' THEN 1 ELSE 0 END) AS unread_count
FROM teachers t
LEFT JOIN messages m ON (
    (m.sender_id=t.id AND m.sender_type='teacher' AND m.receiver_id=?)
 OR (m.sender_id=? AND m.sender_type='student' AND m.receiver_id=t.id)
)
GROUP BY t.id
ORDER BY last_msg_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii",$student_id,$student_id,$student_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $teachers[] = $row;
$stmt->close();

/* ---------- Selected teacher ---------- */
$selected_teacher_id = intval($_GET['teacher_id'] ?? 0);
$teacher_name = "Teacher";
foreach($teachers as $t){
    if($t['id']==$selected_teacher_id){
        $teacher_name = $t['full_name'];
        break;
    }
}

/* ---------- Unread count (all teachers) ---------- */
$stmt2 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND sender_type='teacher' AND is_read=0");
$stmt2->bind_param("i",$student_id);
$stmt2->execute();
$unread_count = $stmt2->get_result()->fetch_assoc()['unread_count'];
$stmt2->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Chat</title>
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
.teacher-list{flex:1;overflow-y:auto;}
.teacher-list div{padding:8px;border-bottom:1px solid #eee;cursor:pointer;}
.teacher-list .active{background:#d0e7ff;}
.teacher-list .unread{font-weight:bold;}
.chat-box{flex:1;overflow-y:auto;background:#f5f5f5;padding:10px;border:1px solid #ccc;border-radius:6px;display:flex;flex-direction:column;}
.message{max-width:65%;padding:8px 12px;border-radius:12px;margin-bottom:6px;font-size:0.85rem;position:relative;word-wrap:break-word;}
.student-message{background:#007bff;color:#fff;align-self:flex-end;border-bottom-right-radius:0;}
.teacher-message{background:#e9ecef;color:#000;align-self:flex-start;border-bottom-left-radius:0;}
.time{font-size:10px;opacity:0.6;margin-top:2px;text-align:right;}
textarea{width:100%;height:45px;padding:6px;border-radius:5px;border:1px solid #ccc;resize:none;}
button{background:#28a745;color:#fff;border:none;padding:6px 12px;border-radius:5px;cursor:pointer;float:right;margin-top:4px;}
</style>
</head>
<body>
<div class="navbar">
    <div>Student Dashboard</div>
    <div>
        <a href="student_dashboard.php">Dashboard</a>
        <a href="#">Messages <?php if($unread_count>0): ?><span><?=($unread_count>9?'9+':$unread_count)?></span><?php endif; ?></a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
<div class="left-panel">
<div class="search-bar"><input type="text" placeholder="Search Teacher..."></div>
<div class="teacher-list">
<?php foreach($teachers as $t): ?>
<div class="<?=($selected_teacher_id==$t['id']?'active':'')?> <?=($t['unread_count']>0?'unread':'')?>" data-id="<?=$t['id']?>"><?=htmlspecialchars($t['full_name'])?></div>
<?php endforeach; ?>
</div>
</div>

<div class="right-panel">
<?php if($selected_teacher_id): ?>
<strong>Chat with <?=htmlspecialchars($teacher_name)?></strong>
<div class="chat-box" id="chat-box"></div>
<form id="chat-form" method="post">
<input type="hidden" name="receiver_id" value="<?=$selected_teacher_id?>">
<textarea name="message" required placeholder="Type message..."></textarea>
<button type="submit">Send</button>
</form>
<?php else: ?>
<p>Select a teacher to start chat</p>
<?php endif; ?>
</div>
</div>

<script>
$('.teacher-list div').click(function(){
    window.location.href='student_chat.php?teacher_id='+$(this).data('id');
});

function fetchMessages(){
    let teacher_id = <?=$selected_teacher_id?>;
    if(!teacher_id) return;
    $.getJSON('fetch_messages.php',{receiver_id:teacher_id},function(data){
        let html = '';
        data.messages.forEach(m=>{
            let cls = (m.sender_type=='student')?'student-message':'teacher-message';
            html += `<div class='message ${cls}'>${m.message}<div class='time'>${m.created_at}</div></div>`;
        });
        $('#chat-box').html(html);
        $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);

        // Update badge
        let span = $('.navbar a:contains("Messages") span');
        if(data.unread_count>0){
            if(span.length==0){
                $('.navbar a:contains("Messages")').append(`<span>${data.unread_count>9?'9+':data.unread_count}</span>`);
            } else {
                span.text(data.unread_count>9?'9+':data.unread_count);
            }
        } else {
            span.remove();
        }
    });
}

$('#chat-form').submit(function(e){
    e.preventDefault();
    $.post('send_message.php',$(this).serialize(),function(res){
        $('textarea[name="message"]').val('');
        fetchMessages();
    });
});

setInterval(fetchMessages,2000);
$(document).ready(fetchMessages);
</script>
</body>
</html>
