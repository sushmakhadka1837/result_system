<?php
session_start();
require 'db_config.php';

if(!isset($_SESSION['student_id']) || $_SESSION['user_type']!='student'){
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch teachers with last message time and unread count
$teachers = [];
$sql = "
SELECT t.id, t.full_name,
       MAX(m.sent_at) as last_msg_time,
       SUM(CASE WHEN m.receiver_id=? AND m.is_read=0 THEN 1 ELSE 0 END) AS unread_count
FROM teachers t
LEFT JOIN messages m ON ( (m.sender_id=t.id AND m.sender_type='teacher' AND m.receiver_id=?) 
                       OR (m.sender_id=? AND m.sender_type='student' AND m.receiver_id=t.id) )
GROUP BY t.id
ORDER BY last_msg_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $student_id, $student_id, $student_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $teachers[] = $row;
$stmt->close();

// Get selected teacher
$selected_teacher_id = $_GET['teacher_id'] ?? 0;

// Fetch unread messages count for navbar
$stmt2 = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id=? AND is_read=0");
$stmt2->bind_param("i", $student_id);
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
body {font-family: Arial; margin:0; padding:0; background:#f4f6f8;}
.navbar{display:flex;justify-content:space-between;background:#1a73e8;color:white;padding:15px 20px;}
.navbar a{color:white;text-decoration:none;margin-left:15px;font-weight:bold;position:relative;}
.navbar a span{background:red;color:white;font-size:12px;padding:2px 6px;border-radius:50%;position:absolute;top:-8px;right:-10px;}
.container {display:flex;height:90vh;flex-wrap:wrap;}
.left-panel {width:260px;border-right:1px solid #ccc; display:flex; flex-direction:column;}
.right-panel {flex:1; display:flex; flex-direction:column; padding:10px;}
.search-bar {padding:8px; border-bottom:1px solid #ccc;}
.search-bar input {width:100%; padding:6px; border-radius:5px; border:1px solid #ccc; font-size:0.9rem;}
.teacher-list {flex:1; overflow-y:auto;}
.teacher-list div {padding:8px; cursor:pointer; border-bottom:1px solid #eee; font-size:0.9rem;}
.teacher-list div:hover {background:#f0f0f0;}
.teacher-list .active {background:#d0e7ff;}
.teacher-list .unread {font-weight:bold;}
.chat-header {margin-bottom:5px; display:flex; align-items:center; gap:10px;}
.chat-box {flex:1; overflow-y:auto; padding:10px; background:#f5f5f5; border-radius:6px; border:1px solid #ccc; margin-bottom:5px; display:flex; flex-direction:column;}
.message {max-width:65%; padding:6px 10px; border-radius:12px; margin-bottom:6px; word-wrap:break-word; font-size:0.85rem; display:inline-block; position:relative;}
.student-message {background:#007bff; color:#fff; align-self:flex-end; border-bottom-right-radius:0;}
.teacher-message {background:#e9ecef; color:#000; align-self:flex-start; border-bottom-left-radius:0;}
.message .actions {position:absolute; top:2px; right:2px; font-size:10px; display:none;}
.message:hover .actions {display:block;}
.actions span {cursor:pointer; margin-left:4px; color:#555;}
.time {font-size:10px; opacity:0.7; margin-top:2px; text-align:right;}
textarea {width:100%; height:45px; padding:6px; border-radius:5px; border:1px solid #ccc; resize:none; font-size:0.85rem; margin-top:3px;}
button {padding:5px 10px; border-radius:5px; border:none; background:#28a745; color:#fff; cursor:pointer; font-size:0.85rem; margin-top:3px; float:right;}
@media(max-width:768px){.container{flex-direction:column;}.left-panel{width:100%;height:auto;}.right-panel{width:100%;}}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div>Student Dashboard</div>
    <div>
        <a href="student_dashboard.php">Dashboard</a>
        <a href="#" id="nav-messages">Message
            <?php if($unread_count>0): ?>
                <span id="unread-count"><?php echo ($unread_count>9)?'9+':$unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <!-- Left Panel -->
    <div class="left-panel">
        <div class="search-bar">
            <input type="text" placeholder="Search Teacher..." id="search-teacher">
        </div>
        <div class="teacher-list" id="teacher-list">
            <?php foreach($teachers as $tch): 
                $classes = [];
                if($selected_teacher_id==$tch['id']) $classes[] = 'active';
                if($tch['unread_count']>0) $classes[] = 'unread';
            ?>
            <div class="<?= implode(' ',$classes) ?>" data-id="<?= $tch['id'] ?>">
                <?= htmlspecialchars($tch['full_name']) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="right-panel">
        <?php if($selected_teacher_id): ?>
        <div class="chat-header">
            <strong>Chat with <?= htmlspecialchars($teachers[array_search($selected_teacher_id, array_column($teachers,'id'))]['full_name'] ?? 'Teacher') ?></strong>
        </div>
        <div class="chat-box" id="chat-box"></div>
        <form id="chat-form">
            <input type="hidden" name="receiver_id" value="<?= $selected_teacher_id ?>">
            <textarea name="message" placeholder="Type your message..." required></textarea>
            <button type="submit">Send</button>
        </form>
        <?php else: ?>
            <p>Select a teacher to start chat.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Teacher click
$('#teacher-list div').click(function(){
    let teacher_id = $(this).data('id');
    window.location.href='student_chat.php?teacher_id='+teacher_id;
});

// Fetch messages
function fetchMessages(){
    let teacher_id = <?= $selected_teacher_id ?>;
    if(!teacher_id) return;
    $.get('fetch_messages.php',{receiver_id:teacher_id,type:'student'}, function(data){
        $('#chat-box').html(data);
        $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
    });
}

// Send message
$('#chat-form').submit(function(e){
    e.preventDefault();
    $.post('send_message.php', $(this).serialize()+'&type=student', function(){
        $('textarea[name="message"]').val('');
        fetchMessages();
    });
});

// Edit/Delete handlers
$(document).on('click','.edit-msg',function(){
    let msg_id = $(this).data('id');
    let current = $(this).closest('.message').find('.msg-text').text();
    let new_msg = prompt("Edit your message:", current);
    if(new_msg!==null && new_msg.trim()!==''){
        $.post('edit_message.php',{msg_id:msg_id,message:new_msg},fetchMessages);
    }
});
$(document).on('click','.delete-msg',function(){
    if(confirm("Are you sure to delete this message?")){
        let msg_id = $(this).data('id');
        $.post('delete_message.php',{msg_id:msg_id},fetchMessages);
    }
});

// Fetch unread count
function updateUnreadCount(){
    $.get('fetch_unread_count.php',function(data){
        $('#unread-count').remove();
        if(data>0){
            $('#nav-messages').append('<span id="unread-count">'+(data>9?'9+':data)+'</span>');
            // Update teacher list bold for unread
            $('#teacher-list div').each(function(){
                let tid = $(this).data('id');
                if($(this).text() && data>0){ // Simple bold
                    $(this).toggleClass('unread', true);
                }
            });
        }
    });
}

// Interval
setInterval(fetchMessages,2000);
setInterval(updateUnreadCount,3000);

$(document).ready(function(){ fetchMessages(); });
</script>

</body>
</html>
