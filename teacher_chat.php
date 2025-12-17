<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$search = $_GET['search'] ?? '';
$selected_student_id = $_GET['student_id'] ?? 0;

// Fetch students
$students = [];
$stmt = $conn->prepare("SELECT id, full_name, symbol_no FROM students WHERE full_name LIKE ? OR symbol_no LIKE ? ORDER BY full_name ASC");
$like = "%$search%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $students[] = $row;
$stmt->close();

// Fetch unread count for teacher navbar
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
<title>Teacher Messenger</title>
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
.student-list {flex:1; overflow-y:auto;}
.student-list div {padding:8px; cursor:pointer; border-bottom:1px solid #eee; font-size:0.9rem;}
.student-list div:hover {background:#f0f0f0;}
.student-list .active {background:#d0e7ff;}
.student-list .unread {font-weight:bold;}
.chat-box {flex:1; overflow-y:auto; padding:10px; background:#f5f5f5; border-radius:6px; border:1px solid #ccc; margin-bottom:5px; display:flex; flex-direction:column;}
.message {max-width:65%; padding:6px 10px; border-radius:12px; margin-bottom:6px; word-wrap:break-word; font-size:0.85rem; display:inline-block; position:relative;}
.teacher-message {background:#007bff; color:#fff; align-self:flex-end; border-bottom-right-radius:0;}
.student-message {background:#e9ecef; color:#000; align-self:flex-start; border-bottom-left-radius:0;}
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

<div class="navbar">
    <div>Teacher Dashboard</div>
    <div>
        <a href="teacher_dashboard.php">Dashboard</a>
        <a href="#" id="nav-messages">Message
            <?php if($unread_count>0): ?>
                <span id="unread-count"><?= ($unread_count>9)?'9+':$unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <!-- Left Panel -->
    <div class="left-panel">
        <div class="search-bar">
            <form method="get">
                <input type="text" name="search" placeholder="Search Student..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>
        <div class="student-list" id="student-list">
            <?php foreach($students as $stu): 
                $classes = [];
                if($selected_student_id==$stu['id']) $classes[]='active';
            ?>
                <div class="<?= implode(' ',$classes) ?>" data-id="<?= $stu['id'] ?>">
                    <?= htmlspecialchars($stu['full_name']) ?> (<?= htmlspecialchars($stu['symbol_no']) ?>)
                </div>
            <?php endforeach; ?>
            <?php if(empty($students)) echo "<div style='padding:8px;'>No students found.</div>"; ?>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="right-panel">
        <?php if($selected_student_id): ?>
            <h4 style="margin-bottom:5px;">Chat with <span id="student-name"><?= htmlspecialchars($students[array_search($selected_student_id,array_column($students,'id'))]['full_name'] ?? '') ?></span></h4>
            <div class="chat-box" id="chat-box"></div>
            <form id="chat-form">
                <input type="hidden" name="student_id" value="<?= $selected_student_id ?>">
                <textarea name="message" placeholder="Type your message..." required></textarea>
                <button type="submit">Send</button>
            </form>
        <?php else: ?>
            <p style="padding:10px;">Select a student from the left panel to start chatting.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Left panel click
$('#student-list div').click(function(){
    let student_id = $(this).data('id');
    let search = "<?= urlencode($search) ?>";
    window.location.href='teacher_chat.php?student_id='+student_id+'&search='+search;
});

// Fetch messages
function fetchMessages(){
    let student_id = <?= $selected_student_id ?>;
    if(!student_id) return;
    $.get('fetch_messages.php',{receiver_id:student_id,type:'teacher'}, function(data){
        $('#chat-box').html(data);
        $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
    });
}

// Send message
$('#chat-form').submit(function(e){
    e.preventDefault();
    $.post('send_teacher_message.php', $(this).serialize(), fetchMessages);
    $('textarea[name="message"]').val('');
});

// Edit/Delete messages
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

// Update unread badge
function updateUnreadCount(){
    $.get('fetch_unread_count.php',{user:'teacher'}, function(data){
        $('#unread-count').remove();
        if(data>0){
            $('#nav-messages').append('<span id="unread-count">'+(data>9?'9+':data)+'</span>');
        }
    });
}

setInterval(fetchMessages,2000);
setInterval(updateUnreadCount,3000);
$(document).ready(function(){ fetchMessages(); });
</script>
</body>
</html>
